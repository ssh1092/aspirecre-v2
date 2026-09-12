# Homepage presentation

## Presentation and publishing architecture

The homepage moves from Houston geography into a client objective, advisory work, physical properties, real people and a conversation. Atlas remains the hero, followed by six compositions: Client Journey, Current Opportunities, Property Types, Team, In the Field and the final human CTA. Large property photography, editorial serif statements and restrained sans-serif metadata provide scale. White, warm white and architectural gray carry the page; Aspire green identifies actions and geographic markers.

Published **Home, Page 74**, remains the static front page. `front-page.php` renders `the_content()` within the existing theme header/footer. The `presentation-*.php` patterns create native Group, Heading, Paragraph, Image and link content in the page; they are authoring templates, not a second hard-coded frontend. Journey narratives, artifact prompts, milestones, headings and contact copy remain editable through Pages → Home → Edit. The explicit `bin/setup-home-presentation.php` theme script saves the previous content/revision and skips an already-upgraded Home, preserving subsequent edits.

Existing Atlas and server-rendered Aspire Core blocks provide dynamic systems. The opportunities block uses `presentation: portfolio`, Team uses `presentation: portraits`, and property types and `aspire-core/field-feed` render their respective real-data rails. Editor previews are static and never initialize WebGL.

## Journey, artifacts and motion

All four approved six-stage journeys remain intact. Each stage provides the client objective, Aspire's contribution, the resulting understanding and technology's supporting role. The shared visual scene changes composition rather than creating another application dashboard:

- **Define:** an editable Real Estate Brief with Use, Size, Area, Timing and Priorities prompts.
- **Explore:** the same brief reduces beside the Atlas map and a real property annotation.
- **Compare:** two genuine published properties become photographic alternatives with selective facts and explicit unknowns.
- **Validate:** the property photograph carries interactive diligence questions.
- **Negotiate:** typographic commercial topics reveal advisor-oriented explanations without invented terms or values.
- **Execute:** the composition resolves into selected property, commercial terms, LOI, documentation and handoff/occupancy milestones, followed by an advisor action.

Owner, investor and management journeys reuse the scene with an Asset Assessment, Investment Brief or Asset Review. Their prompts, topics and milestones reflect the relevant work; management execution concerns operations rather than pretending to negotiate. These are illustrative discussion prompts, not completed client documents. Comparison explicitly says suitability has not been determined.

CSS coordinates document movement, image crops, clip-path reveals, scale and opacity. Stage selection drives the choreography; normal browser scrolling remains intact. The small presentation bridge maps Atlas Find Space → tenant, Buy/Invest → investor, Lease/Sell → owner and Manage an Asset → management. It shares only the selected intent, without changing filters, saved Brief data or enquiry behavior. Explicit intent also adjusts the final advisor label and prioritizes relevant published opportunities while retaining the other inventory.

## Cartography and continuity

The corporate Atlas presentation replaces green-on-black with warm neutral land, muted pale-blue water, natural parks, gray building footprints and charcoal labels. Roads distinguish local streets, arterials and highways through width and restrained casings. Houston locality labeling is emphasized; neighborhood and street labels become useful at closer zooms. Dark-green markers use a clear white outline and modest selected/hover enlargement, without glow or pulsing.

The adapter uses the inspected existing Protomaps v4 `earth`, `landuse`, `water`, `buildings`, `roads` and `places` source layers. Local z9, z12 and z15 samples informed road kinds and detail. MapLibre, the bundled PMTiles archive, local glyphs and OSM/Protomaps attribution remain. The reusable style adapter is opt-in: Directory and Property Detail retain their existing map appearance.

Journey geography comes from a one-time image capture during a render of the existing Atlas canvas, with projected real-property annotations. It creates no second map instance and leaves `preserveDrawingBuffer` disabled. If capture is unavailable, the existing static scene and Atlas link remain usable; the preview is not another live search map.

## Properties, people and activity

Current Opportunities uses large photographic panels with type, transaction, name, locality and one useful metric from the existing property presenter. Only published Property records appear; drafts and protected pricing stay excluded. Property-type territories use actual taxonomy counts and real Directory filter links. A type with no published image does not receive invented inventory or photography.

The Team rail uses native Team titles, roles, short bios, portraits and profile links. Four verified live-site people were added: Brandon Avedikian, Bradley Segreto, D.A. Smith and Alex Bibb. The explicit bounded importer retains source provenance, checks image hashes and skips existing source IDs. These are four new WebP portrait attachments; existing media and Property relationships were not rewritten. The remaining live roster was not imported.

In the Field supports official Instagram media through Aspire Core's server-side adapter. **No Instagram credentials are configured locally**, so the current rail truthfully says “Curated photography from Aspire’s property collection”; it contains genuine published-property images, no invented posts or dates. The final market-updates link leads to the verified existing subscription form on Aspire's current website, since this prototype has no newsletter backend.

Production Instagram configuration requires `ASPIRE_INSTAGRAM_ACCOUNT_ID`, `ASPIRE_INSTAGRAM_ACCESS_TOKEN` and an explicitly supported `ASPIRE_INSTAGRAM_API_VERSION`, supplied as protected environment values or PHP constants. A professional Business/Creator account and official Instagram Login authorization with `instagram_business_basic` are required. Tokens stay server-side. Twice-daily scheduled refresh, six-hour cache, seven-day last-good fallback and an administrator-only nonce-protected refresh action avoid frontend API dependency. Videos use real posters and external links. Token rotation, cron execution and an authorized live connection remain deployment work; CDN expiry is handled gracefully but still requires healthy refresh. See [Instagram integration](instagram-integration.md) for the exact operational contract.

