# Aspire Atlas Task 5 — My CRE Brief

Implemented in the existing local WordPress project. Task 5 ends here; no Task 6 or mobile sheet redesign is included.

## Architecture and files

The existing Atlas state controller owns `briefOpen`, the current normalized brief, and the camera/selection/rail state needed to return to the originating journey. Brief data is detached from discovery filters and guided answers. In-memory drafts are keyed by originating mode and prefill values. Closing/reopening the same journey restores its draft; changing source filters creates a corresponding new draft. No accounts or browser storage are used.

The Brief is a right-side panel, reusing the existing Property Focus controller for matching properties. Returning from Property Focus restores the Brief review and its scroll position. Closing the Brief restores the original filters or owner/management review, selection and map camera. The former prepared-details placeholder state was retired.

All paths enter the same question/review/contact/success flow. Find Space and Invest have a restrained Build My Brief action in the results heading; guided reviews retain their approved CTA. The approved Explore screen is unchanged.

New files under `wp-content/plugins/aspire-core/`:

- `atlas/brief-schema.json`: shared allowed labels, approximate area bounds, size/budget ranges and conditional option groups. These describe brief input, not new CPT metadata.
- `atlas/brief.php`: frontend panel shell and submission nonces.
- `atlas/src/brief-data.js`: prefill, normalization, step completion, readable summaries and deterministic matching.
- `atlas/src/brief.js`: accessible native controls, edit/review flow, compact matching cards and contact submission.
- `atlas/src/brief.css`: scoped desktop/tablet/stacked-mobile panel styles.
- `includes/class-atlas-inquiries.php`: private enquiry model, POST endpoint, validation, server matching, local storage and admin display.
- `tests/atlas-inquiries.php`: security/storage/matching tests with fixture cleanup.

Modified files under that plugin:

- `aspire-core.php`: loads enquiry integration.
- `atlas/template.php`: includes the frontend Brief shell.
- `atlas/find-space.php`: adds the discovery entry action.
- `atlas/src/find-space.js`: shared state, launch/close and existing dossier integration.
- `atlas/src/guided.js`, `atlas/src/workflow-data.js`: launch the real Brief and retire the prepared-details placeholder.
- `atlas/src/filters.js`: uses the same unchanged approximate area definitions from the shared schema.
- `atlas/src/view.js`: Brief camera padding, area/review composition and camera restoration on the existing map instance.
- `atlas/tests/contracts.test.mjs`: adds Brief model/matching coverage and updates retired placeholder assertions.
- `atlas/build/view.js`, `atlas/build/view.css`, `atlas/build/manifest.json`: rebuilt frontend assets.

This report is `docs/aspire-atlas-task5.md`.

No changes to the theme, header/footer architecture, Gutenberg Home content, property/team metadata definitions, existing property API values, Docker configuration, PMTiles archive, map sources or cartographic styles. The four real property features compare equal to the Task 4 API snapshot. No property or team content was added to the prototype; temporary fixtures from the existing tests were removed.

## Prefill and conditional questions

The normalized payload contains `goal`, `propertyTypes`, `location.text`, `location.areaPreset`, a size code, budget code, transaction, timing, priorities, management needs and owner intent. Shared codes derive their human labels and bounds from one JSON schema, avoiding duplicate numeric bounds in submitted data.

| Entry | Carried forward | New answers ordinarily requested |
| --- | --- | --- |
| Find Space | Lease goal, type, transaction, size and area | Missing type/size, timing, relevant priorities |
| Buy / Invest | Invest goal, type, sale transaction, price, size and area | Missing type/size, timing, relevant priorities |
| Lease / Sell review | Location, type, exact size range and owner intent | Timing and owner priorities |
| Management review | Location, type, exact size range and management needs | Timing |

Known answers are skipped, with section controls and per-row Edit actions for all answers. Flexible Houston is a valid carried-forward location. Editing an answer never overwrites the originating Atlas filters/review.

All seven goals are editable. The prototype selects one property type per brief to keep SF/acre requirements unambiguous. Land uses acres; buildings use SF. Switching between them clears incompatible sizes. Buy/Invest supports budget choices; other goals omit budget. Broader source ranges such as 50,000+ SF and the owner workflow's 5,000–25,000 SF remain exact when carried forward. They are not silently narrowed.

