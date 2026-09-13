# Homepage hero conversion and Atlas property previews

Implemented against `502b210` (homepage checkpoint). Work is limited to the corporate Atlas hero, its map previews and the connection to the existing requirement workflow.

## Hero and WordPress

Home remains published static Page **74**, rendered through `front-page.php` and `the_content()`. Its native Atlas block holds the company supporting sentence and advisor prompt. `bin/setup-home-hero-conversion.php` makes the one-time update, saves the previous content and a revision, and is idempotent. Every byte outside that block is preserved. The editor remains a static native block preview without WebGL.

The sole homepage H1 remains **Houston Commercial Real Estate, Made Clear.** The heading, company copy, four objective buttons, input label and both actions are server rendered. The primary action is **TELL US WHAT YOU NEED →**; **Prefer to talk first? Talk to an Aspire advisor →** remains secondary.

Each objective changes the question and placeholder of one editable input. Submission carries the selected goal and the visitor's words into the existing workflow, starting at property type. The existing `location.text` field carries the requirement, capped at its existing 240-character limit; hero-originated screens label it as a requirement / area. No type, size, price, location or suitability is inferred from the text. Submission of the hero itself creates no enquiry.

## Real property previews

Desktop cards are **208px wide**, with **117px-high photography**, a proper property name, type / transaction and one safe metric. Selecting a card reveals its address and real property permalink. Focus and hover emphasize the card and corresponding location. Projected coordinates remain fixed; leader lines connect displaced cards to the true map points. Placement avoids card collisions, the hero, navigation and map controls. Properties without a valid image or a readable card position retain their map point and keyboard selector.

Find Space prioritizes published lease listings; Buy or Invest prioritizes sale listings. Other properties remain available. Owner and management objectives leave the map contextual. This is transaction-based presentation, not a suitability recommendation.

Featured images use a native WordPress medium preview and responsive candidates capped at 800px, with sizes matching the 208px desktop card and mobile width. Image requests begin only for visible preview cards; no galleries are fetched by the new preview component. No invented imagery or property records are used.

Below 768px, the complete conversion hierarchy precedes a **410px map** and one active card with **180px photography** (160px below 360px). Marker selection, swiping, previous / next buttons and arrow keys synchronize the active card and map. Mobile actions are at least 44px high; the primary button is 56px. Existing workflow sheets remain available after the hero handoff.

## Existing map evidence

The map style, provider and bundled PMTiles file are unchanged. Existing tooling read the actual `houston.pmtiles` header: bounds **[-95.95, 29.45, -95.05, 30.25]**, zooms **0–15**, SHA-256 `9b84021518f3a7bca2be37add84a8520d3a9c4f216f2c1555d21b91ca3c5dfb2`.

Metadata identifies building geometry at zooms 11–15. A read-only probe around downtown Houston found 9 building polygons in tile 12/962/1693 and 437 in tile 15/7703/13544. The existing neutral footprint layer becomes visible from zoom 12. No extrusion or second map source was added. The corporate camera uses the existing wider workflow navigation envelope so content padding does not crop the portfolio; this does **not** expand tile coverage.

## Verification

Browser captures were inspected for default, Find Space, Buy or Invest, hover / keyboard focus and selected desktop cards; mobile default, Find Space and multiple active property states. Captures were reviewed in the browser. A native PNG save was interrupted; no saved PNG deliverable is claimed.

| Width | Visible preview cards | Horizontal overflow | Card collisions |
|---:|---:|---|---|
| 1440 | 3 | None | None |
| 1280 | 3 | None | None |
| 1024 | 2 | None | None |
| 768 | 2 | None | None |
| 430 | 1 | None | None |
| 390 | 1 | None | None |
| 375 | 1 | None | None |
| 320 | 1 | None | None |

All four hero objectives preserved the entered need and selected the correct existing goal in browser tests. Keyboard intent and property selection, visible focus, real property navigation, mobile next / previous, menu Enter / Escape and a native pointer swipe passed. The swipe changed Atascocita to Clay Road and updated the selected map marker. No relevant browser console errors were observed.

Validation passed: **42 JavaScript tests**, all **13 existing PHP suites**, **35 new read-only hero/map assertions**, Atlas build, PHP lint for all six changed / new PHP files, JavaScript syntax and whitespace checks. Existing PHP suites cover native blocks, Gutenberg, homepage fixtures, homepage presentation, core integration, Atlas, enquiries, Directory, Dossier, property presenter, Intelligence, Workspace and Instagram. Three obsolete Atlas copy assertions and two homepage copy assertions now expect the approved conversion copy.

SQL snapshots confirm unchanged properties, property metadata, taxonomies / relationships, attachments, team, enquiries, static-page options and other pages. There are **4 published properties**, 2 drafts and 2 pre-existing auto-drafts; **59 attachments**, **4 team members**, and **5 enquiries** remain unchanged. Sugarwell Plaza and Sienna Park Office Condos remain drafts and are excluded from Atlas. FM 1093 exposes acreage (2.21 AC), with public prices still suppressed. Hash verification passed for **125 protected files**, including all other homepage sections and the Property, migration, Docker and Instagram surfaces.

Detailed local test logs and safety snapshots are under the ignored `var/hero-map-qa/` directory. No files were staged or committed.
