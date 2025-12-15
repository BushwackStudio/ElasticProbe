<?php
/**
 * Plugin Name: Custom Autosuggest Placeholder
 * Description: Changes the default autosuggest placeholder text for testing purposes
 * Author:      10up Inc.
 * License:     GPLv2 or later
 *
 * @package ElasticProbe_Tests_E2e
 */

/**
 * Filter the autosuggest placeholder text
 */
add_filter(
	'eprobe_autosuggest_query_placeholder',
	function () {
		return 'eprobe_autosuggest_custom_placeholder';
	}
);
