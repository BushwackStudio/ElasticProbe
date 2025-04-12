<?php
/**
 * Test the main plugin file functions
 *
 * @since 4.7.0
 * @package wpprobe
 */

namespace WPProbeTest;

/**
 * TestElasticPress test class
 */
class TestElasticPress extends BaseTestCase {
	/**
	 * Test the `get_container` function
	 *
	 * @group elasticpress
	 */
	public function test_get_container() {
		$container = \WPProbe\get_container();

		$this->assertInstanceOf( '\WPProbe\Container', $container );

		// Calling it again should return the same instance
		$this->assertSame( $container, \WPProbe\get_container() );
	}
}
