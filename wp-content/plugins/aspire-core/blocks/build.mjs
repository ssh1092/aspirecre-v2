// No transpilation required: uses the official WordPress-provided runtime packages.
import { readFileSync, writeFileSync, copyFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
const root = fileURLToPath(new URL('.', import.meta.url));
execFileSync(process.execPath, ['--check', root + 'src/editor.js'], { stdio: 'inherit' });
for (const slug of ['property-finder','featured-properties','team-grid','property-types']) {
 const metadata = JSON.parse(readFileSync(root + 'build/' + slug + '/block.json', 'utf8'));
 if (metadata.apiVersion !== 3 || !metadata.name.startsWith('aspire-core/')) throw Error('Invalid block metadata');
}
for (const file of ['editor.js','editor.css']) copyFileSync(root + 'src/' + file, root + 'build/' + file);
const hash = createHash('sha256').update(readFileSync(root + 'src/editor.js')).update(readFileSync(root + 'src/editor.css')).digest('hex').slice(0,12);
writeFileSync(root + 'build/editor.asset.php', `<?php\nreturn array( 'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ), 'version' => '${hash}' );\n`);
console.log('Built four API v3 blocks; editor JavaScript syntax and metadata valid.');
