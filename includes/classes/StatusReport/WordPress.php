<?php
/**
 * Elasticsearch Server report class
 *
 * @since 4.4.0
 * @package wpprobe
 */

namespace WPProbe\StatusReport;

defined( 'ABSPATH' ) || exit;

/**
 * ElasticsearchServer report class
 *
 * @package WPProbe
 */
class WordPress extends Report {

	/**
	 * Return the report title
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'WordPress', 'wpprobe' );
	}

	/**
	 * Return the report fields
	 *
	 * @return array
	 */
	public function get_groups(): array {
		return [
			$this->get_wp_basic_group(),
			$this->get_server_group(),
		];
	}

	/**
	 * Process basic WordPress info
	 *
	 * @return array
	 */
	protected function get_wp_basic_group(): array {
		global $wp_version;

		$fields = [];

		$fields['wp_version'] = [
			'label' => __( 'WordPress Version', 'wpprobe' ),
			'value' => $wp_version,
		];

		$fields['home_url'] = [
			'label' => __( 'Home URL', 'wpprobe' ),
			'value' => get_home_url(),
		];

		$fields['site_url'] = [
			'label' => __( 'Site URL', 'wpprobe' ),
			'value' => get_site_url(),
		];

		$fields['is_multisite'] = [
			'label' => __( 'Multisite', 'wpprobe' ),
			'value' => is_multisite(),
		];

		$active_theme  = wp_get_theme();
		$theme_name    = wp_strip_all_tags( $active_theme->get( 'Name' ) );
		$theme_version = wp_strip_all_tags( $active_theme->get( 'Version' ) );

		$fields['theme'] = [
			'label' => __( 'Theme', 'wpprobe' ),
			'value' => sprintf( '%s (%s)', $theme_name, $theme_version ),
		];

		if ( is_child_theme() ) {
			$parent_theme   = wp_get_theme( $active_theme->get( 'Template' ) );
			$parent_name    = wp_strip_all_tags( $parent_theme->get( 'Name' ) );
			$parent_version = wp_strip_all_tags( $parent_theme->get( 'Version' ) );

			$fields['parent_theme'] = [
				'label' => __( 'Parent Theme', 'wpprobe' ),
				'value' => sprintf( '%s (%s)', $parent_name, $parent_version ),
			];
		}

		$plugins = [];
		foreach ( get_plugins() as $plugin_path => $plugin ) {
			// If plugin is not active, skip it.
			if ( ! is_plugin_active( $plugin_path ) ) {
				continue;
			}

			$plugins[] = sprintf( '%s (%s)', $plugin['Name'], $plugin['Version'] );
		}
		$fields['plugins'] = [
			'label' => __( 'Active Plugins', 'wpprobe' ),
			'value' => wp_sprintf( '%l', $plugins ),
		];

		$fields['revisions'] = [
			'label' => __( 'Revisions allowed', 'wpprobe' ),
			'value' => WP_POST_REVISIONS === true ? 'all' : (int) WP_POST_REVISIONS,
		];

		return [
			'title'  => __( 'WordPress Environment', 'wpprobe' ),
			'fields' => $fields,
		];
	}

	/**
	 * Process the info about the server
	 *
	 * @return array
	 */
	protected function get_server_group(): array {
		$fields = [];

		$fields['php_version'] = [
			'label' => __( 'PHP Version', 'wpprobe' ),
			'value' => phpversion(),
		];

		$fields['memory_limit'] = [
			'label' => __( 'Memory Limit', 'wpprobe' ),
			'value' => WP_MEMORY_LIMIT,
		];

		$fields['timeout'] = [
			'label' => __( 'Maximum Execution Time', 'wpprobe' ),
			'value' => (int) ini_get( 'max_execution_time' ),
		];

		return [
			'title'  => __( 'Server Environment', 'wpprobe' ),
			'fields' => $fields,
		];
	}
}
