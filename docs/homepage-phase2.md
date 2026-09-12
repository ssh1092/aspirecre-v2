# Aspire Commercial homepage — Phase 2

Home is published Page **74**, the static WordPress front page. It previously contained only Atlas. It now contains Atlas followed by nine native Gutenberg sections. `front-page.php` still calls `the_content()`; no competing page template or frontend application was added.

## Narrative and SEO

The page establishes Aspire Commercial as a Houston commercial real estate brokerage, advisory and property-management company serving tenants, owners, investors and developers. Atlas provides property context; human advisors provide interpretation, representation and execution.

The only H1 is **Houston Commercial Real Estate, Made Clear.** The separate product line remains **Explore Houston. Find your next move.**

| Order | Section | H2 |
| --- | --- | --- |
| 1 | Atlas company hero | H1 above |
| 2 | Current Opportunities | Commercial Real Estate Opportunities Across Houston |
| 3 | Client Objectives | Commercial Real Estate Services Built Around Your Objective |
| 4 | Human Expertise | Local Expertise. Better Real Estate Decisions. |
| 5 | Expertise / Services | Commercial Real Estate Expertise Across the Property Lifecycle |
| 6 | How Aspire Works | A Clearer Way Through Commercial Real Estate |
| 7 | Atlas explanation | A Better View of Houston Commercial Real Estate |
| 8 | Property Management | Commercial Property Management That Goes Beyond Operations |
| 9 | Insights | Insights From Houston Commercial Real Estate |
| 10 | Final CTA | What’s Your Next Real Estate Move? |

Subtopics use H3. Existing Atlas workflow headings remain contextual H2/H3/H4; they do not add another H1. The target search intent is Houston commercial real estate representation, leasing, investment and ownership advice. Visible company/service relationships and descriptive links carry that meaning, without keyword stuffing or invented credentials.

The native WordPress `document_title_parts` filter provides **Houston Commercial Real Estate | Aspire Commercial** on the front page. The canonical remains the site root; the page remains indexable. No SEO plugin or second metadata system was introduced.

No safe editable meta-description mechanism exists, so no new one was added. Recommended future description:

> Aspire Commercial helps tenants, owners, investors and developers navigate Houston commercial real estate, from leasing and investment sales to property management and advisory.

## Gutenberg ownership

`bin/setup-home-phase2.php` in the theme performs the explicit, Home-only one-time setup, saves the previous content in `_aspirecre_before_home_phase2` and uses WordPress revisions. It reuses the existing Home and preserves later editorial changes when run again. It does not run during web requests.

The nine `patterns/phase2-*.php` files are reusable native Group, Heading, Paragraph, Image, Button and Query block patterns. In **Pages → Home → Edit**, named top-level sections make List View legible. All marketing text, native images and links are editable. The inserted patterns are ordinary page content, not live PHP templates; future pattern edits do not overwrite Home.

Existing first-class blocks are reused:

- `aspire/atlas`: opt-in `corporateHero` presentation; company heading/support, product language and advisor/continuation labels are block attributes. Gutenberg uses a static preview, without WebGL, marked “ASPIRE ATLAS HERO”, map enabled, four client intents and Brief enabled.
- `aspire-core/featured-properties`: count 3, `presentation: editorial`. Published available properties, featured first; real thumbnails, clean titles, all transaction terms, locality and up to two spatial metrics. Existing classic presentation remains the default. The shared metric fallback now honors the existing public price-suppression option.
- `aspire-core/team-grid`: count 3, editorial, `hideWhenEmpty: true`. No public placeholder cards; editors receive an explanatory empty state. Real published Team records remain managed in their CPT.
- Native `core/query`: up to three published posts. Empty inventory produces no invented Insights cards or dates.

Current opportunities are FM 1093, Presidio and Clay Road through the existing query, not hardcoded IDs. Sugarwell and Sienna remain Draft. No people or articles were created.

## Corporate design foundation

`assets/corporate.css` owns reusable, scoped `--corp-*` tokens for ink, muted copy, accent, line, surfaces, gutters, section spacing and type. White and restrained warm/green-gray surfaces, DM Sans body/control text and Source Serif editorial headings connect architecture and advisory content. Numbered objective rows, a compact two-column service index, process steps and real property photography avoid a page of identical feature cards.

