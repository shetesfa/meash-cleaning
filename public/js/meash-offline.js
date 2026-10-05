/**
 * MEASH CLEANING SOLUTION - Offline-First IndexedDB & Sync Engine
 * Built for high-reliability field operations with automatic retry & conflict detection.
 */

class MeashOfflineEngine {
    constructor() {
        this.dbName = 'MeashCleaningDB';
        this.dbVersion = 1;
        this.db = null;
        this.isOnline = navigator.onLine;
        this.isSyncing = false;
        this.listeners = [];
        this.init();
    }

    async init() {
        await this.openDatabase();
        this.setupNetworkListeners();
        this.updateOnlineStatus();

        // Attempt initial sync if online
        if (this.isOnline) {
            setTimeout(() => this.syncQueue(), 2000);
        }
    }

    openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Sync Queue Store
                if (!db.objectStoreNames.contains('sync_queue')) {
                    const queueStore = db.createObjectStore('sync_queue', { keyPath: 'client_uuid' });
                    queueStore.createIndex('status', 'status', { unique: false });
                    queueStore.createIndex('timestamp', 'client_timestamp', { unique: false });
                }

                // Cached Orders Store
                if (!db.objectStoreNames.contains('orders')) {
                    const ordersStore = db.createObjectStore('orders', { keyPath: 'id' });
                    ordersStore.createIndex('order_number', 'order_number', { unique: true });
                }

                // Cached Cleaner Today Jobs Store
                if (!db.objectStoreNames.contains('my_jobs')) {
                    db.createObjectStore('my_jobs', { keyPath: 'id' });
                }

