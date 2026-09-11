# AspireCRE Gutenberg homepage

## Edit the homepage

Open **Pages → Home → Edit**. Home is a published WordPress Page and the static front page. `front-page.php` runs the normal page loop and `the_content()` between the existing theme header and footer.

Gutenberg List View contains seven named sections: Hero, Property Finder, Featured Properties, Services, Houston / Why Aspire, Team, and Final CTA. Editorial text uses native Paragraph and Heading blocks; primary buttons use Buttons/Button blocks; other CTA links are editable Paragraph links. Layout uses native Group blocks with the existing AspireCRE CSS classes. There are no Custom HTML blocks, custom React blocks, or page builders.

The hero and Houston areas each contain a native Image block. Choose Upload or Media Library inside that block and set its alt text normally. Until a real image is selected, the approved labelled photographic fallback remains visible on the frontend; selecting an image hides that fallback automatically. Images are now owned by the page, not Customizer settings.

Reusable section patterns are available under **Patterns → AspireCRE**, sourced from `patterns/home-*.php`. Inserting one creates normal editable blocks; later pattern-file changes do not overwrite page edits. Preserve the section CSS classes when editing content to retain the approved layout.

## Dynamic sections

Three native Shortcode blocks render read-only dynamic content:

- `[aspire_property_finder]`: existing form and taxonomy options, GET to `/properties/`.
- `[aspire_featured_properties]`: property cards/empty state only. Eyebrow, heading and View All link are separate editable blocks.
- `[aspire_team_members]`: team cards/empty state only. Introduction and CTA are separate editable blocks.

The shortcodes reuse the existing queries and Aspire Core's `_aspire_` metadata. They do not write metadata or define duplicate fields. Real properties/team members remain managed through their CPT screens. The property metric order and prioritization are unchanged. Header, footer, navigation, logo, contact details and verified legal links remain theme-controlled. No directory, filtering engine or other pages were built.

## One-time setup

Already executed in the existing Docker installation:

```sh
docker compose exec -T wordpress php wp-content/themes/aspirecre/bin/setup-home.php
```

This CLI-only script reuses a Home page by slug/title or creates one, populates native blocks, publishes it, and sets `show_on_front=page` and `page_on_front`. A migration marker prevents subsequent runs from replacing editor changes. Any pre-existing content is backed up in post metadata and a revision before replacement. The script never runs during normal requests.

## Architecture correction files

Changed: `front-page.php`, `functions.php`, `style.css`, `inc/homepage.php`, `inc/customizer.php`, the property/team collection parts, and this README.

Added: `inc/blocks.php`, `assets/editor.css`, seven native section patterns, `bin/setup-home.php`, `tests/blocks.php`.

Removed obsolete editorial PHP parts: `template-parts/home/hero.php`, `services.php`, `houston.php`, `contact.php`. The remaining finder/properties/team parts are exclusively shortcode renderers. There is one homepage implementation: the Home page's saved blocks.

## Verification

```sh
docker compose exec -T wordpress sh -c 'find wp-content/themes/aspirecre -name "*.php" -exec php -l {} \;'
docker compose exec -T wordpress php wp-content/themes/aspirecre/tests/blocks.php
docker compose exec -T wordpress php wp-content/themes/aspirecre/tests/homepage.php
docker compose exec -T wordpress php wp-content/plugins/aspire-core/tests/integration.php
```

104 read-only block/migration checks, 19 existing homepage checks, and 84 existing Core assertions passed. Existing fixture-based tests roll back or remove their temporary records; no property/team records remain. Aspire Core has no source changes. PHP lint and Git whitespace checks pass. Browser verification covered Gutenberg's named List View and heading Outline, no block-validation errors, desktop/mobile layouts and no horizontal overflow. The in-app browser's Gutenberg iframe canvas did not display, so direct canvas typing was not verified there.

The existing `wp-config.php` still emits the pre-existing duplicate `WP_DEBUG` warning, outside the theme. No custom-theme PHP errors were observed. Real photography, logo, contact/legal information and published property/team records remain to be supplied.
