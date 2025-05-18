<?php
/**
 * Create an ElasticProbe dashboard page.
 *
 * @package elasticprobe
 * @since   1.9
 */

namespace ElasticProbe\Dashboard;

use ElasticProbe\AdminNotices;
use ElasticProbe\Elasticsearch;
use ElasticProbe\Features;
use ElasticProbe\Installer;
use ElasticProbe\Screen;
use ElasticProbe\Stats;
use ElasticProbe\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Setup actions and filters for all things settings
 *
 * @since  2.1
 */
function setup() {
	if ( defined( 'EPROBE_IS_NETWORK' ) && EPROBE_IS_NETWORK ) { // Must be network admin in multisite.
		add_action( 'network_admin_menu', __NAMESPACE__ . '\action_admin_menu' );
		add_action( 'admin_bar_menu', __NAMESPACE__ . '\action_network_admin_bar_menu', 50 );
	}

	add_action( 'admin_menu', __NAMESPACE__ . '\action_admin_menu' );
	add_action( 'wp_ajax_eprobe_save_feature', __NAMESPACE__ . '\action_wp_ajax_ep_save_feature' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\action_admin_enqueue_dashboard_scripts' );
	add_action( 'admin_init', __NAMESPACE__ . '\maybe_clear_es_info_cache' );
	add_action( 'admin_init', __NAMESPACE__ . '\maybe_skip_install' );
	add_action( 'wp_ajax_eprobe_notice_dismiss', __NAMESPACE__ . '\action_wp_ajax_ep_notice_dismiss' );
	add_action( 'admin_notices', __NAMESPACE__ . '\maybe_notice' );
	add_action( 'network_admin_notices', __NAMESPACE__ . '\maybe_notice' );
	add_filter( 'plugin_action_links', __NAMESPACE__ . '\filter_plugin_action_links', 10, 2 );
	add_filter( 'network_admin_plugin_action_links', __NAMESPACE__ . '\filter_plugin_action_links', 10, 2 );
	add_action( 'eprobe_add_query_log', __NAMESPACE__ . '\log_version_query_error' );
	add_filter( 'eprobe_analyzer_language', __NAMESPACE__ . '\use_language_in_setting', 10, 2 );
	add_filter( 'wp_kses_allowed_html', __NAMESPACE__ . '\filter_allowed_html', 10, 2 );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\block_assets' );

	if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
		add_action( 'block_categories_all', __NAMESPACE__ . '\block_categories' );
	} else {
		add_action( 'block_categories', __NAMESPACE__ . '\block_categories' );
	}

	/**
	 * Filter whether to show 'ElasticProbe Indexing' option on Multisite in admin UI or not.
	 *
	 * @since  3.6.0
	 * @hook eprobe_show_indexing_option_on_multisite
	 * @param  {bool}  $show True to show.
	 * @return {bool}  New value
	 */
	$show_indexing_option_on_multisite = apply_filters( 'eprobe_show_indexing_option_on_multisite', defined( 'EPROBE_IS_NETWORK' ) && EPROBE_IS_NETWORK );

	if ( $show_indexing_option_on_multisite ) {
		add_filter( 'wpmu_blogs_columns', __NAMESPACE__ . '\filter_blogs_columns', 10, 1 );
		add_action( 'manage_sites_custom_column', __NAMESPACE__ . '\add_blogs_column', 10, 2 );
		add_action( 'wp_ajax_eprobe_site_admin', __NAMESPACE__ . '\action_wp_ajax_ep_site_admin' );
	}
}

/**
 * Add ep-html kses context
 *
 * @param  array  $allowedtags HTML tags
 * @param  string $context     Context string
 * @since  3.0
 * @return array
 */
function filter_allowed_html( $allowedtags, $context ) {
	global $allowedposttags;

	if ( 'ep-html' === $context ) {
		$ep_tags = $allowedposttags;

		$atts = [
			'type'            => true,
			'checked'         => true,
			'selected'        => true,
			'disabled'        => true,
			'value'           => true,
			'href'            => true,
			'class'           => true,
			'data-*'          => true,
			'data-field-name' => true,
			'data-ep-notice'  => true,
			'data-feature'    => true,
			'id'              => true,
			'style'           => true,
			'title'           => true,
			'name'            => true,
			'placeholder'     => '',
		];

		$ep_tags['input']    = $atts;
		$ep_tags['select']   = $atts;
		$ep_tags['textarea'] = $atts;
		$ep_tags['option']   = $atts;

		$ep_tags['form'] = [
			'action'         => true,
			'accept'         => true,
			'accept-charset' => true,
			'enctype'        => true,
			'method'         => true,
			'name'           => true,
			'target'         => true,
		];

		$ep_tags['a'] = array_merge(
			$atts,
			[ 'target' => true ]
		);

		return $ep_tags;
	}

	return $allowedtags;
}

/**
 * Stores the results of the version query.
 *
 * @param  array $query The version query.
 * @since  3.0
 */
function log_version_query_error( $query ) {
	// Ignore fake requests like the autosuggest template generation
	if ( ! empty( $query['request'] ) && is_array( $query['request'] ) && ! empty( $query['request']['is_ep_fake_request'] ) ) {
		return;
	}

	$logging_key = 'logging_eprobe_es_info';

	$logging = Utils\get_transient( $logging_key );

	// Are we logging the version query results?
	if ( '1' === $logging ) {
		/**
		 * Filter how long results of Elasticsearch version query are stored
		 *
		 * @since  23.0
		 * @hook eprobe_es_info_cache_expiration
		 * @param  {int} Time in seconds
		 * @return  {int} New time in seconds
		 */
		$cache_time         = apply_filters( 'eprobe_es_info_cache_expiration', ( 5 * MINUTE_IN_SECONDS ) );
		$response_code_key  = 'eprobe_es_info_response_code';
		$response_error_key = 'eprobe_es_info_response_error';
		$response_code      = 0;
		$response_error     = '';

		if ( ! empty( $query['request'] ) ) {
			$response_code  = absint( wp_remote_retrieve_response_code( $query['request'] ) );
			$response_error = wp_remote_retrieve_response_message( $query['request'] );
			if ( empty( $response_error ) && is_wp_error( $query['request'] ) ) {
				$response_error = $query['request']->get_error_message();
			}
		}

		// Store the response code, and remove the flag that says
		// we're logging the response code so we don't log additional
		// queries.
		Utils\set_transient( $response_code_key, $response_code, $cache_time );
		Utils\set_transient( $response_error_key, $response_error, $cache_time );
		Utils\delete_transient( $logging_key );
	}
}

/**
 * Allow user to skip install process.
 *
 * @since  3.5
 */
function maybe_skip_install() {
	if ( ! is_admin() && ! is_network_admin() ) {
		return;
	}

	if ( empty( $_GET['ep-skip-install'] ) || empty( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'ep-skip-install' ) || ! in_array( Screen::factory()->get_current_screen(), [ 'install' ], true ) ) {
		return;
	}

	if ( ! empty( $_GET['ep-skip-features'] ) ) {
		$features = \ElasticProbe\Features::factory()->registered_features;

		foreach ( $features as $slug => $feature ) {
			\ElasticProbe\Features::factory()->deactivate_feature( $slug );
		}
	}

	if ( defined( 'EPROBE_IS_NETWORK' ) && EPROBE_IS_NETWORK ) {
		$redirect_url = network_admin_url( 'admin.php?page=elasticprobe' );
	} else {
		$redirect_url = admin_url( 'admin.php?page=elasticprobe' );
	}
	Utils\update_option( 'eprobe_skip_install', true );

	wp_safe_redirect( $redirect_url );
	exit;
}

/**
 * Clear ES info cache whenever EP dash or settings page is viewed. Also clear cache
 * when "try again" notification link is clicked.
 *
 * @since  2.3.1
 */
