<?php
/**
 * Settings screen.
 *
 * @since 5.0.0
 * @package ElasticProbe
 */

namespace ElasticProbe\Screen;

use ElasticProbe\Screen;
use ElasticProbe\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Settings screen Class.
 */
class Settings {
	/**
	 * Previous language
	 *
	 * @var string
	 */
	protected $prev_ep_language = '';

	/**
	 * Previous URL host
	 *
	 * @var string
	 */
	protected $prev_ep_host = '';

	/**
	 * Previous credentials array
	 *
	 * @var array
	 */
	protected $prev_ep_credentials = [];

	/**
	 * Previous "Content Items per Index Cycle" setting
	 *
	 * @var int
	 */
	protected $prev_ep_bulk_setting = 350;

	/**
	 * Previous subscription ID
	 *
	 * @var string
	 */
	protected $prev_ep_sid = '';

	/**
	 * Initialize class
	 */
	public function setup() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'admin_init', [ $this, 'action_admin_init' ], 8 );
	}

	/**
	 * Enqueue script
	 */
	public function admin_enqueue_scripts() {
		if ( 'settings' !== Screen::factory()->get_current_screen() ) {
			return;
		}

		wp_enqueue_script(
			'eprobe_settings_scripts',
			EPROBE_URL . 'dist/js/settings-script.js',
			Utils\get_asset_info( 'settings-script', 'dependencies' ),
			Utils\get_asset_info( 'settings-script', 'version' ),
			true
		);

		wp_set_script_translations( 'eprobe_settings_scripts', 'elasticprobe' );
	}

	/**
	 * Admin-init actions
	 *
	 * Sets up Settings API.
	 */
	public function action_admin_init() {
		$post = wp_unslash( $_POST );

		if ( empty( $post['ep_settings_nonce'] ) || ! wp_verify_nonce( $post['ep_settings_nonce'], 'elasticpress_settings' ) ) {
			return;
		}

		$this->prev_ep_language     = Utils\get_language();
		$this->prev_ep_host         = Utils\get_host();
		$this->prev_ep_credentials  = Utils\get_epio_credentials();
		$this->prev_ep_bulk_setting = Utils\get_option( 'eprobe_bulk_setting', 350 );
		$this->prev_ep_sid          = Utils\get_subscription_id();

		$language = sanitize_text_field( $post['eprobe_language'] );
		Utils\update_option( 'eprobe_language', $language );

		if ( isset( $post['ep_host'] ) ) {
			$host = esc_url_raw( trim( $post['ep_host'] ) );
			Utils\update_option( 'eprobe_host', $host );
		}

		if ( isset( $post['sid'] ) ) {
			$sid = sanitize_text_field( trim( $post['sid'] ) );
			Utils\update_option( 'eprobe_subscription_id', $sid );
		}

		if ( isset( $post['eprobe_credentials'] ) ) {
			$credentials = ( isset( $post['eprobe_credentials'] ) ) ? Utils\sanitize_credentials( $post['eprobe_credentials'] ) : [
				'username' => '',
				'token'    => '',
			];

			Utils\update_option( 'eprobe_credentials', $credentials );
		}

		if ( isset( $post['eprobe_bulk_setting'] ) ) {
			Utils\update_option( 'eprobe_bulk_setting', $this->sanitize_bulk_settings( $post['eprobe_bulk_setting'] ) );
		}

		$es_info = \ElasticProbe\Elasticsearch::factory()->get_elasticsearch_info( true );
		if ( empty( $es_info['version'] ) ) {
			add_action( 'admin_notices', [ $this, 'add_validation_notice' ] );

			unset( $_POST['ep_host'] ); // Needed to prevent going to the next installation step
			$this->reset_settings();
		}
	}

	/**
	 * Add a notice to the Settings form when the host was not set yet
	 */
	public function add_validation_notice() {
		$target = ( Utils\is_epio() ) ?
			_x( 'WPProbe.com account', 'Settings validation message', 'elasticprobe' ) :
			_x( 'Elasticsearch server', 'Settings validation message', 'elasticprobe' );

		if ( empty( $this->prev_ep_host ) ) {
			// Setting it for the first time -- probably during the install process.
			$message = sprintf(
				/* translators: WPProbe.com account or ES server. */
				__( 'It was not possible to connect to your %s. Please check your settings and try again.', 'elasticprobe' ),
				$target
			);
		} else {
			$message = sprintf(
				/* translators: WPProbe.com account or ES server. */
				__( 'It was not possible to connect to your %s. Your settings were reverted.', 'elasticprobe' ),
				$target
			);
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php echo wp_kses( $message, 'ep-html' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Sanitize bulk settings.
	 *
	 * @param int $bulk_settings Number of bulk content items
	 * @return int
	 */
	protected function sanitize_bulk_settings( $bulk_settings = 350 ) {
		$bulk_settings = absint( $bulk_settings );

		return ( 0 === $bulk_settings ) ? 350 : $bulk_settings;
	}

	/**
	 * Reset settings to their previous values
	 */
	protected function reset_settings() {
		Utils\update_option( 'eprobe_language', $this->prev_ep_language );
		Utils\update_option( 'eprobe_host', $this->prev_ep_host );
		Utils\update_option( 'eprobe_credentials', $this->prev_ep_credentials );
		Utils\update_option( 'eprobe_bulk_setting', $this->prev_ep_bulk_setting );
		Utils\update_option( 'eprobe_subscription_id', $this->prev_ep_sid );

		\ElasticProbe\Elasticsearch::factory()->get_elasticsearch_info( true );
	}
}
