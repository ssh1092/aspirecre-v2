# Aspire Core

Property and team data/admin layer, independent of the active theme. No content migration or frontend templates are included.

## Administration

Properties and Team Members use the native WordPress classic editor, including the long-form content editor, featured image, and revisions. Properties also support excerpts. The two internal hierarchical taxonomies have native checkboxes and management screens. Office Condo is seeded under Office. Existing terms are retained on activation.

Property fields are split into Property Details, Address & Location, Size & Building Details, Pricing & Lease Information, Property Content, Suite Availability, Media, and Listing Brokers. Team contact fields and short biography have their own box. Add/remove suites, media changes, and broker selections are committed by Save Draft/Update/Publish. Brochure selection uses the PDF-filtered native Media Library; gallery selection supports multiple images. Both dialogs support WordPress uploads. Broker checkboxes include a text filter.

## Storage contract

All field keys use `_aspire_` followed by the requested field name. The complete scalar definitions are in `includes/class-fields.php`.

- `_aspire_listing_status`: available, under_contract, leased, sold, off_market; default available.
- `_aspire_featured_property`: registered boolean (WordPress scalar metadata is read back as strings).
- `_aspire_state`: default TX only when metadata is absent. An explicitly saved empty state stays empty.
- Measurements/prices: nonnegative decimal numbers; years, stories, parking spaces, and traffic counts use nonnegative integers. Invalid/blank numeric inputs remove the value; zero remains valid. Latitude/longitude support negative values within geographic bounds. Postal codes, building class, parking ratio, phone, and license number stay text. No currency or lease-unit formatting is invented.
- Multiline fields: plain text with newlines retained; HTML is stripped.
- `_aspire_suites`: one ordered array of rows containing suite_name, square_feet, rate, rate_type, former_use, notes, availability_status. Status is available, coming_soon, leased, or unavailable. Invalid status falls back to available. No suite post type is created.
- `_aspire_brochure_attachment_id`: PDF attachment ID, or 0 when cleared.
- `_aspire_gallery_attachment_ids`: ordered array of unique image attachment IDs.
- `_aspire_listing_broker_id`: **repeated** integer metadata rows, one per team member; duplicates and non-team IDs are rejected.

Query by broker without serialized-array matching:

```php
$properties = get_posts( array(
    'post_type'  => 'property',
    'meta_key'   => '_aspire_listing_broker_id',
    'meta_value' => $team_member_id,
) );
```

Metadata is protected and not exposed for REST editing. Form writes require a post-specific nonce and edit capability and skip autosaves/revisions. Revision support applies to the standard WordPress post content; custom metadata is not revisioned. Native WordPress taxonomy and upload handlers supply their own authorization and nonce checks.

## URLs and lifecycle

Singles use `/properties/{slug}/` and `/team/{slug}/`, without CPT archives. Public taxonomy routes and query-string taxonomy/CPT archives return 404; internal admin filtering remains available.

Activation or the versioned one-time upgrade seeds terms and flushes rewrites. If the installation has plain permalinks (empty structure), this lifecycle step enables `/%postname%/` so the requested single URLs resolve. This also enables pretty URLs for standard WordPress posts. Existing nonempty permalink structures are preserved. Deactivation removes registered routes and flushes once. No regular-request flushing occurs after setup succeeds. Deactivation retains content, terms, and metadata.

## Verification

Run against the existing Docker installation from the project root:

```sh
docker compose exec -T wordpress sh -c 'find wp-content/plugins/aspire-core -name "*.php" -exec php -l {} \;'
docker compose exec -T wordpress php wp-content/plugins/aspire-core/tests/integration.php
```

The integration script is CLI-only. It exercises plugin activation/deactivation and uses temporary records removed in `finally`; run it only in this local prototype. HTTP checks target the existing Docker web service and use the configured localhost:8080 host. It verifies registration, taxonomy seeding/assignment, scalar fields, validation, authorization guards, suites, attachment restrictions, multiple brokers, queryability, rewrites, HTTP singles, and blocked archives.

Browser checks also covered suite add/edit/remove/re-add and removal of all rows, two broker selections, taxonomy selection, PDF selection, two-image selection, media clearing, persistence after saving, and both editor screens. Temporary UI records and uploaded fixtures were removed afterward.

Known environment warning: the existing `wp-config.php` defines `WP_DEBUG` twice. It is outside this plugin and was not changed. The project folder has no Git repository, so `git diff --stat` and `git status` are unavailable.
