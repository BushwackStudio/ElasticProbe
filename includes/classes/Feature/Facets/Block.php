<?php
/**
 * Abstract Facet Block class.
 *
 * @since 4.7.0
 * @package wpprobe
 */

namespace WPProbe\Feature\Facets;

/**
 * Abstract Facet Block class.
 */
abstract class Block {

	/**
	 * Setup hooks and filters for facet block.
	 */
	abstract public function setup();

	/**
	 * Register facet block.
	 */
	abstract public function register_block();

	/**
	 * Render the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	abstract public function render_block( $attributes );
}
