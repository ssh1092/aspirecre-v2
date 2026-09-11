# Aspire Atlas Task 5.5 — Brief UX and enquiry admin

This correction supersedes Task 5's skipped-question flow and post-style enquiry administration. The existing private enquiry data and public submission contract are retained.

## Customer experience

- Customer-facing entry actions now say **CREATE MY REAL ESTATE BRIEF**. Find Space and Buy / Invest include the compact contextual requirement prompt; owner/management reviews use the same CTA without repeating a long explanation.
- A new introduction uses the supplied headline, explanatory copy, minute estimate, three value cues, Create My Brief action and Back to Atlas action.
- Every new brief proceeds through all six visible stages: Goal → Property → Location → Size / Budget → Timing → Priorities. Known answers remain preselected and editable. Management needs occupy the sixth stage. Continue no longer searches for the next unanswered question.
- Named `STEP N OF 6` progress and a native progress bar replace the unlabeled circles. Review Edit actions still return directly to review when the amended brief is complete; dependent unanswered fields must be completed before review.
- The review groups data under Your Requirement, Space / Investment (or Property Details for owners/management), and Timing & Priorities / Management Needs. Relevant per-row edits remain.
- Match, no-match, contact and success copy use the supplied customer-facing language. Algorithm explanations and off-market implications were removed from review.
- Existing deterministic matching, Property Focus reuse, in-session drafts, closing/state restoration, contact validation and POST submission behavior remain intact.

## Purpose-built WordPress administration

WordPress → **Atlas Enquiries** now opens `/wp-admin/admin.php?page=aspire-atlas-enquiries`.

The dedicated list includes Contact, Requirement, Area, Timing, Contact Requested, Received and Status. It supports contact/requirement search, status filtering, and pagination at 25 enquiries per page. Search uses the existing contact and readable-summary metadata; internal notes are not part of public or customer-facing search.

The detail screen uses compact native-admin cards for Contact, Real Estate Brief, Matching Aspire Properties and Follow-up. It contains no post-title field, Classic Editor, Gutenberg editor, content area, taxonomy controls or publishing box. The underlying CPT explicitly has no editor/title support and its standard UI is hidden. Existing post-edit and list URLs redirect to the custom screens; generated enquiry edit links also target the new detail view.

Internal status choices are New, Contacted, Qualified and Closed. Records without a stored status display New, including all older enquiries, without a migration. Status and multiline internal notes are stored in protected `_atlas_internal_status` and `_atlas_internal_notes` metadata. Submitted contact/brief data, post content, private status and stored matches are not rewritten when staff save follow-up information.

Admin pages and updates require `manage_options`. Saves use POST, a record-specific nonce, record-type checks, an exact status allowlist, a 10,000-character notes limit, text sanitization and escaped output. No public notes/status endpoint was added. The existing submission endpoint, validation, nonce strategy, honeypot, rate limiter, private storage and matching code remain unchanged.

The four pre-existing enquiries remain available and private. A real older enquiry was inspected through its legacy edit URL. Only the temporary browser-test enquiry and automated test fixtures were removed.

## Files

New:

- `wp-content/plugins/aspire-core/includes/class-atlas-enquiry-admin.php` — dedicated list/detail pages, secure follow-up saves, old-link redirects and admin styling hook.
- `wp-content/plugins/aspire-core/atlas/admin.css` — scoped native-admin layout.
- `docs/aspire-atlas-task5-5.md` — this report.

Modified:

- `atlas/src/brief.js` — intro, sequential navigation, progress and customer copy/grouped review.
- `atlas/src/brief.css` — progress, value cues, grouped summaries and compact contextual entry layout.
- `atlas/brief.php` — panel terminology and progress container.
- `atlas/find-space.php`, `atlas/src/guided.js`, `atlas/template.php` — entry terminology/context.
- `includes/class-atlas-inquiries.php` — hide default CPT UI, explicitly disable post supports, retire old admin metabox/columns and load the dedicated admin class. Submission/security/matching methods are unchanged.
- `tests/atlas-inquiries.php` — custom admin rendering, old-record default, secure status/notes saves, preservation and privacy coverage.
- `atlas/build/view.js`, `atlas/build/view.css`, `atlas/build/manifest.json` — rebuilt assets.

