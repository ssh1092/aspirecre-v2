# Aspire Atlas — Task 1 foundation

The existing Home page (ID 74) now contains `aspire/atlas`. The theme's header/footer and `front-page.php` page loop are unchanged. No filters, results rail, dossier, natural-language parser, brief builder or other intent workflow is implemented. Intent buttons acknowledge selection; input is an explicitly described placeholder. A keyboard-accessible property selector gives the same selected marker state as a pointer.

## Source and build

- `includes/class-atlas.php`: public GeoJSON API, block registration, escaped server markup, conditional assets.
- `atlas/build/block.json`, `atlas/template.php`: native block and shared static editor representation.
- `atlas/src/{view,properties,style}.js`: modular, framework-free runtime, GeoJSON layers and custom Protomaps v4 style.
- `atlas/src/{view,editor}.css`, `atlas/src/editor.js`: scoped frontend styles and Gutenberg preview/Inspector.
- `atlas/build.mjs`, `package.json`, `package-lock.json`: reproducible local bundles. Generated JS/CSS and vendor license notices are tracked; node_modules is ignored.

```sh
cd wp-content/plugins/aspire-core
npm ci
npm run build
npm run test:atlas
```

Pinned dependencies: MapLibre GL JS 6.9.0, PMTiles 4.5.0; esbuild 0.28.2 for development builds. WordPress provides the official Gutenberg React/editor packages. The frontend and worker run without a React application runtime, external script CDN, hosted tile API, API key or paid service. Atlas map bundles load only on pages containing the block; editor preview loads no map bundle, WebGL or PMTiles.

## Local Houston map

Run from the project root:

```sh
python3 bin/setup-atlas-map.py
# Reproduce this extraction, or refresh explicitly:
python3 bin/setup-atlas-map.py --date 20260911 --force
```

The script discovers completed builds from the official Protomaps build index, downloads a checksum-verified official go-pmtiles 1.31.2 CLI for macOS/Linux, and extracts by HTTP ranges. It never downloads the planet archive. Bbox is **[-95.95, 29.45, -95.05, 30.25]**, zooms **0–15**. Low-zoom tiles naturally contain context outside the bbox; this is a bounded sub-pyramid, not polygon clipping.

Generated file: `wp-content/uploads/aspire-atlas/houston.pmtiles` — **91,497,425 bytes (87.26 MiB)** from `20260911.pmtiles`, tileset schema 4.15.2. `source.json` alongside it records the source date, bounds, checksum and CLI version. Both the archive and generated assets are ignored by Git. Existing Apache serves the file directly with HTTP 206 and `Content-Range`; Docker was not changed. The setup also downloads local Noto Sans Regular glyph ranges for English/Latin Houston labels. No sprites or POI icons are required by this custom style. See `docs/licenses` for font/vendor notices; OSM/Protomaps attribution remains visible even on failure.

Sources: [Protomaps CLI extraction](https://docs.protomaps.com/pmtiles/cli), [basemap assets](https://docs.protomaps.com/basemaps/maplibre), [v4 layer schema](https://docs.protomaps.com/basemaps/layers).

## API and pricing policy

`GET /wp-json/aspire/v1/atlas/properties` returns a GeoJSON FeatureCollection with four features on this prototype. Each feature has its stable post ID, `[longitude, latitude]` geometry, title/slug/permalink, taxonomy slug+label, location, nullable numeric metrics, nullable pricing, local featured-image dimensions/alt, and coordinate status. Only published available records with finite, in-range coordinates qualify. Missing meta defaults to available consistently with Aspire Core. Private fields and raw `_aspire_*` keys are not exposed. Singular taxonomy representations select the oldest assigned term deterministically.

The `aspire_atlas_suppressed_price_ids` option suppresses all Atlas pricing for FM 1093. Its existing WordPress sale price and price-display metadata are unchanged. This policy is restricted to the Atlas representation and does not change conventional property renderers. Missing pricing stays `null`; the opening UI does not display prices.

## Coordinates

| Property | ID | Latitude | Longitude | Status |
| --- | --- | --- | --- | --- |
| Atascocita Retail Center | 119 | 29.9981 | -95.1616 | User-supplied prototype |
| 16840 Clay Road | 120 | 29.8344342 | -95.6575678 | User-supplied prototype |
| Presidio Square | 121 | 29.7076 | -95.6420 | **Provisional — final visual verification required** |
| 21617 FM 1093 | 122 | 29.6773 | -95.7195 | **Provisional — final visual verification required** |

These are not claimed to be verified parcel centroids. The Atlas UI, accessible selection labels and REST output identify provisional locations. No records were duplicated.

## Safe migration and recovery

The starting repository checkpoint was `05a55d9` (pre-Aspire Atlas). `docs/home-before-atlas.blocks.html` exactly matches the previous saved Home content, including its locally imported image blocks. The database also retains `_aspire_before_atlas_task1` and normal WordPress revisions.

```sh
docker compose exec -T wordpress php wp-content/plugins/aspire-core/bin/setup-atlas.php
# Restore the conventional Home content on the SAME page:
docker compose exec -T wordpress php wp-content/plugins/aspire-core/bin/setup-atlas.php --restore
```

The explicit local-only migration reuses posts by slug, populates only coordinate fields and Atlas display options, and keeps the existing static Home page. Repeat runs preserve edited Atlas attributes. Restore only restores Home content; coordinate/display-policy options remain. No migration runs on normal requests.

## Verification and limits

- 33 Atlas PHP contract checks, 84 existing Aspire Core assertions and two JS tests pass; fixtures roll back/are removed.
- PHP lint, JS bundles, MapLibre style validation and npm audit pass.
- HTTP 206 verified for the local PMTiles file; no map scripts appear on a non-Atlas property page.
- Actual browser renders the custom dark map, four GeoJSON markers, OSM attribution, pointer hover/selection and accessible selector. Desktop, 820 px tablet and 390 px mobile checks show no horizontal overflow; mobile intent controls measure 167 × 96 px.
- Missing map, failed REST, empty FeatureCollection and simulated unsupported WebGL each show useful fallback states. Temporary QA files are removed afterward.
- The existing `/properties/` directory does not exist. Atlas's View Properties fallback expands a minimal server-rendered list of real property links, so it remains useful even if REST/WebGL fails. This is not a results rail or new directory page; existing single-property presentation remains the foundation template.
- Gutenberg registers Aspire Atlas in the AspireCRE inserter; its REST preview and all four editorial attributes pass checks. The available in-app browser still leaves WordPress's editor iframe blank, and no Chrome connection is available. Direct canvas/Inspector visual interaction could not be verified there; the editor implementation does not initialize WebGL.
- Existing duplicate `WP_DEBUG` configuration warning remains outside this change.
- Desktop-first foundation only; final mobile bottom sheets, search/filters, Property Focus and brief workflows remain later tasks. Labels currently use local English/Latin glyph ranges.
