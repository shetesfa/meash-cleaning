/**
 * MEASH CLEANING SOLUTION - Offline-First IndexedDB & Sync Engine
 * Built for high-reliability field operations with automatic retry, failure tracking & conflict handling.
 */

class MeashOfflineEngine {
    constructor() {
        this.dbName = 'MeashCleaningDB';
        this.dbVersion = 1;
        this.db = null;
        this.isOnline = navigator.onLine;
        this.isSyncing = false;
        this.listeners = [];
        this.init().catch(err => {
            console.error('Fatal initialization error in MeashOfflineEngine:', err);
            this.showDbErrorBanner('ዳታቤዝ መክፈት አልተቻለም (IndexedDB unavailable): ' + err.message);
        });
    }

    async init() {
        try {
            await this.openDatabase();
        } catch (err) {
            this.showDbErrorBanner('ስልክዎ ላይ ዳታ ማስቀመጥ አልተቻለም (IndexedDB failed to open).');
            throw err;
        }

        this.setupNetworkListeners();
        this.updateOnlineStatus();

        if (this.isOnline) {
            setTimeout(() => this.syncQueue(), 1500);
        }
    }

    showDbErrorBanner(msg) {
        let banner = document.getElementById('indexeddb-error-banner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'indexeddb-error-banner';
            banner.className = 'fixed top-0 left-0 right-0 z-50 bg-red-600 text-white p-3 text-xs font-bold text-center shadow-lg';
            document.body.prepend(banner);
        }
        banner.innerText = ' ' + msg;
        banner.classList.remove('hidden');
    }

    openDatabase() {
        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                return reject(new Error('IndexedDB not supported on this browser'));
            }

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
                console.error('IndexedDB open error:', event.target.error);
                reject(event.target.error || new Error('Unknown IndexedDB open error'));
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

    async notifyStatus(cb = null) {
        const counts = await this.getQueueCounts();
        const status = {
            online: this.isOnline,
            isSyncing: this.isSyncing,
            pendingCount: counts.pending,
            failedCount: counts.failed,
            label: this.isOnline
                ? (this.isSyncing ? ` Syncing (${counts.pending})...` : (counts.failed > 0 ? ` ${counts.failed} failed items` : (counts.pending > 0 ? ` Online (${counts.pending} queued)` : ' Online')))
                : ` Offline (${counts.pending} saved locally)`,
        };

        if (cb) {
            cb(status);
        } else {
            this.listeners.forEach(fn => fn(status));
        }

        // Update UI pill if present
        const pill = document.getElementById('offline-sync-pill');
        if (pill) {
            pill.innerText = status.label;
            if (counts.failed > 0) {
                pill.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-950/80 text-red-300 border border-red-700 cursor-pointer';
                pill.onclick = () => this.showFailedItemsModal();
            } else if (!this.isOnline) {
                pill.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-950/80 text-amber-300 border border-amber-700';
                pill.onclick = null;
            } else {
                pill.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-700';
                pill.onclick = null;
            }
        }
    }

    updateOnlineStatus() {
        this.notifyStatus();
    }

    getCurrentUserId() {
        try {
            const u = JSON.parse(localStorage.getItem('meash_user') || '{}');
            if (u && u.id) return u.id;
        } catch (e) {}
        if (window.currentUser && window.currentUser.id) {
            return window.currentUser.id;
        }
        return null;
    }

    getCurrentUserRole() {
        try {
            const u = JSON.parse(localStorage.getItem('meash_user') || '{}');
            if (u && u.role) return u.role;
        } catch (e) {}
        if (window.currentUser && window.currentUser.role) {
            return window.currentUser.role;
        }
        return null;
    }

