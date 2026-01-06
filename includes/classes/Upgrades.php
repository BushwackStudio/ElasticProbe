<?php
/**
 * Handle upgrades.
 *
 * @since  3.x
 * @package elasticprobe
 */

namespace ElasticProbe;

use ElasticProbe\Features;
use ElasticProbe\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Upgrades
 *
 * @package ElasticProbe
 */
class Upgrades {

	/**
	 * Store the version number before performing upgrades.
	 * Set in the `setup()` method.
	 *
	 * @var null|string
	 */
	protected $old_version;

	/**
	 * Initialize class
	 */
	public function setup() {
		$this->old_version = Utils\get_option( 'eprobe_version', false );

		/**
		 * An array with the upgrades routines.
		 * Indexes are the ElasticProbe version and values
		 * are an array with the method name and, if needed,
		 * the action name where it should be hooked.
		 */
		$routines = [];

		array_walk( $routines, [ $this, 'run_upgrade_routine' ] );

		/**
		 * Check if a reindex is needed.
		 */
		add_action( 'plugins_loaded', [ $this, 'check_reindex_needed' ], 5 );

		/**
		 * Update the version number.
		 * Note: if a upgrade routine method is hooked to some action,
		 * this code will be executed *earlier* than the routine method.
		 */
		Utils\update_option( 'eprobe_version', sanitize_text_field( EPROBE_VERSION ) );
	}

	/**
	 * Run the upgrade routine, if needed.
	 *
	 * @param array  $routine Array with the info about the method to call.
	 *                        If needed to be run in an action, it'll contain the action name.
	 * @param string $version The version number to be tested.
	 */
	protected function run_upgrade_routine( $routine, $version ) {
		if ( version_compare( $this->old_version, $version, '<' ) ) {
			$function_name = $routine[0];
			$action_tag    = $routine[1];
			$priority      = ( ! empty( $routine[2] ) ) ? $routine[2] : 10;
			if ( $action_tag ) {
				add_action( $action_tag, [ $this, $function_name ], $priority );
			} else {
				$this->$function_name();
			}
		}
	}

	/**
	 * Check if a reindex is needed based on the version number.
	 */
	public function check_reindex_needed() {
		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			return;
		}

		$last_sync = Utils\get_option( 'eprobe_last_sync', 'never' );

		// No need to upgrade since we've never synced.
		if ( empty( $last_sync ) || 'never' === $last_sync ) {
			return;
		}

		/**
		 * Reindex if we cross a reindex version in the upgrade
		 */
		$reindex_versions = apply_filters(
			'eprobe_reindex_versions',
			array(
				'0.1.0',
			)
		);

		$need_upgrade_sync = false;

		if ( false !== $this->old_version ) {
			$last_reindex_version = $reindex_versions[ count( $reindex_versions ) - 1 ];

			if ( -1 === version_compare( $this->old_version, $last_reindex_version ) && 0 <= version_compare( EPROBE_VERSION, $last_reindex_version ) ) {
				$need_upgrade_sync = true;
			}
		}

		if ( $need_upgrade_sync ) {
			Utils\update_option( 'eprobe_need_upgrade_sync', true );
		}
	}

	/**
	 * Return singleton instance of class
	 *
	 * @return self
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}
