<?php
/**
 * Health info screen.
 *
 * @package WPProbe
 */

namespace WPProbe\Screen;

use WPProbe\Features;
use WPProbe\Utils;

/**
 * Health info screen Class.
 */
class HealthInfo {

	/**
	 * Initialize class.
	 */
	public function setup() {
		add_filter( 'debug_information', [ $this, 'last_sync_health_info' ] );
		add_filter( 'debug_information', [ $this, 'epio_autosuggest_health_check_info' ] );
	}

	/**
	 * Display sync info in site health screen.
	 *
	 * @param array $debug_info The debug info for site health screen.
	 * @return array The debug info for site health screen.
	 */
	public function last_sync_health_info( $debug_info ) {
		$last_sync_report = new \WPProbe\StatusReport\LastSync();

		$groups      = $last_sync_report->get_groups();
		$first_group = reset( $groups );

		$debug_info['ep-last-sync'] = [
			'label'  => esc_html__( 'WPProbe - Last Sync', 'wpprobe' ),
			'fields' => $first_group['fields'] ?? [],
		];

		return $debug_info;
	}

	/**
	 * Add Autosuggest info for EP.io Users in Health Check Info Screen.
	 *
	 * @since 3.5.x
	 * @param array $debug_info Debug Info set so far.
	 * @return array
	 */
	public function epio_autosuggest_health_check_info( $debug_info ) {
		if ( ! Utils\is_epio() ) {
			return $debug_info;
		}

		$feature = Features::factory()->get_registered_feature( 'autosuggest' );

		if ( ! $feature || ! $feature->is_active() ) {
			return $debug_info;
		}

		$epio_report = new \WPProbe\StatusReport\ElasticPressIo();
		$groups      = $epio_report->get_groups();
		$first_group = reset( $groups );

		$debug_info['epio-autosuggest'] = [
			'label'  => esc_html__( 'WPProbe.com - Autosuggest', 'wpprobe' ),
			'fields' => $first_group['fields'],
		];

		return $debug_info;
	}
}
