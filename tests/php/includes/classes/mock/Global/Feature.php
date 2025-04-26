<?php
/**
 * GlobalFeature feature
 *
 * @since 5.0.0
 * @package wpprobe
 */

namespace WPProbeTest\GlobalIndexable;

use WPProbe\Indexables;

require_once __DIR__ . '/Indexable.php';

/**
 * Global feature class
 */
class GlobalFeature extends \WPProbe\Feature {
	/**
	 * Initialize feature setting it's config
	 */
	public function __construct() {
		$this->slug                     = 'global';
		$this->title                    = 'Global';
		$this->requires_install_reindex = true;

		Indexables::factory()->register( new Indexable(), false );

		parent::__construct();
	}

	/**
	 * Activate the indexable
	 */
	public function setup() {
		Indexables::factory()->activate( 'global' );
	}

	/**
	 * Output feature box long text
	 */
	public function output_feature_box_long() {}

	/**
	 * Determine feature reqs status
	 */
	public function requirements_status() {
		return new \WPProbe\FeatureRequirementsStatus( 1 );
	}
}

\WPProbe\Features::factory()->register_feature(
	new GlobalFeature()
);
