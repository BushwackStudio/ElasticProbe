<?php
/**
 * Last sync report class
 *
 * @since 4.4.0
 * @package elasticprobe
 */

namespace ElasticProbe\StatusReport;

defined( 'ABSPATH' ) || exit;

/**
 * Last sync report class
 *
 * @package ElasticProbe
 */
class LastSync extends Report {

	/**
	 * Return the report title
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Last Sync', 'elasticprobe' );
	}

	/**
	 * Return the report fields
	 *
	 * @return array
	 */
	public function get_groups(): array {
		$fields = [];

		$sync_info = \ElasticProbe\IndexHelper::factory()->get_last_sync();

		if ( empty( $sync_info ) ) {
			return [];
		}

		if ( ! empty( $sync_info['end_time_gmt'] ) ) {
			unset( $sync_info['end_time_gmt'] );
		}

		if ( ! empty( $sync_info['total_time'] ) ) {
			$sync_info['total_time'] = human_readable_duration( gmdate( 'H:i:s', ceil( $sync_info['total_time'] ) ) );
		}

		if ( ! empty( $sync_info['end_date_time'] ) ) {
			$sync_info['end_date_time'] = wp_date(
				'Y/m/d g:i:s a',
				strtotime( $sync_info['end_date_time'] )
			);
		}

		if ( ! empty( $sync_info['start_date_time'] ) ) {
			$sync_info['start_date_time'] = wp_date(
				'Y/m/d g:i:s a',
				strtotime( $sync_info['start_date_time'] )
			);
		}

		if ( ! empty( $sync_info['method'] ) ) {
			$methods = [
				'web' => esc_html__( 'WP Dashboard', 'elasticprobe' ),
				'cli' => esc_html__( 'WP-CLI', 'elasticprobe' ),
			];

			$sync_info['method'] = $methods[ $sync_info['method'] ] ?? $sync_info['method'];
		}

		if ( isset( $sync_info['is_full_sync'] ) ) {
			$sync_info['is_full_sync'] = $sync_info['is_full_sync'] ? esc_html__( 'Yes', 'elasticprobe' ) : esc_html__( 'No', 'elasticprobe' );
		}

		$labels = [
			'total'           => esc_html__( 'Total', 'elasticprobe' ),
			'synced'          => esc_html__( 'Synced', 'elasticprobe' ),
			'skipped'         => esc_html__( 'Skipped', 'elasticprobe' ),
			'failed'          => esc_html__( 'Failed', 'elasticprobe' ),
			'errors'          => esc_html__( 'Errors', 'elasticprobe' ),
			'method'          => esc_html__( 'Method', 'elasticprobe' ),
			'is_full_sync'    => esc_html__( 'Full Sync', 'elasticprobe' ),
			'end_date_time'   => esc_html__( 'End Date Time', 'elasticprobe' ),
			'start_date_time' => esc_html__( 'Start Date Time', 'elasticprobe' ),
			'total_time'      => esc_html__( 'Total Time', 'elasticprobe' ),
		];

		/**
		 * Apply a custom order to the table rows.
		 *
		 * As some rows could be unavailable (if the last sync was done using an older version of the plugin, for example),
		 * the usual `array_replace(array_flip())` strategy to reorder an array adds a wrong numeric value to the
		 * non-existent row.
		 */
		$preferred_order   = [ 'method', 'is_full_sync', 'start_date_time', 'end_date_time', 'total_time', 'total', 'synced', 'skipped', 'failed', 'errors' ];
		$ordered_sync_info = [];
		foreach ( $preferred_order as $field ) {
			if ( array_key_exists( $field, $sync_info ) ) {
				$ordered_sync_info[ $field ] = $sync_info[ $field ] ?? esc_html_x( 'N/A', 'Sync info not available', 'elasticprobe' );
				unset( $sync_info[ $field ] );
			}
		}
		$sync_info = $ordered_sync_info + $sync_info;

		foreach ( $sync_info as $label => $value ) {
			$fields[ sanitize_title( $label ) ] = [
				'label' => $labels[ $label ] ?? $label,
				'value' => $value,
			];
		}
		$title = $sync_info['start_date_time'] ?? '';
		if ( false !== \ElasticProbe\Utils\get_indexing_status() ) {
			/* translators: last sync title */
			$title = sprintf( __( '%s (In Progress)', 'elasticprobe' ), $title );
		}
		if ( 'status-report' === \ElasticProbe\Screen::factory()->get_current_screen() ) {
			unset( $fields['start_date_time'] );
		}

		return [
			[
				'title'  => $title,
				'fields' => $fields,
			],
		];
	}
}
