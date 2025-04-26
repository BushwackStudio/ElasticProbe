<?php
/**
 * WPProbe test functions
 *
 * @package wpprobe
 */

namespace WPProbeTest\Functions;

use WPProbe;

/**
 * Get all sites, count indexes
 *
 * @return array total index count with last blog id to manipulate blog with an index
 */
function count_indexes() {
	$sites = WPProbe\Utils\get_sites();

	$last_blog_id_with_index = 0;

	$count_indexes = 0;
	foreach ( $sites as $site ) {
		if ( WPProbe\Indexables::factory()->get( 'post' )->index_exists( $site['blog_id'] ) ) {
			++$count_indexes;
			$last_blog_id_with_index = $site['blog_id'];
		}
	}

	return array(
		'total_indexes'           => $count_indexes,
		'last_blog_id_with_index' => $last_blog_id_with_index,
	);
}
