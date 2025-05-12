=== ElasticProbe ===
Contributors: bushwackstudio, nshayanfar
Tags:         performance, search, elasticsearch, fuzzy, related posts
Tested up to: 6.8
Stable tag:   0.2.0
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html

A fast and flexible search and query engine for WordPress.

== Description ==
ElasticProbe, a fast and flexible search and query engine for WordPress, enables WordPress to find or “query” relevant content extremely fast through a variety of highly customizable features. WordPress out-of-the-box struggles to analyze content relevancy and can be very slow. ElasticProbe supercharges your WordPress website making for happier users and administrators. The plugin even contains features for popular plugins.

Here is a list of the amazing ElasticProbe features included in the plugin:

__Search__: Instantly find the content you’re looking for. Even when you misspell.

__WooCommerce__: With ElasticProbe, filtering WooCommerce product results is fast and easy. Your customers can find and buy exactly what they're looking for, even if you have a large or complex product catalog.

__Related Posts__: ElasticProbe understands data in real time, so it can instantly deliver engaging and precise related content with no impact on site performance.

__Protected Content__: Optionally index all of your content, including private and unpublished content, to speed up searches and queries in places like the administrative dashboard.

__Filters__: Add controls to your website to filter content by one or more taxonomies.

__Comments__: Indexes your comments and provides a widget with type-ahead search functionality. It works with WooCommerce product reviews out-of-the-box.

== Frequently Asked Questions ==

= How does ElasticProbe work? =

The ElasticProbe plugin enables you to connect your WordPress site to the WPProbe.com service, a SaaS solution that provides an enhanced search experience while reducing load on your WordPress site.

= Where can I find ElasticProbe documentation and user guides? =

Please refer to [GitHub](https://github.com/BushwackStudio/ElasticProbe) for detailed usage instructions and documentation.

= I have a problem with the plugin. Where can I get help? =

If you have identified a bug or would like to suggest an enhancement, please refer to our [GitHub repo](https://github.com/BushwackStudio/ElasticProbe). We do not provide support here at WordPress.org forums.

= Is ElasticProbe compatible with OpenSearch or Elasticsearch X.Y? =

ElasticProbe requirements can be found in the [Requirements section](https://github.com/BushwackStudio/ElasticProbe#requirements) of our GitHub repository.

= I really like ElasticProbe! Can I contribute? =

For sure! Feel free to submit ideas or feedback in general to our [GitHub repo](https://github.com/BushwackStudio/ElasticProbe).

== Installation ==
1. First, you will need to properly [install and configure](https://www.elastic.co/guide/en/elasticsearch/reference/current/setup.html) Elasticsearch.
2. Activate the plugin in WordPress.
3. In the ElasticProbe settings page, input your Elasticsearch host.
4. Sync your content by clicking the sync icon.
5. Enjoy!

== Screenshots ==
1. Features Page
2. Search Fields & Weighting Dashboard
3. Sync Page
4. Synonyms Dashboard

== Changelog ==

= 0.2.1 - 2024-05-12 =

__Added:__


__Changed:__

* Wordpress screenshots

__Fixed:__

* Some hosted PHP tests


__Security:__


__Developer:__


= 0.2.0 - 2024-05-10 =

__Added:__

* Cloned tests of ES for the hosted service

__Changed:__

* Plugin name

__Fixed:__

* Some tests which failed when running against the hosted service


__Security:__


__Developer:__


= 0.1.1 - 2024-04-26 =

__Added:__


__Changed:__

* Bumped the tested wordpress version and plugin version

__Fixed:__


__Security:__


__Developer:__


= 0.1.0 - 2024-04-23 =

__Added:__


__Changed:__

* Changed the code to reflect the new architecture.

__Fixed:__


__Security:__


__Developer:__


[View historical changelog details here](https://github.com/BushwackStudio/ElasticProbe/blob/dev/CHANGELOG.md).