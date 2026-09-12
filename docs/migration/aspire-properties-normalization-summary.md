# Aspire property normalization audit

**87/87 records normalized. Audit only: no property or media import. V1 and V2 remain unchanged.**

## Coverage

| Measure | Result |
|---|---|
| normalized | 87 |
| readiness | {"READY": 0, "READY_WITH_NULLS": 3, "REVIEW_REQUIRED": 71, "BLOCKED": 13} |
| transaction_coverage | {"v2": 25, "v3": 43} |
| transaction_methods | {"strongly_derived": 19, "unresolved": 44, "explicit": 24} |
| property_type_changes | [{"source_title": "1700 Wirt Road Houston, TX 77055", "before": "Office", "after": "Industrial / Flex"}] |
| addresses_reliable | 74 |
| geocoding_needed | 82 |
| geocoding_eligible_addresses | 71 |
| parser_matches_removed | 322 |
| context_conflicts_resolved | {"lease_rate_min": 4, "traffic_count_vpd": 3, "lot_acres": 1, "sale_price": 1, "transaction_type": 8, "maximum_available_sf": 1, "minimum_available_sf": 1} |
| remaining_conflicts | {"price_per_sf": 1, "lot_acres": 4, "suite_fields": 4, "sale_price": 1, "warehouse_sf": 1, "available_sf": 4, "maximum_contiguous_sf": 1, "suite_inventory": 4, "address": 7, "traffic_count_vpd": 2, "building_sf": 1, "decision_facts.building_size": 1, "brochure_subject": 1} |
| properties_with_remaining_conflicts | 23 |
| suite_count | 45 |
| availability_context_count | 3 |
| offering_context_count | 3 |
| contact_count | 181 |
| contact_properties | 75 |
| primary_images_high_confidence | 27 |
| primary_images_need_review | 60 |
| price_anomaly_properties | ["4303 Sienna Parkway Missouri City, TX 77459", "Clodine Road Richmond, TX 77407"] |

## Rules and limitations

- Import candidates are separate from immutable V2 evidence. READY and READY_WITH_NULLS are draft-review classifications, never publication approval.
- Explicit transaction wording is Tier A. Labelled property prices and suite lease rates are Tier B and require approval. Ordinary sale + lease are two semantic intents; ground lease remains distinct. Complementary offers do not establish which source is current.
- Every decision-field matcher has a positive context gate. Generic headings, restaurant names, availability of signage/parking, malformed frontage units and truncated claims are excluded. Statements remain attributed to the source.
- Combined built/renovated labels are parsed in order. Malformed comma groupings quarantine numeric prices and preserve raw anomaly evidence. FM 1093 pricing suppression/review is unchanged.
- Suite rates remain suite-specific; no property-level aggregate rate is manufactured. Source total-space labels are separate from suite areas. Source suite inventory differences still require review.
- Parcel prices are attached only within source blocks. Map-only acreage labels have no assigned price or availability and require review. No parcel sizes are summed.
- Same-road traffic disagreements remain conflicts. Unnamed counts retain a null road; no road is inferred from the address.
- The 82 reused source coordinates are excluded. Five V2 usable metadata candidates remain unverified source locations. No external geocoding or image downloads.
- Contact candidates are deduplicated per person/email and are not approved for display. No primary is selected from a generic team listing.

## Schema gaps

- WordPress transaction taxonomy can store multiple terms, but Aspire_Atlas::term() selects only the first term and Atlas filters/Brief use one scalar intent. Sale + Ground Lease must remain separate in V3; no taxonomy/frontend changes made.
- Office Condo remains source provenance; existing taxonomy models it as an Office child. No new property taxonomy terms are proposed.

## Remaining property reviews