function maybe_clear_es_info_cache() {
	if ( ! is_admin() && ! is_network_admin() ) {
		return;
	}

	$isset_retry = ! empty( $_GET['ep-retry'] ) &&
		! empty( $_GET['ep_retry_nonce'] ) &&
		wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ep_retry_nonce'] ) ), 'ep_retry_nonce' );

	if ( ! $isset_retry && ! in_array( Screen::factory()->get_current_screen(), [ 'dashboard', 'settings', 'install' ], true ) ) {
		return;
	}

	if ( defined( 'EPROBE_IS_NETWORK' ) && EPROBE_IS_NETWORK ) {
		delete_site_transient( 'eprobe_es_info' );
	} else {
		delete_transient( 'eprobe_es_info' );
	}

	if ( $isset_retry ) {
		wp_safe_redirect( remove_query_arg( [ 'ep-retry', 'ep_retry_nonce' ] ) );
		exit();
	}
}

/**
 * Show ElasticProbe in network admin menu bar
 *
 * @param  object $admin_bar WP_Admin Bar reference.
 * @since  2.2
 */
function action_network_admin_bar_menu( $admin_bar ) {
	$admin_bar->add_menu(
		array(
			'id'     => 'network-admin-elasticpress',
			'parent' => 'network-admin',
			'title'  => 'ElasticProbe',
			'href'   => esc_url( network_admin_url( 'admin.php?page=elasticprobe' ) ),
		)
	);
}

/**
 * Output dashboard link in plugin actions
 *
 * @param  array  $plugin_actions Array of HTML.
 * @param  string $plugin_file Path to plugin file.
 * @since  2.1
 * @return array
 */
function filter_plugin_action_links( $plugin_actions, $plugin_file ) {

	if ( is_network_admin() ) {
		$url = admin_url( 'network/admin.php?page=elasticprobe' );

		if ( ! defined( 'EPROBE_IS_NETWORK' ) || ! EPROBE_IS_NETWORK ) {
			return $plugin_actions;
		}
	} else {
		$url = admin_url( 'admin.php?page=elasticprobe' );

		if ( defined( 'EPROBE_IS_NETWORK' ) && EPROBE_IS_NETWORK ) {
			return $plugin_actions;
		}
	}

	$new_actions = [];

	if ( basename( EPROBE_PATH ) . '/elasticprobe.php' === $plugin_file ) {
		$new_actions['ep_dashboard'] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), __( 'Dashboard', 'elasticprobe' ) );
	}

	return array_merge( $new_actions, $plugin_actions );
}

/**
 * Output variety of dashboard notices.
 *
 * @param  bool $force Force ES info hard lookup.
 * @since  3.0
 */
function maybe_notice( $force = false ) {
	if ( ! current_user_can( Utils\get_capability() ) ) {
		return false;
	}

	/**
	 * Filter how long results of Elasticsearch version query are stored
	 *
	 * @since  23.0
	 * @hook eprobe_es_info_cache_expiration
	 * @param  {int} Time in seconds
	 * @return  {int} New time in seconds
	 */
	$cache_time = apply_filters( 'eprobe_es_info_cache_expiration', ( 5 * MINUTE_IN_SECONDS ) );

	Utils\set_transient(
		'logging_eprobe_es_info',
		'1',
		$cache_time
	);

	// Fetch ES version
	Elasticsearch::factory()->get_elasticsearch_version( $force );

	AdminNotices::factory()->process_notices();

	$notices = AdminNotices::factory()->get_notices();

	foreach ( $notices as $notice_key => $notice ) {
		?>
		<div data-ep-notice="<?php echo esc_attr( $notice_key ); ?>" class="notice notice-
		<?php echo esc_attr( $notice['type'] ); ?> 
		<?php
		if ( $notice['dismiss'] ) :
			?>
			is-dismissible<?php endif; ?>">
			<p>
				<?php echo wp_kses( $notice['html'], 'ep-html' ); ?>
			</p>
		</div>
		<?php
	}

	wp_enqueue_script( 'ep_notice_script' );

	return $notices;
}

/**
 * Dismiss notice via ajax
 *
 * @since 2.2
 */
function action_wp_ajax_ep_notice_dismiss() {
	if ( empty( $_POST['notice'] ) || ! check_ajax_referer( 'ep_admin_nonce', 'nonce', false ) ) {
		wp_send_json_error();
		exit;
	}

	if ( ! current_user_can( Utils\get_capability() ) ) {
		wp_send_json_error();
		exit;
	}

	AdminNotices::factory()->dismiss_notice( sanitize_key( $_POST['notice'] ) );

	wp_send_json_success();
}

/**
 * Getting the status of ongoing index fired by WP CLI
 *
 * @since  2.1
 */
function action_wp_ajax_ep_cli_index() {
	_deprecated_function( __CLASS__, '0.1.0', '\ElasticProbe\Screen::factory()->sync_screen->action_wp_ajax_ep_cli_index()' );
}

/**
 * Continue index
 *
 * @since  2.1
 */
function action_wp_ajax_ep_index() {
	_deprecated_function( __CLASS__, '0.1.0', '\ElasticProbe\Screen::factory()->sync_screen->action_wp_ajax_ep_index()' );
}

/**
 * Cancel index
 *
 * @since  2.1
 */
function action_wp_ajax_ep_cancel_index() {
	_deprecated_function( __CLASS__, '0.1.0', '\ElasticProbe\Screen::factory()->sync_screen->action_wp_ajax_ep_cancel_index()' );
}

/**
 * Save individual feature settings
 *
 * @since  2.2
 */
function action_wp_ajax_ep_save_feature() {
	$post = wp_unslash( $_POST );

	if ( empty( $post['feature'] ) || empty( $post['settings'] ) || ! check_ajax_referer( 'ep_dashboard_nonce', 'nonce', false ) ) {
		wp_send_json_error();
		exit;
	}

	if ( Utils\is_indexing() ) {
		$error = new \WP_Error( 'is_indexing' );

		wp_send_json_error( $error );
		exit;
	}

	$data = Features::factory()->update_feature( $post['feature'], $post['settings'] );

	// Since we deactivated, delete auto activate notice.
	if ( empty( $post['settings']['active'] ) ) {
		Utils\delete_option( 'eprobe_feature_auto_activated_sync' );
	}

	wp_send_json_success( $data );
}

/**
 * Register and Enqueue JavaScripts for dashboard
 *
 * @since 2.2
 */
