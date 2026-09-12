# Property Detail Task 1 — Desktop dossier

Implemented the existing `/properties/{slug}/` single-property route using the theme's `single-property.php`. The existing site header/footer are retained. No Atlas, directory, Docker, metadata registration, taxonomy, property record, or team record changes are included.

## Presentation

- Approximately 62% featured imagery / 38% summary at desktop widths. Responsive WordPress images, genuine type/transaction labels, cleaned frontend title, location, real metrics and telephone/Brief actions.
- Sticky conditional section navigation with keyboard focus transfer and reduced-motion support.
- Actual editor content when present, structured highlights, meaningful suite columns, type-aware detail groups, optional gallery/local PDF brochure/published assigned advisors, address and final conversion section.
- Current records have no editor body, galleries, brochures or assigned advisors. These absent sections/actions are omitted; nothing was generated to fill them.
- Gallery images link to the original local image. No gallery dependency or new viewer was introduced.
- Narrow layouts stack safely as a foundation only; final mobile Property Detail was not built.

## Data and map reuse

`inc/property-dossier.php` reads Aspire Core's existing fields. Suite sanitation uses `Aspire_Core_Fields::suites()` and `clean()`. Broker assignments use the actual repeated `_aspire_listing_broker_id` key. No fields are registered by the theme.

Mapped records reuse `Aspire_Atlas::collection()` display titles and safe pricing. Unmapped/inactive records use the same suffix-only title rule and the same `aspire_atlas_suppressed_price_ids` policy. FM 1093's stored price remains unchanged and is absent from rendered pricing and map data.

The Location section lazily imports the existing directory map bundle and stylesheet, using the same Atlas worker, style, layers and local Houston PMTiles file. It passes only the selected property, makes no REST inventory request and never initializes the Atlas application. Missing/invalid coordinates omit the map configuration; map failures leave the address and property content available.

## Validation

- Visually inspected Clay Road at 1440px, Atascocita at 1280px, Presidio at 1024px and FM 1093 at 1440px.
- Verified Atascocita Suite F: 4,361 SF, $18, Base + NNN, Second Gen Retail, Available, $5.84/SF NNN.
- Verified Presidio: 42,716 SF, 2014, Class B, 3 stories, 160 spaces, 3.95 spaces per 1,000 SF.
- Verified FM 1093: 2.21 AC, no suppressed price.
- Checked selected map marker, lazy map assets, sticky navigation, Enter navigation/focus, telephone/Brief hrefs, and no relevant browser errors.
- Checked 768px tablet and 320/390px narrow fallback without page-level overflow. Availability scrolls within its labeled table region.
- Confirmed dossier assets are absent from `/properties/` and `/` and the locked source/build files remain unchanged.

Commands and results:

- `npm --prefix wp-content/plugins/aspire-core run build` — passed, no generated Atlas/directory changes.
- Existing Atlas/directory JS suites — 23 passed.
- `node --test wp-content/themes/aspirecre/tests/property-dossier.test.mjs` — 4 passed (deferred assets, missing coordinates, failure fallback, reduced-motion/focus behavior).
- `docker compose exec -T wordpress php wp-content/themes/aspirecre/tests/property-dossier.php` — 65 read-only checks passed; approved property/team rows and metadata fingerprints unchanged. Optional media/missing-data branches use intercepted reads, not database writes.
- Existing PHP suites — Core 84, Atlas 61, enquiries 50, directory 36 passed. Existing suites clean up their temporary fixtures.
- Four changed/new PHP files lint clean; frontend JS syntax and git whitespace checks passed.
- Existing duplicate `WP_DEBUG` configuration warning persists; Docker/wp-config were not modified.

## Files

- `wp-content/themes/aspirecre/functions.php` — loads the single-property presentation helper.
- `wp-content/themes/aspirecre/single-property.php` — dynamic dossier template.
- `wp-content/themes/aspirecre/inc/property-dossier.php` — safe view model and scoped asset enqueue.
- `wp-content/themes/aspirecre/assets/property-dossier.css` — desktop design and stacking foundation.
- `wp-content/themes/aspirecre/assets/js/property-dossier.js` — section navigation and lazy shared map.
- `wp-content/themes/aspirecre/tests/property-dossier.php` — read-only PHP contracts.
- `wp-content/themes/aspirecre/tests/property-dossier.test.mjs` — browser-controller contracts.

No commit created. Stopped after desktop Property Detail.
