# Aspire property enrichment audit

**Audit only. No import, OCR, geocoding or image downloads. All 87 records still need human review.**

Enriched: 87/87. PDF downloads valid/readable: 87/87. Usable embedded text: 80/87. Partial text encoding: 3.

| Coverage | V1 | V2 non-conflicting candidates |
|---|---:|---:|
| transaction_candidates | 24 | 25 |
| addresses | 77 | 74 |
| building_sf | 1 | 2 |
| available_sf | 14 | 12 |
| lot_acres | 29 | 30 |
| year_built | 12 | 13 |
| clear_height_ft | 2 | 3 |
| parking_spaces | 1 | 1 |
| sale_price | 9 | 9 |
| lease_rate_min | 1 | 3 |
| availability_ranges | 0 | 4 |
| suite_properties | 13 | 17 |
| broker_properties | 0 | 76 |
| coordinates | 0 | 5 |
| high_confidence_primary_images | 0 | 27 |

Explicit transaction evidence remains limited. The requested substantial reduction in unknown transactions was not achieved; no candidate was forced from a generic team heading, existing tenancy or legal text.

Newly resolved/conflicting coverage: {"transactions_newly_resolved": 6, "transactions_newly_conflicting": 5, "addresses_newly_resolved": 4, "addresses_newly_conflicting": 7}.

Broker candidates: 184. Suite candidates: 45. Separate availability contexts: 3. Properties with unresolved source/field differences: 33.

## Conflicts

- lease_rate_min: 4 properties
- price_per_sf: 1 properties
- traffic_count_vpd: 5 properties
- lot_acres: 5 properties
- sale_price: 2 properties
- transaction_type: 8 properties
- suite_fields: 4 properties
- warehouse_sf: 1 properties
- available_sf: 4 properties
- maximum_contiguous_sf: 1 properties
- suite_inventory: 4 properties
- minimum_available_sf: 1 properties
- maximum_available_sf: 1 properties
- address: 7 properties
- building_sf: 1 properties
- brochure_subject: 1 properties

## Warnings

- transaction_still_unresolved: 62
- coordinate_reused_across_properties: 82
- brochure_partial_text: 3
- brochure_text_unavailable: 7
- source_field_conflict: 33
- suite_inventory_source_difference: 4
- address_still_unresolved: 13
- brochure_subject_mismatch: 1

## Interpretation

- V1 fields and evidence remain verbatim within each record. New candidates and source evidence live in `enriched_fields`, `transaction_evidence_v2`, `address_evidence_v2`, `suite_candidates_v2`, `availability_contexts_v2`, and `decision_facts`.
- Exact numeric disagreements remain null candidates with both values retained. Qualitative decision facts are quoted claims with evidence, not rewritten marketing prose. Display prices retain separate contexts. Brochures are not assumed newer.
- Internal completeness is the percentage of enumerated type-specific fields with non-conflicting evidence. The field catalog is in the manifest. This is not a consumer property score.
- Legal IABS/TREC boilerplate and generic About Us pages are excluded from property fact extraction. Brochure contact candidates require a property-associated contact/team heading, a name, an email and a nearby telephone number; authors are never used.
- Addresses require a complete source street/locality/ZIP pairing. Generic brochure footer localities alone are not treated as addresses. Spelling equivalence is limited to common directional/street suffix normalization. Conflicting subject-address strings remain for human review.
- Coordinates come only from cached source metadata and must be in valid ranges and broadly within Texas; no address-to-coordinate inference occurs. Repeated identical coordinates across different addresses are withheld as usable candidates and retained in coordinate_evidence.
- Coordinate metadata found on 87 pages; 82 repeated-location candidates rejected for review.
- Squarespace social derivatives may use a different path. A primary-image match requires an exact underlying URL or a unique full filename within the same source site and the legitimate V1 property-image set. Gallery order remains unchanged.
- Source availability may differ: page and brochure suite inventories are compared and retained separately when different. No suite is marked available merely because it exists.
- FM 1093 source prices and its existing V1 pricing-review warning are retained. Local frontend suppression was not touched.
- Image-only or unusable PDFs are flagged without OCR. Partially encoded text is retained with a warning; no contact or fact is invented to compensate.

## Safety

- WordPress properties 4 → 4; attachments 7 → 7.
- Property records/meta fingerprints, taxonomy counts and relationships match using direct SQL read-only snapshots.
- V1 outputs retain their exact SHA-256 hashes, stored in V2. Raw PDFs and text remain in the ignored cache.
- No Atlas, directory, property frontend, Docker or taxonomy-definition changes.

## Outputs

- `docs/migration/aspire-properties-manifest-v2.json`
- `docs/migration/aspire-properties-review-v2.csv`
- `docs/migration/aspire-properties-enrichment-summary.md`