function action_admin_enqueue_dashboard_scripts() {
	if ( isset( get_current_screen()->id ) && strpos( get_current_screen()->id, 'sites-network' ) !== false ) {
		wp_enqueue_style( 'wp-components' );

		wp_enqueue_script(
			'eprobe_admin_sites_scripts',
			EPROBE_URL . 'dist/js/sites-admin-script.js',
			Utils\get_asset_info( 'sites-admin-script', 'dependencies' ),
			Utils\get_asset_info( 'sites-admin-script', 'version' ),
			true
		);

		wp_set_script_translations( 'eprobe_admin_sites_scripts', 'elasticprobe' );

		$data = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'epsa' ),
		];

		wp_localize_script( 'eprobe_admin_sites_scripts', 'epsa', $data );
	}

	if ( in_array( Screen::factory()->get_current_screen(), [ 'dashboard', 'settings', 'install', 'health', 'weighting', 'synonyms', 'sync', 'status-report' ], true ) ) {
		wp_enqueue_style(
			'eprobe_admin_styles',
			EPROBE_URL . 'dist/css/dashboard-styles.css',
			Utils\get_asset_info( 'dashboard-styles', 'dependencies' ),
			Utils\get_asset_info( 'dashboard-styles', 'version' )
		);
		wp_enqueue_script(
			'eprobe_admin_script',
			EPROBE_URL . 'dist/js/admin-script.js',
			Utils\get_asset_info( 'admin-script', 'dependencies' ),
			Utils\get_asset_info( 'admin-script', 'version' ),
			true
		);

		wp_set_script_translations( 'eprobe_admin_script', 'elasticprobe' );
	}

	if ( 'weighting' === Screen::factory()->get_current_screen() ) {

		wp_enqueue_style(
			'eprobe_weighting_styles',
			EPROBE_URL . 'dist/css/weighting-script.css',
			[ 'wp-components', 'wp-edit-post' ],
			Utils\get_asset_info( 'weighting-script', 'version' )
		);

		wp_enqueue_script(
			'eprobe_weighting_script',
			EPROBE_URL . 'dist/js/weighting-script.js',
			Utils\get_asset_info( 'weighting-script', 'dependencies' ),
			Utils\get_asset_info( 'weighting-script', 'version' ),
			true
		);

		$weighting = Features::factory()->get_registered_feature( 'search' )->weighting;

		$api_url                 = esc_url_raw( rest_url( 'elasticprobe/v1/weighting' ) );
		$meta_mode               = $weighting->get_meta_mode();
		$weightable_fields       = $weighting->get_weightable_fields();
		$weighting_configuration = $weighting->get_weighting_configuration_with_defaults();

		/**
		 * Filter weighting dashboard options.
		 *
		 * @hook eprobe_weighting_options
		 * @param  {array} $data Weighting dashboard options
		 * @return  {array} New options array
		 * @since 5.1.0
		 */
		$data = apply_filters(
			'eprobe_weighting_options',
			[
				'apiUrl'                 => $api_url,
				'metaMode'               => $meta_mode,
				'weightableFields'       => $weightable_fields,
				'weightingConfiguration' => $weighting_configuration,
			]
		);

		wp_localize_script(
			'eprobe_weighting_script',
			'epWeighting',
			$data
		);

		wp_set_script_translations( 'eprobe_weighting_script', 'elasticprobe' );
	}

	if ( in_array( Screen::factory()->get_current_screen(), [ 'dashboard', 'install' ], true ) ) {
		wp_enqueue_script(
			'eprobe_dashboard_scripts',
			EPROBE_URL . 'dist/js/dashboard-script.js',
			Utils\get_asset_info( 'dashboard-script', 'dependencies' ),
			Utils\get_asset_info( 'dashboard-script', 'version' ),
			true
		);

		wp_set_script_translations( 'eprobe_dashboard_scripts', 'elasticprobe' );

		$sync_url = Utils\get_sync_url( true );

		$skip_url = ( defined( 'EPROBE_IS_NETWORK' ) && EPROBE_IS_NETWORK ) ?
				network_admin_url( 'admin.php?page=elasticprobe' ) :
				admin_url( 'admin.php?page=elasticprobe' );

		$data = array(
			'skipUrl' => add_query_arg(
				array(
					'ep-skip-install'  => 1,
					'ep-skip-features' => 1,
					'nonce'            => wp_create_nonce( 'ep-skip-install' ),
				),
				$skip_url
			),
			'syncUrl' => $sync_url,
		);

		wp_localize_script( 'eprobe_dashboard_scripts', 'epDash', $data );
	}

	if ( in_array( Screen::factory()->get_current_screen(), [ 'health' ], true ) && ! empty( Utils\get_host() ) ) {
		Stats::factory()->build_stats();

		$data = Stats::factory()->get_localized();

		wp_enqueue_script(
			'eprobe_stats',
			EPROBE_URL . 'dist/js/stats-script.js',
			Utils\get_asset_info( 'stats-script', 'dependencies' ),
			Utils\get_asset_info( 'stats-script', 'version' ),
			true
		);

		wp_set_script_translations( 'eprobe_stats', 'elasticprobe' );

		wp_localize_script( 'eprobe_stats', 'epChartData', $data );
	}

	wp_register_script(
		'eprobe_notice_script',
		EPROBE_URL . 'dist/js/notice-script.js',
		Utils\get_asset_info( 'notice-script', 'dependencies' ),
		Utils\get_asset_info( 'notice-script', 'version' ),
		true
	);

	wp_set_script_translations( 'eprobe_notice_script', 'elasticprobe' );

	wp_localize_script(
		'eprobe_notice_script',
		'epAdmin',
		array(
			'nonce' => wp_create_nonce( 'ep_admin_nonce' ),
		)
	);

	wp_enqueue_style(
		'eprobe_general_styles',
		EPROBE_URL . 'dist/css/general-styles.css',
		Utils\get_asset_info( 'general-styles', 'dependencies' ),
		Utils\get_asset_info( 'general-styles', 'version' )
	);
}

/**
 * Output current ElasticProbe dashboard screen
 *
 * @since 3.0
 */
function resolve_screen() {
	Screen::factory()->output();
}

/**
 * Admin menu actions
 *
 * Adds options page to admin menu.
 *
 * @since 1.9
 * @return void
 */
