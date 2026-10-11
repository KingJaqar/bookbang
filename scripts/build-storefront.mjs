import { cp, mkdir, readFile, readdir, rm, writeFile } from 'node:fs/promises';
import { dirname, extname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const output = join(root, 'dist');
// Publish an allowlist of public assets, never PHP, configuration, or database files.
const assetExtensions = new Set(['.css', '.js', '.png', '.jpg', '.jpeg', '.webp', '.gif', '.svg', '.ico', '.woff', '.woff2']);
await rm(output, { recursive: true, force: true });
await mkdir(output, { recursive: true });

async function copyAssets(directory, relative = '') {
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    const path = join(relative, entry.name);
    if (entry.isDirectory()) {
      await copyAssets(join(directory, entry.name), path);
    } else if (entry.isFile() && assetExtensions.has(extname(entry.name).toLowerCase())) {
      await mkdir(dirname(join(output, path)), { recursive: true });
      await cp(join(root, path), join(output, path));
    }
  }
}

for (const file of [
  'Homepage.css', 'Carousel.css', 'Carousel.js', 'ProductPage.css',
  'ProductDescription.css', 'navbar.css', 'footer.css', 'bookbang-theme.css',
  'storefront.js', 'gcash.png', 'maya.png', 'bdo.jpg', 'unionb.png',
  'mastercard.png', 'VISA-Logo-2006.png',
]) {
  await cp(join(root, file), join(output, file));
}
for (const directory of ['Assets', 'ProductPageAssets']) {
  await copyAssets(join(root, directory), directory);
}

const navbar = `<nav id="main-nav" aria-label="Main navigation">
  <div class="navbar-container">
    <a class="navbar-left" href="/" style="text-decoration:none">
      <img src="/Assets/bookbanglogo.png" alt="BookBang" class="logo">
      <span class="logo-text">BookBang</span>
    </a>
    <div class="navbar-right">
      <a href="/">Home</a>
      <a href="/ProductPage.html">Books</a>
      <a href="/unavailable.html">Cart</a>
      <a href="/unavailable.html">Sign In</a>
    </div>
  </div>
</nav>`;
const footerSource = await readFile(join(root, 'footer.php'), 'utf8');
const footer = footerSource.match(/<footer\b[\s\S]*?<\/footer>/i)?.[0];
if (!footer || footer.includes('<?')) throw new Error('Expected a static footer fragment');

const sharedStyles = `<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="/navbar.css"><link rel="stylesheet" href="/footer.css">`;

for (const page of ['Homepage.html', 'ProductPage.html', 'ProductDescription.html', 'unavailable.html']) {
  let html = await readFile(join(root, page), 'utf8');
  // Embed shared markup at build time; a static host cannot execute PHP includes.
  html = html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, (script) =>
    /fetch\(['"](?:navbar|footer)\.php['"]\)/.test(script) ? '' : script);
  html = html.replace(/<script\s+src="map\.js"\s*><\/script>/g, '');
  html = html.replace(/navbar_footer\//g, '');
  html = html.replace(/<div id="navbar"><\/div>/, navbar);
  html = html.replace(/<div id="footer"><\/div>/, footer);
  html = html.replace(/<head>/i, `<head>\n${sharedStyles}`);
  html = html.replace(/<\/body>/i, '<script src="/storefront.js"></script>\n</body>');
  if (html.includes('<?') || /fetch\(['"](?:navbar|footer)\.php['"]\)/.test(html)) {
    throw new Error(`Unprocessed server code in ${page}`);
  }
  await writeFile(join(output, page), html);
  if (page === 'Homepage.html') await writeFile(join(output, 'index.html'), html);
}
console.log('Built BookBang storefront in dist/ (PHP backend files excluded).');
