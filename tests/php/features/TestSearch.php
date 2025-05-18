<?php
/**
 * Test search feature
 *
 * @package elasticprobe
 */

namespace ElasticProbeTest;

use ElasticProbe;

/**
 * Search test class
 */
class TestSearch extends BaseTestCase {

	/**
	 * Setup each test.
	 *
	 * @since 2.1
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
	 * Test that search is on
	 *
	 * @since 2.1
	 * @group search
	 */
	public function testSearchOn() {
		ElasticProbe\Features::factory()->activate_feature( 'search' );
		ElasticProbe\Features::factory()->setup_features();

		// Need to call this since it's hooked to init
		ElasticProbe\Features::factory()->get_registered_feature( 'search' )->search_setup();

		$this->ep_factory->post->create();
		$this->ep_factory->post->create();
		$this->ep_factory->post->create( array( 'post_content' => 'findme' ) );

		ElasticProbe\Elasticsearch::factory()->refresh_indices();

		$args = array(
			's' => 'findme',
		);

		$query = new \WP_Query( $args );

		$this->assertTrue( $query->elasticsearch_success );
	}

	/**
	 * Test case for when index is deleted, request for Elasticsearch should fall back to WP Query
	 *
	 * @group search
	 */
	public function testSearchIndexDeleted() {
		global $wpdb;

		ElasticProbe\Features::factory()->activate_feature( 'search' );
		ElasticProbe\Features::factory()->setup_features();

		// Need to call this since it's hooked to init
		ElasticProbe\Features::factory()->get_registered_feature( 'search' )->search_setup();

		$post_ids = array();

		$this->ep_factory->post->create();
		$this->ep_factory->post->create();
		$this->ep_factory->post->create( array( 'post_content' => 'findme' ) );

		ElasticProbe\Elasticsearch::factory()->delete_all_indices();

		ElasticProbe\Elasticsearch::factory()->refresh_indices();

		$args = array(
			's' => 'findme',
		);

		$query = new \WP_Query( $args );

		$this->assertTrue( empty( $query->elasticsearch_success ) );
		$this->assertEquals( 1, count( $query->posts ) );
	}

	/**
	 * Test if decaying is enabled.
	 *
	 * @since 2.4
	 * @group search
	 */
	public function testDecayingEnabled() {
		ElasticProbe\Features::factory()->activate_feature( 'search' );
		ElasticProbe\Features::factory()->setup_features();

		// Need to call this since it's hooked to init
		ElasticProbe\Features::factory()->get_registered_feature( 'search' )->search_setup();

		ElasticProbe\Features::factory()->update_feature(
			'search',
			array(
				'active'           => true,
				'decaying_enabled' => true,
			)
		);

		$this->ep_factory->post->create(
			array(
				'post_content' => 'findme test 1',
				'tags_input'   => array(
					'one',
					'two',
				),
			)
		);
		ElasticProbe\Elasticsearch::factory()->refresh_indices();

		$this->assertTrue( ElasticProbe\Features::factory()->get_registered_feature( 'search' )->is_decaying_enabled() );

		add_filter( 'eprobe_formatted_args', array( $this, 'catch_ep_formatted_args' ), 20 );
		$query = new \WP_Query(
			array(
				's' => 'test',
			)
		);

		$this->assertTrue( isset( $this->fired_actions['eprobe_formatted_args'] ) );
		$this->assertDecayEnabled( $this->fired_actions['eprobe_formatted_args']['query'] );

		/**
		 * Test the `eprobe_is_decaying_enabled` filter
		 */
		add_filter( 'eprobe_is_decaying_enabled', '__return_true' );
		$this->assertTrue( ElasticProbe\Features::factory()->get_registered_feature( 'search' )->is_decaying_enabled() );
		add_filter( 'eprobe_is_decaying_enabled', '__return_false' );
		$this->assertFalse( ElasticProbe\Features::factory()->get_registered_feature( 'search' )->is_decaying_enabled() );
	}

	/**
	 * Test if decaying is disabled.
	 *
	 * @since 2.4
	 * @group search
	 */
	public function testDecayingDisabled() {
		ElasticProbe\Features::factory()->activate_feature( 'search' );
		ElasticProbe\Features::factory()->setup_features();

		// Need to call this since it's hooked to init
		ElasticProbe\Features::factory()->get_registered_feature( 'search' )->search_setup();

		ElasticProbe\Features::factory()->update_feature(
			'search',
			array(
				'active'           => true,
				'decaying_enabled' => false,
			)
		);

		$this->ep_factory->post->create(
			array(
				'post_content' => 'findme test 1',
				'tags_input'   => array(
					'one',
					'two',
				),
			)
		);
		ElasticProbe\Elasticsearch::factory()->refresh_indices();

		add_filter( 'eprobe_formatted_args', array( $this, 'catch_ep_formatted_args' ) );

		$query = new \WP_Query(
			array(
				's' => 'test',
			)
		);

		$this->assertTrue( isset( $this->fired_actions['eprobe_formatted_args'] ) );
		$this->assertDecayDisabled( $this->fired_actions['eprobe_formatted_args']['query'] );
		$this->assertTrue(
			isset(
				$this->fired_actions['eprobe_formatted_args']['query']['bool'],
				$this->fired_actions['eprobe_formatted_args']['query']['bool']['should']
			)
		);
	}