Paths in the modified list are relative to `wp-content/plugins/aspire-core/`.

## Verification

- Build passed.
- JavaScript suite: **15 tests passed**.
- Core integration: **84 assertions passed**.
- Atlas PHP suite: **61 assertions passed**.
- Enquiry PHP suite: **48 assertions passed**.
- PHP lint passed for all six added/modified PHP files.
- `git diff --check` passed.
- Public enquiry GET routes return **404**. Public property API output compares equal to the earlier approved API snapshot.
- Browser tests verified the intro and all six stages for Find Space, Invest, owner and management paths; preselected filters/answers; review edits; matching/no-match copy; Property Focus return; contact validation; submission and updated success copy; and return to the original journey.
- Browser admin checks verified the useful list, an older enquiry, absence of editor/title controls, real status/notes persistence after redirect, and combined contact/status filtering. Database inspection independently confirmed the saved status.
- Narrow layouts at **390px and 320px** had no page/Brief/contextual-entry horizontal overflow. No mobile sheet redesign was introduced.
- No relevant browser console warnings/errors. Browser returned to Explore and the temporary viewport override was reset.
- The pre-existing duplicate `WP_DEBUG` bootstrap warning remains. No Docker/wp-config changes were made.

No map architecture, PMTiles data, cartographic styles, property matching rules, real property/team records, theme/header/footer or Home content changes were made. No external CRM, email integration or additional Atlas functionality was added. Task 5.5 ends here.

## Git snapshot

Changes remain uncommitted. The diff stat covers tracked files; new files appear separately in status.

### git diff --stat

```text
 wp-content/plugins/aspire-core/atlas/brief.php     |  4 +-
 .../plugins/aspire-core/atlas/build/manifest.json  |  2 +-
 .../plugins/aspire-core/atlas/build/view.css       |  2 +-
 wp-content/plugins/aspire-core/atlas/build/view.js | 24 +++++-----
 .../plugins/aspire-core/atlas/find-space.php       |  2 +-
 wp-content/plugins/aspire-core/atlas/src/brief.css |  9 +++-
 wp-content/plugins/aspire-core/atlas/src/brief.js  | 53 +++++++++++++---------
 wp-content/plugins/aspire-core/atlas/src/guided.js |  2 +-
 wp-content/plugins/aspire-core/atlas/template.php  |  2 +-
 .../aspire-core/includes/class-atlas-inquiries.php | 23 ++--------
 .../plugins/aspire-core/tests/atlas-inquiries.php  | 15 +++++-
 11 files changed, 76 insertions(+), 62 deletions(-)
```

### git status --short

```text
 M wp-content/plugins/aspire-core/atlas/brief.php
 M wp-content/plugins/aspire-core/atlas/build/manifest.json
 M wp-content/plugins/aspire-core/atlas/build/view.css
 M wp-content/plugins/aspire-core/atlas/build/view.js
 M wp-content/plugins/aspire-core/atlas/find-space.php
 M wp-content/plugins/aspire-core/atlas/src/brief.css
 M wp-content/plugins/aspire-core/atlas/src/brief.js
 M wp-content/plugins/aspire-core/atlas/src/guided.js
 M wp-content/plugins/aspire-core/atlas/template.php
 M wp-content/plugins/aspire-core/includes/class-atlas-inquiries.php
 M wp-content/plugins/aspire-core/tests/atlas-inquiries.php
?? docs/aspire-atlas-task5-5.md
?? wp-content/plugins/aspire-core/atlas/admin.css
?? wp-content/plugins/aspire-core/includes/class-atlas-enquiry-admin.php
```