function action_admin_menu() {
	Installer::factory()->calculate_install_status();
	if ( true !== Installer::factory()->get_install_status() && ! Utils\is_top_level_admin_context() ) {
		return;
	}

	if ( ! Utils\is_site_indexable() && ! is_network_admin() ) {
		return;
	}

	$capability = ( defined( 'EPROBE_IS_NETWORK' ) && EPROBE_IS_NETWORK ) ? Utils\get_network_capability() : Utils\get_capability();

	add_menu_page(
		'ElasticProbe',
		'ElasticProbe',
		$capability,
		'elasticprobe',
		__NAMESPACE__ . '\resolve_screen',
		'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PCEtLSBHZW5lcmF0b3I6IEdyYXZpdC5pbyAtLT48c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHN0eWxlPSJpc29sYXRpb246aXNvbGF0ZSIgdmlld0JveD0iMCAwIDM0IDM2IiB3aWR0aD0iMzRwdCIgaGVpZ2h0PSIzNnB0Ij48ZGVmcz48Y2xpcFBhdGggaWQ9Il9jbGlwUGF0aF8yZWowNmJDVjc2YThxOUZtaVZ0UWYwMmRGQ0IyMzIzOSI+PHJlY3Qgd2lkdGg9IjM0IiBoZWlnaHQ9IjM2Ii8+PC9jbGlwUGF0aD48L2RlZnM+PGcgY2xpcC1wYXRoPSJ1cmwoI19jbGlwUGF0aF8yZWowNmJDVjc2YThxOUZtaVZ0UWYwMmRGQ0IyMzIzOSkiPjxyZWN0IHdpZHRoPSIzNCIgaGVpZ2h0PSIzNiIgc3R5bGU9ImZpbGw6cmdiKDgsOCw4KSIgZmlsbC1vcGFjaXR5PSIwIi8+PGxpbmVhckdyYWRpZW50IGlkPSJfbGdyYWRpZW50XzAiIHgxPSIwLjA1Nzc2NTE1MTUxNTE1MjQxIiB5MT0iMC4yNDQ2NjE3MjcyNjc4NjEyOCIgeDI9IjEuMDYwNDM4OTQ4MzA2NTk0OCIgeTI9IjAuNDA4ODYwODQ5NDc0OTI4NCIgZ3JhZGllbnRUcmFuc2Zvcm09Im1hdHJpeCgzMywwLDAsMjIuNDU0LC0zLDcuMjI1KSIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiPjxzdG9wIG9mZnNldD0iMCUiIHN0b3Atb3BhY2l0eT0iMSIgc3R5bGU9InN0b3AtY29sb3I6cmdiKDI1NSwyNTUsMjU1KSIvPjxzdG9wIG9mZnNldD0iNjEuODYxODc3MTk0MjgyNzIlIiBzdG9wLW9wYWNpdHk9IjEiIHN0eWxlPSJzdG9wLWNvbG9yOnJnYigyNTUsMjU1LDI1NSkiLz48c3RvcCBvZmZzZXQ9IjY0LjgzNzExNTgwNjI4MDUyJSIgc3RvcC1vcGFjaXR5PSIwIiBzdHlsZT0ic3RvcC1jb2xvcjpyZ2IoMjU1LDI1NSwyNTUpIi8+PHN0b3Agb2Zmc2V0PSI5NSUiIHN0b3Atb3BhY2l0eT0iMCIgc3R5bGU9InN0b3AtY29sb3I6cmdiKDI1NSwyNTUsMjU1KSIvPjxzdG9wIG9mZnNldD0iOTkuMTY2NjY2NjY2NjY2NjclIiBzdG9wLW9wYWNpdHk9IjEiIHN0eWxlPSJzdG9wLWNvbG9yOnJnYigyNTUsMjU1LDI1NSkiLz48L2xpbmVhckdyYWRpZW50PjxwYXRoIGQ9IiBNIDI5Ljk3NSAxNC40NTYgUSAyOS44NTcgMTkuNjMxIDI2LjQ3MSAyMS43NjUgUSAyNC43NzYgMjIuNzE0IDIxLjg3NyAyMi40NDIgTCAyMS44NzcgMjIuNDQyIEwgMjEuODc3IDIyLjQ0MiBRIDIwLjggMjUuNzg5IDIwLjggMjcuOTAzIEwgMjAuOCAyNy45MDMgTCAyMC44IDI3LjkwMyBRIDIwLjgxNSAyOC44NDcgMjEuMjAyIDI5LjEzNCBMIDIxLjk3NSAyOS4zMDIgUSAyMi4wNzQgMjkuMzQyIDIyLjA2NiAyOS40ODkgUSAyMi4wNTcgMjkuNjM2IDIxLjk3NSAyOS42NzUgTCAyMS41MiAyOS42NTEgUSAyMS41MjEgMjkuNTgyIDE4LjI4NSAyOS41ODIgTCAxNS40MjEgMjkuNTgyIEwgMTUuNDIxIDI5LjU4MiBRIDE2LjQ3IDI5LjU4MiAxNC44OTYgMjkuNjc1IEwgMTQuODk2IDI5LjY3NSBMIDE0Ljg5NiAyOS42NzUgUSAxNC43NDMgMjkuNzA3IDE0LjcyMSAyOS40ODkgTCAxNC43MjEgMjkuNDg5IEwgMTQuNzIxIDI5LjQ4OSBRIDE0LjY5OSAyOS4yNzEgMTQuODUyIDI5LjI0IEwgMTQuODUyIDI5LjI0IEwgMTQuODUyIDI5LjI0IFEgMTcuMjE0IDI4LjUyNSAxNy4yMTQgMjYuNTk3IEwgMTcuMjE0IDI2LjU5NyBMIDE3LjIxNCAyNi41OTcgUSAxNy4yMTQgMjUuNzI2IDE2LjE0MiAyNS43MjYgTCAxNi4xNDIgMjUuNzI2IEwgMTYuMTQyIDI1LjcyNiBRIDE0Ljc0MyAyNS43MjYgMTIuMzkyIDI3LjQ5OSBMIDEyLjM5MiAyNy40OTkgTCAxMi4zOTIgMjcuNDk5IFEgMTAuMDQyIDI5LjI3MSA4LjQ0NiAyOS4zMDIgTCA4LjQ0NiAyOS4zMDIgTCA4LjQ0NiAyOS4zMDIgUSA3Ljc5IDI5LjMwMiA2Ljc5NSAyOS4xIEwgNi43OTUgMjkuMSBMIDYuNzk1IDI5LjEgUSA1LjggMjguODk4IDUuMjA5IDI4Ljg5OCBMIDUuMjA5IDI4Ljg5OCBMIDUuMjA5IDI4Ljg5OCBRIDQuNzA2IDI4Ljg5OCA0LjAxOCAyOS4yNCBMIDQuMDE4IDI5LjI0IEwgNC4wMTggMjkuMjQgUSAzLjMyOSAyOS41ODIgMy4zMDcgMjkuNTgyIEwgMy4zMDcgMjkuNTgyIEwgMy4zMDcgMjkuNTgyIFEgMy4xNzYgMjkuNTgyIDMuMTc2IDI5LjMzMyBMIDMuMTc2IDI5LjMzMyBMIDMuMTc2IDI5LjMzMyBRIDMuMTc2IDI5LjE3OCAzLjI2MyAyOS4xMTYgTCAzLjI2MyAyOS4xMTYgTCAzLjI2MyAyOS4xMTYgUSA0LjAyOSAyOC41ODcgNS43NzggMjguMjQ1IEwgNS43NzggMjguMjQ1IEwgNS43NzggMjguMjQ1IFEgNy42NTggMjcuODQxIDguMjI3IDI3LjU2MSBMIDguMjI3IDI3LjU2MSBMIDguMjI3IDI3LjU2MSBRIDkuMzQyIDI3LjAwMSA5LjM0MiAyNS43MjYgTCA5LjM0MiAyNS43MjYgTCA5LjM0MiAyNS43MjYgUSA5LjM0MiAyNC45OCA4LjYyIDI0LjU3NiBMIDguNjIgMjQuNTc2IEwgOC42MiAyNC41NzYgUSA4LjAzIDI0LjIzNCA3LjIyMSAyNC4yMzQgTCA3LjIyMSAyNC4yMzQgTCA3LjIyMSAyNC4yMzQgUSA2LjQ3OCAyNC4yMzQgNS4yMzEgMjQuNDUyIEwgNS4yMzEgMjQuNDUyIEwgNS4yMzEgMjQuNDUyIFEgMy45ODUgMjQuNjY5IDMuMTU0IDI0LjY2OSBMIDMuMTU0IDI0LjY2OSBMIDMuMTU0IDI0LjY2OSBRIDEuNzU1IDI0LjY2OSAtMC41ODUgMjMuMzk0IEwgLTAuNTg1IDIzLjM5NCBMIC0wLjU4NSAyMy4zOTQgUSAtMC42OTQgMjMuMzMyIC0wLjY5NCAyMy4xNzcgTCAtMC42OTQgMjMuMTc3IEwgLTAuNjk0IDIzLjE3NyBRIC0wLjY5NCAyMi44MDQgLTAuNDU0IDIyLjk1OSBMIC0wLjQ1NCAyMi45NTkgTCAtMC40NTQgMjIuOTU5IFEgMC45MjQgMjMuODMgMS43MTEgMjMuODMgTCAxLjcxMSAyMy44MyBMIDEuNzExIDIzLjgzIFEgMy4wMjMgMjMuODMgNS4wNzggMjIuNzEgTCA1LjA3OCAyMi43MSBMIDUuMDc4IDIyLjcxIFEgNy4xMzQgMjEuNTkxIDguNDg5IDIxLjU5MSBMIDguNDg5IDIxLjU5MSBMIDguNDg5IDIxLjU5MSBRIDkuMDU4IDIxLjU5MSA5LjkyMSAyMS42ODQgTCA5LjkyMSAyMS42ODQgTCA5LjkyMSAyMS42ODQgUSAxMC43ODUgMjEuNzc3IDExLjMzMiAyMS43NzcgTCAxMS4zMzIgMjEuNzc3IEwgMTEuMzMyIDIxLjc3NyBRIDE1LjExNSAyMS43NzcgMTUuMTE1IDE5LjU3IEwgMTUuMTE1IDE5LjU3IEwgMTUuMTE1IDE5LjU3IFEgMTUuMTE1IDE4LjYwNiAxNC40MTUgMTguMDc3IEwgMTQuNDE1IDE4LjA3NyBMIDE0LjQxNSAxOC4wNzcgUSAxMy44NDYgMTcuNjQyIDEyLjk5NCAxNy42NDIgTCAxMi45OTQgMTcuNjQyIEwgMTIuOTk0IDE3LjY0MiBRIDEyLjAzMiAxNy42NDIgMTAuNTU2IDE4LjU0NCBMIDEwLjU1NiAxOC41NDQgTCAxMC41NTYgMTguNTQ0IFEgOS4wOCAxOS40NDUgOC4wOTYgMTkuNDQ1IEwgOC4wOTYgMTkuNDQ1IEwgOC4wOTYgMTkuNDQ1IFEgNy4yNjUgMTkuNDQ1IDUuOTk3IDE4Ljk0OCBMIDUuOTk3IDE4Ljk0OCBMIDUuOTk3IDE4Ljk0OCBRIDQuNzI4IDE4LjQ1IDMuODU0IDE4LjQ1IEwgMy44NTQgMTguNDUgTCAzLjg1NCAxOC40NSBRIDMuNDM4IDE4LjQ1IDIuODM3IDE4LjY5OSBMIDIuODM3IDE4LjY5OSBMIDIuODM3IDE4LjY5OSBRIDIuMjM2IDE4Ljk0OCAyLjE3IDE4Ljk0OCBMIDIuMTcgMTguOTQ4IEwgMi4xNyAxOC45NDggUSAyLjAzOSAxOC45NDggMi4wMzkgMTguNjk5IEwgMi4wMzkgMTguNjk5IEwgMi4wMzkgMTguNjk5IFEgMi4wMzkgMTguNTQ0IDIuMTI2IDE4LjQ4MSBMIDIuMTI2IDE4LjQ4MSBMIDIuMTI2IDE4LjQ4MSBRIDMuMTMyIDE3Ljc5NyA0LjIyNSAxNy43MzUgTCA0LjIyNSAxNy43MzUgTCA0LjIyNSAxNy43MzUgUSA0LjQ2NiAxNy43MDQgNC43OTQgMTcuNzUxIEwgNC43OTQgMTcuNzUxIEwgNC43OTQgMTcuNzUxIFEgNS4xMjIgMTcuNzk3IDUuMzQxIDE3Ljc5NyBMIDUuMzQxIDE3Ljc5NyBMIDUuMzQxIDE3Ljc5NyBRIDcuNzI0IDE3Ljc5NyA3LjcyNCAxNS45NjMgTCA3LjcyNCAxNS45NjMgTCA3LjcyNCAxNS45NjMgUSA3LjcyNCAxNC41MzIgNS43NzggMTQuNTMyIEwgNS43NzggMTQuNTMyIEwgNS43NzggMTQuNTMyIFEgNC43NSAxNC41MzIgMy4xODcgMTUuNDk2IEwgMy4xODcgMTUuNDk2IEwgMy4xODcgMTUuNDk2IFEgMS42MjMgMTYuNDYgMC41NzQgMTYuNDYgTCAwLjU3NCAxNi40NiBMIDAuNTc0IDE2LjQ2IFEgLTEuMjg1IDE2LjQ2IC0yLjg4MSAxNS44NjkgTCAtMi44ODEgMTUuODY5IEwgLTIuODgxIDE1Ljg2OSBRIC0zLjAzNCAxNS44MDcgLTIuOTkgMTUuNTkgTCAtMi45OSAxNS41OSBMIC0yLjk5IDE1LjU5IFEgLTIuOTQ3IDE1LjM0MSAtMi43OTMgMTUuNDM0IEwgLTIuNzkzIDE1LjQzNCBMIC0yLjc5MyAxNS40MzQgUSAtMi4xMzggMTUuNzQ1IC0xLjI2MyAxNS43NDUgTCAtMS4yNjMgMTUuNzQ1IEwgLTEuMjYzIDE1Ljc0NSBRIDAuMzk5IDE1Ljc0NSAzLjMxOCAxNC4wMDQgTCAzLjMxOCAxNC4wMDQgTCAzLjMxOCAxNC4wMDQgUSA2LjIzNyAxMi4yNjIgOC4xODMgMTIuMjYyIEwgOC4xODMgMTIuMjYyIEwgOC4xODMgMTIuMjYyIFEgOS43MTQgMTIuMjYyIDEyLjEzIDEzLjAwOSBMIDEyLjEzIDEzLjAwOSBMIDEyLjEzIDEzLjAwOSBRIDE0LjU0NiAxMy43NTUgMTUuNDg2IDEzLjc1NSBMIDE1LjQ4NiAxMy43NTUgTCAxNS40ODYgMTMuNzU1IFEgMTUuOTAyIDEzLjc1NSAxNi4yMyAxMy40NzUgTCAxNi4yMyAxMy40NzUgTCAxNi4yMyAxMy40NzUgUSAxNi42NDUgMTMuMTY0IDE2LjY0NSAxMi42NjcgTCAxNi42NDUgMTIuNjY3IEwgMTYuNjQ1IDEyLjY2NyBRIDE2LjY0NSAxMS45MiAxNS45NjcgMTEuNjcyIEwgMTUuOTY3IDExLjY3MiBMIDE1Ljk2NyAxMS42NzIgUSAxNS41OTYgMTEuNTQ3IDE0LjY1NSAxMS41NDcgTCAxNC42NTUgMTEuNTQ3IEwgMTQuNjU1IDExLjU0NyBRIDEzLjEyNSAxMS41NDcgMTEuNTgzIDEwLjMzNSBMIDExLjU4MyAxMC4zMzUgTCAxMS41ODMgMTAuMzM1IFEgMTAuMDQyIDkuMTIyIDkuNDA4IDkuMTIyIEwgOS40MDggOS4xMjIgTCA5LjQwOCA5LjEyMiBRIDkuMTQ1IDkuMTIyIDguNzUyIDkuMjc3IEwgOC43NTIgOS4yNzcgTCA4Ljc1MiA5LjI3NyBRIDguNTExIDkuMzcxIDguNTExIDkuMDI5IEwgOC41MTEgOS4wMjkgTCA4LjUxMSA5LjAyOSBRIDguNTExIDguODExIDguOTkyIDguNjI0IEwgOC45OTIgOC42MjQgTCA4Ljk5MiA4LjYyNCBRIDkuNDA4IDguNDY5IDkuNjI2IDguNDY5IEwgOS42MjYgOC40NjkgTCA5LjYyNiA4LjQ2OSBRIDEwLjM5MiA4LjQ2OSAxMS41NCA5LjE4NCBMIDExLjU0IDkuMTg0IEwgMTEuNTQgOS4xODQgUSAxMi42ODggOS44OTkgMTMuNDc1IDkuODk5IEwgMTMuNDc1IDkuODk5IEwgMTMuNDc1IDkuODk5IFEgMTQuNDgxIDkuODk5IDE1Ljk3OCA5LjAyOSBMIDE1Ljk3OCA5LjAyOSBMIDE1Ljk3OCA5LjAyOSBRIDE3LjQ3NiA4LjE1OCAxOC40NiA4LjE1OCBMIDE4LjQ2IDguMTU4IEwgMTguNDYgOC4xNTggUSAxOS40NDQgOC4xNTggMjAuOCA5LjMwOCBMIDIwLjggOS4zMDggTCAyMC44IDkuMzA4IFEgMjAuOTA5IDguNDM4IDIwLjg0MyA3LjU5OCBMIDIwLjg0MyA3LjU5OCBMIDIwLjg0MyA3LjU5OCBRIDIwLjg2NSA3LjQ3NCAyMC45OTcgNy4zNDkgTCAyMC45OTcgNy4zNDkgTCAyMC45OTcgNy4zNDkgUSAxOS4wNzMgNy4zODEgMjAuOTk3IDcuMjI1IEwgMjAuOTk3IDcuMjI1IEwgMjAuOTk3IDcuMjI1IEwgMjQuMzkxIDcuMjMyIEwgMjQuMzkxIDcuMjMyIFEgMjYuNzkzIDcuNDM3IDI3LjkzIDguNjQxIFEgMzAuMjI1IDEwLjgzOSAyOS45NzUgMTQuNDU2IFogIE0gNy42OCA5Ljc0NCBMIDcuNjggOS43NDQgTCA3LjY4IDkuNzQ0IFEgNi41NjUgMTAuNDI4IDUuNjQ3IDEwLjQ1OSBMIDUuNjQ3IDEwLjQ1OSBMIDUuNjQ3IDEwLjQ1OSBRIDUuMDU2IDEwLjQ5IDQuMTI3IDkuODIxIEwgNC4xMjcgOS44MjEgTCA0LjEyNyA5LjgyMSBRIDMuMTk4IDkuMTUzIDIuNDc2IDkuMTg0IEwgMi40NzYgOS4xODQgTCAyLjQ3NiA5LjE4NCBRIDIuMjE0IDkuMTg0IDEuNjY3IDkuMzA4IEwgMS42NjcgOS4zMDggTCAxLjY2NyA5LjMwOCBRIDEuNTM2IDkuMzcxIDEuNDcgOS4xODQgTCAxLjQ3IDkuMTg0IEwgMS40NyA5LjE4NCBRIDEuNDA1IDkuMDI5IDEuNTE0IDguOTM1IEwgMS41MTQgOC45MzUgTCAxLjUxNCA4LjkzNSBRIDIuNjUxIDguMTI3IDMuNzY2IDguMDY1IEwgMy43NjYgOC4wNjUgTCAzLjc2NiA4LjA2NSBRIDQuMjY5IDguMDM0IDUuMzA4IDguNzY0IEwgNS4zMDggOC43NjQgTCA1LjMwOCA4Ljc2NCBRIDYuMzQ2IDkuNDk1IDYuNzQgOS40NjQgTCA2Ljc0IDkuNDY0IEwgNi43NCA5LjQ2NCBRIDcuMDI0IDkuNDY0IDcuNTkzIDkuMzcxIEwgNy41OTMgOS4zNzEgTCA3LjU5MyA5LjM3MSBRIDcuNzAyIDkuMzM5IDcuNzQ2IDkuNDk1IEwgNy43NDYgOS40OTUgTCA3Ljc0NiA5LjQ5NSBRIDcuNzkgOS42ODIgNy42OCA5Ljc0NCBaICIgZmlsbD0idXJsKCNfbGdyYWRpZW50XzApIi8+PHBhdGggZD0iIE0gMTUuNzA1IDI3LjIxNCBMIDE1LjI5OSAyNy43OTcgTCAxNS4wNCAyOC4xMjEgTCAxNC41ODYgMjguNDQ1IEwgMTMuNjc5IDI4Ljg5OSBMIDEyLjc3MiAyOS4xNTggTCAxMi4yNTQgMjkuMjg3IEwgMTIuMTI0IDI5LjQxNyBMIDEyLjEyNCAyOS41NDYgTCAxMi4yNTQgMjkuNjc2IEwgMTIuMzgzIDI5Ljc0MSBMIDE0LjA2OCAyOS42NzYgTCAxNS4xNjkgMjkuNjExIEwgMTUuNzA1IDI3LjIxNCBaICIgZmlsbD0icmdiKDI1LDI1LDI1KSIvPjxwYXRoIGQ9IiBNIDE5LjY0IDcuMDY1IEwgMjAuODE2IDcgTCAyMy4yODkgNyBMIDI2LjExOSA3LjA2NSBMIDI4LjQ1NyA3LjMyNSBMIDMwLjQ1OSA4LjA0MiBMIDMyLjQwMyA5LjQ3NSBMIDMzLjUwNSAxMS4wODIgTCAzNC4wMjMgMTIuMzc3IEwgMzQuMDg4IDEzLjgwMyBMIDM0LjAyMyAxNS4yOTMgTCAzMy42OTkgMTYuNTg5IEwgMzMuMjQ1IDE3Ljk1MiBMIDMyLjE0NCAxOS42MzQgTCAzMS4xMDcgMjAuNjcgTCAyOS41NTIgMjEuNjkxIEwgMjguMTkyIDIyLjIyNSBMIDI2LjQ0MyAyMi42MTQgTCAyMy43ODYgMjIuNzIgTCAyMi40OSAyMi42MTQgTCAyMS41ODMgMjQuODgyIEwgMjEuMDk3IDI2LjY5NiBMIDIxIDI4LjEyMSBMIDIxLjI1OSAyOC45MjEgTCAyMi42MiAyOS4xNTggTCAyMi44MDIgMjkuNDE3IEwgMjIuNjIgMjkuODA2IEwgMjIuNDkgMzAgTCAyMC44MTYgMzAgTCAxNS43MDUgMzAgTCAxMi4zNCAzMCBMIDEyLjA1OSAyOS42MTEgTCAxMi4wNTkgMjkuMjY0IEwgMTMuNDIgMjguNzY5IEwgMTUuMDQgMjcuNzk3IEwgMTUuNTU4IDI2LjYxNSBMIDE1LjU1OCAyNi4zMDcgUSAxNS41OSAyNi4xNDcgMTUuNjIzIDI2LjA0OCBRIDE1Ljc4OCAyNS41NTcgMTYuMDEyIDI0LjY4MyBMIDE2LjMzNiAyMy41ODYgTCAxNi41OTUgMjIuNzIgTCAxNy40MzcgMTkuNzYzIEwgMTguNjAzIDE1Ljc0NiBMIDIwLjU3NiA5LjAwOCBMIDIwLjgxNiA3LjcxMyBMIDE5LjY0IDcuNTc0IEwgMTkuNjQgNy4wNjUgWiAgTSAyOC44OTggMTQuODggQyAyOC44OTggMTQuMTkxIDI4LjYzNyAxMy41MDUgMjguMTE1IDEyLjk4MyBDIDI3LjA2NyAxMS45MzUgMjUuMzY1IDExLjkzNSAyNC4zMTcgMTIuOTgzIEMgMjMuMjY5IDE0LjAzMSAyMy4yNjkgMTUuNzMyIDI0LjMxNyAxNi43OCBDIDI1LjM2NSAxNy44MjggMjcuMDY3IDE3LjgyOCAyOC4xMTUgMTYuNzggQyAyOC42MTcgMTYuMjc1IDI4Ljg5OSAxNS41OTIgMjguODk4IDE0Ljg4IEwgMjguODk4IDE0Ljg4IFogIE0gMTkuNzEgMjEuMzg0IEMgMTkuNTY3IDIxLjI0IDE5LjU2NyAyMS4wMDUgMTkuNzEgMjAuODYxIEwgMjIuMjggMTguMjk1IEMgMjIuNDI0IDE4LjE1MSAyMi42NTkgMTguMTUxIDIyLjgwMiAxOC4yOTUgQyAyMi44NzQgMTguMzY3IDIyLjkxIDE4LjQ2MSAyMi45MSAxOC41NTYgQyAyMi45MSAxOC42NTEgMjIuODc0IDE4Ljc0NSAyMi44MDIgMTguODE3IEwgMjAuMjMzIDIxLjM4NyBDIDIwLjA4NiAyMS41MjcgMTkuODU0IDIxLjUyNyAxOS43MSAyMS4zODQgTCAxOS43MSAyMS4zODQgTCAxOS43MSAyMS4zODQgWiAgTSAyMy4wNDQgMTQuODggQyAyMy4wNDQgMTQuMDY3IDIzLjM1NCAxMy4yNTQgMjMuOTcxIDEyLjYzNyBDIDI1LjIwOSAxMS4zOTkgMjcuMjIgMTEuMzk5IDI4LjQ1NyAxMi42MzcgQyAyOS42OTUgMTMuODc0IDI5LjY5NSAxNS44ODUgMjguNDU3IDE3LjEyMyBDIDI3LjIyIDE4LjM2IDI1LjIwOSAxOC4zNiAyMy45NzEgMTcuMTIzIEMgMjMuMzc3IDE2LjUyOCAyMy4wNDMgMTUuNzIxIDIzLjA0NCAxNC44OCBMIDIzLjA0NCAxNC44OCBMIDIzLjA0NCAxNC44OCBaICBNIDIwLjU3NiAyMS43MyBMIDIzLjE0NSAxOS4xNiBDIDIzLjQxMiAxOC44OTMgMjMuNDcxIDE4LjQ4MiAyMy4yODkgMTguMTUxIEwgMjMuNTk5IDE3Ljg0MSBDIDI1LjE1IDE5LjIxMiAyNy41MjcgMTkuMTU3IDI5LjAwOSAxNy42NzQgQyAzMC41NSAxNi4xMzMgMzAuNTUgMTMuNjI5IDI5LjAwOSAxMi4wODggQyAyNy40NjggMTAuNTQ3IDI0Ljk2NCAxMC41NDcgMjMuNDIzIDEyLjA4OCBDIDIyLjY3NSAxMi44MzYgMjIuMjY3IDEzLjgyNSAyMi4yNjcgMTQuODgzIEMgMjIuMjY3IDE1Ljg1NiAyMi42MTYgMTYuNzc3IDIzLjI1NiAxNy40OTggTCAyMi45NDYgMTcuODA4IEMgMjIuNjIzIDE3LjYzMiAyMi4yMDggMTcuNjc4IDIxLjkzNyAxNy45NTIgTCAxOS4zNjggMjAuNTIyIEMgMTkuMjAxIDIwLjY4OCAxOS4xMTYgMjAuOTA3IDE5LjExNiAyMS4xMjYgQyAxOS4xMTYgMjEuMzQ0IDE5LjIwMSAyMS41NjMgMTkuMzY4IDIxLjczIEMgMTkuNjk3IDIyLjA2MyAyMC4yNDMgMjIuMDYzIDIwLjU3NiAyMS43MyBMIDIwLjU3NiAyMS43MyBMIDIwLjU3NiAyMS43MyBaICIgZmlsbC1ydWxlPSJldmVub2RkIiBmaWxsPSJyZ2IoMjU1LDI1NSwyNTUpIi8+PHBhdGggZD0iIE0gMjMuMDQ0IDE0Ljg4IEMgMjMuMDQ0IDE0LjA2NyAyMy4zNTQgMTMuMjU0IDIzLjk3MSAxMi42MzcgQyAyNS4yMDkgMTEuMzk5IDI3LjIyIDExLjM5OSAyOC40NTcgMTIuNjM3IEMgMjkuNjk1IDEzLjg3NCAyOS42OTUgMTUuODg1IDI4LjQ1NyAxNy4xMjMgQyAyNy4yMiAxOC4zNiAyNS4yMDkgMTguMzYgMjMuOTcxIDE3LjEyMyBDIDIzLjM3NyAxNi41MjggMjMuMDQzIDE1LjcyMSAyMy4wNDQgMTQuODggTCAyMy4wNDQgMTQuODggWiAgTSAyOC44OTggMTQuODggQyAyOC44OTggMTQuMTkxIDI4LjYzNyAxMy41MDUgMjguMTE1IDEyLjk4MyBDIDI3LjA2NyAxMS45MzUgMjUuMzY1IDExLjkzNSAyNC4zMTcgMTIuOTgzIEMgMjMuMjY5IDE0LjAzMSAyMy4yNjkgMTUuNzMyIDI0LjMxNyAxNi43OCBDIDI1LjM2NSAxNy44MjggMjcuMDY3IDE3LjgyOCAyOC4xMTUgMTYuNzggQyAyOC42MTcgMTYuMjc1IDI4Ljg5OSAxNS41OTIgMjguODk4IDE0Ljg4IFogIE0gMjAuNTc2IDIxLjczIEwgMjMuMTQ1IDE5LjE2IEMgMjMuNDEyIDE4Ljg5MyAyMy40NzEgMTguNDgyIDIzLjI4OSAxOC4xNTEgTCAyMy41OTkgMTcuODQxIEMgMjUuMTUgMTkuMjEyIDI3LjUyNyAxOS4xNTcgMjkuMDA5IDE3LjY3NCBDIDMwLjU1IDE2LjEzMyAzMC41NSAxMy42MjkgMjkuMDA5IDEyLjA4OCBDIDI3LjQ2OCAxMC41NDcgMjQuOTY0IDEwLjU0NyAyMy40MjMgMTIuMDg4IEMgMjIuNjc1IDEyLjgzNiAyMi4yNjcgMTMuODI1IDIyLjI2NyAxNC44ODMgQyAyMi4yNjcgMTUuODU2IDIyLjYxNiAxNi43NzcgMjMuMjU2IDE3LjQ5OCBMIDIyLjk0NiAxNy44MDggQyAyMi42MjMgMTcuNjMyIDIyLjIwOCAxNy42NzggMjEuOTM3IDE3Ljk1MiBMIDE5LjM2OCAyMC41MjIgQyAxOS4yMDEgMjAuNjg4IDE5LjExNiAyMC45MDcgMTkuMTE2IDIxLjEyNiBDIDE5LjExNiAyMS4zNDQgMTkuMjAxIDIxLjU2MyAxOS4zNjggMjEuNzMgQyAxOS42OTcgMjIuMDYzIDIwLjI0MyAyMi4wNjMgMjAuNTc2IDIxLjczIEwgMjAuNTc2IDIxLjczIFogIE0gMTkuNzEgMjEuMzg0IEMgMTkuNTY3IDIxLjI0IDE5LjU2NyAyMS4wMDUgMTkuNzEgMjAuODYxIEwgMjIuMjggMTguMjk1IEMgMjIuNDI0IDE4LjE1MSAyMi42NTkgMTguMTUxIDIyLjgwMiAxOC4yOTUgQyAyMi44NzQgMTguMzY3IDIyLjkxIDE4LjQ2MSAyMi45MSAxOC41NTYgQyAyMi45MSAxOC42NTEgMjIuODc0IDE4Ljc0NSAyMi44MDIgMTguODE3IEwgMjAuMjMzIDIxLjM4NyBDIDIwLjA4NiAyMS41MjcgMTkuODU0IDIxLjUyNyAxOS43MSAyMS4zODQgTCAxOS43MSAyMS4zODQgWiAiIGZpbGw9Im5vbmUiLz48L2c+PC9zdmc+'
	);

	if ( ! Utils\is_top_level_admin_context() ) {
		return;
	}

	add_submenu_page(
		'elasticprobe',
		esc_html__( 'ElasticProbe Features', 'elasticprobe' ),
		esc_html__( 'Features', 'elasticprobe' ),
		$capability,
		'elasticprobe',
		__NAMESPACE__ . '\resolve_screen'
	);

	add_submenu_page(
		'elasticprobe',
		esc_html__( 'ElasticProbe Settings', 'elasticprobe' ),
		esc_html__( 'Settings', 'elasticprobe' ),
		$capability,
		'elasticprobe-settings',
		__NAMESPACE__ . '\resolve_screen'
	);

	add_submenu_page(
		'elasticprobe',
		'ElasticProbe ' . esc_html__( 'Sync', 'elasticprobe' ),
		esc_html__( 'Sync', 'elasticprobe' ),
		$capability,
		'elasticprobe-sync',
		__NAMESPACE__ . '\resolve_screen'
	);

	add_submenu_page(
		'elasticprobe',
		esc_html__( 'ElasticProbe Index Health', 'elasticprobe' ),
		esc_html__( 'Index Health', 'elasticprobe' ),
		$capability,
		'elasticprobe-health',
		__NAMESPACE__ . '\resolve_screen'
	);

	add_submenu_page(
		'elasticprobe',
		esc_html__( 'ElasticProbe Status Report', 'elasticprobe' ),
		esc_html__( 'Status Report', 'elasticprobe' ),
		$capability,
		'elasticprobe-status-report',
		__NAMESPACE__ . '\resolve_screen'
	);
}