	/**
	 * Test allowed tags for highlighting sub-feature.
	 *
	 * @group search
	 */
	public function testAllowedTags() {
		ElasticProbe\Features::factory()->activate_feature( 'search' );
		ElasticProbe\Features::factory()->setup_features();

		// Need to call this since it's hooked to init
		ElasticProbe\Features::factory()->get_registered_feature( 'search' )->search_setup();

		// a tag that is in the array of allowed tags
		$allowed_tag    = 'span';
		$search_feature = ElasticProbe\Features::factory()->get_registered_feature( 'search' );

		$this->assertTrue( 'span' === $search_feature->get_highlighting_tag( $allowed_tag ) );
	}

	/**
	 * Test not-allowed tags for highlighting sub-feature.
	 *
	 * @group search
	 */
	public function testNotAllowedTags() {
		ElasticProbe\Features::factory()->activate_feature( 'search' );
		ElasticProbe\Features::factory()->setup_features();

		// Need to call this since it's hooked to init
		ElasticProbe\Features::factory()->get_registered_feature( 'search' )->search_setup();

		// a tag that is not in the array of allowed tags
		$not_allowed_tag = 'div';
		$search_feature  = ElasticProbe\Features::factory()->get_registered_feature( 'search' );

		$this->assertTrue( 'mark' === $search_feature->get_highlighting_tag( $not_allowed_tag ) );
	}

	/**
	 * Testing changing color and tag settings for highlighting sub-feature.
	 *
	 * @group search
	 */
	public function testHighlightSetting() {

		ElasticProbe\Features::factory()->activate_feature( 'search' );
		ElasticProbe\Features::factory()->setup_features();

		// Need to call this since it's hooked to init
		ElasticProbe\Features::factory()->get_registered_feature( 'search' )->search_setup();

		ElasticProbe\Features::factory()->update_feature(
			'search',
			array(
				'active'            => true,
				'highlight_enabled' => '1',
				'highlight_tag'     => 'span',
			)
		);

		$settings = ElasticProbe\Features::factory()->get_registered_feature( 'search' )->get_settings();

		$this->assertTrue( 'span' === $settings['highlight_tag'] );
	}

	/**
	 * Testing setting a tag that's not allowed
	 *
	 * Leverages the eprobe_highlighting_tag filter used when updating settings.
	 * Should return 'mark' as the tag.
	 *
	 * @group search
	 */
	public function testBadTagSetting() {

		ElasticProbe\Features::factory()->activate_feature( 'search' );
		ElasticProbe\Features::factory()->setup_features();

		// Need to call this since it's hooked to init
		ElasticProbe\Features::factory()->get_registered_feature( 'search' )->search_setup();

		ElasticProbe\Features::factory()->update_feature(
			'search',
			array(
				'active'            => true,
				'highlight_enabled' => '1',
				'highlight_tag'     => 'div',
			)
		);

		$settings = ElasticProbe\Features::factory()->get_registered_feature( 'search' )->get_settings();
		$tag      = apply_filters( 'eprobe_highlighting_tag', $settings['highlight_tag'] );

		$this->assertTrue( 'mark' === $tag );
	}

	/**
	 * Testing excerpt enabled on settings
	 *
	 * @group search
	 */
	public function testExcerptSetting() {

		ElasticProbe\Features::factory()->activate_feature( 'search' );
		ElasticProbe\Features::factory()->setup_features();

		// Need to call this since it's hooked to init
		ElasticProbe\Features::factory()->get_registered_feature( 'search' )->search_setup();

		ElasticProbe\Features::factory()->update_feature(
			'search',
			array(
				'active'            => true,
				'highlight_enabled' => '1',
				'highlight_excerpt' => '1',
			)
		);

		$settings = ElasticProbe\Features::factory()->get_registered_feature( 'search' )->get_settings();

		$this->assertSame( $settings['highlight_excerpt'], '1' );
	}

	/**
	 * Test Search settings schema
	 *
	 * @since 5.0.0
	 * @group search
	 */
	public function test_get_settings_schema() {
		$settings_schema = \ElasticProbe\Features::factory()->get_registered_feature( 'search' )->get_settings_schema();

		$settings_keys = wp_list_pluck( $settings_schema, 'key' );

		$expected = [ 'active', 'decaying_enabled', 'highlight_enabled', 'highlight_excerpt', 'highlight_tag', 'synonyms_editor_mode' ];
		if ( ! is_multisite() ) {
			$expected[] = 'additional_links';
		}

		$this->assertSame( $expected, $settings_keys );
	}
}
