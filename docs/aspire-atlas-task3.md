# Aspire Atlas — Task 3: Property Focus

Property Focus opens inside the existing Atlas map from a result button, a map marker, or the Explore keyboard selector. The approved Explore and Find Space presentations remain unchanged before selection. No new framework, dependency, property record, media, gallery, full property page, other intent workflow, or Task 4 feature was added.

## Files changed

- `atlas/property-focus.php`: initially hidden, frontend-only semantic dossier, Back control, scrollable details and two real actions. Included from `atlas/template.php` without changing the static Gutenberg preview.
- `atlas/src/focus-data.js`: safe property view model, genuine metrics, nullable data and same-origin media/permalink handling.
- `atlas/src/property-focus.js`: DOM rendering, local featured image with error/missing-image fallback, metrics and up to five highlights.
- `atlas/src/property-focus.css`: focus-only 38% desktop / 48% tablet dossier and stacked mobile composition.
- `atlas/src/find-space.js`: shared focus state, opening/closing, announcements, preserved filters and horizontal rail position, sensible focus restoration.
- `atlas/src/view.js`: saved camera, focus padding/zoom and restoration, persistent selection and secondary marker dimming.
- `includes/class-atlas.php`: one additive REST property, `propertyHighlights`.
- `atlas/tests/contracts.test.mjs`, `tests/atlas.php`: data, fallback and compatibility checks.
- `atlas/build/{view.js,view.css,manifest.json}`: generated with the existing build.

The original cartographic style, PMTiles, metadata definitions, property coordinates, filter logic, opening/discovery CSS, theme and Docker configuration are unchanged.

## REST addition and data rules

`properties.propertyHighlights` is an array of nonempty sanitized lines read directly from the existing `_aspire_property_highlights` field. All stored lines remain available in the API; the dossier shows at most five in stored order. No frontend HTML is parsed. A before/after comparison of the four real features, excluding this addition, found every existing field and value unchanged.

Metrics show positive available SF, existing lease-rate display text, positive building SF and lot acres, plus existing permitted pricing when present. Presidio's exact standalone stored highlight `160 parking spaces` supplies the parking metric and is not repeated in the highlight list. No new parking field or metadata definition was added. Missing metrics have no label or placeholder. FM 1093 continues to receive null pricing and shows only its 2.21 AC site metric.

The featured image uses the existing local WordPress media URL. Missing, rejected external or failed images get a restrained Aspire Commercial fallback. No gallery UI is fabricated. View Full Property uses the existing same-origin property permalink. Talk to Aspire links to `tel:+17139332001`; no contact page, form or email was created.

## State, camera and restoration

The existing framework-free state now includes `propertyFocusOpen`, `cameraBeforeFocus` and `railScrollBeforeFocus`, alongside the existing mode, filters and selection IDs. Focus is a composition within the current mode, not a new page or modal. Filter and card DOM remain intact while hidden; no new API fetch occurs on selection.

Opening focus hides the results/filter rail (or opening panel when entered from Explore), renders the right dossier, selects the marker and eases to zoom 14 over 700ms. Padding reserves the dossier width plus a small inset so the marker sits slightly left of the usable map's center. Map controls and attribution stay in the usable map portion. Other markers may remain visible at reduced opacity; the selected label persists. The map style and archive remain unchanged.

Back restores the previous mode, filters, count, selected card, rail scroll position and saved camera center/zoom/bearing/pitch. Padding is recomputed for the current viewport, allowing sensible restoration after a resize. Result-card entry returns keyboard focus to that card; marker entry returns focus to the map without scrolling the card rail; Explore keyboard entry restores its selector. Direct Explore entry keeps mode `explore` and appropriately labels the action `Back to Explore`.

An unavailable/unrenderable selection leaves or returns to the originating discovery view with a concise accessible message; it does not blank Atlas. Missing highlights omit the section, missing pricing is omitted, and invalid numeric metrics are not rendered.

## Accessibility and responsive behavior

