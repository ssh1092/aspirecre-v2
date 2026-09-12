# Aspire legacy property dry-run audit

Generated 2026-09-12T02:27:48.099333+00:00. **No import performed.** All 87 records remain `needs_review`; this report does not establish active/current availability.

## Discovery and parsing

- https://www.aspirecre.com/properties: 20 unique links on that archive.
- https://www.aspirecre.com/properties?offset=1752069960170: 20 unique links on that archive.
- https://www.aspirecre.com/properties?offset=1748785847222: 20 unique links on that archive.
- https://www.aspirecre.com/properties?offset=1746117645948: 20 unique links on that archive.
- https://www.aspirecre.com/properties?offset=1713848107409: 7 unique links on that archive.

Unique properties: **87**. Parsed: **87**. Failed: **0**. URLs were discovered from links, not a hand-maintained list.

## Category and transaction counts

| Source category | Count |
|---|---:|
| Industrial | 5 |
| Land | 19 |
| Office | 20 |
| Office Condos | 16 |
| Retail | 27 |

| Explicit transaction candidate | Count |
|---|---:|
| For Lease | 15 |
| For Sale | 1 |
| For Sale or Lease | 8 |
| Unknown / ambiguous | 63 |

## Coverage

- Single brochure URL: 87/87 properties.
- At least one property image: 87/87 properties.
- Multiple unique property images: 87/87 properties.
- Gallery images: 87/87 properties.
- Suite candidates: 13/87 properties.
- Unparsed availability blocks: 6/87 properties.
- Normalized structured pricing: 10/87 properties.
- Legacy paths requiring a redirect: 1/87 properties.
- Total suite candidates: 32. No inferred suite availability statuses.

| Parsed field | Non-null records |
|---|---:|
| `source_title` | 87/87 |
| `source_category` | 87/87 |
| `source_author` | 87/87 |
| `source_publish_date` | 87/87 |
| `property_type` | 87/87 |
| `transaction_type_candidate` | 24/87 |
| `address_line_1` | 77/87 |
| `city` | 77/87 |
| `postal_code` | 86/87 |
| `building_sf` | 1/87 |
| `available_sf` | 14/87 |
| `minimum_available_sf` | 0/87 |
| `maximum_contiguous_sf` | 9/87 |
| `lot_acres` | 29/87 |
| `year_built` | 12/87 |
| `renovated_year` | 2/87 |
| `building_class` | 3/87 |
| `stories` | 1/87 |
| `clear_height_ft` | 2/87 |
| `parking_spaces` | 1/87 |
| `parking_ratio` | 1/87 |
| `traffic_count_vpd` | 9/87 |
| `sale_price` | 9/87 |
| `lease_rate_display` | 6/87 |
| `featured_image_url` | 87/87 |
| `brochure_url` | 87/87 |
| `property_highlights` | 84/87 |
| `suite_candidates` | 13/87 |

## Existing prototype matches

- WordPress 121: https://www.aspirecre.com/properties/14602-presidio-square-boulevard-houston-tx-77083 (`exact_slug`).
- WordPress 120: https://www.aspirecre.com/properties/16840-clay-road-houston-tx-77084 (`exact_slug`).
- WordPress 122: https://www.aspirecre.com/properties/21617-fm-1093-richmond-tx-77407 (`exact_slug`).
- WordPress 119: https://www.aspirecre.com/properties/7506-e-fm-1960-humble-texas-77346 (`exact_slug`).

## Warnings

| Code | Properties |
|---|---:|
| `featured_image_low_confidence` | 87 |
| `missing_transaction` | 60 |
| `ambiguous_address` | 9 |
| `suite_parse_review` | 8 |
| `structured_value_review` | 8 |
| `structured_value_conflict` | 8 |
| `no_structured_highlights` | 3 |
| `ambiguous_transaction` | 3 |
| `existing_wp_pricing_review` | 1 |
| `legacy_url_requires_redirect` | 1 |
| `missing_address` | 1 |
| `missing_or_malformed_postal_code` | 1 |

## Review recommendations

- Approve transaction intent and listing status manually before any future import. Missing transaction evidence stays null, even where the local prototype has a known transaction.
- Confirm addresses marked ambiguous; malformed ZIPs and missing city/address components are not filled from local knowledge or geocoding.
- Review all proposed slugs, especially the Pearland Parkway path containing embedded slashes. Preserve exact source URLs for the later redirect map.
- Review Woodson’s mixed retail/medical/office rate and size ranges as separate availability contexts. Unparsed blocks are retained; the parser does not fabricate suite numbers or aggregate areas.
- Reconcile the published FM 1093 $3,410,000 price with the existing local suppression policy before any future import. The manifest retains the source price; local records and frontend policy remain untouched.
- Clay Road currently publishes 14-foot clear height but does not explicitly state a transaction in the parsed property body. Presidio contains a truncated building-size unit and a combined built/renovated label; uncertain values remain null.
- Featured-image selection uses the first legitimate standalone property image where no reliable primary-image marker agrees. Every low-confidence choice is flagged; gallery assets are deduplicated by underlying URL while preserving order.
- Source authors are audit provenance only and must never become listing advisors automatically.
- Brochure and image URLs were recorded only. No PDFs/images were fetched or added to WordPress. No external scraping, geocoding or market-data service was used.

## Safety and reproducibility

- Property count: 4 → 4. Attachments: 7 → 7.
- Property taxonomy term rows/counts, relationships and complete property post/meta fingerprints match before/after.
- Property taxonomy counts: property_type/retail: 1 → 1, property_type/office: 1 → 1, property_type/industrial-flex: 1 → 1, property_type/land: 1 → 1, transaction_type/for-lease: 2 → 2, transaction_type/for-sale: 2 → 2, transaction_type/for-sale-or-lease: 0 → 0, transaction_type/ground-lease: 0 → 0, property_type/office-condo: 0 → 0.
- SQL snapshots use a direct read-only transaction, without bootstrapping WordPress. No application, schema, taxonomy or Docker edits.
- HTML cache and SQL snapshots are Git-ignored under `var/migration/aspire-properties/`.
- Run `python3 tools/migration/crawl.py` to reuse cached HTML; `--refresh` explicitly refreshes all fixed archives/property pages. The 87-page gate runs before property fetching/parsing.
- Run the read-only snapshot command before and after the crawl, then `var/migration/venv/bin/python tools/migration/audit.py`. See the tool README for complete commands.
- Network fetching uses system curl with TLS verification, a descriptive browser-style User-Agent, 45-second request timeout, two retries and a 0.75-second pause before each uncached request; requests are sequential.

## Outputs

- `docs/migration/aspire-properties-manifest.json`
- `docs/migration/aspire-properties-review.csv`
- `docs/migration/aspire-properties-summary.md`
