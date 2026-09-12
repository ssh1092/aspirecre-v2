/** Read the archive actually configured by Atlas using its installed PMTiles reader. */
import fs from 'node:fs/promises';
import crypto from 'node:crypto';
import { PMTiles } from '../../wp-content/plugins/aspire-core/node_modules/pmtiles/dist/esm/index.js';
const config = await fs.readFile(new URL('../../wp-content/plugins/aspire-core/includes/class-atlas.php', import.meta.url), 'utf8');
const match = config.match(/content_url\('([^']+\.pmtiles)'\)/);
if (!match) throw Error('Cannot resolve Atlas PMTiles path');
const path = new URL('../../wp-content' + match[1], import.meta.url);
const file = await fs.open(path, 'r');
try {
  const source = { getKey: () => path.pathname, getBytes: async (offset, length) => {
    const buffer = Buffer.alloc(length);
    const { bytesRead } = await file.read(buffer, 0, length, offset);
    return { data: buffer.buffer.slice(buffer.byteOffset, buffer.byteOffset + bytesRead) };
  }};
  const archive = new PMTiles(source);
  const header = await archive.getHeader();
  const metadata = await archive.getMetadata();
  const probes = [];
  for (const z of [...new Set([header.minZoom, header.maxZoom])]) {
    const n = 2 ** z;
    const x = Math.floor((header.centerLon + 180) / 360 * n);
    const rad = header.centerLat * Math.PI / 180;
    const y = Math.floor((1 - Math.asinh(Math.tan(rad)) / Math.PI) / 2 * n);
    const tile = await archive.getZxy(z, x, y);
    probes.push({ location: 'archive header center', z, x, y, present: Boolean(tile), bytes: tile?.data.byteLength ?? 0 });
  }
  const digest = crypto.createHash('sha256');
  for await (const chunk of file.createReadStream({ start: 0, autoClose: false })) digest.update(chunk);
  console.log(JSON.stringify({ path: 'wp-content' + match[1], sha256: digest.digest('hex'), bounds: { west: header.minLon, south: header.minLat, east: header.maxLon, north: header.maxLat }, min_zoom: header.minZoom, max_zoom: header.maxZoom, header, metadata, probes, limitation: 'Bounding-box inclusion is not proof of tile detail. Probes establish tile presence only at sampled locations/zooms.' }, null, 2));
} finally { await file.close(); }