/**
 * Languages supported in Elasticsearch mappings.
 *
 * If $format is 'elasticsearch', the array format is `Elasticsearch analyzer name => [ WordPress language package names ]`.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html
 * @since 4.7.0
 * @param string $format Format of the return ('locales' or 'elasticsearch' )
 * @return array
 */
function get_available_languages( string $format = 'elasticsearch' ): array {
	/**
	 * Filter available languages in Elasticsearch.
	 *
	 * The returned array should follow the format `Elasticsearch analyzer name => [ WordPress language package names ]`.
	 *
	 * @since 4.7.0
	 * @hook eprobe_available_languages
	 * @param  {bool} $available_languages List of available languages
	 * @return {bool} New list
	 */
	$es_languages = apply_filters(
		'eprobe_available_languages',
		[
			'arabic'     => [ 'ar', 'ary' ],
			'armenian'   => [ 'hy' ],
			'basque'     => [ 'eu' ],
			'bengali'    => [ 'bn', 'bn_BD' ],
			'brazilian'  => [ 'pt_BR' ],
			'bulgarian'  => [ 'bg', 'bg_BG' ],
			'catalan'    => [ 'ca' ],
			'cjk'        => [], // CJK characters (not a language)
			'czech'      => [ 'cs', 'cs_CZ' ],
			'danish'     => [ 'da', 'da_DK' ],
			'dutch'      => [ 'nl_NL_formal', 'nl_NL', 'nl_BE' ],
			'english'    => [ 'en', 'en_AU', 'en_GB', 'en_NZ', 'en_CA', 'en_US', 'en_ZA' ],
			'estonian'   => [ 'et' ],
			'finnish'    => [ 'fi' ],
			'french'     => [ 'fr', 'fr_CA', 'fr_FR', 'fr_BE' ],
			'galician'   => [ 'gl_ES' ],
			'german'     => [ 'de', 'de_DE', 'de_DE_formal', 'de_CH', 'de_CH_informal', 'de_AT' ],
			'greek'      => [ 'el' ],
			'hindi'      => [ 'hi_IN' ],
			'hungarian'  => [ 'hu_HU' ],
			'indonesian' => [ 'id_ID' ],
			'irish'      => [], // WordPress doesn't support Irish as an active locale currently
			'italian'    => [ 'it_IT' ],
			'latvian'    => [ 'lv' ],
			'lithuanian' => [ 'lt_LT' ],
			'norwegian'  => [ 'nb_NO' ],
			'persian'    => [ 'fa_IR' ],
			'portuguese' => [ 'pt', 'pt_AO', 'pt_PT', 'pt_PT_ao90' ],
			'romanian'   => [ 'ro_RO' ],
			'russian'    => [ 'ru_RU' ],
			'sorani'     => [ 'ckb' ],
			'spanish'    => [ 'es_CR', 'es_MX', 'es_VE', 'es_AR', 'es_CL', 'es_GT', 'es_PE', 'es_ES', 'es_UY', 'es_CO' ],
			'swedish'    => [ 'sv_SE' ],
			'turkish'    => [ 'tr_TR' ],
			'thai'       => [ 'th' ],
		]
	);

	if ( 'locales' === $format ) {
		$arr = array_reduce(
			$es_languages,
			function ( $acc, $lang ) {
				$lang = array_filter(
					$lang,
					function ( $locale ) {
						// English is always added. This removes the duplicates
						return ! in_array( $locale, [ 'en', 'en_US' ], true );
					}
				);
				$acc  = array_merge( $acc, $lang );
				return $acc;
			},
			[]
		);
		return $arr;
	}

	return $es_languages;
}

