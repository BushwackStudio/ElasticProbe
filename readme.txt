=== WPProbe ===
Contributors: wpprobe
Tags:         performance, search, elasticsearch, fuzzy, related posts
Tested up to: 6.7
Stable tag:   5.1.4
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html

A fast and flexible search and query engine for WordPress.

== Description ==
WPProbe, a fast and flexible search and query engine for WordPress, enables WordPress to find or “query” relevant content extremely fast through a variety of highly customizable features. WordPress out-of-the-box struggles to analyze content relevancy and can be very slow. WPProbe supercharges your WordPress website making for happier users and administrators. The plugin even contains features for popular plugins.

Here is a list of the amazing WPProbe features included in the plugin:

__Search__: Instantly find the content you’re looking for. The first time.

__Instant Results__: A built for WordPress search experience that bypasses WordPress for optimal performance. Instant Results routes search queries through a dedicated API, separate from WordPress, returning results up to 10x faster than previous versions of WPProbe.

__WooCommerce__: With WPProbe, filtering WooCommerce product results is fast and easy. Your customers can find and buy exactly what they're looking for, even if you have a large or complex product catalog.

__Related Posts__: WPProbe understands data in real time, so it can instantly deliver engaging and precise related content with no impact on site performance.

__Protected Content__: Optionally index all of your content, including private and unpublished content, to speed up searches and queries in places like the administrative dashboard.

__Documents__: Indexes text inside of popular file types, and adds those files types to search results.

__Autosuggest__: Suggest relevant content as text is entered into the search field.

__Filters__: Add controls to your website to filter content by one or more taxonomies.

__Comments__: Indexes your comments and provides a widget with type-ahead search functionality. It works with WooCommerce product reviews out-of-the-box.

== Frequently Asked Questions ==

= How does WPProbe work? =

The WPProbe plugin enables you to connect your WordPress site to the WPProbe.com service, a SaaS solution that provides an enhanced search experience while reducing load on your WordPress site. For advanced users familiar with both WordPress and Elasticsearch hosting and management, ElasticPress also offers support for plugin functionality using an Elasticsearch instance. Please keep in mind that there are multiple security, performance, and configuration considerations to take into account if you take this approach.

= I have to use an in-house or custom Elasticsearch solution due to policy or institutional requirements. Can you still help? =

