# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](https://keepachangelog.com/).

## [Unreleased]

<!--
### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security
### Developer
-->

## [5.1.4] - 2024-12-12

### Added
* New filter `ep_facet_selected_filters`. Props [@burhandodhy](https://github.com/burhandodhy) via [#3953](https://github.com/10up/ElasticPress/pull/3953).
* New filter `ep_disable_query_logging` to disable query logging. Props [@davidsword](https://github.com/davidsword) and [@rebeccahum](https://github.com/rebeccahum) via [#4019](https://github.com/10up/ElasticPress/pull/4019).
* New setting to Protect Content to use WP default order in admin. Props [@felipeelia](https://github.com/felipeelia) and [@realrellek](https://github.com/realrellek) via [#4028](https://github.com/10up/ElasticPress/pull/4028).

### Changed
* Apply ElasticPress filters to the requests in status and stats CLI commands. Props [@edpittol](https://github.com/edpittol) via [#4000](https://github.com/10up/ElasticPress/pull/4000).
* Autosuggest Endpoint field explanation. Props [@burhandodhy](https://github.com/burhandodhy) and [@felipeelia](https://github.com/felipeelia) via [#4009](https://github.com/10up/ElasticPress/pull/4009).
* Alignment of custom search results action icons. Props [@felipeelia](https://github.com/felipeelia) and [@anjulahettige](https://github.com/anjulahettige) via [#4020](https://github.com/10up/ElasticPress/pull/4020).
* Update all of our blocks apiVersion from 2 to 3, to indicate support for working in an iframed editor. Props [@dkotter](https://github.com/dkotter) and [@JakePT](https://github.com/JakePT) via [#4029](https://github.com/10up/ElasticPress/pull/4029).
* If using the new way to index meta, avoid querying distinct meta fields in the sync page. Props [@felipeelia](https://github.com/felipeelia) and [@majiix](https://github.com/majiix) via [#4033](https://github.com/10up/ElasticPress/pull/4033).
* Updated several composer and node packages. Node 20 is now the default version. Props [@felipeelia](https://github.com/felipeelia) via [#4039](https://github.com/10up/ElasticPress/pull/4039) and [#4043](https://github.com/10up/ElasticPress/pull/4043).
* Improve readability of sync output (MB/GB) and number formatting on the Health Status page. Props [@columbian-chris](https://github.com/columbian-chris) via [#4042](https://github.com/10up/ElasticPress/pull/4042).

### Fixed
* Hardcoded `tmp` path replaced with a dynamic value. Props [@burhandodhy](https://github.com/burhandodhy) via [#3962](https://github.com/10up/ElasticPress/pull/3962).
* Variable names and descriptions in the docblocks for `ep_formatted_args` and `ep_post_formatted_args`. Props [@barryceelen](https://github.com/barryceelen) via [#3977](https://github.com/10up/ElasticPress/pull/3977).
* Remove 'None' from Highlight tag list. Props [@burhandodhy](https://github.com/burhandodhy) via [#3979](https://github.com/10up/ElasticPress/pull/3979).
* [Facets] Incorrect link on description when not using a block theme. Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy) via [#3997](https://github.com/10up/ElasticPress/pull/3997).
* Deprecation warning in `strtotime()` call. Props [@felipeelia](https://github.com/felipeelia) and [@barryceelen](https://github.com/barryceelen) via [#3998](https://github.com/10up/ElasticPress/pull/3998).
* Special characters like `\` in search terms for both Autosuggest and Instant Results. Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy) via [#3999](https://github.com/10up/ElasticPress/pull/3999).
* [WooCommerce] Incompatibility when "Enable table usage" was enabled to filter the product catalog. Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy) via [#4002](https://github.com/10up/ElasticPress/pull/4002).
* Deprecation warning related to PluginPostStatusInfo. Props [@burhandodhy](https://github.com/burhandodhy) and [@felipeelia](https://github.com/felipeelia) via [#4008](https://github.com/10up/ElasticPress/pull/4008).
* [Custom Results] Inconsistent Reordering Issue. Props [@felipeelia](https://github.com/felipeelia), [@anjulahettige](https://github.com/anjulahettige), [@burhandodhy](https://github.com/burhandodhy) via [#4018](https://github.com/10up/ElasticPress/pull/4018) and [#4045](https://github.com/10up/ElasticPress/pull/4045).
* Update supported document file types in Documents feature summary. Props [@burhandodhy](https://github.com/burhandodhy) via [#4024](https://github.com/10up/ElasticPress/pull/4024).
* "Exclude from search results" to work in AJAX contexts. Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy) via [#4025](https://github.com/10up/ElasticPress/pull/4025).
* Retain CR & RD Labels Upon Saving Custom Search Result Posts. Props [@felipeelia](https://github.com/felipeelia) and [@anjulahettige](https://github.com/anjulahettige) via [#4020](https://github.com/10up/ElasticPress/pull/4020).
* Typo in "All filters" text domain. Props [@felipeelia](https://github.com/felipeelia) and [@arturomonge](https://github.com/arturomonge) via [#4031](https://github.com/10up/ElasticPress/pull/4031).
* Autosuggest GA tracking to work when ad blocks are enabled. The dataLayer.push() call now pushes a custom event called ep_autosuggest_click with ep_autosuggest_search_term and ep_autosuggest_clicked_url as custom parameters. Props [@felipeelia](https://github.com/felipeelia) and [@anjulahettige](https://github.com/anjulahettige) via [#4032](https://github.com/10up/ElasticPress/pull/4032).
* Delay `load_plugin_textdomain` to `init` and set a Domain Path. Props [@felipeelia](https://github.com/felipeelia) via [#4036](https://github.com/10up/ElasticPress/pull/4036).
* Only display the Exclude From Search checkbox if the post type supports `custom-fields`. Props [@felipeelia](https://github.com/felipeelia) and [@maartenhunink](https://github.com/maartenhunink) via [#4040](https://github.com/10up/ElasticPress/pull/4040).
* JS error when submit button is clicked without selecting a date. Props [@burhandodhy](https://github.com/burhandodhy) via [#4044](https://github.com/10up/ElasticPress/pull/4044).
* Deprecated warnings for margin style. Props [@burhandodhy](https://github.com/burhandodhy) via [#4046](https://github.com/10up/ElasticPress/pull/4046).

### Security
* Bumped `composer/composer` from 2.7.0 to 2.7.8. Props [@dependabot](https://github.com/dependabot) via [#3972](https://github.com/10up/ElasticPress/pull/3972).
* Bumped `symfony/process` from 6.4.8 to 6.4.14. Props [@dependabot](https://github.com/dependabot) via [#3996](https://github.com/10up/ElasticPress/pull/3996).

### Developer
* Tests use ES 8 by default. Props [@felipeelia](https://github.com/felipeelia) via [#4017](https://github.com/10up/ElasticPress/pull/4017).
* Update E2E tests to work properly with the iframed block editor. Props [@dkotter](https://github.com/dkotter) via [#4029](https://github.com/10up/ElasticPress/pull/4029).
* E2e tests for WP 6.6. Props [@felipeelia](https://github.com/felipeelia) via [#3959](https://github.com/10up/ElasticPress/pull/3959).
* E2e tests for WP 6.7. Props [@felipeelia](https://github.com/felipeelia) via [#4010](https://github.com/10up/ElasticPress/pull/4010).
* Unit Tests: Fail faster on requests we know will fail. Props [@felipeelia](https://github.com/felipeelia) via [#4047](https://github.com/10up/ElasticPress/pull/4047).
* E2e tests: Fix the debug-bar-elasticpress dependency of ElasticPress. Props [@felipeelia](https://github.com/felipeelia) via [#4048](https://github.com/10up/ElasticPress/pull/4048).

[Unreleased]: https://github.com/10up/ElasticPress/compare/trunk...develop
[5.1.4]: https://github.com/10up/ElasticPress/compare/5.1.3...5.1.4
