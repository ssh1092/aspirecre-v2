# Aspire legacy property audit (dry run only)

These tools discover the five fixed archives, require exactly 87 unique property URLs, cache source HTML, and generate review artifacts. There is no importer or WordPress write path. V1 performs no image or PDF downloads; the V2 enrichment below adds an ignored PDF cache.

Run from the repository root:

```sh
python3 -m venv var/migration/venv
var/migration/venv/bin/pip install -r tools/migration/requirements.txt
mkdir -p var/migration/aspire-properties
docker compose exec -T wordpress php < tools/migration/snapshot.php > var/migration/aspire-properties/before.json
python3 tools/migration/crawl.py
docker compose exec -T wordpress php < tools/migration/snapshot.php > var/migration/aspire-properties/after.json
var/migration/venv/bin/python tools/migration/audit.py
var/migration/venv/bin/python -m unittest discover -s tools/migration/tests -v
```

`crawl.py` always validates discovery before requesting properties. `--refresh` explicitly refetches archives and properties; otherwise all existing cache entries are reused. Fetches are sequential with a 0.75-second pause before each request, verified HTTPS through system curl, bounded timeouts and retries. DNS/connection/TLS failures stop the crawl. No external scraping services or browser automation are involved.

`snapshot.php` connects directly using the existing container's database environment, without bootstrapping WordPress. It starts a read-only, consistent-snapshot transaction and executes only SELECT/SHOW queries, then rolls back. It captures all Property post rows, metadata, taxonomy term counts, relationships and attachment count. No credentials are printed or saved. `audit.py` refuses to overwrite review artifacts if the snapshots differ, the 87-URL gate is unsatisfied, or a source page cannot be parsed.

Generated review artifacts:

- `docs/migration/aspire-properties-manifest.json` — complete nullable fields, evidence, warnings, source authors/dates, media candidates, conservative local matches and safety summary.
- `docs/migration/aspire-properties-review.csv` — 87 rows with blank review-decision/notes columns. Potential spreadsheet formula prefixes are escaped.
- `docs/migration/aspire-properties-summary.md` — coverage, warnings, matches and recommendations.

All records are `needs_review`. No listing status is inferred. Suite availability is retained only when explicitly stated. Local match IDs are suggestions for review, not update instructions. Sources may contain prices that are intentionally suppressed in V2; the audit retains source facts without changing existing policy.

Parser decisions:

- Scope extraction to the Squarespace property article/body, excluding outer navigation, pagination, newsletter and footer.
- Preserve safe slugs; normalize embedded-slash paths and flag required redirects. Detect proposed-slug collisions.
- Prefer exact legacy URL or slug matches; use normalized full address/title only when unambiguous.
- Never convert unlabelled SF into building area, or individual suite area into total availability. Conflicting values and size ranges stay unresolved.
- Keep complex availability blocks and separate suite candidates. Do not invent suite identifiers or aggregate phase-level rates into a single rate range.
- Separate NNN from base rent; keep exact display text when normalization is uncertain. Do not infer sale/lease solely from bare dollar-per-SF values.
- Deduplicate property media by normalized underlying URL (query/transform parameters removed), preserving gallery order and marking any featured-image overlap.

