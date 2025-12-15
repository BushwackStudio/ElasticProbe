<?php
/**
 * Plugin Name: Sync Error - Specific Post
 * Description: Cause an error during sync, for test purposes, for a specific post.
 * Version:     1.0.0
 * Author:      10up Inc.
 * License:     GPLv2 or later
 *
 * @package ElasticProbe_Tests_E2e
 */

namespace ElasticProbe\Tests\E2E\SyncErrorSpecificPost;

/**
 * Throw an exception for the specific post.
 *
 * @param array $args The arguments for the post sync.
 * @return array The arguments for the post sync.
 * @throws \Exception If the post ID is 1.
 */
function throw_exception( $args ) {
	if ( 1 === $args['post_id'] ) {
		throw new \Exception( 'Something went wrong.' );
	}
	return $args;
}
add_filter( 'eprobe_post_sync_args_post_prepare_meta', __NAMESPACE__ . '\throw_exception' );