Atlas retains its dark spatial setting with graphite/deep green-gray cartography, quieter roads and markers, a light company introduction and visible advisor path. `atlas/src/corporate-style.js` adapts a clone of the shared map style and applies marker paints only to this Atlas instance. Directory and Property map sources/build outputs retain their original bytes. The single Atlas application stays at the top; its lower explanation returns to `#aspire-atlas`.

The corporate footer retains the existing brand, approved phone/address and legal/TREC labels. Homepage navigation uses real published routes when present and useful Home anchors or the approved phone otherwise. Non-home footer content remains compatible with the existing baseline.

## Destinations and honest content gaps

Properties is the existing `/properties/` Page. Services currently lead to the service index and the six meaningful Home service sections. Future intended destinations are:

- `/services/tenant-representation/`
- `/services/landlord-representation/`
- `/services/investment-sales/`
- `/services/investor-developer-services/`
- `/services/property-management/`
- `/services/cre-consulting/`

About and Team currently lead to Human Expertise; Insights leads to its editorial section; contact uses `tel:+17139332001`. No full service/company pages or placeholder shells were created. Dedicated Team, Insights and Property Management page CTAs are offered by the patterns only when the corresponding published destination exists. Until then management and Insights provide a direct advisor path. When those pages are built, update the ordinary links already saved in Home; navigation helpers will resolve the new pages automatically.

There are no published Team records or Insights posts. The human section therefore uses company/advisory text and the existing Houston skyline, not fabricated profiles. Existing imagery quality is retained, including the small skyline and source property watermark. No email address or legal destination was invented; unconfigured legal links remain a pre-existing content limitation.

The existing “Describe what you’re looking for…” prompt remains read-only. The working **Create My Real Estate Brief** button opens the established requirements workflow. This task adds no freeform parser or AI behavior.

## Responsive, accessibility and performance

The corporate hero uses a near-viewport desktop composition. On mobile it remains in document flow and reserves a visible continuation link, releasing the previous whole-page scroll lock only for `corporateHero`. The existing sheet states, drag handling, keyboard alternatives, reparented sticky actions and safe-area CSS remain intact. Below the hero, compact numbered pathways, editorial property images and service rows adapt to available width; no mobile CSS changes unrelated pages.

An Atlas-only camera-padding boundary keeps transient hidden-panel and scrolled-root measurements finite, nonnegative and within the map canvas. This fixes a MapLibre error exposed during responsive panel reparenting without changing normal camera composition. Fresh-browser Property Focus and Brief transitions between mobile and desktop now complete with the map ready and no console warnings/errors.

QA covers 1440, 1280, 1024, 768, 390, 375 and 320px: single H1, section order, no horizontal overflow, real image loading, menu-to-section navigation, sheet keyboard/drag controls, Find Space, Property Focus actions, Brief questions/review/matching/contact and visible sticky actions. Touch controls retain approximately 44px minimums. This is browser viewport testing, not a physical-device safe-area/browser-chrome certification. Existing reduced-motion handling is preserved; no new motion is required.

All five below-fold photos use local WordPress responsive images and lazy loading. A narrowly scoped native Image render filter supplies appropriate half-column `sizes` for the two corporate photographs without changing Gutenberg serialization. No new frontend JavaScript was added below Atlas, no new framework, video, map provider or PMTiles download.

Gutenberg rendered correctly in Chrome with editable native content and the static Atlas preview; the Codex in-app browser showed a blank blob iframe canvas, while its List View still loaded. This was isolated to that browser surface. No block-recovery warnings appeared in the working Chrome editor.

## Validation and frozen-system safety

All relevant Core, block, theme, Atlas, enquiry, Directory, Property Detail/presenter/intelligence/workspace, JavaScript and offline migration suites pass. Previously stale homepage tests now validate this composition and isolate fixtures from real inventory. Build, PHP/JavaScript lint and `git diff --check` pass. Existing duplicate `WP_DEBUG` configuration warnings remain; Docker/wp-config were not changed.

SQL snapshots match exactly before/after for all **8 Property rows** (four published, Sugarwell/Sienna Draft, two pre-existing auto-drafts), Property metadata/taxonomy/relationships, **55 attachments** and **5 enquiries**. The 96 protected file hashes also match, including migration V1–V4, actual PMTiles, Docker, Directory and Property systems. No import or media download ran. Property Detail and Property Workspace remain **functional / provisional design — revisit later**.

Local ignored QA evidence lives in `var/homepage-phase2-qa/`. Scope ends here; future corporate pages, Property redesign and further migration remain separate tasks.
