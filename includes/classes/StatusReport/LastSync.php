<?php
/**
 * Last sync report class
 *
 * @since 4.4.0
 * @package wpprobe
 */

namespace WPProbe\StatusReport;

defined( 'ABSPATH' ) || exit;

/**
 * Last sync report class
 *
 * @package WPProbe
 */
class LastSync extends Report {

	/**
	 * Return the report title
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Last Sync', 'wpprobe' );
	}

	/**
	 * Return the report fields
	 *
	 * @return array
	 */
	public function get_groups(): array {
		$fields = [];

		$sync_info = \WPProbe\IndexHelper::factory()->get_last_sync();

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
				'web' => esc_html__( 'WP Dashboard', 'wpprobe' ),
				'cli' => esc_html__( 'WP-CLI', 'wpprobe' ),
			];

			$sync_info['method'] = $methods[ $sync_info['method'] ] ?? $sync_info['method'];
		}

		if ( isset( $sync_info['is_full_sync'] ) ) {
			$sync_info['is_full_sync'] = $sync_info['is_full_sync'] ? esc_html__( 'Yes', 'wpprobe' ) : esc_html__( 'No', 'wpprobe' );
		}

		$labels = [
			'total'           => esc_html__( 'Total', 'wpprobe' ),
			'synced'          => esc_html__( 'Synced', 'wpprobe' ),
			'skipped'         => esc_html__( 'Skipped', 'wpprobe' ),
			'failed'          => esc_html__( 'Failed', 'wpprobe' ),
			'errors'          => esc_html__( 'Errors', 'wpprobe' ),
			'method'          => esc_html__( 'Method', 'wpprobe' ),
			'is_full_sync'    => esc_html__( 'Full Sync', 'wpprobe' ),
			'end_date_time'   => esc_html__( 'End Date Time', 'wpprobe' ),
			'start_date_time' => esc_html__( 'Start Date Time', 'wpprobe' ),
			'total_time'      => esc_html__( 'Total Time', 'wpprobe' ),
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
				$ordered_sync_info[ $field ] = $sync_info[ $field ] ?? esc_html_x( 'N/A', 'Sync info not available', 'wpprobe' );
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
		if ( false !== \WPProbe\Utils\get_indexing_status() ) {
			/* translators: last sync title */
			$title = sprintf( __( '%s (In Progress)', 'wpprobe' ), $title );
		}
		if ( 'status-report' === \WPProbe\Screen::factory()->get_current_screen() ) {
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
