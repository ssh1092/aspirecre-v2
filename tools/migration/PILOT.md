# Six-property local pilot

The allowlist is fixed: `aspire-040` (119), `aspire-015` (120),
`aspire-012` (121), `aspire-021` (122), `aspire-011` (Sugarwell draft),
and `aspire-081` (Sienna draft). No other V4 records can be selected.

Run from the repository root:

```sh
var/migration/venv/bin/python tools/migration/pilot.py --pilot --dry-run
var/migration/venv/bin/python tools/migration/pilot.py --pilot --apply
# Optional individual allowlisted migration ID:
var/migration/venv/bin/python tools/migration/pilot.py --property aspire-011 --dry-run
```

Default execution is dry-run. `--stage-only` stages real media and verifies the
backup without WordPress writes. There is intentionally no destructive automatic
media refresh. Provenance lookups reuse existing attachments before downloading;
byte-identical files also reuse attachment IDs. Unique matching primary/gallery
filenames within one property's source media are canonicalized to the gallery
asset. Gallery order follows V4, omitting the featured attachment and duplicates.

The local-only PHP runner validates the allowlist, identity, draft status,
existing taxonomy terms, accepted new coordinates and every staged media file
before creating posts. PDFs are opened with pypdf during staging, then checked
for actual MIME/signature; images require an actual image MIME and dimensions.
WordPress attachment APIs perform sideloading and image metadata generation.
No runtime HTTP import/geocoder endpoint is installed.

## Merge and storage

Existing properties keep their titles, slugs, publication/listing status, terms,
coordinates, prices, metrics, highlights, suites and featured images. Only safe
blank address components are considered for filling; inventory/price fields are
intentionally not filled on the existing four. Intelligence is additive, with
curated intelligence winning on reruns. Clay's suite-specific office/warehouse
split and one-door claim are not asserted as current building inventory.

New records are drafts, using safe V4 metrics, accepted Census coordinates,
clean source highlights and normalized suites. Unknown suite/listing statuses
remain empty, including after native-admin saves. Sienna gets both For Sale and
For Lease; Atlas's scalar handling is unchanged and remains a publication gap.

Protected `_aspire_property_intelligence` is a versioned object with property
type, field-keyed facts and known/unknown/conflicting status. The type-specific
catalog is `includes/intelligence-fields.json`. Values retain structured numbers,
traffic rows or source statements. Native fields display readable labels and
hide unknown fields in an expandable section. Unchanged admin forms preserve
structured source evidence. Nonce, capability, autosave and revision guards
protect saves; unknown/conflicting values are not stored as known facts.

Protected `_aspire_migration` holds migration ID/schema, legacy URL/date,
manifest hash/reference, initial timestamp, review state/warnings, image review,
coordinate evidence, brochure URL/hash, media references, contact candidates and
V4 review evidence. It is not exposed through REST. The Source & Migration box
shows a concise summary and unassigned contact candidates. No team members or
broker assignments are created. Attachment provenance is protected too.

## FM 1093 exception approved by the user

The original PDF visibly lists a legacy price. It stays outside the webroot at:

`var/migration/aspire-properties/pilot/media/61f74db495b5a6e7874f9905.pdf`

It is not sideloaded into the public Media Library and the public brochure field
remains unset. Source URL/SHA and the private path remain in protected migration
metadata. Its gallery was visually checked and contains no price annotations.
The stored price and existing public suppression option remain unchanged.

## Backup and rollback

The first application creates a full database dump before any pilot writes:

`var/migration/aspire-properties/pilot/backup.sql`

Its SHA is in `backup.sha256`. The directory is Git-ignored and restricted to the
local user. `uploads-before.json` records original files. Existing files are never
replaced. The generated pilot result and validation reports identify new IDs.

Rollback restores the **entire pre-pilot database**, so do not run it after other
work without first backing up that newer work. Stop local editing and take a
fresh safety backup first. From the repository root, the database restoration is:

```sh
docker compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' < var/migration/aspire-properties/pilot/backup.sql
```

Remove only files listed in `uploads-created.json` from `wp-content/uploads/`
after confirming none are referenced by later work. The file list is generated
from the before/after inventory and includes generated image sizes. Do not delete
all uploads or the PMTiles file. Keep the private backup and source media for
review. Code changes can remain installed after data rollback; the protected
admin panels do not create records by themselves. Rollback was documented,
not executed.