| Property | Readiness | Conflicts / blockers |
|---|---|
| 14211 Hiram Clarke Road Houston, TX 77045 | REVIEW_REQUIRED | price_per_sf |
| 23400 Aldine Westfield Road - 23400 Aldine Westfield Road Spring, TX 77373 | REVIEW_REQUIRED | lot_acres |
| 2404 Smith Ranch Road Pearland, TX 77584 | REVIEW_REQUIRED | suite_fields |
| 2539-2611 Preston Avenue Pasadena, TX 77503 | REVIEW_REQUIRED | sale_price, warehouse_sf |
| Cypresswood Shopping Center - 25819 Cypresswood Drive, Spring, TX 77373 | REVIEW_REQUIRED | available_sf, maximum_contiguous_sf, suite_fields, suite_inventory |
| 4118 FM Road 2977 Richmond, TX 77469 | BLOCKED | unresolved_critical_address, address |
| 5530 Long Prairie Trace Richmond, TX 77407 | REVIEW_REQUIRED | suite_fields |
| 7395 Mchard Road Houston, TX 77053 | REVIEW_REQUIRED | suite_inventory |
| 747 N Eldridge Parkway, Houston, TX 77079 | REVIEW_REQUIRED | traffic_count_vpd |
| Atascocita Retail Center - 7506 E FM-1960 Humble, TX 77346 | REVIEW_REQUIRED | suite_inventory |
| Seven-Seventy Post Oak - 770 Post Oak Lane Houston, TX 77056 | BLOCKED | unresolved_critical_address, address |
| 9630 Huffmeister - 9630 Huffmeister Road Houston, TX 77095 | REVIEW_REQUIRED | available_sf |
| Aldine Square - Aldine Westfield Road and Aldine Mail Route Houston, TX 77039 | BLOCKED | unresolved_critical_address |
| Centre at Pearland Parkway - 1849/1851/1853 Pearland Parkway Pearland, TX 77581 | BLOCKED | unresolved_critical_address, address, available_sf, suite_fields, suite_inventory |
| Chasewood Technology Park - 20333 -20445 State Highway 249 Houston, TX 77070 | REVIEW_REQUIRED | building_sf, decision_facts.building_size |
| Clodine Road Richmond, TX 77407 | BLOCKED | unresolved_critical_address |
| Crystal Beach Island - 1689 Highway 87 Crystal Beach, TX 77650 | REVIEW_REQUIRED | lot_acres |
| FM 2218 Road & Airport Avenue, Rosenberg, TX 77471 | BLOCKED | unresolved_critical_address |
| Glenn Lakes Professional Building - 3634 Glenn Lakes Lane Missouri City, TX 77459 | REVIEW_REQUIRED | available_sf |
| Grand Mission Land - Grand Mission Boulevard Richmond, TX 77407 | BLOCKED | unresolved_critical_address, lot_acres |
| Grand Reserve Office Condos - 23410 Grand Reserve Drive Katy, TX 77494 | BLOCKED | unresolved_critical_address, wrong_brochure_subject, address, brochure_subject |
| James Place Business Park - 8466 North Sam Houston Parkway West Houston, TX 77064 | BLOCKED | unresolved_critical_address, address |
| l - 32 Pad Site - Cotulla, Texas - Sec of l - 35 and Mars Drive Cotulla, TX 78014 | REVIEW_REQUIRED | traffic_count_vpd |
| NEC Aldine Bender Rd & JFK Blvd. Houston, Texas 77032 | BLOCKED | unresolved_critical_address, lot_acres |
| Park Central Plaza - 1111 North Loop West Houston, TX 77008 | BLOCKED | unresolved_critical_address, address |
| Sierra Vista Office Condos - Meridiana Parkway, Iowa Colony, TX, 77583 | BLOCKED | unresolved_critical_address |
| The Palm Office Condos - 1842 Snake Road Katy, TX 77449 | BLOCKED | unresolved_critical_address, address |

## Safety

Properties 4 → 4; attachments 7 → 7. Full read-only SQL snapshots match, including property/meta fingerprints, taxonomy rows and relationships.

No WordPress bootstrap/write API, attachments, taxonomy mutations, frontend changes or Docker edits. All six V1/V2 output hashes are preserved in the V3 manifest.
