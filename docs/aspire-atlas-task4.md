# Aspire Atlas — Task 4: Remaining intent modes

All five modes now run inside the existing framework-free Atlas application: `explore`, `find-space`, `invest`, `owner-disposition`, and `manage-asset`. No mode transition reloads the page. The approved opening, Find Space filters, map style and Property Focus visual architecture are retained. No database setup is required.

## Files changed

- `atlas/template.php`: intent mode identifiers and the initially hidden guided panel include.
- `atlas/guided.php`: semantic guided panel, Back/Continue, live status, and phone link.
- `atlas/find-space.php`: conditional investment price control and contextual size label.
- `atlas/src/find-space.js`: extends the existing state controller, preserves independent discovery filters, and connects the shared guided renderer.
- `atlas/src/invest.js`: investment filtering, reliable price ranges, contextual size options and card metrics.
- `atlas/src/workflow-data.js`: independent workflow defaults, step validation, multi-select rules and detached prepared-details payload.
- `atlas/src/guided.js`, `guided.css`: one progressive UI for both owner workflows, reviews, prepared state and responsive styling.
- `atlas/src/find-space.css`: applies existing discovery styling to Invest as well as Find Space; no visual redesign of Find Space.
- `atlas/src/view.js`: routes all modes through the existing map/selection adapter, dims listings in guided modes, and reuses area-preset camera behavior.
- `includes/class-atlas.php`: additive `displayTitle` field.
- `atlas/src/focus-data.js`: uses the display title in the existing dossier.
- `atlas/tests/contracts.test.mjs`, `tests/atlas.php`: extended regression tests.
- `atlas/build/{view.js,view.css,manifest.json}`: generated with the existing build.

No PMTiles, cartographic style, marker-layer definitions, original Find Space filter logic, Property Focus CSS, metadata definitions, theme, Docker configuration, dependencies or stored properties were changed.

## State architecture

The existing controller still owns mode, selection, hover, Property Focus and camera/rail restoration. It now retains separate `findSpaceFilters` and `investFilters`; `filters` points at the active discovery state. These remain useful when revisiting either discovery mode during the page session.

Each guided workflow owns a separate object:

- `ownerDisposition`: `step`, `locationText`, `areaPreset`, `propertyType`, `sizeRange`, `intent`.
- `management`: `step`, `locationText`, `areaPreset`, `propertyType`, `sizeRange`, `needs`.

Steps 1–4 collect answers, step 5 reviews them, and step 6 displays prepared details. Returning to Explore clears the departing guided object, its rendered answer content and `briefPreparation`. Nothing uses localStorage or server persistence.

`briefPreparation` is only an in-memory handoff: `{ status: 'prepared', source, details }`. Build My CRE Brief copies the current answers into it and shows “Your property details are ready.” Review details returns to review and invalidates that prepared snapshot so edits can be reflected in the next preparation. No brief is generated, submitted, sent or saved; Task 5 is not implemented.

## Buy / Invest

Invest reuses the existing filter rail, result cards, source/layers, hover/selection, dossier and Back restoration. Its heading is BUY / INVEST and rail language is EXPLORE OPPORTUNITIES. The underlying transaction intent is For Sale, including the actual dual-listing slug `for-sale-or-lease` when present.

- Property Type uses existing registered terms.
- Price uses only positive finite numeric `pricing.salePrice`: under $1M, $1M–<2.5M, $2.5M–<5M, and $5M+. Missing, invalid, zero or suppressed prices never match an active price range. Display strings are not parsed as prices.
- All/non-land types show Building SF presets and filter `buildingSf`. Choosing Land switches to Lot Acres (under 2, 2–<5, 5–<20, 20+ acres). Switching across the land/building boundary clears the prior size selection. Active SF ranges exclude land; active acre ranges exclude building-oriented assets.
- Area reuses the same documented approximate boxes in `filters.js`; no official boundaries or new polygons are claimed.

Cards emphasize building SF or site acres and append a reliable numeric sale price only if available. The current two sale records are Presidio and FM 1093. Neither currently has a reliable sale price in Atlas, so every active price range correctly yields zero results. FM 1093 remains suppressed and displays 2.21 AC without price. No replacement prices were invented.

## Lease / Sell workflow

1. Location: free-text address/area and/or an existing approximate area preset.
2. Property type: Retail, Office, Industrial / Flex, Land, Other.
3. Approximate size: building SF bands, or acreage bands for Land; Not sure is supported. Switching between land and building types clears an incompatible prior size answer.
4. Intent: Lease it, Sell it, Both, Not sure yet.
5. YOUR PROPERTY review, with Both displayed as Lease or Sell.
6. Prepared details, with Review details and the phone action.

## Management workflow

The same panel collects location, type (Retail, Office, Industrial / Flex, Mixed Use, Other), building-size band, and multiple management needs. Specific needs can be combined. Not sure yet is exclusive: selecting it clears specifics; selecting a specific need clears Not sure yet. Back navigation retains selections. Review uses YOUR PROPERTY MANAGEMENT NEED and the same preparation behavior.

Intake type labels are workflow answers, not new WordPress taxonomy terms or Property records.

## Map and network relationship

The existing map instance and already-loaded REST features are reused. Invest retains property discovery behavior. Guided modes dim the existing listing markers and ignore their property-selection gestures so the map serves as location context. Selecting an area eases the same camera to its known approximate box. Free text only updates `locationText`; it is never converted to coordinates, added as a map marker or sent to a service.

No geocoder, autocomplete, paid API, AI, analytics, form submission, email or CRM integration was introduced. The only property fetch remains the existing Atlas REST load. Area navigation naturally continues loading the existing local map tiles/glyphs.

