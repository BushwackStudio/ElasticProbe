<?php
/**
 * Plugin Name: Disable WordPress.org Lookups
 * Version:     1.0.0
 * Author:      10up Inc.
 * License:     GPLv2 or later
 *
 * @package ElasticProbe_Tests_E2e
 */

namespace ElasticProbe_Tests_E2e;

/**
 * Completely skip looking up translations
 *
 * @since  3.0
 * @return array
 */
function skip_translations_api() {
	return [
		'translations' => [],
	];
}

add_filter( 'translations_api', __NAMESPACE__ . '\skip_translations_api' );