/**
 * Uses the language from EP settings in mapping.
 *
 * @param string $language The current language.
 * @param string $context  The context where the function is running.
 * @return string          The updated language.
 */
function use_language_in_setting( $language = 'english', $context = '' ) {
	global $locale, $wp_local_package;

	// Get the currently set language.
	$eprobe_language = Utils\get_language();

	// Bail early if no EP language is set.
	if ( empty( $eprobe_language ) ) {
		return $language;
	}

	/**
	 * WordPress does not reset the language when switch_blog() is called.
	 *
	 * @see https://core.trac.wordpress.org/ticket/49263
	 */
	if ( 'site-default' === $eprobe_language ) {
		$locale           = null;
		$wp_local_package = null;
		$eprobe_language  = get_locale();
	}

	require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	$translations = wp_get_available_translations();

	// Default to en_US if not in the array of available translations.
	if ( ! empty( $translations[ $eprobe_language ]['english_name'] ) ) {
		$wp_language = $translations[ $eprobe_language ]['language'];
	} else {
		$wp_language = 'en_US';
	}

	$es_languages = get_available_languages();

	/**
	 * Languages supported in Elasticsearch snowball token filters.
	 *
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-snowball-tokenfilter.html
	 */
	$es_snowball_languages = [
		'Armenian',
		'Basque',
		'Catalan',
		'Danish',
		'Dutch',
		'English',
		'Finnish',
		'French',
		'German',
		'German2', // currently unused
		'Hungarian',
		'Italian',
		'Kp', // currently unused
		'Lithuanian',
		'Lovins', // currently unused
		'Norwegian',
		'Porter', // currently unused
		'Portuguese',
		'Romanian',
		'Russian',
		'Spanish',
		'Swedish',
		'Turkish',
	];

	$es_snowball_similar = [
		'Brazilian' => 'Portuguese',
	];

	foreach ( $es_languages as $analyzer_name => $analyzer_language_codes ) {
		if ( in_array( $wp_language, $analyzer_language_codes, true ) ) {
			$language = $analyzer_name;
			break;
		}
	}

	if ( 'filter_ewp_snowball' === $context ) {
		$uc_first_language = ucfirst( $language );
		if ( in_array( $uc_first_language, $es_snowball_languages, true ) ) {
			return $uc_first_language;
		}

		return $es_snowball_similar[ $uc_first_language ] ?? 'English';
	}

	if ( 'filter_ep_stop' === $context ) {
		return "_{$language}_";
	}

	return $language;
}

