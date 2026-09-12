# Aspire Census geocoding and map coverage audit

Attempted **74**: 71 eligible addresses plus 3 reliable-address source-coordinate comparisons. Accepted **50**, review required **4**, unmatched **20**. Thirteen incomplete/ineligible addresses were not submitted.

Used [Public_AR_Current, locations/addressbatch](https://geocoding.geo.census.gov/geocoder/Geocoding_Services_API.html). Census coordinates are interpolated along address ranges, not verified rooftop/parcel positions. Exact V3 address fields were submitted without rewriting. No fallback provider was used.

## Existing map

File: `wp-content/uploads/aspire-atlas/houston.pmtiles`. Header bounds: {"west": -95.95, "south": 29.45, "east": -95.05, "north": 30.25}. Zooms 0–15. SHA-256: `9b84021518f3a7bca2be37add84a8520d3a9c4f216f2c1555d21b91ca3c5dfb2`.

Accepted inside: **47**; outside: **3**; coordinate unavailable: **37**.

Header-center tiles were present at the sampled minimum and maximum zooms. This proves only those tile samples exist; bounding-box inclusion does not establish detailed coverage at every property.

**B. Current basemap needs expansion before full migration**. This is a recommendation only; no map was downloaded, rebuilt or modified.

### Outside-map properties

- 1475 Washington Boulevard Beaumont, TX 77705
- 5601 East Parkway Street Groves, TX 77619
- Crystal Beach Island - 1689 Highway 87 Crystal Beach, TX 77650

## Source-coordinate comparisons

| Property | Status | Distance m |
|---|---|---:|
| The Offices at Westheimer - 5959 Westheimer Road Houston, TX 77057 | CONSISTENT | 134.0 |
| Seven-Seventy Post Oak - 770 Post Oak Lane Houston, TX 77056 | NOT_COMPARABLE | None |
| Glenn Lakes Professional Building - 3634 Glenn Lakes Lane Missouri City, TX 77459 | CONSISTENT | 69.1 |
| One Westchase Center - 10777 Westheimer Road Houston, TX 77042 | CONSISTENT | 51.6 |
| The Palm Office Condos - 1842 Snake Road Katy, TX 77449 | NOT_COMPARABLE | None |

Distances compare source metadata to returned Census coordinates; non-accepted returns never populate import coordinates. Thresholds: ≤250 m consistent; >250 m–2 km review; >2 km conflict. Two source-coordinate records lack reliable addresses and cannot be compared.

## Existing WordPress comparisons

| Property | Census status | Distance m | Comparison |
|---|---|---:|---|
| 14602 Presidio Square Boulevard, Houston, TX 77083 | ACCEPTED | 188.1 | CONSISTENT |
| 16840 Clay Road Houston, TX 77084 | ACCEPTED | 390.9 | REVIEW |
| 21617 FM 1093 Richmond TX, 77407 | UNMATCHED | None | NOT_COMPARABLE |
| Atascocita Retail Center - 7506 E FM-1960 Humble, TX 77346 | UNMATCHED | None | NOT_COMPARABLE |

## Representative QA

| Property | Result | Inside bounds | Review reason |
|---|---|---|---|
| Sugarwell Plaza - 14248 Bellaire Boulevard Houston, TX 77083 | ACCEPTED | True |  |
| 14602 Presidio Square Boulevard, Houston, TX 77083 | ACCEPTED | True |  |
| 1475 Washington Boulevard Beaumont, TX 77705 | ACCEPTED | False |  |
| 16840 Clay Road Houston, TX 77084 | ACCEPTED | True |  |
| 21617 FM 1093 Richmond TX, 77407 | UNMATCHED | None |  |
| Broadway Shopping Center - 5900 - 5940 Broadway Street Galveston, TX 77551 | UNMATCHED | None |  |
| Atascocita Retail Center - 7506 E FM-1960 Humble, TX 77346 | UNMATCHED | None |  |
| Crystal Beach Island - 1689 Highway 87 Crystal Beach, TX 77650 | ACCEPTED | False |  |
| Grand Parkway Times Square - 400 W Grand Parkway S | UNMATCHED | None |  |
| Kingsley Ridge Office Condos - 3129 Kingsley Drive Pearland, TX 77584 | UNMATCHED | None |  |
| l - 32 Pad Site - Cotulla, Texas - Sec of l - 35 and Mars Drive Cotulla, TX 78014 | UNMATCHED | None |  |

Unmatched addresses were not rewritten or replaced with city/ZIP centroids. In particular, the Cotulla and Galveston results do not establish any property location or map inclusion. Accepted Beaumont and Crystal Beach locations provide direct evidence of geographic limits.

## City distribution

| City | Inventory | Attempted | Accepted | Review | Unmatched | Inside | Outside |
|---|---:|---:|---:|---:|---:|---:|---:|
| (UNRESOLVED ADDRESS) | 13 | 0 | 0 | 0 | 0 | 0 | 0 |
| BEAUMONT | 1 | 1 | 1 | 0 | 0 | 0 | 1 |
| CONROE | 1 | 1 | 0 | 0 | 1 | 0 | 0 |
| COTULLA | 1 | 1 | 0 | 0 | 1 | 0 | 0 |
| CRYSTAL BEACH | 1 | 1 | 1 | 0 | 0 | 0 | 1 |
| CYPRESS | 1 | 1 | 0 | 0 | 1 | 0 | 0 |
| GALVESTON | 1 | 1 | 0 | 0 | 1 | 0 | 0 |
| GROVES | 1 | 1 | 1 | 0 | 0 | 0 | 1 |
| HOUSTON | 34 | 34 | 30 | 2 | 2 | 30 | 0 |
| HUMBLE | 4 | 4 | 2 | 0 | 2 | 2 | 0 |
| KATY | 5 | 5 | 3 | 0 | 2 | 3 | 0 |
| KEMAH | 1 | 1 | 0 | 0 | 1 | 0 | 0 |
| MAGNOLIA | 1 | 1 | 0 | 1 | 0 | 0 | 0 |
| MISSOURI CITY | 3 | 3 | 3 | 0 | 0 | 3 | 0 |
| MONTGOMERY | 1 | 1 | 0 | 0 | 1 | 0 | 0 |
| PASADENA | 1 | 1 | 0 | 1 | 0 | 0 | 0 |
| PEARLAND | 2 | 2 | 1 | 0 | 1 | 1 | 0 |
| RICHMOND | 6 | 6 | 3 | 0 | 3 | 3 | 0 |
| SHEPHERD | 1 | 1 | 0 | 0 | 1 | 0 | 0 |
| SPRING | 5 | 5 | 4 | 0 | 1 | 4 | 0 |
| SUGAR LAND | 3 | 3 | 1 | 0 | 2 | 1 | 0 |

## Review matches

| Property | Matched address | Reasons |
|---|---|---|
| 2539-2611 Preston Avenue Pasadena, TX 77503 | 2611 PRESTON AVE, PASADENA, TX, 77503 | non_exact_match_requires_precision_review; street_number_mismatch; address_range_precision_requires_review |
| Barker Cypress Plaza - 1855 Barker Cypress Road Houston, TX 77084 | 1855 BARKER CYPRESS RD, HOUSTON, TX, 77084 | non_exact_match_requires_precision_review; material_road_difference |
| Church’s Chicken - 6962 TC Jester Blvd, Houston TX 77091 | 6962 W T C JESTER BLVD, HOUSTON, TX, 77091 | non_exact_match_requires_precision_review; material_road_difference |
| Magnolia Office Park - 31300-31368 Nichols Sawmill Road Magnolia, TX 77355 | 31368 NICHOLS SAWMILL RD, MAGNOLIA, TX, 77355 | non_exact_match_requires_precision_review; street_number_mismatch; material_road_difference; address_range_precision_requires_review |

## Safety and interpretation

WordPress properties 4 → 4; attachments 7 → 7. Full before/after read-only snapshots match, including property/meta fingerprint and taxonomy snapshot.

V1/V2/V3 artifacts remain byte-for-byte unchanged. Only import_candidate.coordinates changes within the copied V3 candidate. Existing V3 coordinate provenance is preserved in the source comparison and immutable V3 reference. Coordinate readiness never approves unresolved V3 transactions, images or other decisions. No WordPress/media/taxonomy/frontend/Docker writes, runtime Census dependency or basemap download.
