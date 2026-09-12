# Task 6B — mobile Atlas

Implemented below 768 CSS pixels. Existing desktop CSS, PHP templates, CPT definitions, metadata, matching, REST endpoints, tiles, and enquiry storage are unchanged.

## Architecture

`atlas/src/mobile.js` owns one mobile workspace sheet. It moves the existing Explore, discovery, dossier, guided and Brief panels into one content scroller. Current workflow actions move into a shared footer; placeholder comments restore every panel/action to its original location when leaving the mobile breakpoint. The existing controller remains the sole workflow/data owner.

The sheet supports peek, half and expanded positions, a Pointer Events drag handle, native content scrolling, a pull-down collapse gesture from the content top, and labelled keyboard-operable Expand/Collapse buttons. Peek makes the content inert. Workflow changes select an appropriate position, and returning to a previous panel restores its sheet position and scroll offset.

Map padding uses the target sheet position. Drag movement only updates a CSS height variable; MapLibre resize/padding updates happen on snapping, workflow transitions and debounced viewport changes, never every drag frame. Mobile cards select on first activation and open the existing dossier on the second. Desktop cards retain immediate opening.

`mobile.css` supplies the fixed dynamic-height shell, compact menu, vertical results, 16px inputs, safe-area padding, shared action footer, reachable map controls and attribution. Landscape uses the same sheet with a shorter map clearance and compact action layout. Existing reduced-motion rules disable sheet transitions.

## Validation

- Build succeeded; 15 Atlas JavaScript contract tests passed.
- Atlas PHP: 61 assertions; Core integration: 84; enquiry integration: 50.
- Changed PHP test file lint passed. An outdated Atlas editor assertion was corrected from the old “Build My Brief” label to the already-approved “CREATE MY REAL ESTATE BRIEF”.
- Browser checks: 390/375/320px mobile; 667×375 landscape; 768px tablet; 1024/1280/1440px desktop. No horizontal overflow in inspected workflows.
- Exercised Explore, Find Space filtering, two-tap property selection, dossier/back restoration, Buy / Invest land filtering, all owner steps, management steps/multi-select/review, Brief intro, all six questions, review scrolling, contact submission and success.
- Tested handle dragging down to half and up from peek, peek state, keyboard expansion, independent content scrolling, menu opening and Escape closing.
- Confirmed map controls and attribution above the sheet during Brief; one content scroller with footer outside its scrolling area. Relevant browser warning/error log empty.
- Browser submission fixture was removed after checking success. Existing five private enquiries retained; no property/team records created.
- Desktop/tablet screenshots inspected; original desktop panel placement and single-click dossier behavior retained. No pixel-diff baseline was generated.

Physical iPhone safe-area/virtual-keyboard behavior and native touch overscroll were not tested on hardware. Safe-area, dynamic viewport and reduced-motion handling were checked in source; pointer drag and keyboard behavior were exercised in the browser. A pre-existing duplicate WP_DEBUG definition warning remains in the local WordPress bootstrap.

## Files

- Added `atlas/src/mobile.js` and `atlas/src/mobile.css`.
- Updated `atlas/src/find-space.js` presentation hook/mobile card selection.
- Updated `atlas/src/view.js` mobile composition and mobile-only direct map gestures.
- Rebuilt `atlas/build/view.js`, `view.css`, `manifest.json`.
- Corrected `tests/atlas.php` stale copy assertion.
- Added this report.

No Docker, theme, data schema, public API or server-side product code changes. No new libraries or build toolchain. Stop at Task 6B.