/**
 * Add column to sites admin table.
 *
 * @param string[] $columns Array of columns.
 *
 * @return string[]
 */
function filter_blogs_columns( $columns ) {
	$columns['elasticprobe'] = esc_html__( 'ElasticProbe Indexing', 'elasticprobe' );

	return $columns;
}

/**
 * Populate column with checkbox/switch.
 *
 * @param string $column_name The name of the current column.
 * @param int    $blog_id The blog ID.
 *
 * @return void | string
 */
function add_blogs_column( $column_name, $blog_id ) {
	if ( 'elasticprobe' !== $column_name ) {
		return;
	}

	$site = get_site( $blog_id );
	if ( $site->deleted || $site->archived || $site->spam ) {
		return;
	}

	$is_indexable = get_site_meta( $blog_id, 'ep_indexable', true );
	$is_indexable = '' !== $is_indexable ? $is_indexable : 'yes';

	printf(
		'<input %1$s class="index-toggle" data-blog-id="%2$s" disabled type="checkbox">',
		checked( $is_indexable, 'yes', false ),
		esc_attr( $blog_id )
	);
}

/**
 * AJAX callback to update ep_indexable site option.
 */
function action_wp_ajax_ep_site_admin() {
	$blog_id = ( ! empty( $_POST['blog_id'] ) ) ? absint( wp_unslash( $_POST['blog_id'] ) ) : - 1;
	$checked = ( ! empty( $_POST['checked'] ) ) ? sanitize_text_field( wp_unslash( $_POST['checked'] ) ) : 'no';

	if ( - 1 === $blog_id || ! check_ajax_referer( 'epsa', 'nonce', false ) ) {
		return wp_send_json_error();
	}

	/**
	 * NOTE: This will be removed in ElasticProbe 5.0.0. Implementations should rely on site_meta since 4.7.0.
	 */
	$result = update_blog_option( $blog_id, 'ep_indexable', $checked );

	$result = update_site_meta( $blog_id, 'ep_indexable', $checked );
	$data   = [
		'blog_id' => $blog_id,
		'result'  => $result,
	];

	return wp_send_json_success( $data );
}

