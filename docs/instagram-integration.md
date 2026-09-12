# Aspire In the Field

`aspire-core/field-feed` is a server-rendered Gutenberg block. It uses an official Instagram API feed when connected; otherwise it clearly labels genuine published Property photography as a curated collection. It does not invent Instagram posts, dates or activity. Photos remain links to their real Property pages. No Instagram scraper or embed script is used.

## Production connection

Use **Instagram API with Instagram Login**, a Business or Creator account and a Meta app configured for that login product. Request the minimum read permission, `instagram_business_basic`, for the Aspire professional account. Complete Meta's applicable access review and obtain a valid long-lived account token through Meta's supported authorization flow.

Configure these outside version control, either as protected PHP constants before WordPress loads or as deployment environment secrets:

- `ASPIRE_INSTAGRAM_ACCOUNT_ID`: numeric professional Instagram user ID.
- `ASPIRE_INSTAGRAM_ACCESS_TOKEN`: the authorized long-lived token.
- `ASPIRE_INSTAGRAM_API_VERSION`: an explicitly selected currently supported version, in `vNN.0` format. The implementation intentionally does not assume the production app's version.

The adapter performs a read-only GET to `https://graph.instagram.com/{version}/{account_id}/media`, requesting `id,caption,media_type,media_url,thumbnail_url,permalink,timestamp`. Authentication uses a server-side Bearer header; redirects are disabled. No token is saved in WordPress options, printed in admin, localized to JavaScript or exposed by a public endpoint. Token renewal/rotation remains a deployment responsibility; this implementation does not invent an OAuth callback or require a client secret in the browser.

Settings → **Aspire Instagram** reports connection/refresh status and provides an administrator-only, nonce-protected refresh button. No credentials are configured in the local prototype, so no live Instagram request has been made or live feed claimed as verified.

## Refresh and fallback

When credentials are configured, WordPress schedules a twice-daily refresh. A successful sanitized response enters a six-hour transient and a non-autoloaded last-successful-feed option. A failed response preserves that feed for up to seven days, then uses local curated photography. Changing the configured account/version cannot display another account's cache. Page rendering reads cache only and never waits for Meta. Normal WP-Cron traffic or a production cron runner is required; no Docker changes are needed.

Only Instagram post/reel permalinks and HTTPS media URLs on Meta's Instagram/Facebook CDN domains are accepted. Unknown fields, pagination, raw errors and access-token-bearing URLs are discarded. Video posts use their real poster thumbnails and link to Instagram; the homepage does not autoplay or download videos. CDN URLs can expire independently of the cache, so production refresh health still matters.

Meta's direct documentation returned HTTP 429 during implementation. The product/permission model was checked against [Meta's official Instagram API collection](https://www.postman.com/meta/instagram/folder/1z5vxzu/instagram-api-with-instagram-login). Production operators should confirm their configured version and account authorization against [Meta's Instagram Login guide](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/) and [Business Login guide](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/business-login/) before connecting. Offline tests mock HTTP responses; they do not substitute for a successful live authorized connection.

## Verified Team addition

The live [Aspire Team page](https://www.aspirecre.com/our-team) renders its Team through public Elfsight widget `e13d1f11-6cdc-4196-b9dd-9e9cd53f96dd`. Four visible source records were matched to explicit portrait URLs and biographies: Brandon Avedikian (Founder), Bradley Segreto (Director), D.A. Smith (Managing Director, Leasing & Property Management), and Alex Bibb (Director). Exact source role strings are retained in WordPress. Short bios are concise factual paraphrases; performance-volume claims and awards are omitted.

The explicit CLI `wp-content/plugins/aspire-core/bin/import-verified-team.php` accepts only the four reviewed identities from a local manifest, checks portrait SHA-256 and file type, creates new WebP attachments and uses existing Team fields. It skips previously imported source IDs without overwriting editorial changes. Protected provenance records source IDs, page/portrait URLs, source hashes and verification time. No brokers were automatically assigned to Properties.

Local evidence and creation IDs are in `var/homepage-presentation-qa/team-source/` and `team-import-report.json`. The remaining live Team records have not been imported. Importing the full roster is outside this bounded homepage content addition.
