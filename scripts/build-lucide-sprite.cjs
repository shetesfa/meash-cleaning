// Build the 67-icon SVG sprite for the public page from the vendored
// lucide.min.js. Run: node scripts/build-lucide-sprite.cjs
// Output: resources/views/partials/lucide-sprite.blade.php
// Icon list: scripts/lucide-sprite-icons.txt (one kebab-case name per
// line — keep it in the repo; the old /tmp/q2-icons.txt is gone, and
// /tmp is wiped between runs). Add a name here whenever the public page
// uses a new data-lucide icon, then regenerate.
const fs = require('fs');
const vm = require('vm');
const path = require('path');

const root = path.join(__dirname, '..');
const iconsFile = path.join(root, 'public', 'vendor', 'lucide.min.js');

const kebabNames = fs.readFileSync(path.join(root, 'scripts', 'lucide-sprite-icons.txt'), 'utf8')
  .split('\n').map(s => s.trim()).filter(Boolean);

const toPascal = (kebab) => kebab.split('-').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join('');

const code = fs.readFileSync(iconsFile, 'utf8');
const sandbox = { window: {}, console };
vm.createContext(sandbox);
vm.runInContext(code, sandbox);
const lucide = sandbox.window.lucide || sandbox.lucide;
if (!lucide || !lucide.icons) { console.error('lucide.icons not found'); process.exit(1); }

const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
const nodeToSvg = ([tag, attrs]) =>
  `<${tag} ${Object.entries(attrs).map(([k, v]) => `${k}="${esc(v)}"`).join(' ')}/>`;

let symbols = '';
const missing = [];
for (const kebab of kebabNames) {
  const pascal = toPascal(kebab);
  const icon = lucide.icons[pascal];
  if (!icon) { missing.push(kebab + ' (as ' + pascal + ')'); continue; }
  const children = icon[2] || [];
  symbols += `  <symbol id="lucide-${kebab}" viewBox="0 0 24 24">${children.map(nodeToSvg).join('')}</symbol>\n`;
}

const out = `{{-- Q2: inline SVG sprite — only the ${kebabNames.length} icons the public page uses,
     extracted from public/vendor/lucide.min.js (see scripts/build-lucide-sprite.cjs
     and the icon list in scripts/lucide-sprite-icons.txt).
     The full 359 KB lucide.min.js is kept for /app only. Symbols are
     referenced by the small createIcons() shim in website.blade.php. --}}
<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
${symbols}</svg>
`;

const outPath = path.join(root, 'resources', 'views', 'partials', 'lucide-sprite.blade.php');
fs.mkdirSync(path.dirname(outPath), { recursive: true });
fs.writeFileSync(outPath, out);
console.log('icons requested:', kebabNames.length);
console.log('icons written:', kebabNames.length - missing.length);
console.log('missing:', missing.length ? missing.join(', ') : 'none');
console.log('sprite bytes:', Buffer.byteLength(out));
console.log('lucide.min.js bytes:', fs.statSync(iconsFile).size);
