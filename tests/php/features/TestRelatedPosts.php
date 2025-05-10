<?php
/**
 * Test related posts feature
 *
 * @package elasticprobe
 */

namespace ElasticProbeTest;

use ElasticProbe;

/**
 * Related post test class
 */
class TestRelatedPosts extends BaseTestCase {

	/**
	 * Setup each test.
	 *
	 * @since 2.1
	 * @group related_posts
	 */
	public function set_up() {
		global $wpdb;
		parent::set_up();
		$wpdb->suppress_errors();

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( $admin_id );

		ElasticProbe\Elasticsearch::factory()->delete_all_indices();
		ElasticProbe\Indexables::factory()->get( 'post' )->put_mapping();

		ElasticProbe\Indexables::factory()->get( 'post' )->sync_manager->reset_sync_queue();

		$this->setup_test_post_type();
	}

	/**
	 * Clean up after each test. Reset our mocks
	 *
	 * @since 2.1
	 */
	public function tear_down() {
		parent::tear_down();

		$this->fired_actions = array();
	}

	/**
	 * Log action usage for tests
	 *
	 * @since  2.1
	 */
	public function action_ep_related_html_attached() {
		$this->fired_actions['ep_related_html_attached'] = true;
	}

	/**
	 * Test for related post args filter
	 *
	 * @group related_posts
	 */
	public function testFindRelatedPostFilter() {
		$post_id = $this->ep_factory->post->create( array( 'post_content' => 'findme test 1' ) );
		$this->ep_factory->post->create( array( 'post_content' => 'findme test 2' ) );
		$this->ep_factory->post->create(
			array(
				'post_content' => 'findme test 3',
				'post_type'    => 'page',
			)
		);

		ElasticProbe\Elasticsearch::factory()->refresh_indices();

		ElasticProbe\Features::factory()->activate_feature( 'related_posts' );

		ElasticProbe\Features::factory()->setup_features();

		$related = ElasticProbe\Features::factory()->get_registered_feature( 'related_posts' )->find_related( $post_id );
		$this->assertEquals( 1, count( $related ) );
		$this->assertTrue( isset( $related[0] ) && isset( $related[0]->elasticsearch ) );

		add_filter( 'ep_find_related_args', array( $this, 'find_related_posts_filter' ), 10, 1 );
		$related = ElasticProbe\Features::factory()->get_registered_feature( 'related_posts' )->find_related( $post_id );
		$this->assertEquals( 2, count( $related ) );
		$this->assertTrue( isset( $related[0] ) && isset( $related[0]->elasticsearch ) );

		// Make sure it will use the number of posts to be returned.
		$related = ElasticProbe\Features::factory()->get_registered_feature( 'related_posts' )->find_related( $post_id, 1 );
		$this->assertEquals( 1, count( $related ) );
		$this->assertTrue( isset( $related[0] ) && isset( $related[0]->elasticsearch ) );
	}

	/**
	 * Test for related posts query
	 *
	 * @group related_posts
	 */
	public function testGetRelatedQuery() {
		$post_id = $this->ep_factory->post->create( array( 'post_content' => 'findme test 1' ) );

		$related_post_title = 'related post test';
		$this->ep_factory->post->create(
			array(
				'post_title'   => $related_post_title,
				'post_content' => 'findme test 2',
			)
		);

		ElasticProbe\Elasticsearch::factory()->refresh_indices();
		ElasticProbe\Features::factory()->activate_feature( 'related_posts' );
		ElasticProbe\Features::factory()->setup_features();

		$query = ElasticProbe\Features::factory()->get_registered_feature( 'related_posts' )->get_related_query( $post_id, 1 );

		$this->assertTrue( $query->elasticsearch_success );
		$this->assertNotEmpty( $query->posts );
		$this->assertEquals( '1', $query->post_count );
		$this->assertEquals( $related_post_title, $query->posts[0]->post_title );
	}

	/**
	 * Detect EP fire
	 *
	 * @param array $args Query args
	 * @return mixed
	 */
	public function find_related_posts_filter( $args ) {
		$args['post_type'] = array( 'post', 'page' );

		return $args;
	}
}
