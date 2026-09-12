# Desktop Property Intelligence Dossier

Implemented in the existing WordPress theme and Aspire Core. No property records,
admin fields, migration metadata, publication statuses, media attachments or terms
were changed. The final direct SQL snapshot exactly matches the initial snapshot:
**six properties and 55 attachments**. V1–V4 and all existing migration reports
remain byte-for-byte unchanged.

## Experience

The normal Aspire header leads into a 68/32 photography mosaic, property identity,
type/transaction context, a flexible facts ribbon and restrained phone/Brief/document
actions. Conditional sticky navigation follows the available sections.

The body is Aspire Lens, availability/offering, Property Details and documents,
Questions Worth Confirming, Location Context, substantial photography and the final
phone/Brief action. Real published advisor relationships remain conditional.
Additional source highlights remain available in an expandable reference section.
One information disclaimer appears beneath the details.

The warm canvas, serif headings, dark Lens, fine rules and teal actions are scoped
to the Property page. There are no score badges, dashboard cards or new UI libraries.

## Architecture and rules

- `Aspire_Property_Dossier` in Core now owns the complete read-only page model.
  It reads this property directly; it no longer scans the Atlas collection.
- `Aspire_Property_Lens` in Core owns type-specific rules, labels, formatting,
  priorities, decision groups, fixed generic implications and confirmation questions.
- The theme owns presentation and eight small partials: media, Lens, availability,
  details, questions, location, gallery and advisors. The old theme helper is only
  an asset loader and compatibility alias; there are no competing page templates.

Industrial/Flex organizes Space, Operations, Access and Economics. Retail uses
Space & availability, Access & visibility, Trade area and Retail infrastructure.
Office/Office Condo uses Building, Access & parking, Operations and Economics.
Land uses Site, Development, Access and Offering. Empty groups are omitted.

Only known, non-conflicting, non-review-only decision values enter the public
projection. Existing curated metrics override source intelligence comparisons.
Up to four priority facts become “What stands out”; remaining facts form editorial
grouped rows. Context qualifiers remain attached to their values. Implications are
fixed decision guidance, never business suitability promises or generated claims.
No generated text is stored in WordPress and no AI/API dependency was added.

Questions are deterministic and type-specific, capped at six. Answered fields are
omitted. Checks distinguish an actual NNN/CAM amount from a base rate that merely
mentions NNN. Uncertain office/warehouse allocations remain questions, not facts.

The public projection does not include evidence objects, raw migration data,
contact candidates, source URLs, private review labels or the Sienna image flag.
Protected metadata remains absent from public REST.

`availability_contexts` and `offering_contexts` have a presentation contract:
a context has a label, `status: known`, and independently labelled known facts.
Contexts remain separate; review contexts and suppressed economics are omitted.
The pilot does not populate these contexts, and no migration evidence is repurposed
as public context. Future reviewed storage/adapters can supply them without
flattening distinct parcels or programs. The existing intelligence admin sanitizer
is intentionally unchanged. An empty `aspire_property_dossier_after_lens` action
provides a future insertion point; Brief matching is not implemented.

## Media, performance and accessibility

The mosaic adapts to one, two or three-plus usable local images without empty cells.
The viewer includes the featured photograph once plus unique gallery attachments.
Clay therefore shows **five total photos**, with its unchanged **four-image lower
gallery**. Native links still open images if JavaScript/dialog support is unavailable.

The vanilla JavaScript native-dialog viewer supports previous/next, wrapping arrow
keys, Escape, image count, meaningful fallback alt text, focus trapping/restoration
and background scroll lock. Images use responsive WordPress sources. Only the
first mosaic image is eager/high priority; secondary and lower images are lazy.
Full viewer sources are assigned when a photo is opened, not all fetched up front.
Reduced-motion preferences and visible keyboard focus are respected.

Brochure links appear near the primary actions and in Property Documents only when
a valid local PDF attachment exists. All five PDFs returned HTTP 200 with actual PDF
content. FM 1093 has no brochure action or document shell.

Location Context reuses the existing lazy MapLibre/PMTiles renderer, single-property
feature and address fallback. No Atlas application or portfolio REST request loads.
No new basemap, geocoder, frontend dependency or map data was introduced.

## Pilot QA

All six pages were visually inspected at **1440, 1280 and 1024 pixels**. The 18
checks recorded no horizontal overflow or broken loaded images. All six maps
initialized with a canvas, cleared loading status and no browser errors. Basic
768/390 fallback checks found no overflow; this is not the final mobile design.

| Property | Confirmed |
|---|---|
| Clay, 120 | Real mosaic/gallery/PDF; 14′ clear height; masonry; rear/grade-level loading; Highway 6; current 11,273 SF and $9.75–$11.50 rate retained |
| Sugarwell, 351 | Authenticated Draft preview; Retail traffic/turn-lane/demographics; four real suite rows; $24/SF Base remains in Suite 103 context; no invented suite area/status |
| Presidio, 121 | Office Lens; Class B; 42,716 SF; 160 spaces; curated 3.95 spaces per 1,000 SF; local PDF/gallery |
| Sienna, 392 | Authenticated Draft preview; Office Condo; FOR SALE · FOR LEASE; contextual 1,225 SF units/up to 7,350 SF; no public image-review flag |
| FM 1093, 122 | Sparse Land Lens and useful questions; existing acreage; no disputed price, brochure action or empty intelligence grid |
| Atascocita, 119 | Real media and Retail Lens; curated Suite F, 4,361 SF, $18 Base + NNN and $5.84/SF NNN retained |

Drafts remain excluded from the public directory, Atlas, anonymous permalinks,
search and sitemap. Public directory exclusion was checked in the browser and the
pilot validator. Both transaction terms render on Sienna independently of Atlas's
unchanged scalar transaction representation.

**Clay limitation:** the requested legacy 1,687 SF office / 2,087 SF warehouse split
is not published. Migration correctly withheld it because it conflicts with current
curated inventory. The dossier instead asks about the current split. No legacy
Suite 101 was invented or substituted. This follows the stronger requirement that
conflicting/review-only evidence must not be presented as fact.

## Tests and evidence

Passed:

- Core integration: 84 assertions; Atlas PHP: 61; enquiry: 50; Directory PHP: 36.
- Existing intelligence admin/sanitizer tests: 10.
- New read-only presenter/rendering tests: 127.
- Property Detail: 73 read-only checks; unchanged original pilot validator: 230.
- Migration Python: 58.
- JavaScript: Atlas 15, Directory 8, Dossier 6 (including new gallery keyboard/focus tests).
- Existing build; PHP lint; Python compilation; `git diff --check`.
- Exact before/after property/meta/taxonomy snapshots; immutable migration outputs.

The historical geocoding determinism test previously regenerated authoritative V4
files in place and included later reports as historical inputs. Its output was
restored byte-for-byte, and the test now regenerates into a temporary directory
using the original recorded inputs and copied cached responses. It passes without
writing authoritative audit files or making new geocoding requests.

The three known stale Core/theme homepage/block suites were not rewritten or used
as acceptance gates. Their earlier empty-inventory/seven-section assertions remain
unchanged. WordPress CLI still emits the pre-existing duplicate `WP_DEBUG` warning;
Docker/configuration was not altered. Browser checks reported no relevant errors.

Private QA logs and snapshot evidence are under
`var/migration/property-intelligence-qa/`, including `browser.json`, `before.json`,
`after.json`, suite logs and `pilot-validation.json`.

Stopped at desktop. Final mobile design, related opportunities, Atlas Brief
matching, SEO/schema, more imports and basemap expansion remain outside this task.