    isOwnerOrAdmin() {
        const role = this.getCurrentUserRole();
        return role === 'owner' || role === 'admin';
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    async enqueue(entityType, action, payload, clientVersion = 1) {
        if (!this.db) {
            const err = new Error('IndexedDB not ready. Action cannot be queued offline.');
            this.showDbErrorBanner(err.message);
            throw err;
        }

        const currentUserId = this.getCurrentUserId();

        const item = {
            client_uuid: this.generateUUID(),
            user_id: currentUserId,
            entity_type: entityType,
            action: action,
            payload: payload,
            client_version: clientVersion,
            client_timestamp: new Date().toISOString(),
            status: 'pending',
            retries: 0,
            last_error: null,
        };

        await this.putStoreItem('sync_queue', item);

        if (entityType === 'order' || entityType === 'job_action') {
            await this.applyLocalOrderUpdate(item);
        }

        this.notifyStatus();

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
                        existing.completed_at = new Date().toISOString();
                    } else if (nextStatus === 'start') {
                        existing.order_status = 'cleaning';
                    } else if (nextStatus === 'on_the_way') {
                        existing.order_status = 'on_the_way';
                    } else {
                        existing.order_status = nextStatus;
                    }

                    if (payload.notes) existing.completion_notes = payload.notes;
                    existing._locally_updated = true;
                    await this.putStoreItem('my_jobs', existing);
                }
            }
        } catch (e) {
            console.warn('Local optimistic cache update skipped:', e);
        }
    }

    async syncQueue() {
        if (this.isSyncing || !this.isOnline || !this.db) return;

        const pendingItems = await this.getPendingItems();
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

            if (response.status === 401) {
                console.warn('Sync 401 Unauthorized: please log in again.');
                window.dispatchEvent(new CustomEvent('meash-auth-required', { detail: 'Please login again to sync offline data.' }));
                return;
            }

            if (!response.ok) {
                // Non-200 response: increment retries on all attempted items
                const statusText = `Server error HTTP ${response.status}`;
                for (const item of pendingItems) {
                    item.retries = (item.retries || 0) + 1;
                    item.last_error = statusText;
                    if (item.retries >= 5) {
                        item.status = 'failed';
                    }
                    await this.putStoreItem('sync_queue', item);
                }
                return;
            }

            const data = await response.json();

            // 1. Process successfully synced items (only permanently purge completed or already_processed items)
            if (data.synced && Array.isArray(data.synced)) {
                for (const s of data.synced) {
                    if (s.status === 'synced' || s.status === 'already_processed') {
                        await this.deleteStoreItem('sync_queue', s.client_uuid);
                    }
                }
            }

            // 1b. In-progress items from concurrent locks must be retained in queue
            if (data.in_progress && Array.isArray(data.in_progress)) {
                console.log('Concurrent sync in progress for items, retaining locally:', data.in_progress);
            }

            // 2. Process individual item errors from server
            if (data.errors && Array.isArray(data.errors)) {
                for (const errItem of data.errors) {
                    const item = await this.getStoreItem('sync_queue', errItem.client_uuid);
                    if (item) {
                        item.status = 'failed';
                        item.last_error = errItem.error || 'Server validation failed';
                        await this.putStoreItem('sync_queue', item);
                    }
                }
            }

            // 3. Process conflicts
            if (data.conflicts && data.conflicts.length > 0) {
                window.dispatchEvent(new CustomEvent('meash-sync-conflict', { detail: data.conflicts }));
            }

            window.dispatchEvent(new CustomEvent('meash-sync-completed', { detail: data }));
        } catch (err) {
            console.error('Sync batch network error:', err);
            for (const item of pendingItems) {
                item.retries = (item.retries || 0) + 1;
                item.last_error = err.message;
                if (item.retries >= 5) {
                    item.status = 'failed';
                }
                await this.putStoreItem('sync_queue', item);
            }
        } finally {
            this.isSyncing = false;
            this.notifyStatus();
        }
    }

    async getQueueCounts() {
        const all = await this.getAllItems('sync_queue');
        const currentUserId = this.getCurrentUserId();
        let pending = 0;
        let failed = 0;
        let otherUserPending = 0;
        for (const it of all) {
            if (currentUserId && it.user_id && it.user_id !== currentUserId) {
                if (it.status !== 'failed') otherUserPending++;
                continue;
            }
            if (it.status === 'failed') failed++;
            else pending++;
        }
        return { pending, failed, otherUserPending, total: all.length };
    }

    async getPendingItems() {
        const all = await this.getAllItems('sync_queue');
        const currentUserId = this.getCurrentUserId();
        if (!currentUserId) return [];

        return all.filter(it => {
            if (it.status === 'failed' || it.status === 'quarantined') return false;
            // Account Isolation: Quarantine unowned legacy records missing user_id. Never submit under new user!
            if (!it.user_id) {
                it.status = 'quarantined';
                it.last_error = 'Quarantined: legacy offline operation missing user_id attribution.';
                this.putStoreItem('sync_queue', it);
                return false;
            }
            // Never sync another user's pending operations with current user's token
            if (it.user_id !== currentUserId) {
                return false;
            }
            return true;
        });
    }

    async getFailedItems() {
        const all = await this.getAllItems('sync_queue');
        const currentUserId = this.getCurrentUserId();
        if (!currentUserId) return [];
        // Strict privacy: Only show the authenticated user's own failed operations.
        // Unowned legacy records are NEVER leaked into ordinary failed items!
        return all.filter(it => it.status === 'failed' && it.user_id === currentUserId);
    }

    getAuthToken() {
        return localStorage.getItem('meash_token') || (window.authToken || null);
    }

    async getQuarantinedItems() {
        // Strict privacy: Only authenticated Owner/Admin accounts can inspect quarantined unassigned records
        if (!this.isOwnerOrAdmin()) {
            console.warn('Privacy violation prevented: Quarantined records can only be inspected by an authorized Owner.');
            return [];
        }
        const all = await this.getAllItems('sync_queue');
        return all.filter(it => it.status === 'quarantined' || !it.user_id);
    }

    async exportQuarantinedItems() {
        if (!this.isOwnerOrAdmin()) {
            alert('Permission Denied: Only system Owner or Administrator can export quarantined offline records.');
            return;
        }
        const quarantined = await this.getQuarantinedItems();
        if (quarantined.length === 0) {
            alert('No quarantined records found.');
            return;
        }

        // Server-Side Authorization Verification: Do not rely solely on client-side state
        try {
            const token = this.getAuthToken();
            const res = await fetch('/api/sync/quarantine/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(token ? { 'Authorization': `Bearer ${token}` } : {})
                },
                body: JSON.stringify({ items: quarantined.map(i => ({ client_uuid: i.client_uuid })) })
            });

            if (!res.ok) {
                const errData = await res.json().catch(() => ({}));
                alert(errData.error || 'Server authorization failed: Only an authenticated Owner can export quarantined records.');
                return;
            }
        } catch (netErr) {
            console.error('Failed to verify export authorization with server:', netErr);
            alert('Security verification failed: Cannot export quarantined records without server verification.');
            return;
        }

        const blob = new Blob([JSON.stringify(quarantined, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `meash-quarantined-records-owner-export-${new Date().toISOString().slice(0, 10)}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    async recoverQuarantinedItems(targetUserId, ownerConfirmation = false) {
        if (!this.isOwnerOrAdmin() || !ownerConfirmation) {
            console.error('Security alert: Quarantined item recovery strictly requires explicit Owner authorization.');
            throw new Error('Unauthorized: Only an authenticated Owner with explicit confirmation can reassign quarantined offline records.');
        }
        const currentUserId = this.getCurrentUserId();
        // Prevent assigning to arbitrary non-existent or unverified user ID:
        // Must match currently authenticated user or be strictly verified
        if (!targetUserId || targetUserId !== currentUserId) {
            targetUserId = currentUserId;
        }

        const all = await this.getAllItems('sync_queue');
        const quarantined = all.filter(it => it.status === 'quarantined' || !it.user_id);
        if (quarantined.length === 0) {
            return 0;
        }

        // Server-Side Verification: Ensure server approves the recovery and verifies owner permissions
        const token = this.getAuthToken();
        const res = await fetch('/api/sync/quarantine/recover', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {})
            },
            body: JSON.stringify({
                items: quarantined.map(i => ({ client_uuid: i.client_uuid })),
                target_user_id: targetUserId
            })
        });

        if (!res.ok) {
            const errData = await res.json().catch(() => ({}));
            const msg = errData.error || 'Server authorization failed: Only an authenticated Owner can recover quarantined records.';
            console.error(msg);
            throw new Error(msg);
        }

        const resData = await res.json();
        const authorizedTarget = resData.target_user_id || targetUserId;

        let count = 0;
        for (const it of quarantined) {
            it.user_id = authorizedTarget;
            it.status = 'pending';
            it.retries = 0;
            it.last_error = null;
            it._recovered_by_owner_at = new Date().toISOString();
            await this.putStoreItem('sync_queue', it);
            count++;
        }
        this.notifyStatus();
        return count;
    }

    async retryFailedItem(clientUuid) {
        const item = await this.getStoreItem('sync_queue', clientUuid);
        const currentUserId = this.getCurrentUserId();
        if (item) {
            // Verify ownership before retry: unowned legacy or another user's item cannot be submitted
            if (!item.user_id || item.user_id !== currentUserId) {
                console.warn('Cannot retry item: ownership mismatch or missing user attribution.');
                return;
            }
            item.status = 'pending';
            item.retries = 0;
            item.last_error = null;
            await this.putStoreItem('sync_queue', item);
            this.notifyStatus();
            this.syncQueue();
        }
    }

    async discardFailedItem(clientUuid) {
        const item = await this.getStoreItem('sync_queue', clientUuid);
        const currentUserId = this.getCurrentUserId();
        if (item && item.user_id === currentUserId) {
            await this.deleteStoreItem('sync_queue', clientUuid);
            this.notifyStatus();
        }
    }

    async exportUserQueue() {
        const currentUserId = this.getCurrentUserId();
        if (!currentUserId) {
            console.warn('Cannot export queue: user not authenticated.');
            return;
        }
        const all = await this.getAllItems('sync_queue');
        // Strict account isolation: only export items strictly belonging to current authenticated user
        const userItems = all.filter(it => it.user_id === currentUserId);
        const blob = new Blob([JSON.stringify(userItems, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `meash-offline-backup-user-${currentUserId}-${new Date().toISOString().slice(0, 10)}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    async showFailedItemsModal() {
        const failed = await this.getFailedItems();
        let modal = document.getElementById('failed-sync-items-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'failed-sync-items-modal';
            modal.className = 'fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4';
            document.body.appendChild(modal);
        }

        modal.innerHTML = `
            <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 text-white shadow-2xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold flex items-center gap-2 text-red-400">
                        <span></span>
                        <span>ያልተመሳሰሉ መረጃዎች (${failed.length} Failed Items)</span>
                    </h3>
                    <button onclick="document.getElementById('failed-sync-items-modal').remove()" class="text-slate-400 hover:text-white"></button>
                </div>
                <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                    ${failed.length === 0 ? '<p class="text-xs text-slate-400">ምንም ስህተት ያለበት መረጃ የለም (No failed items)</p>' : failed.map(item => `
                        <div class="p-3 bg-slate-800 rounded-xl border border-red-900/40 text-xs">
                            <p class="font-bold text-white">${item.action} (${item.entity_type})</p>
                            <p class="text-[11px] text-red-400 mt-1">ስህተት፡ ${item.last_error || 'Validation failed'}</p>
                            <div class="flex gap-2 mt-2">
                                <button onclick="window.meashOffline.retryFailedItem('${item.client_uuid}').then(() => window.meashOffline.showFailedItemsModal())" class="px-2.5 py-1 bg-cyan-600 hover:bg-cyan-500 rounded text-[10px] font-bold">እንደገና ሞክር (Retry)</button>
                                <button onclick="window.meashOffline.discardFailedItem('${item.client_uuid}').then(() => window.meashOffline.showFailedItemsModal())" class="px-2.5 py-1 bg-slate-700 hover:bg-red-800 rounded text-[10px] font-bold">አስወግድ (Discard)</button>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <button onclick="document.getElementById('failed-sync-items-modal').remove()" class="w-full mt-4 py-2.5 bg-slate-800 hover:bg-slate-700 rounded-xl text-xs font-bold">ዝጋ (Close)</button>
            </div>
        `;
    }

    // Low-level IndexedDB helpers
    putStoreItem(storeName, item) {
        return new Promise((resolve, reject) => {
            if (!this.db) return reject(new Error('DB not open'));
            const tx = this.db.transaction([storeName], 'readwrite');
            const store = tx.objectStore(storeName);
            const req = store.put(item);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    getStoreItem(storeName, key) {
        return new Promise((resolve, reject) => {
            if (!this.db) return reject(new Error('DB not open'));
            const tx = this.db.transaction([storeName], 'readonly');
            const store = tx.objectStore(storeName);
            const req = store.get(key);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    deleteStoreItem(storeName, key) {
        return new Promise((resolve, reject) => {
            if (!this.db) return reject(new Error('DB not open'));
            const tx = this.db.transaction([storeName], 'readwrite');
            const store = tx.objectStore(storeName);
            const req = store.delete(key);
            req.onsuccess = () => resolve(true);
            req.onerror = () => reject(req.error);
        });
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

    async clearUserData() {
        if (!this.db) return;
        return new Promise((resolve) => {
            try {
                const stores = ['orders', 'my_jobs', 'customers'];
                const tx = this.db.transaction(stores, 'readwrite');
                stores.forEach(name => {
                    if (this.db.objectStoreNames.contains(name)) {
                        tx.objectStore(name).clear();
                    }
                });
                tx.oncomplete = () => {
                    console.log('User offline cache cleanly wiped on logout.');
                    resolve(true);
                };
                tx.onerror = () => resolve(false);
            } catch (e) {
                console.warn('Error clearing offline user data:', e);
                resolve(false);
            }
        });
    }

    async cacheItems(storeName, items) {
        if (!this.db || !Array.isArray(items)) return;
        return new Promise((resolve) => {
            try {
                const tx = this.db.transaction([storeName], 'readwrite');
                const store = tx.objectStore(storeName);
                for (const item of items) {
                    try {
                        store.put(item);
                    } catch (e) {
                        // ignore individual item error
                    }
                }
                tx.oncomplete = () => resolve(true);
                tx.onerror = () => resolve(false);
            } catch (err) {
                console.warn('cacheItems transaction error on store ' + storeName, err);
                resolve(false);
            }
        });
    }
}

window.meashOffline = new MeashOfflineEngine();