## SEO, accessibility and responsive behavior

Headings, all journey narratives, service links, property content and Team content render on the server. The single H1 is **Houston Commercial Real Estate, Made Clear.** Six principal H2s express the section subjects, with H3s for stages, services, property types and records. JavaScript controls presentation only. Without it, the journeys remain readable; enhanced users can also choose “Read complete journey.” Inactive scene controls become inert instead of leaving invisible focus targets.

A current HTTP check returns `/` with status 200, title **Houston Commercial Real Estate | Aspire Commercial**, canonical `http://localhost:8080/`, robots `max-image-preview:large`, and no `noindex` or `X-Robots-Tag`. This is a local prototype canonical, not a production-domain deployment or evidence of search indexing. The existing public-site setting remains enabled. No meta-description mechanism was added. Published destinations are preferred; unavailable service pages resolve to meaningful homepage anchors rather than empty pages.

Mobile retains visible marketing sections, large imagery and horizontal touch rails, while Atlas keeps its existing sheet system. Journey arrows, numbered stages, keyboard navigation, visible focus, live position announcements and rail previous/next controls provide non-drag alternatives. Horizontal gestures preserve vertical scrolling. Reduced-motion preferences disable presentation transitions and smooth scrolling while retaining the full content and controls.

Homepage-only styles and deferred framework-free JavaScript limit scope. Responsive WordPress images, below-fold lazy loading, asynchronous decoding and poster-only social video constrain media cost. ResizeObserver handles layout measurements; requestAnimationFrame only throttles rail position updates. There is no continuous animation loop or additional animation framework.

Browser QA covered 1440, 1280, 1024, 768, 430, 390, 375 and 320 CSS pixels with no document-level horizontal overflow. Desktop inspection covered all six tenant stages and every homepage composition; mobile inspection covered the full page, journey navigation, photographic rails, menu, property-focus actions and Brief progression through Contact without submitting an enquiry. Property-focus actions fit at 320, 375 and 390 pixels, stacking at 320. Keyboard stage navigation, question/topic reveals, rail controls and accessible Atlas sheet controls worked. The native WordPress editor showed directly editable headings and paragraphs and static dynamic-block previews without recovery warnings. Reduced-motion mode was explicitly enabled and tested; disabling JavaScript retained all four readable journeys and real property/Team links. Both browser overrides were restored. No relevant frontend console errors were observed. These are functional and visual observations, not a Lighthouse performance score or an exhaustive assistive-technology audit.

Useful browser captures are saved in the ignored `var/homepage-presentation-qa/` directory: `desktop-1440-atlas-hero.png`, `desktop-1440-complete.png`, `mobile-390-complete.png` and `mobile-390-compare-reduced-motion.png`. Full-page captures use a device-pixel ratio of one to avoid browser image-size truncation. The final Git report is `final-git-report.txt`; screenshots and audit evidence are not staged or committed.

## Automated validation

| Suite | Result |
| --- | --- |
| Native dynamic blocks and real-data rails | 82 checks pass |
| Gutenberg homepage structure | 34 checks pass |
| Isolated homepage/data fixtures | 24 checks pass |
| Rendered homepage, 24 journey stages, SEO and links | 335 checks pass |
| Core integration | 84 assertions pass |
| Atlas PHP | 71 checks pass |
| Atlas Enquiry | 50 assertions pass |
| Directory PHP | 36 checks pass |
| Property Detail | 78 checks pass |
| Property presenter | 127 assertions pass |
| Protected Intelligence | 10 assertions pass |
| Property Workspace | 67 assertions pass |
| Instagram integration | 35 checks pass; test options restored |
| JavaScript | 37 tests pass: Atlas 22, Directory 8, property tabs/map/gallery 7 |
| Migration | 58 offline tests pass |
| Build and syntax | Native blocks, Atlas and Directory build; 31 PHP files lint; presentation JS syntax and git diff whitespace check pass |

PHP suites ran sequentially so temporary records could not interfere with each other. Fixtures were rolled back or removed. Read-only rendering checks reject external API calls, verify the exact single H1, six principal H2s, all four approved six-stage journeys, their client/advisor/outcome/technology copy, server-rendered real property/Team content, valid service destinations and no ordinary mobile accordions. Dynamic blocks retain static editor previews without WebGL. The pre-existing duplicate `WP_DEBUG` configuration warning remains; Docker/configuration were not changed.

SQL-only comparisons confirm all Property rows, metadata, taxonomy and relationships remain byte-for-byte equivalent to the task baseline. The eight Property rows comprise four Published, two Draft and two pre-existing auto-drafts. Sugarwell/Sienna remain Draft. All five existing enquiries and their metadata remain unchanged. The original 55 attachment rows/meta remain unchanged; the only four added attachments are the verified Team portraits, bringing the count to 59. Exactly four verified Team records exist and no fixture records remain. All 96 frozen file hashes in the task baseline match.

Ignored evidence lives in `var/homepage-presentation-qa/`: `php-tests.json`, `js-tests.json`, `migration-tests.json`, `safety.json` and individual logs. Re-run the read-only safety capture with `python3 var/homepage-presentation-qa/safety-compare.py --capture`.
