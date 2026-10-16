# SwissChess WordPress Plugin

![Coverage](https://raw.githubusercontent.com/kindermaenner/sonar-badges/main/badges/swisschess-wp-plugin/coverage.svg)
![Bugs](https://raw.githubusercontent.com/kindermaenner/sonar-badges/main/badges/swisschess-wp-plugin/bugs.svg)
![Code Smells](https://raw.githubusercontent.com/kindermaenner/sonar-badges/main/badges/swisschess-wp-plugin/code_smells.svg)
![Security Hotspots](https://raw.githubusercontent.com/kindermaenner/sonar-badges/main/badges/swisschess-wp-plugin/security_hotspots.svg)

swisschess-wp-plugin is a WordPress plugin that scans a folder for SwissChess HTML exports and turns them into WordPress content with configurable templates.

It currently creates and updates three kinds of output:

1. A static tournament page (example see [Stadtmeisterschaft 2026](https://sg-koenigslutter.de/stadtmeisterschaft-koenigslutter-2026/))
2. A post for a newly drawn round (example see [Stadtmeisterschaft 2026 Runde 2](https://sg-koenigslutter.de/2026/06/14/stadtmeisterschaft-koenigslutter-2026-runde-2-ausgelost/))
3. A post with the final tournament results

## How It Works

1. SwissChess export files are placed in the import folder.
2. The plugin scans the folder.
3. It parses participants, rankings, and pairings.
4. It fills the configured templates.
5. It creates or updates the matching WordPress content.

The default import folder is:

```text
wp-content/uploads/swisschess
```

## Installation

1. Download the latest swisschess-wp-plugin ZIP:

   ```text
   https://kindermaenner.de/plugin-updates/swisschess/latest/swisschess-wp-plugin.zip
   ```

2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Select the downloaded ZIP, choose **Install Now**, and then **Activate Plugin**.
4. Open the **Swiss Chess** settings page in the WordPress admin.
5. Configure the API key, author, and template names.
6. Put the SwissChess HTML export files into the import folder.

### Updates

New releases are offered through the WordPress plugin updater. To check manually, open **Dashboard → Updates** (or **Plugins → Installed Plugins**) and check for updates. When a swisschess-wp-plugin update is available, choose **Update now**. Back up the site before updating, as with any plugin.

## Configuration

The plugin adds a new menu point "Swiss Chess" in the admin dashboard. The following settings are available there:

| Setting | Description |
| --- | --- |
| `swisschess_author` | WordPress user ID used as the author for generated posts |
| `swisschess_template_static_page` | Template page used for the static tournament page |
| `swisschess_template_next_round_post` | Template post used for the new round announcement |
| `swisschess_template_final_results_post` | Template post used for the final results post |
| `swisschess_api_key` | API key used to secure the REST endpoint |
| `swisschess_delete_after_import` | Deletes imported HTML files after a successful scan |

It is recommended NOT to use an administrator account as swisschess_author.

## Output Rules

### 1. Static Tournament Page

The static tournament page is always created or updated on every scan.

Template rules:

- The template is loaded from `swisschess_template_static_page`.
- The page content is rendered with these placeholders:
	- `{{tournament_name}}`
	- `{{participants}}`
	- `{{ranking}}`
	- `{{all_pairings}}`
- Existing static pages are identified through a marker meta key and updated instead of being recreated.
- Template meta data is copied to the generated page.
- The featured image (`_thumbnail_id`) is copied from the template.
- Categories are copied from the template, except the category named `Template` or `template`.
- The generated static page is removed from navigation menus if WordPress has added it automatically.
- Table styles are loaded as frontend stylesheets; CSS is not stored in the page content.

### 2. New Round Post

A post for a newly drawn round is created or updated when the last round is considered empty.

A round counts as empty when all boards are either:

- `-`
- bye-style entries such as `+ - -`

Template rules:

- The template is loaded from `swisschess_template_next_round_post`.
- The content supports these placeholders:
	- `{{tournament_name}}`
	- `{{round_no_actual}}`
	- `{{round_no_last}}`
	- `{{pairings_actual_round}}`
	- `{{pairings_last_round}}`
- Existing posts are identified through a marker meta key and updated instead of being recreated.
- Template meta data is copied to the generated post.
- The featured image (`_thumbnail_id`) is copied from the template.
- Categories are copied from the template, except the category named `Template` or `template`.

### 3. Final Results Post

A final results post is created or updated when all rounds are complete.

A round counts as complete only if every board has a non-empty result different from `-`.

Template rules:

- The template is loaded from `swisschess_template_final_results_post`.
- The content supports these placeholders:
	- `{{tournament_name}}`
	- `{{participants}}`
	- `{{ranking}}`
	- `{{all_pairings}}`
- Existing posts are identified through a marker meta key and updated instead of being recreated.
- Template meta data is copied to the generated post.
- The featured image (`_thumbnail_id`) is copied from the template.
- Categories are copied from the template, except the category named `Template` or `template`.

## Template Placeholder Overview

### Static page

- `{{tournament_name}}`
- `{{participants}}`
- `{{ranking}}`
- `{{all_pairings}}`

### New round post

- `{{tournament_name}}`
- `{{round_no_actual}}`
- `{{round_no_last}}`
- `{{pairings_actual_round}}`
- `{{pairings_last_round}}`

### Final results post

- `{{tournament_name}}`
- `{{participants}}`
- `{{ranking}}`
- `{{all_pairings}}`

## REST API / Cron Job

The plugin exposes a REST endpoint that can be used by a provider cron job to trigger a scan.

Endpoint:

```text
POST /wp-json/swisschess/v1/scan
```

Authentication:

- Preferred: send the API key in the `X-MB-Key` header
- Cron-friendly fallback: pass the key as query parameter `key`

Example:

```text
https://kindermaenner.de/wp-json/swisschess/v1/scan?key=SC_API_KEY
```

Example curl call:

```bash
curl -X POST "https://kindermaenner.de/wp-json/swisschess/v1/scan?key=SC_API_KEY"
```

Response shape:

- The REST endpoint always returns a wrapped response.
- The outer response contains `success: true`.
- The runner result is returned in `data`.
- Inspect `data.success` to see whether the scan itself completed successfully.
- Non-fatal problems are returned in `data.warnings`.

Typical error situations:

- Missing API key configuration returns `rest_misconfigured`.
- Wrong API key returns `rest_forbidden`.

## Testing

The project uses Pest for unit tests.

Run the full test suite with:

```bash
vendor/bin/pest
```

Useful test areas:

- `tests/Unit/Api/ApiTest.php`
- `tests/Unit/Runner/SwissChessRunnerRunTest.php`
- `tests/Unit/Runner/SwissChessRunnerScenarioTest.php`
- `tests/Unit/Output/StaticTournamentPageTest.php`
- `tests/Unit/Output/NextRoundPublishedPostTest.php`
- `tests/Unit/Output/FinalResultsPublishedPostTest.php`

## Notes

- The plugin scans the default SwissChess import folder under the WordPress uploads directory.
- Generated posts are reused when the corresponding marker meta key already exists.
- Template posts/pages are meant to define the layout; swisschess-wp-plugin fills the placeholders and copies the relevant template data.
- When `swisschess_delete_after_import` is enabled, imported HTML files are removed after a successful scan.