Tests use eight reduced JSON fixtures derived from the cached real pages (Clay, Atascocita, Broadway, Kingsley Ridge, Woodson's Centre, FM 1093, Presidio, and the embedded-slash Pearland Parkway URL). They contain selected text/image/link facts, not full source HTML. `make_fixtures.py` explicitly regenerates those fixtures from an existing crawl; it makes no requests. Additional tests cover exclusions, ambiguous matches, address uncertainty, field conflicts and manifest invariants.

The cache, local SQL snapshots, virtual environment and Python bytecode are Git-ignored. Do not commit cached source HTML. This task ends at human review; run no import.


## V2 brochure and source-metadata enrichment

Preserve the three V1 outputs. With the existing HTML cache and V1 manifest present, run:

```sh
var/migration/venv/bin/pip install -r tools/migration/requirements.txt
docker compose exec -T wordpress php < tools/migration/snapshot.php > var/migration/aspire-properties/enrichment-before.json
var/migration/venv/bin/python tools/migration/brochures.py
docker compose exec -T wordpress php < tools/migration/snapshot.php > var/migration/aspire-properties/enrichment-after.json
var/migration/venv/bin/python tools/migration/enrich.py
var/migration/venv/bin/python -m unittest discover -s tools/migration/tests -v
```

Do not rerun `audit.py` to enrich V1. `enrich.py` writes only the V2 manifest, V2 review CSV, and enrichment summary. It checks the read-only snapshots, records V1 file hashes, and preserves every V1 record field. Human review columns remain blank.

`brochures.py` downloads sequentially using verified HTTPS, bounded timeouts/retries and a 0.75-second delay before uncached requests. It validates PDF signatures and readability using pinned pypdf. PDFs, hashes, status metadata and extracted page text stay under ignored `var/migration/aspire-properties/brochures/`. Subsequent runs reuse PDFs and extracted text matching their SHA-256; `--refresh` explicitly fetches again. Failures are recorded per URL and do not stop other properties. Image-only PDFs remain text-unavailable; no OCR is performed.

Candidates retain page/brochure evidence separately. Conflicts leave numeric and transaction candidates null, with both values available for review. Distinct suites and phase contexts remain separate. Legal brokerage disclosures and generic company narratives are excluded; contacts require property-associated contact headings. An apparent brochure subject mismatch quarantines brochure candidates. FM 1093's existing pricing-review warning remains intact.

Coordinates and primary-image evidence come from cached HTML only. Repeated coordinates across different addresses are retained as evidence but withheld as usable candidates. Image confidence requires matching legitimate existing property media; gallery ordering is preserved and no images are downloaded. Type-specific completeness scores count known fields divided by the documented field catalog and are internal QA only.

Nine additional reduced evidence fixtures cover Clay, Atascocita, Broadway, Woodson's, Greatwood, Prairie, Monroe, Hiram Clarke land and FM 1093. `make_enrichment_fixtures.py` regenerates them offline from the cache. Cache tests make no network requests. No WordPress write API, importer, frontend changes, external geocoder or media-library operation is involved.

## V3 import-candidate normalization (still audit only)

V3 reads the committed V2 evidence and cached HTML. It makes no network requests,
imports nothing and writes only the three new V3 review outputs. Do not rerun V1
or V2 generators as part of normalization.

```sh
docker compose exec -T wordpress php < tools/migration/snapshot.php > var/migration/aspire-properties/normalization-before.json
docker compose exec -T wordpress php < tools/migration/snapshot.php > var/migration/aspire-properties/normalization-after.json
var/migration/venv/bin/python tools/migration/normalize.py
var/migration/venv/bin/python -m unittest discover -s tools/migration/tests -v
```

Each V3 record has a separate `import_candidate` and an immutable V2 file hash /
record JSON-pointer reference. Evidence pointers beneath that record identify
original quotes. Additional parcel block evidence records the cached HTML hash
and block index. All six V1/V2 artifacts are hash-checked and never rewritten.

The normalizer documents positive gates for every decision-field matcher, fixes
combined built/renovated years, rejects malformed prices, separates suite and
parcel pricing, and groups road-specific traffic. Excluded V2 matches remain in
`parser_matches_removed`; exclusion counts include conservative omissions and
repeated source matches, not just proven false positives. Type changes retain
supporting evidence. Tier B transaction derivations require human approval.

Multiple transaction intents are semantic arrays. Sale plus ground lease never
becomes ordinary sale-or-lease. WordPress can store multiple terms, but Atlas's
current first-term/scalar model needs a separate future compatibility decision.
No taxonomy or frontend changes are made here.

`READY_WITH_NULLS` permits eventual draft migration with missing optional data;
`REVIEW_REQUIRED` covers derived intents, image/type decisions and substantive
conflicts. Missing critical addresses and wrong-subject brochures are `BLOCKED`.
No classification authorizes publication. Contact candidates are not approved
for display and team order does not select a primary. The CSV leaves both human
review columns blank. Tests exercise the ten requested real-property cases plus
immutability, coordinates, pricing policy, transaction semantics and CSV safety.