The dossier is a labeled section, without dialog semantics or a focus trap. Opening is announced and Back receives focus as the first action. The property details region is keyboard focusable and scrollable, followed by the full-property and phone links. Images have meaningful alt text; metrics use `dl`/`dt`/`dd`; focus states remain visible. Closing announces the restored view.

Desktop uses approximately 62% map / 38% dossier. Tablet changes to 52% / 48%. The desktop dossier scrolls internally and keeps actions available. On mobile, a normal stacked dossier follows a reserved map area; all details and actions remain accessible through normal scrolling. No draggable bottom sheet is implemented.

Reduced-motion preference disables the dossier entrance and uses zero-duration camera moves and instant programmatic scrolling. These branches and CSS were checked in source. The available browser surface does not support emulating media preferences, so no browser-emulated reduced-motion claim is made.

## Verification

- 84 Core integration assertions pass; fixtures removed.
- 51 Atlas PHP checks pass, including unchanged existing metrics, four local-image properties, exact stored highlights, static Home/Gutenberg, suppressed price and frontend/editor separation.
- 8 JS tests pass, including prior style/REST/filter tests plus dossier metrics, parking facts, local URLs, missing data, suppressed price and highlight limits.
- Existing build, changed PHP lint and `git diff --check` pass.
- Browser verified card, marker and keyboard entry; real data for all four dossiers; selected zoom 14 and marker position left of the dossier; local image; correct permalink and phone target; hidden results; Back restoration of filters/count/camera/selection and rail scroll (including a nonzero 288px position); keyboard focus restored to card or map.
- Desktop, 820px tablet, 390px and 320px mobile checked without horizontal overflow. Back → details region keyboard flow verified. Current-build browser console reports no warnings/errors.

## Known limits

Presidio Square and FM 1093 retain their previously documented provisional coordinates. Highlight order follows the existing stored editorial order; parking is promoted only from an exact standalone fact. The individual property pages and unrelated navigation destinations remain their existing implementations. The pre-existing duplicate `WP_DEBUG` configuration warning remains outside this change. Final mobile sheets, galleries, analytics, full property page redesigns and further workflows are not implemented.

## Git handoff

`git diff --stat` (tracked files; five new untracked files are listed below):

```text
 .../plugins/aspire-core/atlas/build/manifest.json  |  2 +-
 .../plugins/aspire-core/atlas/build/view.css       |  2 +-
 wp-content/plugins/aspire-core/atlas/build/view.js | 28 +++++------
 .../plugins/aspire-core/atlas/src/find-space.js    | 58 ++++++++++++++++++----
 wp-content/plugins/aspire-core/atlas/src/view.js   | 26 ++++++++--
 wp-content/plugins/aspire-core/atlas/template.php  |  2 +-
 .../aspire-core/atlas/tests/contracts.test.mjs     | 21 ++++++++
 .../plugins/aspire-core/includes/class-atlas.php   |  1 +
 wp-content/plugins/aspire-core/tests/atlas.php     |  4 ++
 9 files changed, 111 insertions(+), 33 deletions(-)
```

`git status --short`:

```text
 M wp-content/plugins/aspire-core/atlas/build/manifest.json
 M wp-content/plugins/aspire-core/atlas/build/view.css
 M wp-content/plugins/aspire-core/atlas/build/view.js
 M wp-content/plugins/aspire-core/atlas/src/find-space.js
 M wp-content/plugins/aspire-core/atlas/src/view.js
 M wp-content/plugins/aspire-core/atlas/template.php
 M wp-content/plugins/aspire-core/atlas/tests/contracts.test.mjs
 M wp-content/plugins/aspire-core/includes/class-atlas.php
 M wp-content/plugins/aspire-core/tests/atlas.php
?? docs/aspire-atlas-task3.md
?? wp-content/plugins/aspire-core/atlas/property-focus.php
?? wp-content/plugins/aspire-core/atlas/src/focus-data.js
?? wp-content/plugins/aspire-core/atlas/src/property-focus.css
?? wp-content/plugins/aspire-core/atlas/src/property-focus.js
```

Nothing is staged. No commit was created.