/**
 * Handle the fetch for indexing status
 */
function handle_indexing_status() {
	$indexing_status = \ElasticProbe\Utils\get_indexing_status();

	$status = array(
		'method'        => '',
		'items_indexed' => 0,
		'total_items'   => 0,
		'indexable'     => '',
	);

	if ( ! empty( $indexing_status ) ) {
		if ( isset( $indexing_status['method'] ) && 'cli' === $indexing_status['method'] ) {
			$status['method']        = $indexing_status['method'];
			$status['items_indexed'] = $indexing_status['items_indexed'];
			$status['total_items']   = $indexing_status['total_items'];
			$status['indexable']     = $indexing_status['slug'];
		} else {
			$status['method']        = 'dashboard';
			$status['items_indexed'] = isset( $indexing_status['offset'] ) ? $indexing_status['offset'] : 0;
			$status['total_items']   = isset( $indexing_status['found_items'] ) ? $indexing_status['found_items'] : 0;
			$status['indexable']     = isset( $indexing_status['current_sync_item']['indexable'] ) ? $indexing_status['current_sync_item']['indexable'] : '';
		}
	}

	return $status;
}

/**
 * Add an ElasticProbe block category.
 *
 * @param array $block_categories Array of categories for block types.
 * @return array Array of categories for block types.
 */
function block_categories( $block_categories ) {
	$block_categories[] = [
		'slug'  => 'elasticprobe',
		'title' => 'ElasticProbe',
	];

	return $block_categories;
}

/**
 * Enqueue shared block editor assets.
 *
 * @return void
 */
function block_assets() {
	wp_enqueue_script(
		'elasticprobe-blocks',
		EPROBE_URL . 'dist/js/blocks-script.js',
		Utils\get_asset_info( 'blocks-script', 'dependencies' ),
		Utils\get_asset_info( 'blocks-script', 'version' ),
		true
	);

	wp_localize_script(
		'elasticprobe-blocks',
		'epBlocks',
		[
			'syncUrl' => Utils\get_sync_url(),
		]
	);
}