Office/land/retail choices omit irrelevant seeker priorities. Owners use owner priorities. Management reuses its captured needs. “Not sure yet” is exclusive in multi-select groups. Free text is never geocoded and never generates coordinates.

## Matching

Matching uses the already-loaded Atlas inventory; no extra property fetch, external API or AI is involved. Lease/buy/invest candidates must satisfy every specified transaction, type, approximate area, size and reliable numeric-price criterion. Numeric ranges are half-open; a missing metric or missing/suppressed price cannot satisfy an active range. A lease brief uses available SF with the existing building-SF fallback; buying uses building SF; land uses acreage.

Exact transaction matches rank ahead of dual listings, with stable ID ordering for ties. No percentages or invented suitability scores appear. Up to three cards show actual local images, cleaned titles, taxonomy labels, metrics and locations. Cards open the existing Property Focus panel.

The review explains that typed locations, timing and priorities are for Aspire's review, rather than verified listing filters. It also explains the reliable-price limitation for investment briefs. Both approved no-match sentences are implemented. Property-data loading/failure is distinguished from a verified empty inventory match.

Owner/management briefs do not imply inventory matches.

## Submission, storage and security

Contact information appears only after the readable Brief review. Name and email are required; phone/company are optional. The contact-request checkbox is recorded as a boolean and is optional. There is no additional message field.

`POST /wp-json/aspire/v1/atlas/inquiries` creates a private `atlas_inquiry` post. Protected post metadata stores sanitized contact details and consent, normalized brief data, a server-generated human-readable summary, UTC timestamp, source `Aspire Atlas`, and server-recomputed matching property IDs.

The endpoint:

- Requires a valid Atlas submission nonce. Signed-in browser requests additionally send WordPress's REST nonce for cookie authentication.
- Accepts JSON with exact expected keys; rejects malformed input, unknown fields, nested/invalid enum values, inconsistent goal/transaction/intent combinations and mismatched units.
- Bounds text lengths and total body size, validates email, sanitizes text, and escapes admin display.
- Rejects a filled honeypot.
- Uses a ten-minute transient window per hashed connection IP: at most 20 structurally valid attempts or five successful submissions. Spoofable forwarding headers are ignored; raw IPs are not stored.
- Recomputes matching IDs from the current public Atlas collection and its existing price suppression rules. Client-supplied IDs or summaries are not accepted.
- Returns only a receipt acknowledgement, with no contact data or enquiry ID.

The CPT is nonpublic, not publicly queryable, excluded from search and absent from public REST. No public GET route exposes submissions. Administration requires `manage_options`; visitors need no account. WordPress → **Atlas Enquiries** lists records with goal/email/date. Opening a record shows contact/consent, source/time, readable brief and links to matched properties. Manual creation, quick edit and bulk editing are disabled; records can be viewed and removed.

Submission does not call or depend on `wp_mail`, SMTP, CRM or any external service. The frontend bounds submission wait time and distinguishes unconfirmed network receipt from a confirmed success. Success uses the approved copy and offers Explore or matching-property review.

## Verification

| Check | Result |
| --- | --- |
| `npm --prefix wp-content/plugins/aspire-core run build` | Passed |
| `npm --prefix wp-content/plugins/aspire-core run test:atlas` | 15 tests passed |
| `docker compose exec -T wordpress php wp-content/plugins/aspire-core/tests/integration.php` | 84 assertions passed |
| `docker compose exec -T wordpress php wp-content/plugins/aspire-core/tests/atlas.php` | 61 assertions passed |
| `docker compose exec -T wordpress php wp-content/plugins/aspire-core/tests/atlas-inquiries.php` | 37 assertions passed |
| PHP lint | All six added/modified PHP files passed |
| `git diff --check` | Passed |
| Property API compared with Task 4 snapshot | Unchanged |
| Public enquiry GET and `/wp/v2/atlas_inquiry` | HTTP 404 |
| Anonymous HTTP POST using frontend guest nonce | HTTP 201, private record saved |
| Signed-in browser POST | Receipt screen, private record visible in admin |
| Relevant browser console warnings/errors | None |

Browser verification covered Explore; existing Find/Invest filters; all four Brief entry points; skipped known answers; goal/type edits; SF/acre conditional reset; budget edit turning an investment no-match into the real FM 1093 match; owner priorities; management reuse and editing; “Not sure” exclusivity; readable reviews; matching cards; shared Property Focus and return; no-match copy; contact validation; success; and restoration of originating filters/guided reviews.

