# AspireCRE Gutenberg homepage

## Edit the homepage

Open **Pages → Home → Edit**. Home is a published WordPress Page and the static front page. `front-page.php` runs the normal page loop and `the_content()` between the existing theme header and footer.

Gutenberg List View contains seven named sections: Hero, Property Finder, Featured Properties, Services, Houston / Why Aspire, Team, and Final CTA. Editorial text uses native Paragraph and Heading blocks; primary buttons use Buttons/Button blocks; other CTA links are editable Paragraph links. Layout uses native Group blocks with the existing AspireCRE CSS classes. Only the three dynamic components use custom blocks. There are no Custom HTML blocks or page builders.

The hero and Houston areas each contain a native Image block. Choose Upload or Media Library inside that block and set its alt text normally. Until a real image is selected, the approved labelled photographic fallback remains visible on the frontend; selecting an image hides that fallback automatically. Images are now owned by the page, not Customizer settings.

Reusable section patterns are available under **Patterns → AspireCRE**, sourced from `patterns/home-*.php`. Inserting one creates normal editable blocks; later pattern-file changes do not overwrite page edits. Preserve the section CSS classes when editing content to retain the approved layout.

## Dynamic sections

Three first-class blocks appear under **AspireCRE** in the block inserter:

- **Property Finder**: visual taxonomy-driven form; submits GET to `/properties/` on the frontend.
- **Featured Properties**: published available properties, featured first; Inspector count 1–8 (default 4).
- **Team Grid**: published team members; Inspector count 1–8 (default 4).

Aspire Core owns the `block.json` registrations, shared PHP renderers and read-only queries. The editor uses WordPress ServerSideRender with useful empty states. Native editorial content remains separate. The theme owns appearance; old shortcodes delegate to the same plugin renderers for compatibility and are absent from Home. No data definitions were duplicated.

## One-time setup

Already executed in the existing Docker installation:

```sh
docker compose exec -T wordpress php wp-content/themes/aspirecre/bin/setup-home.php
```

This CLI-only script reuses a Home page by slug/title or creates one, populates native blocks, publishes it, and sets `show_on_front=page` and `page_on_front`. A migration marker prevents subsequent runs from replacing editor changes. Any pre-existing content is backed up in post metadata and a revision before replacement. The script never runs during normal requests.

## Native block migration and verification

The previous homepage refactor was checkpointed as `081ba1c`. Home ID 74 was migrated in place with:

```sh
docker compose exec -T wordpress php wp-content/plugins/aspire-core/bin/migrate-home-blocks.php
```

Only the three Aspire Shortcode blocks are replaced. Other content remains byte-for-byte unchanged, with a metadata backup and revision. Re-running does not change an already migrated page. Patterns now insert the native blocks directly.

Editor styles provide a wider canvas, normal heading typography, and container-responsive service/Houston layouts that adapt when the Inspector and List View are open. Service words are not split, and fonts are not reduced to tiny sizes.

```sh
npm --prefix wp-content/plugins/aspire-core run build
docker compose exec -T wordpress php wp-content/themes/aspirecre/tests/blocks.php
docker compose exec -T wordpress php wp-content/themes/aspirecre/tests/homepage.php
docker compose exec -T wordpress php wp-content/plugins/aspire-core/tests/blocks.php
docker compose exec -T wordpress php wp-content/plugins/aspire-core/tests/integration.php
```

Results: 107 page/block checks, 22 homepage/data-render checks, 14 REST/migration checks, and 84 Core data assertions pass. PHP lint and JS build pass. Temporary test records are rolled back or removed. Home remains published and configured as the static front page. Docker and existing CPT/taxonomy/metadata definitions were not changed.

Browser checks confirmed the AspireCRE inserter category and all three blocks, named Home sections, no relevant console/validation errors, and preserved desktop/mobile frontend layout without horizontal overflow. The available in-app browser leaves WordPress’s blob-based editor iframe at about:blank; no Chrome connection is available. Therefore direct canvas typing, image replacement, live Inspector interaction and visual editor layout verification remain unverified in this environment. Server preview responses and count validation passed independently; these do not substitute for those UI checks.

The pre-existing duplicate `WP_DEBUG` warning comes from `wp-config.php`; it remains unchanged. Real photography and property/team records have not been imported.
