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

## Fixed in `fix/role-checks-and-landmine`

- **Role checks no longer break for multisite super admins.** Both the tabs
  filter and `enqueue_sortable_language_script()` tested
  `in_array( 'administrator', wp_get_current_user()->roles )`. On multisite a
  super admin usually holds no role on an individual subsite, so `roles` was
  empty, the test failed, and the network administrator was served the
  white-labelled, tab-hidden UI. Both now call
  `bv_trp_user_sees_full_admin()`, which is `current_user_can( 'manage_options' )`.
  Verified: subsite admin → full UI, editor → white-labelled, no caps →
  white-labelled. Only super admins change behaviour.
- **`bv_remove_translatepress_tabs()` no longer assumes `$tabs[0]`.** An empty
  or non-zero-indexed array was an undefined-key warning and a broken settings
  screen; it now returns the input unchanged.
- **Removed `bv_rename_translatepress()`.** Dead — its `add_action` was
  commented out — and it would have fatalled on PHP 8 if enabled, because
  `property_exists( $submenu, … )` rejects an array argument with a
  `TypeError`. Nothing referenced it and the tabs filter supersedes it.
- **Guarded the external dependencies.** `bv_get_required_level_status()`
  (voyage) is behind `function_exists()`, and `BV_TRANSLATION_LIMIT_PREMIUM` /
  `BV_TRANSLATION_LIMIT_BASIC` behind `defined()` — an undefined constant is a
  fatal on PHP 8, and this plugin is active on every subsite.
- **The site-wide override handles an empty option.**
  `get_option( 'bv_translation_limit', null ) ?? $limit` falls through for a
  missing option but not for one saved as `''`, which is not null — the limit
  silently became `''`. It now requires a numeric value.

## Known issues

- **The plan limit is still enforced only in the browser.** `$language_limit`
  is shipped to JS via `wp_localize_script` and
  `assets/js/trp-sortable-languages.js` merely hides `#trp-new-language` once
  the row count reaches it. Nothing validates the language count when
  TranslatePress settings are saved, so a basic-plan member can add unlimited
  languages with devtools or a direct POST.

  **Deliberately not fixed here.** Adding server-side enforcement would
  immediately affect any existing member whose saved language count already
  exceeds their tier — their settings would start being rejected or silently
  truncated on the next save. That needs a decision about what happens to
  those members (grandfather, warn, truncate) and an audit of current usage,
  not a quiet code change.
- **Duplicated upstream code.** `class-extra-languages.php` and
  `partials/language-selector-pro.php` are forks of the add-on pinned to
  version 1.0.1, and the plugin advertises itself for EDD updates from
  translatepress.com — an upstream update would overwrite the beauVoyage
  changes.
- **`trp_el_run()` and `TRP_EL_PLUGIN_DIR` collide with
  `tp-add-on-extra-languages`**, which must stay deactivated.
- A commented-out `bv_translatepress_menu_page()` block remains in
  `class-extra-languages.php` (lines 22-59), superseded by the tabs filter.

## License

GPL-2.0-or-later, as inherited from TranslatePress. See [LICENSE](LICENSE).

## Credits

Upstream **TranslatePress Extra Languages Add-on**, Copyright 2017 Cozmoslabs
(Razvan Mocanu). beauVoyage modifications by the Lai Consulting Team.
Previously hosted at `gitlab.com/beauvoyage/bv_translatepress_menu`.
