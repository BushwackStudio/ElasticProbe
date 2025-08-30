<?php
/**
 * Plugin Name:       ElasticProbe
 * Plugin URI:        https://github.com/BushwackStudio/ElasticProbe
 * Description:       Supercharge your WordPress search with ElasticSearch® precision.
 * Version:           1.3.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            BushwackStudio
 * Author URI:        https://github.com/orgs/BushwackStudio
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       elasticprobe
 * Domain Path:       /lang
 *
 * This program derives work from 10up's Elasticpress, Alley Interactive's SearchPress
 * and Automattic's VIP search plugin:
 *
 * Copyright (C) 2012-2013 Automattic
 * Copyright (C) 2013 SearchPress
 * Copyright (C) 2025 10up
 *
 * @package  elasticprobe
 */

namespace ElasticProbe;

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'EPROBE_URL', plugin_dir_url( __FILE__ ) );
define( 'EPROBE_PATH', plugin_dir_path( __FILE__ ) );
define( 'EPROBE_FILE', plugin_basename( __FILE__ ) );
define( 'EPROBE_VERSION', '1.3.0' );

define( 'EPROBE_PHP_VERSION_MIN', '7.4' );

if ( ! version_compare( phpversion(), EPROBE_PHP_VERSION_MIN, '>=' ) ) {
	add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Minimum required PHP version */
							__( 'ElasticProbe requires PHP version %s or later. Please upgrade PHP or disable the plugin.', 'elasticprobe' ),
							EPROBE_PHP_VERSION_MIN
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

// Require Composer autoloader if it exists.
if ( file_exists( __DIR__ . '/vendor-prefixed/autoload.php' ) ) {
	require_once __DIR__ . '/vendor-prefixed/autoload.php';
}

/**
 * PSR-4-ish autoloading
 *
 * @since 2.6
 */
spl_autoload_register(
	function ( $class_name ) {
			// project-specific namespace prefix.
			$prefix = 'ElasticProbe\\';

			// base directory for the namespace prefix.
			$base_dir = __DIR__ . '/includes/classes/';

			// does the class use the namespace prefix?
			$len = strlen( $prefix );

		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			return;
		}

			$relative_class = substr( $class_name, $len );

			$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			// if the file exists, require it.
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * We compare the current ES version to this compatibility version number. Compatibility is true when:
 *
 * EPROBE_ES_VERSION_MIN <= YOUR ES VERSION <= EPROBE_ES_VERSION_MAX
 *
 * We don't check minor releases so if your ES version if 7.10.1, we consider that 7.10 in our comparison.
 *
 * @since  2.2
 */
define( 'EPROBE_ES_VERSION_MAX', '8.99' );
define( 'EPROBE_ES_VERSION_MIN', '7.0' );

require_once __DIR__ . '/includes/compat.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/health-check.php';

// Define a constant if we're network activated to allow plugin to respond accordingly.
$network_activated = Utils\is_network_activated( EPROBE_FILE );

if ( $network_activated ) {
	define( 'EPROBE_IS_NETWORK', true );
}

/**
 * Return the ElasticProbe container
 *
 * @since 4.7.0
 * @return Container
 */
function get_container() {
	static $container = null;

	if ( ! $container ) {
		$container = new Container();
	}

	return $container;
}

/**
 * Sets up the indexables and features.
 *
 * @return void
 */
function register_indexable_posts() {
	/**
	 * Handle indexables
	 */
	Indexables::factory()->register( new Indexable\Post\Post() );

	/**
	 * Handle features
	 */
	Features::factory()->register_feature(
		new Feature\Search\Search()
	);

	Features::factory()->register_feature(
		new Feature\InstantResults\InstantResults()
	);

	// TODO: work out the custom endpoints and enable this
	Features::factory()->register_feature(
		new Feature\Autosuggest\Autosuggest()
	);

	Features::factory()->register_feature(
		new Feature\DidYouMean\DidYouMean()
	);

	Features::factory()->register_feature(
		new Feature\WooCommerce\WooCommerce()
	);

	Features::factory()->register_feature(
		new Feature\Facets\Facets()
	);

	Features::factory()->register_feature(
		new Feature\RelatedPosts\RelatedPosts()
	);

	Features::factory()->register_feature(
		new Feature\SearchOrdering\SearchOrdering()
	);

	Features::factory()->register_feature(
		new Feature\ProtectedContent\ProtectedContent()
	);

	// TODO: Pipelines should be worked out
	// Features::factory()->register_feature(
	// new Feature\Documents\Documents()
	// );

	Features::factory()->register_feature(
		new Feature\AcfRepeater\AcfRepeater()
	);

	Features::factory()->register_feature(
		new Feature\Comments\Comments()
	);

	Features::factory()->register_feature(
		new Feature\Terms\Terms()
	);

	/**
	 * Register search algorithms
	 */
	SearchAlgorithms::factory()->register( new SearchAlgorithm\DefaultAlgorithm() );
	SearchAlgorithms::factory()->register( new SearchAlgorithm\Version_350() );
	SearchAlgorithms::factory()->register( new SearchAlgorithm\Version_400() );

	/**
	 * Filter the query logger object
	 *
	 * @since 4.4.0
	 * @hook eprobe_query_logger
	 * @param {QueryLogger} $query_logger Default query logger
	 * @return {QueryLogger} New query logger
	 */
	$query_logger = apply_filters( 'eprobe_query_logger', new \ElasticProbe\QueryLogger() );
	get_container()->set( '\ElasticProbe\QueryLogger', $query_logger, true );

	get_container()->set( '\ElasticProbe\BlockTemplateUtils', new \ElasticProbe\BlockTemplateUtils(), true );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\register_indexable_posts' );

/**
 * Set the availability of dashboard sync functionality. Defaults to true (enabled).
 *
 * Sync can be disabled by defining EPROBE_DASHBOARD_SYNC as false in wp-config.php.
 * NOTE: Must be defined BEFORE `require_once(ABSPATH . 'wp-settings.php');` in wp-config.php.
 *
 * @since  2.3
 */
if ( ! defined( 'EPROBE_DASHBOARD_SYNC' ) ) {
	define( 'EPROBE_DASHBOARD_SYNC', true );
}

/**
 * Setup installer
 */
Installer::factory();

/**
 * Setup screen
 */
Screen::factory();

/**
 * Setup dashboard
 */
require_once __DIR__ . '/includes/dashboard.php';
Dashboard\setup();

/**
 * WP CLI Commands
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'elasticprobe', __NAMESPACE__ . '\Command' );
}

/**
 * Setup upgrades
 */
Upgrades::factory();

/**
 * Handle upgrades. Certain version require a re-sync on upgrade.
 * Deprecated in favor of `\ElasticProbe\Upgrades::factory()`.
 *
 * @since  2.2
 */
function handle_upgrades() {
	_deprecated_function( __CLASS__, '0.1.0', '\ElasticProbe\Upgrades::factory()' );
}

/**
 * Load text domain and handle debugging
 *
 * @since  2.2
 */
function setup_misc() {
	if ( is_user_logged_in() && ! defined( 'EPROBE_DEBUG' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		define( 'EPROBE_DEBUG', is_plugin_active( 'debug-bar-elasticprobe/debug-bar-elasticprobe.php' ) );
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\setup_misc' );

/**
 * Load text domain
 *
 * @since 5.1.4
 */
function i18n() {
	load_plugin_textdomain( 'elasticprobe', false, basename( __DIR__ ) . '/lang' );
}
add_action( 'init', __NAMESPACE__ . '\i18n' );

/**
 * Set up role(s) with EP capability
 */
function setup_roles() {
	// add custom capabilities to admin role
	$role = get_role( 'administrator' );

	$role->add_cap( Utils\get_capability() );
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\setup_roles' );

/**
 * Fires after ElasticProbe plugin is loaded
 *
 * @since  2.0
 * @hook eprobe_loaded
 */
do_action( 'eprobe_loaded' );
