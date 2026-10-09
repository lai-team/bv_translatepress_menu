# BV TranslatePress Menu

A fork of the commercial **TranslatePress Extra Languages** add-on by
Cozmoslabs, modified to cap how many translation languages a member may add
based on their subscription plan, and to white-label the TranslatePress UI for
non-administrators.

It **replaces** the stock add-on: both define `trp_el_run()` and
`TRP_EL_PLUGIN_DIR`, so `tp-add-on-extra-languages` must stay deactivated or
the site fatals with a duplicate function declaration.

## Requirements

- WordPress multisite
- **TranslatePress** (`TRP_Translate_Press`, `TRP_PLUGIN_VERSION`) — the class
  is guarded, the constant is not
- **`voyage`** plugin — `bv_get_required_level_status()`
  (`voyage/voyage.php:833`), unguarded
- A valid TranslatePress licence in the `trp_license_key` option, for updates

Constants read from `wp-config.php`, both unguarded:
`BV_TRANSLATION_LIMIT_PREMIUM`, `BV_TRANSLATION_LIMIT_BASIC`.

## What is actually modified

Most of this repository is the upstream add-on verbatim. Only four files
differ, and `includes/`, `partials/license-settings-page.php` are byte-identical
copies of Cozmoslabs code:

| File | Change |
| --- | --- |
| `bv_translatepress_menu.php` | renamed plugin header; adds `bv_remove_translatepress_tabs()` and the dead `bv_rename_translatepress()` |
| `class-extra-languages.php` | computes `$language_limit` and `$whitelabel`, passes both to JS |
| `assets/js/trp-sortable-languages.js` | hides the add-language button past the limit; hides tabs and update notices when white-labelled |
| `partials/language-selector-pro.php` | minor markup changes |

The limit itself:

```php
$language_limit = bv_get_required_level_status( $user_id, get_user_meta( $user_id, 'private_plan', true ) )
    ? BV_TRANSLATION_LIMIT_PREMIUM
    : BV_TRANSLATION_LIMIT_BASIC;
$language_limit = get_option( 'bv_translation_limit', null ) ?? $language_limit;
```

`bv_translation_limit`, if set, overrides the per-plan value for everyone.

## Known issues

Recorded, not fixed — this initial commit is a faithful import of what is
deployed.

- **The plan limit is enforced only in the browser.** `$language_limit` is
  shipped to JS via `wp_localize_script` and
  `assets/js/trp-sortable-languages.js` merely hides `#trp-new-language` once
  the row count reaches it. Nothing validates the language count when
  TranslatePress settings are saved, so a basic-plan member can add unlimited
  languages with devtools or a direct POST. This is a paywall bypass, not a
  data disclosure, but it means the tier is advisory. Real enforcement needs a
  server-side check on the `trp_settings` save.
- **White-labelling is cosmetic for the same reason.** `hide_tabs()` hides the
  tab strip with jQuery; the other settings tabs and their pages still exist
  and remain reachable by URL. `bv_remove_translatepress_tabs()`
  (`bv_translatepress_menu.php:62`) does filter the tab list server-side, which
  is the real control — the JS is belt-and-braces on top of it.
- **Role checks break for multisite super admins.** Both
  `bv_remove_translatepress_tabs()` and `enqueue_sortable_language_script()`
  test `in_array( 'administrator', wp_get_current_user()->roles )`. On
  multisite a super admin often holds **no role on an individual subsite**, so
  `roles` is empty, the test fails, and the network administrator gets the
  white-labelled, tab-hidden UI. `current_user_can( 'manage_options' )` or
  `is_super_admin()` is the correct test.
- **`bv_remove_translatepress_tabs()` assumes `$tabs[0]` exists**
  (`bv_translatepress_menu.php:67`). If the tabs array is ever empty or not
  zero-indexed this is an undefined-key warning plus a broken settings screen.
- **`bv_rename_translatepress()` would fatal on PHP 8 if enabled.**
  `bv_translatepress_menu.php:74` calls
  `property_exists( $submenu, 'options-general.php' )` where `$submenu` is an
  **array**; on PHP 8 `property_exists()` rejects an array argument with a
  `TypeError`. It should be `isset( $submenu['options-general.php'] )`. The
  `add_action` on line 70 is commented out, so this is dormant — a landmine,
  not a live bug.
- **`get_option( 'bv_translation_limit', null ) ?? $language_limit`** falls
  through correctly for a missing option, but an option saved as an empty
  string is not null, so the limit silently becomes `''`.
- **Duplicated upstream code.** `class-extra-languages.php` and
  `partials/language-selector-pro.php` are forks of the add-on pinned to
  version 1.0.1. The plugin advertises itself for updates from
  translatepress.com via EDD, so an upstream update would overwrite the
  beauVoyage changes. `bv_admin_widgets` carried a third copy of the same
  files until it was cleaned up.
- A commented-out `bv_translatepress_menu_page()` block sits in
  `class-extra-languages.php` (lines 22-59), superseded by the tabs filter.

## License

GPL-2.0-or-later, as inherited from TranslatePress. See [LICENSE](LICENSE).

## Credits

Upstream **TranslatePress Extra Languages Add-on**, Copyright 2017 Cozmoslabs
(Razvan Mocanu). beauVoyage modifications by the Lai Consulting Team.
Previously hosted at `gitlab.com/beauvoyage/bv_translatepress_menu`.