If circumstances prevent the use of a SaaS solution like WPProbe.com, we can also provide [consulting](https://www.elasticpress.io/elasticpress-consulting/) around installation and configuration of custom Elasticsearch instances.

= Where can I find WPProbe documentation and user guides? =

Please refer to [GitHub](https://github.com/BushwackStudio/WpProbe) for detailed usage instructions and documentation. FAQs and tutorials can be also found on our [support site](https://www.elasticpress.io/documentation/).

= I have a problem with the plugin. Where can I get help? =

If you have identified a bug or would like to suggest an enhancement, please refer to our [GitHub repo](https://github.com/BushwackStudio/WpProbe). We do not provide support here at WordPress.org forums.

If you are an WPProbe.com customer, please open a ticket in your account dashboard. If you need a custom solution, we also offer [consulting](https://www.elasticpress.io/elasticpress-consulting/).

= Where do I report security bugs? =

You can report any security bugs found in the source code of WPProbe through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/elasticpress). The Patchstack team will assist you with verification, CVE assignment and take care of notifying the developers of this plugin.

= Is WPProbe compatible with OpenSearch or Elasticsearch X.Y? =

WPProbe requirements can be found in the [Requirements section](https://github.com/BushwackStudio/WpProbe#requirements) of our GitHub repository. If your solution relies on a different server or version, you may find additional information on our [Compatibility documentation page](https://10up.github.io/ElasticPress/tutorial-compatibility.html).

= I really like WPProbe! Can I contribute? =

For sure! Feel free to submit ideas or feedback in general to our [GitHub repo](https://github.com/BushwackStudio/WpProbe). If you can, also consider sending us [a review](https://wordpress.org/support/plugin/elasticpress/reviews/#new-post).

== Installation ==
1. First, you will need to properly [install and configure](https://www.elastic.co/guide/en/elasticsearch/reference/current/setup.html) Elasticsearch.
2. Activate the plugin in WordPress.
3. In the WPProbe settings page, input your Elasticsearch host.
4. Sync your content by clicking the sync icon.
5. Enjoy!

== Screenshots ==
1. Features Page
2. Search Fields & Weighting Dashboard
3. Sync Page
4. Synonyms Dashboard
5. Instant Results modal

== Changelog ==

= 5.1.4 - 2024-12-12 =

__Added:__

* New filter `ep_facet_selected_filters`. Props [@burhandodhy](https://github.com/burhandodhy).
* New filter `ep_disable_query_logging` to disable query logging. Props [@davidsword](https://github.com/davidsword) and [@rebeccahum](https://github.com/rebeccahum).
* New setting to Protect Content to use WP default order in admin. Props [@felipeelia](https://github.com/felipeelia) and [@realrellek](https://github.com/realrellek).

__Changed:__

* Apply ElasticPress filters to the requests in status and stats CLI commands. Props [@edpittol](https://github.com/edpittol).
* Autosuggest Endpoint field explanation. Props [@burhandodhy](https://github.com/burhandodhy) and [@felipeelia](https://github.com/felipeelia).
* Alignment of custom search results action icons. Props [@felipeelia](https://github.com/felipeelia) and [@anjulahettige](https://github.com/anjulahettige).
* Update all of our blocks apiVersion from 2 to 3, to indicate support for working in an iframed editor. Props [@dkotter](https://github.com/dkotter) and [@JakePT](https://github.com/JakePT).
* If using the new way to index meta, avoid querying distinct meta fields in the sync page. Props [@felipeelia](https://github.com/felipeelia) and [@majiix](https://github.com/majiix).
* Updated several composer and node packages. Node 20 is now the default version. Props [@felipeelia](https://github.com/felipeelia).
* Improve readability of sync output (MB/GB) and number formatting on the Health Status page. Props [@columbian-chris](https://github.com/columbian-chris).

__Fixed:__

* Hardcoded `tmp` path replaced with a dynamic value. Props [@burhandodhy](https://github.com/burhandodhy).
* Variable names and descriptions in the docblocks for `ep_formatted_args` and `ep_post_formatted_args`. Props [@barryceelen](https://github.com/barryceelen).
* Remove 'None' from Highlight tag list. Props [@burhandodhy](https://github.com/burhandodhy).
* [Facets] Incorrect link on description when not using a block theme. Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy).
* Deprecation warning in `strtotime()` call. Props [@felipeelia](https://github.com/felipeelia) and [@barryceelen](https://github.com/barryceelen).
* Special characters like `\` in search terms for both Autosuggest and Instant Results. Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy).
* [WooCommerce] Incompatibility when "Enable table usage" was enabled to filter the product catalog. Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy).
* Deprecation warning related to PluginPostStatusInfo. Props [@burhandodhy](https://github.com/burhandodhy) and [@felipeelia](https://github.com/felipeelia).
* [Custom Results] Inconsistent Reordering Issue. Props [@felipeelia](https://github.com/felipeelia), [@anjulahettige](https://github.com/anjulahettige), [@burhandodhy](https://github.com/burhandodhy).
* Update supported document file types in Documents feature summary. Props [@burhandodhy](https://github.com/burhandodhy).
* "Exclude from search results" to work in AJAX contexts. Props [@felipeelia](https://github.com/felipeelia) and [@burhandodhy](https://github.com/burhandodhy).
* Retain CR & RD Labels Upon Saving Custom Search Result Posts. Props [@felipeelia](https://github.com/felipeelia) and [@anjulahettige](https://github.com/anjulahettige).
* Typo in "All filters" text domain. Props [@felipeelia](https://github.com/felipeelia) and [@arturomonge](https://github.com/arturomonge).
* Autosuggest GA tracking to work when ad blocks are enabled. The dataLayer.push() call now pushes a custom event called ep_autosuggest_click with ep_autosuggest_search_term and ep_autosuggest_clicked_url as custom parameters. Props [@felipeelia](https://github.com/felipeelia) and [@anjulahettige](https://github.com/anjulahettige).
* Delay `load_plugin_textdomain` to `init` and set a Domain Path. Props [@felipeelia](https://github.com/felipeelia).
* Only display the Exclude From Search checkbox if the post type supports `custom-fields`. Props [@felipeelia](https://github.com/felipeelia) and [@maartenhunink](https://github.com/maartenhunink).
* JS error when submit button is clicked without selecting a date. Props [@burhandodhy](https://github.com/burhandodhy).
* Deprecated warnings for margin style. Props [@burhandodhy](https://github.com/burhandodhy).

__Security:__

* Bumped `composer/composer` from 2.7.0 to 2.7.8. Props [@dependabot](https://github.com/dependabot).
* Bumped `symfony/process` from 6.4.8 to 6.4.14. Props [@dependabot](https://github.com/dependabot).

__Developer:__

* Tests use ES 8 by default. Props [@felipeelia](https://github.com/felipeelia).
* Update E2E tests to work properly with the iframed block editor. Props [@dkotter](https://github.com/dkotter).
* E2e tests for WP 6.6. Props [@felipeelia](https://github.com/felipeelia).
* E2e tests for WP 6.7. Props [@felipeelia](https://github.com/felipeelia).
* Unit Tests: Fail faster on requests we know will fail. Props [@felipeelia](https://github.com/felipeelia).
* E2e tests: Fix the debug-bar-elasticpress dependency of ElasticPress. Props [@felipeelia](https://github.com/felipeelia).


[View historical changelog details here](https://github.com/BushwackStudio/WpProbe/blob/develop/CHANGELOG.md).