# Six-property pilot — completed 2026-09-12

The dry run passed automated identity, allowlist, taxonomy, coordinate and media
checks before applying. It planned four enrichments, two drafts, 52 public media
asset URLs and five public brochures. The sixth brochure (FM 1093) was explicitly
withheld following the user's decision because it visibly contains legacy pricing.

## Actual records and media

Property count: **4 → 6**. Attachment count: **7 → 55**, **48 created**.
No Team Members created (0 before/after), no broker assignments, no new taxonomy
terms. The nine property/transaction taxonomy rows remain identical to the
pre-pilot snapshot. Existing publication statuses remain unchanged.

| Property | Post ID | Status | Featured ID | Gallery count | Brochure ID |
|---|---:|---|---:|---:|---:|
| Atascocita Retail Center | 119 | Published | 115 | 4 | 391 |
| 16840 Clay Road | 120 | Published | 116 | 4 | 372 |
| 14602 Presidio Square Boulevard | 121 | Published | 117 | 7 | 367 |
| 21617 FM 1093 | 122 | Published | 118 | 13 | Unset |
| Sugarwell Plaza | 351 | Draft | 358 | 6 | 359 |
| Sienna Park Office Condos | 392 | Draft | 393 | 6 | 400 |

All galleries preserve V4 order, excluding the featured attachment and duplicate
asset identities. Media lookup uses normalized source URL/aliases before download
and SHA-256 before attachment creation. Six representative local image URLs and
all five public PDFs returned HTTP 200; PDF content was parsed successfully. A
local Sienna gallery image was also visually checked in the browser.

## Preserved and filled fields

The four existing properties retained all original titles, slugs, post bodies,
publication/listing statuses, featured flags/images, coordinates, transaction/type
terms, prices, metrics, highlights and suites. No existing scalar fields were
filled; additions were galleries, allowed brochures, protected provenance and
non-conflicting intelligence.

- Clay: added construction, clear height, generic grade-level loading, loading
  configuration and access facts. Withheld suite-specific office/warehouse SF and
  the one-door count as current inventory. Existing coordinates remain authoritative;
  Census comparison remains REVIEW in provenance.
- Atascocita: added traffic, signalized access, tenant/retailer and demographic facts.
  Existing Suite F and its rate/NNN evidence are unchanged.
- Presidio: added building class/size intelligence; withheld the differently
  qualified source parking ratio. Existing curated parking ratio remains unchanged.
- FM 1093: added acreage/corridor intelligence and safe gallery images. Stored price
  and public suppression are unchanged. Original brochure stays at
  `var/migration/aspire-properties/pilot/media/61f74db495b5a6e7874f9905.pdf`, outside
  the webroot, with private filesystem permissions. No public brochure attachment.
- Sugarwell: added V4 address, available/maximum contiguous SF, accepted Census
  coordinates, clean source highlights, four normalized suites, retail intelligence
  and media. Retail + For Lease. Missing optional fields and suite/listing statuses
  remain unconfirmed. Native Save Draft was tested successfully.
- Sienna: added V4 address, accepted Census coordinates, clean source highlights,
  availability/parking intelligence and media. Office Condo + **For Sale and For
  Lease**, stored as two taxonomy terms. No invented suites or metrics. Draft and
  REVIEW_REQUIRED, with an explicit featured-image review flag.

Atlas's scalar transaction representation remains a known publication/schema gap.
It was deliberately left unchanged. Neither new draft appears in Atlas, public
property search or the property sitemap.

## Storage and admin

Protected `_aspire_property_intelligence` contains schema version 1, property type,
field-keyed V4 decision facts and known/unknown/conflicting statuses. It excludes
raw V1/V2 evidence and generated prose. Type-specific native controls cover Retail,
Office, Office Condo, Industrial/Flex and Land. Unknown fields are collapsible.
Sanitizers and nonce/capability/autosave guards protect edits; unchanged forms
preserve structured source evidence.

Protected `_aspire_migration` contains migration ID/schema, source URL/date,
V4 SHA/reference, import timestamp, review state/warnings, image review flag,
coordinate provenance/evidence, brochure URL/SHA, media references, unassigned
contact candidates and V4 review evidence. Attachment provenance uses protected
source URL, aliases, SHA and property/migration references. These are not exposed
through public REST.

Native PROPERTY INTELLIGENCE and SOURCE & MIGRATION panels provide readable fields
and review summaries, with no raw JSON. Existing Media controls now show local
thumbnails and clickable URLs. Sugarwell, Sienna and Clay admin screens were
inspected; Sienna's two checked transaction terms and review flag were confirmed.
No relevant browser/admin warnings or errors were reported. Clay's public dossier
retains its curated metrics and now uses its real local gallery and brochure.

## Idempotency and rollback

The second apply produced an **identical direct SQL snapshot** to the first apply:
six properties, 55 attachments, unchanged property/meta/taxonomy fingerprint, no
additional suites, gallery IDs or provenance. A later native Sugarwell draft save
was separately validated; the final dry run still requires **zero media downloads**.

Private evidence: `var/migration/aspire-properties/pilot/` contains first/second-run
reports, before/after snapshots, final validation and media HTTP validation.

Pre-write full database backup: `var/migration/aspire-properties/pilot/backup.sql`.
Its SHA is verified against `backup.sha256`. `uploads-before.json` and
`uploads-created.json` identify original versus 435 added upload files, including
WordPress-generated image sizes. The importer maintains this inventory even if an
apply fails partway through.

Exact rollback commands and precautions are in `tools/migration/PILOT.md`: restore
the full pre-pilot SQL dump after protecting any newer work, then remove only the
listed newly created uploads after confirming no later references. Rollback has
not been executed.

## Validation

Passed:

- Core integration: 84 assertions.
- Atlas PHP: 61; enquiry: 50; Directory PHP: 36.
- Property Detail PHP: 73 (updated its obsolete empty-media assertion to verify
  the actual local gallery/PDF fields; no frontend implementation changed).
- Intelligence sanitizer/admin tests: 10.
- Pilot read-only validation: 230, including merge precedence, media identity and
  gallery order, protected metadata, suites, both Sienna terms, FM suppression,
  existing coordinate/price/taxonomy preservation and draft publication safeguards.
- Python migration tests: 58, including three new offline pilot tests.
- JavaScript: Atlas 15, Directory 8, Property Detail 4.
- Existing block/Atlas/Directory build; PHP lint; Python compilation; git diff check.
- All 12 pre-existing migration documentation/output files are byte-identical to HEAD.

Three older additional homepage/block suites still fail stale assumptions:

- Core `tests/blocks.php`: expects an empty featured-property preview despite the
  four published/featured prototype records that already existed before this task.
- Theme `tests/homepage.php`: expects an empty public property state despite that
  pre-existing inventory.
- Theme `tests/blocks.php`: expects the earlier seven-section marketing homepage;
  the existing Home uses the subsequently built Atlas structure. Its published,
  static-front-page and Gutenberg checks pass before this assertion fails.

These suites were run and their failures retained, not reported as passes. Their
homepage assertions and production homepage implementation were left unchanged.
CLI WordPress loads also emit the existing duplicate `WP_DEBUG` definition warning
from wp-config/Docker environment configuration; no Docker/configuration change
was made to suppress it. Logs are in the private pilot `tests/` directory.

No other legacy properties were imported. No Atlas, Directory or Property Detail
frontend runtime, Docker, PMTiles, geocoder, SEO or public contact logic was changed.