## Display titles

Atlas adds `properties.displayTitle` without changing any existing REST field. The helper removes only a trailing city/state/optional postal-code suffix matching that property's actual location metadata. Delimiters are handled explicitly and metadata is escaped in the matching pattern. If there is no safe suffix match, the original title is retained.

Examples: `16840 Clay Road` and `14602 Presidio Square Boulevard`. City/state/ZIP remain on the dossier's separate location line. Cards and marker labels also use the display value, with fallback to the existing `title` field. WordPress post titles, slugs, permalinks and SEO behavior are untouched. A before/after REST comparison excluding `displayTitle` matched every previous field and value for the four real properties.

## Accessibility and responsive behavior

Guided questions use native labeled inputs/selects, fieldsets/legends, radio buttons and checkboxes. Steps announce their change and move focus to the heading; validation announces a concise error and focuses the current controls. Tap targets are at least 46px for choices/primary controls, with teal checked/focus states. Review values are rendered as text, not HTML. Location can be supplied entirely by keyboard without using the map.

Both guided workflows link Talk to Aspire to `tel:+17139332001`. Existing Property Focus keyboard behavior and restoration are shared by Invest. Every mode has an Explore exit.

Desktop uses a compact left overlay panel; tablet keeps it comfortably sized; mobile stacks the panel with map context below. Invest uses the existing responsive rail. No final mobile bottom-sheet system was added. Existing reduced-motion handling applies to panel entrances and camera durations.

## Verification

- 84 Core integration assertions pass; temporary fixtures removed.
- 61 Atlas PHP checks pass, including additive display titles, unchanged WordPress titles, existing REST metrics/highlights/pricing, new intent wiring and no guided submission form.
- 11 JS tests pass: prior style/REST/Find Space/dossier checks plus sale filtering, price boundaries/unavailable prices, SF-versus-acre matching, workflow validation, independent defaults, multi-select behavior and detached preparation payloads.
- Existing build, changed PHP lint and `git diff --check` pass.
- Browser: Invest default results, empty price searches, Land/acre/area filtering, card and marker entry into the existing dossier, selected-card restoration and Invest filters verified.
- Both guided workflows completed through review/preparation; Management also completed using keyboard controls. Back navigation, multiple needs, location validation, exact free text in review, phone target, exit cleanup and fresh re-entry verified.
- Find Space regression: combined type/size filters, Clay Road dossier, shortened title with separate location, and restored results verified.
- 820px tablet, 390px mobile and 320px narrow-screen checks show no horizontal page overflow. Current-build browser console has no errors or warnings.

## Known limitations and scope boundary

Current sale prices are unavailable in Atlas; price-filtered results therefore remain empty until reliable values are supplied through the existing property data. Existing provisional coordinates and approximate-area limitations remain. Guided answers are session memory only; no address geocoding or persistence exists. The prepared-details CTA does not build a CRE Brief. Existing unrelated navigation destinations and the duplicate `WP_DEBUG` configuration warning remain unchanged. No Task 5 implementation was started.

## Git handoff

`git diff --stat` (tracked changes; six new untracked files appear in status):

```text
 .../plugins/aspire-core/atlas/build/manifest.json  |  2 +-
 .../plugins/aspire-core/atlas/build/view.css       |  2 +-
 wp-content/plugins/aspire-core/atlas/build/view.js | 76 +++++++++++-----------
 .../plugins/aspire-core/atlas/find-space.php       |  3 +-
 .../plugins/aspire-core/atlas/src/find-space.css   | 16 ++---
 .../plugins/aspire-core/atlas/src/find-space.js    | 66 +++++++++++++------
 .../plugins/aspire-core/atlas/src/focus-data.js    |  2 +-
 wp-content/plugins/aspire-core/atlas/src/view.js   | 27 ++++----
 wp-content/plugins/aspire-core/atlas/template.php  |  4 +-
 .../aspire-core/atlas/tests/contracts.test.mjs     | 26 ++++++++
 .../plugins/aspire-core/includes/class-atlas.php   |  7 ++
 wp-content/plugins/aspire-core/tests/atlas.php     |  4 ++
 12 files changed, 153 insertions(+), 82 deletions(-)
```

`git status --short`:

```text
 M wp-content/plugins/aspire-core/atlas/build/manifest.json
 M wp-content/plugins/aspire-core/atlas/build/view.css
 M wp-content/plugins/aspire-core/atlas/build/view.js
 M wp-content/plugins/aspire-core/atlas/find-space.php
 M wp-content/plugins/aspire-core/atlas/src/find-space.css
 M wp-content/plugins/aspire-core/atlas/src/find-space.js
 M wp-content/plugins/aspire-core/atlas/src/focus-data.js
 M wp-content/plugins/aspire-core/atlas/src/view.js
 M wp-content/plugins/aspire-core/atlas/template.php
 M wp-content/plugins/aspire-core/atlas/tests/contracts.test.mjs
 M wp-content/plugins/aspire-core/includes/class-atlas.php
 M wp-content/plugins/aspire-core/tests/atlas.php
?? docs/aspire-atlas-task4.md
?? wp-content/plugins/aspire-core/atlas/guided.php
?? wp-content/plugins/aspire-core/atlas/src/guided.css
?? wp-content/plugins/aspire-core/atlas/src/guided.js
?? wp-content/plugins/aspire-core/atlas/src/invest.js
?? wp-content/plugins/aspire-core/atlas/src/workflow-data.js
```

Nothing is staged and no commit was created.
