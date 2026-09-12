# Aspire legacy property audit (dry run only)

These tools discover the five fixed archives, require exactly 87 unique property URLs, cache source HTML, and generate review artifacts. There is no importer or WordPress write path. No image or PDF downloads are performed.

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
