<?php
/**
 * Test abstract SearchAlgorithm
 *
 * @since 4.3.0
 * @package elasticprobe
 */

namespace ElasticProbeTest;

use ElasticProbe\SearchAlgorithm;

/**
 * Test abstract SearchAlgorithm class
 */
class TestSearchAlgorithm extends BaseTestCase {
	/**
	 * "Concrete" stub for the abstract class
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject
	 */
	private $stub;

	/**
	 * Setup each test.
	 */
	public function set_up() {
		$this->stub = $this->getMockForAbstractClass( SearchAlgorithm::class );
		$this->stub->expects( $this->any() )
			->method( 'get_raw_query' )
			->will( $this->returnValue( [] ) );

		parent::set_up();
	}

	/**
	 * Test filters
	 *
	 * @group searchAlgorithms
	 */
	public function testFilters() {
		$test_filter = function () {
			return [ 'changed' ];
		};

		/**
		 * Test the `eprobe_{$indexable_slug}_formatted_args_query` filter.
		 */
		add_filter( 'eprobe_indexable_formatted_args_query', $test_filter );

		$query = $this->stub->get_query( 'indexable', '', [], [] );
		$this->assertEquals( [ 'changed' ], $query );
	}

	/**
	 * Test deprecated/legacy filters
	 *
	 * @expectedDeprecated eprobe_formatted_args_query
	 * @group searchAlgorithms
	 */
	public function testLegacyFilters() {
		$test_filter = function () {
			return [ 'changed' ];
		};

		/**
		 * Test the `eprobe_formatted_args_query` filter.
		 */
		add_filter( 'eprobe_formatted_args_query', $test_filter );

		$query = $this->stub->get_query( 'post', '', [], [] );
		$this->assertEquals( [ 'changed' ], $query );
	}
}
