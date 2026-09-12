# Aspire property system reset

Replaces the rejected public dossier and native metabox editor. The six-property pilot remains the data source; this task does not import inventory or introduce another CMS.

## Public property experience

The theme now renders one photography-led stage, four mode tabs, one active panel and a compact contact strip above the existing footer. The stage uses an approximately 68/32 split and no more than three metrics. White, near-white, charcoal and cool-gray surfaces replace the former cream/green sections; Aspire green is reserved for actions, focus and selection.

- **Property:** two or three deterministic factual sentences plus compact supporting signals. `Aspire_Property_Dossier::workspace()` composes the existing safe model in Core. `Aspire_Property_Lens` retains the known/conflict gates, curated-field precedence, type-specific facts and unresolved questions. Its educational implication copy has been removed. No generated prose is stored and no external service is called.
- **Space:** suite comparison table with saved size, recognized use/former-use context and suite-specific rate. Missing values remain dashes. Industrial/office/land listings use compact operational/offering facts. Office Condo uses the known unit/combinability model without fabricated suites or floor plans.
- **Location:** address and source-backed context around the existing single-property MapLibre/PMTiles renderer. Assets initialize when the mode becomes visible; the full Atlas application is never initialized. Public and admin share the same small map loader and markup. Existing basemap, worker, glyphs and directory map module remain unchanged.
- **Due Diligence:** compact known facts, at most six unresolved questions and local brochure/gallery links. Office Condo has association, combination, use, parking, delivery and utility questions. Conflicting and review-only facts remain withheld.

Tabs support deep-link hashes, arrow keys, Home/End, one tab stop and browser Back/Forward. Hidden modes are not stacked down the page. At 1440px, the property content area measured approximately **963–1,212px**, depending on mode/property, excluding shared header/footer. This replaces the former seven-section vertical sequence; no second gallery or large final promotional section remains.

The featured image plus unique local gallery IDs supply inline previous/next/count and an accessible native fullscreen dialog. Fullscreen supports arrows, Escape, focus return, focus containment, scroll lock and responsive sources. Secondary full-size photos load on interaction. There is no gallery library or frontend geocoder.

### Six-property acceptance

All six records passed all four modes at **1440, 1280 and 1024 actual browser pixels: 72 checks**, with one visible panel and no horizontal overflow. Both authenticated draft previews were included. All six gallery flows passed inline navigation, fullscreen next/arrow, Escape and focus/scroll restoration. Browser Back/Forward and keyboard mode navigation passed. At 375px all six initial views and Sugarwell Space passed the overflow baseline; no final mobile redesign was attempted.

| Property | Preserved behavior |
| --- | --- |
| Clay, 120 | 11,273 SF available, 37,309 SF building, 14′ clear height, current $9.75–$11.50/SF/YR, rear-load and grade-level loading. Legacy 1,687/2,087 SF split withheld. |
| Atascocita, 119 | Real Suite F, 4,361 SF, $18 base and existing $5.84/SF NNN context. |
| Presidio, 121 | 42,716 SF, Class B, three stories, 2014, 160 parking spaces and curated 3.95/1,000 SF ratio. |
| FM 1093, 122 | Acreage and safe corridor facts; suppressed stored price is absent from public presentation; no public brochure. |
| Sugarwell, 351 | Draft preview; 5,082 SF, four spaces, 766–1,630 SF **known** range; Suite 103 area stays unknown and its $24/SF Base rate stays attached to that suite. Traffic remains 22,246 VPD with road not specified. |
| Sienna, 392 | Draft preview; Office Condo; **For Sale · For Lease**; 1,225 SF/unit and up to 7,350 SF in one building; no invented unit inventory. |

Local files were verified: Atascocita 5 photos + PDF; Clay 5 + PDF; Presidio 8 + PDF; FM 14 photos/no PDF; Sugarwell 7 + PDF; Sienna 7 + PDF.

## WordPress Property Workspace

`Aspire_Property_Workspace` renders inside WordPress's existing `#post` form. The native title and publish box are moved into the workspace; the publish box remains available under Publishing options. Header actions invoke WordPress's actual Save Draft/Update controls. Preview uses WordPress's own preview URL. Published/private/scheduled posts use the existing update path; draft/pending saves remain native.

The seven keyboard-operable tabs display one panel at a time:

| Tab | Editing experience |
| --- | --- |
| Overview | Native property title, real taxonomy checkboxes including multiple transactions, status, featured flag, address and type-aware core facts. Other authoritative fields remain under Advanced property fields. |
| Availability | Compact suite summaries, one expanded suite at a time, Edit/Remove/Add Space. All seven existing suite keys and unknown availability states are preserved. |
| Media | Featured thumbnail/change, gallery thumbnails, drag reorder plus keyboard arrow controls, remove/add/select, PDF view/replace/remove through the native Media Library modal. Existing attachment IDs remain authoritative. |
| Intelligence | Human-readable grouped fact summaries with Edit disclosure. Conflict warnings remain visible; unknown fields live under Additional decision fields. Details to confirm come from the same presenter as the public page. |
| Location | Address, current pin, recorded coordinate source/review, and collapsed Adjust coordinates controls. No automatic geocoding or provenance rewriting. |
| Contacts | Real assigned Team relationships, separate migration candidates clearly marked not assigned publicly. No Team records are generated. |
| Source | Existing readable migration summary, warnings, image review and collapsed possible contacts. No raw JSON/hash wall. |

The large Classic Editor, excerpt, default property taxonomy/media controls, Custom Fields and irrelevant blog metaboxes are removed from this editing flow. Team administration retains its native field layout. Property workspace assets load only on Property edit/add screens.

