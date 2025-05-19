<?php
/**
 * ElasticProbe uninstaller
 *
 * Used when clicking "Delete" from inside of WordPress's plugins page.
 *
 * @package elasticprobe
 * @since   1.7
 */

use ElasticProbe\Utils;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/utils.php';

/**
 * Class EPROBE_Uninstaller
 */
class EPROBE_Uninstaller {

	/**
	 * List of option keys that need to be deleted when uninstalling the plugin.
	 *
	 * @var array
	 */
	protected $options = [
		'eprobe_host',
		'eprobe_index_meta',
		'eprobe_feature_settings',
		'eprobe_feature_settings_draft',
		'eprobe_version',
		'eprobe_intro_shown',
		'eprobe_last_sync',
		'eprobe_need_upgrade_sync',
		'eprobe_feature_requirement_statuses',
		'eprobe_feature_auto_activated_sync',
		'eprobe_hide_intro_shown_notice',
		'eprobe_skip_install',
		'eprobe_last_cli_index',
		'eprobe_credentials',
		'eprobe_prefix',
		'eprobe_language',
		'eprobe_bulk_setting',
		'eprobe_sync_history',
		'eprobe_subscription_id',

		'eprobe_weighting',

		// Admin notices options
		'eprobe_hide_host_error_notice',
		'eprobe_hide_es_below_compat_notice',
		'eprobe_hide_es_above_compat_notice',
		'eprobe_hide_need_setup_notice',
		'eprobe_hide_no_sync_notice',
		'eprobe_hide_upgrade_sync_notice',
		'eprobe_hide_auto_activate_sync_notice',
		'eprobe_hide_using_autosuggest_defaults_notice',
		'eprobe_hide_yellow_health_notice',
	];

	/**
	 * List of transient keys that need to be deleted when uninstalling the plugin.
	 *
	 * @var array
	 */
	protected $transients = [
		'eprobe_autosuggest_query_request_cache',
		'eprobe_elasticpress_io_messages',
		'eprobe_es_info',
		'eprobe_es_info_response_code',
		'eprobe_es_info_response_error',
		'eprobe_meta_field_keys',
		'eprobe_wpcli_sync',
		'eprobe_wpcli_sync_interrupted',
		'logging_eprobe_es_info',
	];

	/**
	 * Initialize uninstaller
	 *
	 * Perform some checks to make sure plugin can/should be uninstalled
	 *
	 * @since 1.7
	 */
	public function __construct() {
		// Exit if accessed directly.
		if ( ! defined( 'ABSPATH' ) ) {
			$this->exit_uninstaller();
		}

		// If testing, do not do anything automatically
		if ( defined( 'EPROBE_UNIT_TESTS' ) && EPROBE_UNIT_TESTS ) {
			return;
		}

		// EPROBE_MANUAL_SETTINGS_RESET is used by the `settings-reset` WP-CLI command.
		if ( ! defined( 'EPROBE_MANUAL_SETTINGS_RESET' ) || ! EPROBE_MANUAL_SETTINGS_RESET ) {
			// Not uninstalling.
			if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! WP_UNINSTALL_PLUGIN ) {
				$this->exit_uninstaller();
			}

			// Not uninstalling this plugin.
			if ( dirname( WP_UNINSTALL_PLUGIN ) !== dirname( plugin_basename( __FILE__ ) ) ) {
				$this->exit_uninstaller();
			}
		}

		// Uninstall ElasticProbe.
		$this->clean_options_and_transients();
		$this->clean_site_meta();
		$this->remove_elasticpress_capability();
	}

	/**
	 * Delete all the options in a single site context.
	 */
	protected function delete_options() {
		foreach ( $this->options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Delete all the transients in a single site context.
	 */
	protected function delete_transients() {
		foreach ( $this->transients as $transient ) {
			delete_transient( $transient );
		}
	}

	/**
	 * Delete remaining transients by their option names.
	 *
	 * @since 4.7.0
	 */
	protected function delete_transients_by_option_name() {
		global $wpdb;

		$transients = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT option_name
			FROM {$wpdb->prefix}options
			WHERE
				option_name LIKE '_transient_eprobe_index_settings_%'
				OR option_name LIKE '_transient_eprobe_related_posts_%'
			"
		);

		foreach ( $transients as $transient ) {
			$transient_name = str_replace( '_transient_', '', $transient );
			delete_site_transient( $transient_name );
			delete_transient( $transient_name );
		}
	}

	/**
	 * DEPRECATED. Delete all transients of the Related Posts feature.
	 */
	protected function delete_related_posts_transients() {
		_deprecated_function( __METHOD__, '0.1.0', '\EPROBE_Uninstaller::delete_transients_by_name()' );
	}

	/**
	 * DEPRECATED. Delete all transients of the total fields limit.
	 */
	protected function delete_total_fields_limit_transients() {
		_deprecated_function( __METHOD__, '0.1.0', '\EPROBE_Uninstaller::delete_transients_by_name()' );
	}

	/**
	 * Cleanup options and transients
	 *
	 * Deletes ElasticProbe options and transients.
	 *
	 * @since 4.2.0
	 */
	protected function clean_options_and_transients() {
		if ( is_multisite() ) {
			foreach ( $this->options as $option ) {
				delete_site_option( $option );
			}
			foreach ( $this->transients as $transient ) {
				delete_site_transient( $transient );
			}

			$sites = \get_sites();

			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );

				$this->delete_options();
				$this->delete_transients();
				$this->delete_transients_by_option_name();

				restore_current_blog();
			}
		} else {
			$this->delete_options();
			$this->delete_transients();
			$this->delete_transients_by_option_name();
		}
	}

	/**
	 * Delete all site meta
	 *
	 * @since 4.7.0
	 */
	protected function clean_site_meta() {
		if ( ! is_multisite() ) {
			return;
		}

		$sites = Utils\get_sites();
		foreach ( $sites as $site ) {
			delete_site_meta( $site['blog_id'], 'ep_indexable' );
		}
	}

	/**
	 * Remove the ElasticProbe's capability
	 *
	 * @since 4.5.0
	 */
	protected function remove_elasticpress_capability() {
		$role = get_role( 'administrator' );
		$role->remove_cap( Utils\get_capability() );
	}

	/**
	 * Cleanup options (deprecated, kept for documenting reasons.)
	 *
	 * @since 1.7
	 * @return void
	 * @see clean_options_and_transients
	 */
	protected static function clean_options() {
		_deprecated_function( __FUNCTION__, '0.1.0', '\EPROBE_Uninstaller->clean_options_and_transients()' );
	}

	/**
	 * Exit uninstaller
	 *
	 * Gracefully exit the uninstaller if we should not be here
	 *
	 * @since 1.7
	 * @return void
	 */
	protected function exit_uninstaller() {
		status_header( 404 );
		exit;
	}
}

new EPROBE_Uninstaller();