Real keyboard Space/Enter interactions were checked. Fields have native labels and associated validation messages; steps/results/submission use live status, and focus moves to the current heading or invalid field. Matching cards need no map interaction. Desktop, 820px tablet, 390px mobile and 320px narrow mobile were inspected. No page or Brief horizontal overflow was observed. Mobile uses a stacked panel with map context above it. Existing reduced-motion rules remain and the Brief animation has its own reduced-motion override; browser preference emulation was unavailable.

All temporary inquiry test records and test rate counters were removed. Remaining enquiries after cleanup: zero. Browser returned to Explore with the temporary viewport override reset.

The pre-existing duplicate `WP_DEBUG` definition warning still appears in CLI bootstrap. No new PHP errors were observed; Docker/wp-config were left unchanged.

## Known limitations

- Matching is limited to existing mapped inventory and its available metadata. Text locations and qualitative priorities are not geocoded or automatically verified.
- Current sale listings lack reliable Atlas price values; selecting a specific budget can produce no matches. Suppressed underlying prices remain suppressed.
- Drafts are in memory for this page session. There is no PDF, account, persistent saved brief, email delivery or CRM integration.
- Guest WordPress nonces are shared by anonymous visitors and are not bot authentication. The honeypot and transient rate limit are lightweight prototype controls; the transient counter is not an atomic distributed anti-abuse system. Cached/expired pages must be reloaded to refresh nonces.
- Final draggable mobile sheets are intentionally deferred.

## Git snapshot

Changes are uncommitted. `git diff --stat` covers tracked files only; new files are separately visible in `git status` below.

### git diff --stat

```text
 wp-content/plugins/aspire-core/aspire-core.php     |  1 +
 .../plugins/aspire-core/atlas/build/manifest.json  |  2 +-
 .../plugins/aspire-core/atlas/build/view.css       |  2 +-
 wp-content/plugins/aspire-core/atlas/build/view.js | 74 +++++++++++-----------
 .../plugins/aspire-core/atlas/find-space.php       |  2 +-
 .../plugins/aspire-core/atlas/src/filters.js       |  9 +--
 .../plugins/aspire-core/atlas/src/find-space.js    | 50 +++++++++++----
 wp-content/plugins/aspire-core/atlas/src/guided.js | 20 +++---
 wp-content/plugins/aspire-core/atlas/src/view.js   | 22 +++++--
 .../plugins/aspire-core/atlas/src/workflow-data.js |  3 -
 wp-content/plugins/aspire-core/atlas/template.php  |  2 +-
 .../aspire-core/atlas/tests/contracts.test.mjs     | 37 +++++++++--
 12 files changed, 140 insertions(+), 84 deletions(-)
```

### git status --short

```text
 M wp-content/plugins/aspire-core/aspire-core.php
 M wp-content/plugins/aspire-core/atlas/build/manifest.json
 M wp-content/plugins/aspire-core/atlas/build/view.css
 M wp-content/plugins/aspire-core/atlas/build/view.js
 M wp-content/plugins/aspire-core/atlas/find-space.php
 M wp-content/plugins/aspire-core/atlas/src/filters.js
 M wp-content/plugins/aspire-core/atlas/src/find-space.js
 M wp-content/plugins/aspire-core/atlas/src/guided.js
 M wp-content/plugins/aspire-core/atlas/src/view.js
 M wp-content/plugins/aspire-core/atlas/src/workflow-data.js
 M wp-content/plugins/aspire-core/atlas/template.php
 M wp-content/plugins/aspire-core/atlas/tests/contracts.test.mjs
?? docs/aspire-atlas-task5.md
?? wp-content/plugins/aspire-core/atlas/brief-schema.json
?? wp-content/plugins/aspire-core/atlas/brief.php
?? wp-content/plugins/aspire-core/atlas/src/brief-data.js
?? wp-content/plugins/aspire-core/atlas/src/brief.css
?? wp-content/plugins/aspire-core/atlas/src/brief.js
?? wp-content/plugins/aspire-core/includes/class-atlas-inquiries.php
?? wp-content/plugins/aspire-core/tests/atlas-inquiries.php
```