### Persistence and security

There are no parallel meta keys or new save endpoints. Core's existing `aspire[...]` payload, suite array, ordered gallery IDs, brochure ID and repeated broker IDs remain authoritative. WordPress saves title, taxonomy inputs and `_thumbnail_id`. Existing Core and Intelligence nonces, capabilities, sanitizers, autosave/revision guards and escaping remain in force. Protected migration provenance is never written by the workspace.

Collapsed panels retain their enabled form controls so switching tabs does not discard values. Invalid fields reveal their panel/disclosure before native browser validation. Save controls submit all tabs through the native form. Unchanged intelligence form values preserve structured evidence rather than replacing it with display text.

### Admin QA

Sugarwell, Clay and Sienna each passed all seven tabs at 1280px: **21 checks**, no overflow and no blank Classic Editor. Sugarwell also passed the 1024px header/layout check. Clay's conflicting office/warehouse facts visibly require review. Sienna shows both transactions and remains Draft. Location shows the accepted Census source and actual pin, with raw coordinate inputs behind Adjust coordinates.

- Suite edit UI, one-open-at-a-time behavior and unsaved text changes tested; reload restored the saved values.
- Media Library gallery preselection/selection and PDF selection tested using existing attachments only.
- Gallery arrow reorder and actual drag reorder tested; reload restored the original ID order.
- Native **Save Draft** exercised on one disposable cloned fixture: suite change saved, existing body content survived, both transaction terms remained, thumbnail/gallery/provenance survived, and post stayed Draft. Fixture and its revisions were removed.
- A separate automated fixture round trip verifies collapsed suite values, gallery order, intelligence evidence/statuses, source preservation and nonce rejection. It always cleans up.
- No pilot property was saved during browser QA. No relevant browser console errors remained.

## Safety comparison

| Item | Before | After |
| --- | --- | --- |
| Pilot properties | 6 | 6 |
| All Property database rows | 7 | 7 |
| Published pilot properties | 4 | 4 |
| Pilot drafts | 2 | 2 |
| Attachments | 55 | 55 |

The seventh row is the **pre-existing auto-draft 432**, not a new import. IDs 119/120/121/122 remain Published; 351/392 remain Draft. Property rows (including slugs, titles, body and post statuses), taxonomy snapshots, relationships and property metadata match the baseline, excluding WordPress's normal `_edit_lock` values refreshed by admin visits.

Canonical property-meta SHA-256 excluding `_edit_lock`, before and after:

`f6e04d6d2a8181eef57df779318642297a1d83d257934aca3d9bfcf87d3536b4`

FM's saved price remains private and unchanged; its brochure remains unset. Sugarwell suites/media/coordinates and Sienna media/coordinates/both transactions remain unchanged. No imported media was added or removed. Atlas, Directory, enquiry behavior, CPT/taxonomy/meta definitions, migration V1–V4 artifacts, Docker and the existing PMTiles basemap remain unchanged. Temporary regression fixtures were removed.

## Automated validation

| Suite | Result |
| --- | --- |
| Core integration | 84 assertions pass |
| Atlas PHP | 61 checks pass |
| Atlas enquiry | 50 assertions pass |
| Directory PHP | 36 checks pass |
| Property detail | 77 checks pass |
| Property presenter | 127 assertions pass |
| Protected Intelligence | 10 assertions pass |
| Property Workspace | 67 assertions pass |
| JavaScript | 30 tests pass: Atlas 15, Directory 8, shared tabs/map 5, gallery 2 |
| Migration | 58 offline tests pass |
| Build | Blocks, Atlas and Directory build pass; generated runtime outputs unchanged |
| Syntax/whitespace | Changed PHP and JavaScript lint; `git diff --check` pass |

The three unchanged, unrelated failures are: Core blocks expects the old featured-property empty state; theme blocks expects the former seven-section homepage; theme homepage expects an empty property inventory. These tests and the homepage implementation were not changed. The existing duplicate `WP_DEBUG` configuration warning remains outside task scope; no Docker/config change was made.

## Changed implementation and cleanup

- Core: added workspace controller, shared tab/map assets, visual workspace assets and shared map markup; extended the read-only property presenter; replaced property metabox registration with the workspace while preserving save handlers.
- Theme: replaced `single-property.php`, media/location partials and property CSS/JS; added Property/Space/Due Diligence panels.
- Removed six obsolete theme sections: Lens, Availability, Details, Questions, lower Gallery and Advisors. Assigned advisors now appear compactly in Property mode when real relationships exist.
- Removed obsolete property `admin.js` and old suite/media metabox rendering. Retained only native Team field styling in `admin.css`.
- Updated relevant rendering tests, added workspace persistence and shared-tab/map tests, and replaced this document's rejected-dossier guidance.

Local QA evidence (ignored work files):

- [Public viewport results](../var/migration/property-reset-qa/browser-public.json)
- [Admin tab results](../var/migration/property-reset-qa/browser-admin.json)
- [Gallery checks](../var/migration/property-reset-qa/browser-gallery.json)
- [Narrow baseline](../var/migration/property-reset-qa/browser-narrow.json)
- [Safety comparison](../var/migration/property-reset-qa/safety.json)
- [Test run index](../var/migration/property-reset-qa/tests.json)
- [git diff --stat](../var/migration/property-reset-qa/git-diff-stat.txt)
- [git status](../var/migration/property-reset-qa/git-status.txt)

All implementation changes remain unstaged; no commit or deployment was made. Work stops after the public property and admin workspace reset.
