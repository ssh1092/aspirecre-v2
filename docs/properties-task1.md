# Properties Task 1 — desktop directory

## Delivered

Published WordPress Page **Properties**, ID **297**, at `/properties/`. The page contains native Gutenberg intro blocks, `aspire/property-directory`, and native editorial/link blocks below. No shortcode, CPT archive or hard-coded page content template. The theme's `templates/property-directory.php` only renders the existing header, `the_content()`, and footer.

The explicit local setup script is `wp-content/plugins/aspire-core/bin/setup-property-directory.php`. It backs up pre-directory content when migrating an existing page, preserves an existing directory page's edited content, and reuses the same Page. A regression test covers repeat setup and duplicate prevention. The initial insert-vs-update bug found during testing was corrected and the requested page content restored before completion.

## Ownership and files

Plugin:
- `includes/class-property-directory.php`: block registration, conditional assets, opt-in REST enrichment, robots/canonical hooks.
- `directory/template.php`, `directory/build/block.json`: block markup and metadata.
- `directory/src/data.js`: filtering, query state, sorting, card presentation data.
- `directory/src/view.js`: native controls, results, pagination, selection and lazy map integration.
- `directory/src/map.js`: thin directory adapter around the existing mapping dependencies and shared Atlas helpers.
- `directory/src/editor.js`, `editor.css`: native editor registration/settings/static preview.
- `directory/build.mjs`, generated `directory/build/*`: built assets and required dependency license notice.
- `directory/tests/*`, `tests/directory.php`: JS/editor contracts and WordPress integration checks.
- `bin/setup-property-directory.php`: repeatable Page setup.
- `aspire-core.php` and `package.json`: module/build/test registration only.

Theme:
- `assets/property-directory.css`: lighter page, cards, controls, sticky map and simple responsive fallback.
- `inc/property-directory.php`: directory-only frontend styles/navigation and editor presentation styles.
- `templates/property-directory.php`: normal page renderer.
- `functions.php`: loads the directory presentation module.

No Atlas source/build files, existing Atlas tests, CPT/metadata definitions, Docker files or package dependencies changed. No individual property, mobile application, or curated property-type pages were built.

## Map and data reuse

The directory requests the existing `/wp-json/aspire/v1/atlas/properties?directory=1` endpoint once. A scoped `rest_post_dispatch` filter adds `properties.directory` containing featured status, publication time and selected existing building/site facts. All original GeoJSON fields and pricing remain unchanged; normal Atlas requests receive the original response. No additional Property API or metadata definitions.

The lazy map module uses the existing pinned MapLibre/PMTiles dependencies, imports unchanged Atlas cartography/marker helpers, and points to the same Houston PMTiles, glyphs and built worker. It does not import or initialize Atlas workflows, Brief, dossiers or the Atlas application bundle. The directory map adapter is built separately so those application modules are not loaded. Show Map off excludes map configuration/assets; default List delays loading the map module until Split is requested.

The plugin reuses Atlas's URL/image validation, display formatting and price suppression. Local images are lazy-loaded. Cards show real type/transaction, cleaned title, city/state, appropriate available/building SF or acreage, reliable rates/prices, useful building details and up to three supplied highlights. FM 1093 has no directory price.

## Interaction and URL state

Type, transaction, SF/acreage and approximate Houston area filters run entirely on the loaded collection. Selecting Land changes size units and clears incompatible size state. For Lease/For Sale include dual listings; the For Sale or Lease filter matches that actual term. Legacy footer `property_type`/`transaction_type` query names are accepted; new URLs use `type`, `transaction`, `size`, `area` with readable values.

History API changes do not reload the page. Initial URLs and Back/Forward restore filters. Reset produces `/properties/`. Sort and Split/List remain client state. Featured ranks flagged records first, Newest uses publication time, Largest ranks buildings by available SF with building fallback and groups land separately by acres; the UI explains the unit grouping. Properties per load supports All, 12 and 24 with Load More.

Card hover/focus highlights the marker; card selection moves the map without navigation. Marker interaction highlights/selects the corresponding card, and marker selection scrolls it into view. An accessible Locate a property select offers keyboard access to the same selection. The explicit View Property link follows the real permalink. The selected map preview stays compact. Manual panning does not trigger automatic refitting; camera updates are limited to initial data, filters and explicit selection, respecting reduced motion.

## SEO

For the actual Properties directory Page, relevant query parameters produce `noindex,follow` through `wp_robots`. `get_canonical_url` keeps the canonical at `/properties/`. These conditions do not match future curated child pages. No SEO plugin or filter-combination pages.

## Editor

API v3 block in AspireCRE, with Default View, Show Map and Properties per load settings. Server preview shows the published inventory count, current view/map settings and filter summary, without loading WebGL or exposing paths/coordinates. Intro and editorial copy remain normal editable core blocks.

## Verification

- Build passed, including unchanged Atlas outputs.
- 7 directory JS/editor contract tests passed.
- 36 directory WordPress checks passed, including repeat setup, REST compatibility, safe pricing, permalink resolution, conditional map markup, robots/canonical and static editor rendering.
- Existing tests unchanged and passing: 15 Atlas JS, 61 Atlas PHP, 84 Core integration, 50 enquiry assertions.
- All eight changed/new PHP source/test files linted successfully; whitespace check passed.
- Browser: Split/List, type/transaction/size/area filtering, Land acreage, reset, reload restoration, Back/Forward, empty state, sorting, keyboard card focus/map label, native map selector, direct marker selection, selected preview and sticky containment checked.
- Sticky map bottom matched the directory boundary and remained above the editorial section.
- Directory inspected at 1440/1280/1024, tablet 768, and narrow 390/320 CSS pixels without page-level horizontal overflow. The narrow fallback is intentionally basic, not the final mobile directory product.
- Home/Atlas still renders; Find Space returns the original two lease opportunities; directory scripts absent from Home. Relevant frontend console logs empty.
- Gutenberg block list confirmed the native directory and surrounding Groups. Static preview API/settings and editor registration contracts passed.

## Limits

The in-app browser displays an empty Gutenberg canvas iframe for both the existing Home editor and the new Properties editor. Full live canvas/Inspector visual verification was therefore unavailable in this browser; no global WordPress editor workaround was added. Server-rendered preview and native registration/settings tests pass.

Inventory inherits the existing Atlas endpoint's published/available, valid-coordinate eligibility. All four approved current listings meet it; unmapped future listings are not included. Existing provisional coordinates remain unchanged. No new properties/team records were created for this directory.

Physical-device/mobile-browser testing and the final mobile directory experience remain outside this desktop task. Existing single-property fallback pages remain unchanged. The local bootstrap's pre-existing duplicate WP_DEBUG warning persists; no new PHP errors were observed.