                // Cached Customers Store
                if (!db.objectStoreNames.contains('customers')) {
                    const custStore = db.createObjectStore('customers', { keyPath: 'id' });
                    custStore.createIndex('phone', 'phone', { unique: false });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = (event) => {
                console.error('IndexedDB error:', event.target.error);
                reject(event.target.error);
            };
        });
    }

    setupNetworkListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateOnlineStatus();
            this.syncQueue();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateOnlineStatus();
        });

        // Periodic connectivity heartbeat probe
        setInterval(() => {
            if (navigator.onLine) {
                fetch('/api/ping', { method: 'GET', cache: 'no-cache' })
                    .then(res => {
                        if (res.ok && !this.isOnline) {
                            this.isOnline = true;
                            this.updateOnlineStatus();
                            this.syncQueue();
                        }
                    })
                    .catch(() => {
                        if (this.isOnline) {
                            this.isOnline = false;
                            this.updateOnlineStatus();
                        }
                    });
            }
        }, 15000);
    }

    onStatusChange(callback) {
        this.listeners.push(callback);
        this.notifyStatus(callback);
    }

    notifyStatus(cb = null) {
        this.getPendingCount().then(count => {
            const status = {
                online: this.isOnline,
                isSyncing: this.isSyncing,
                pendingCount: count,
                label: this.isOnline
                    ? (count > 0 ? `🟢 Online (${count} syncing...)` : '🟢 Online')
                    : `🟠 Offline (${count} saved locally)`,
            };
            if (cb) {
                cb(status);
            } else {
                this.listeners.forEach(fn => fn(status));
            }
        });
    }

    updateOnlineStatus() {
        this.notifyStatus();
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    async enqueue(entityType, action, payload, clientVersion = 1) {
        const item = {
            client_uuid: this.generateUUID(),
            entity_type: entityType,
            action: action,
            payload: payload,
            client_version: clientVersion,
            client_timestamp: new Date().toISOString(),
            status: 'pending',
            retries: 0,
        };

        // 1. Save in IndexedDB queue
        await this.putStoreItem('sync_queue', item);

        // 2. Apply optimistic local update to relevant cache store
        if (entityType === 'order' || entityType === 'job_action') {
            await this.applyLocalOrderUpdate(item);
        }

        this.notifyStatus();

        // 3. Try to sync immediately if online
        if (this.isOnline) {
            this.syncQueue();
        }

        return item;
    }

    async applyLocalOrderUpdate(item) {
        const payload = item.payload;
        const orderId = payload.order_id || payload.id;
        if (!orderId) return;

        try {
            const existing = await this.getStoreItem('my_jobs', orderId);
            if (existing) {
                if (item.action === 'job_action' || item.action === 'status_change') {
                    const nextStatus = payload.order_status || payload.action;
                    if (nextStatus === 'complete') {
                        existing.order_status = 'completed';
                    } else if (nextStatus === 'start') {
                        existing.order_status = 'cleaning';
                    } else if (nextStatus === 'on_the_way') {
                        existing.order_status = 'on_the_way';
                    } else {
                        existing.order_status = nextStatus;
                    }

                    if (existing.order_status === 'completed') {
                        existing.completed_at = new Date().toISOString();
                    }
                    if (payload.notes) {
                        existing.completion_notes = payload.notes;
                    }
                    if (payload.latitude && payload.longitude) {
                        existing.latitude = payload.latitude;
                        existing.longitude = payload.longitude;
                    }
                    existing._locally_updated = true;
                    await this.putStoreItem('my_jobs', existing);
                }
            }
        } catch (e) {
            console.warn('Local optimistic cache update skipped:', e);
        }
    }

    async syncQueue() {
        if (this.isSyncing || !this.isOnline) return;

        const pendingItems = await this.getAllPendingItems();
        if (pendingItems.length === 0) {
            this.notifyStatus();
            return;
        }

        this.isSyncing = true;
        this.notifyStatus();

        const token = localStorage.getItem('meash_token');

        try {
            const response = await fetch('/api/sync/batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': token ? `Bearer ${token}` : '',
                },
                body: JSON.stringify({ items: pendingItems }),
            });

            if (response.ok) {
                const data = await response.json();

                // Process successfully synced
                if (data.synced && Array.isArray(data.synced)) {
                    for (const s of data.synced) {
                        await this.deleteStoreItem('sync_queue', s.client_uuid);
                    }
                }

                // Process conflicts
                if (data.conflicts && data.conflicts.length > 0) {
                    window.dispatchEvent(new CustomEvent('meash-sync-conflict', { detail: data.conflicts }));
                }

                window.dispatchEvent(new CustomEvent('meash-sync-completed', { detail: data }));
            }
        } catch (err) {
            console.error('Sync batch error:', err);
        } finally {
            this.isSyncing = false;
            this.notifyStatus();
        }
    }

    // Low-level IndexedDB helpers
    getPendingCount() {
        return new Promise((resolve) => {
            if (!this.db) return resolve(0);
            try {
                const tx = this.db.transaction(['sync_queue'], 'readonly');
                const store = tx.objectStore('sync_queue');
                const req = store.count();
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => resolve(0);
            } catch (e) {
                resolve(0);
            }
        });
    }

    getAllPendingItems() {
        return new Promise((resolve) => {
            if (!this.db) return resolve([]);
            try {
                const tx = this.db.transaction(['sync_queue'], 'readonly');
                const store = tx.objectStore('sync_queue');
                const req = store.getAll();
                req.onsuccess = () => resolve(req.result || []);
                req.onerror = () => resolve([]);
            } catch (e) {
                resolve([]);
            }
        });
    }

    putStoreItem(storeName, item) {
        return new Promise((resolve, reject) => {
            if (!this.db) return resolve(null);
            const tx = this.db.transaction([storeName], 'readwrite');
            const store = tx.objectStore(storeName);
            const req = store.put(item);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    getStoreItem(storeName, key) {
        return new Promise((resolve, reject) => {
            if (!this.db) return resolve(null);
            const tx = this.db.transaction([storeName], 'readonly');
            const store = tx.objectStore(storeName);
            const req = store.get(key);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    deleteStoreItem(storeName, key) {
        return new Promise((resolve, reject) => {
            if (!this.db) return resolve(null);
            const tx = this.db.transaction([storeName], 'readwrite');
            const store = tx.objectStore(storeName);
            const req = store.delete(key);
            req.onsuccess = () => resolve(true);
            req.onerror = () => reject(req.error);
        });
    }

    async cacheItems(storeName, items) {
        if (!this.db || !Array.isArray(items)) return;
        const tx = this.db.transaction([storeName], 'readwrite');
        const store = tx.objectStore(storeName);
        for (const it of items) {
            store.put(it);
        }
    }

    getAllItems(storeName) {
        return new Promise((resolve) => {
            if (!this.db) return resolve([]);
            try {
                const tx = this.db.transaction([storeName], 'readonly');
                const store = tx.objectStore(storeName);
                const req = store.getAll();
                req.onsuccess = () => resolve(req.result || []);
                req.onerror = () => resolve([]);
            } catch (e) {
                resolve([]);
            }
        });
    }
}

window.meashOffline = new MeashOfflineEngine();
