<?php
/**
 * Class for interacting with WPProbe.com
 *
 * @since 4.5.0
 * @package elasticprobe
 */

namespace ElasticProbe;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ElasticPressIo class
 *
 * @package elasticprobe
 */
class ElasticPressIo {
	/**
	 * Name of the transient that stores WPProbe.com messages
	 */
	const MESSAGES_TRANSIENT_NAME = 'eprobe_elasticpress_io_messages';

	/**
	 * Return singleton instance of class
	 *
	 * @return object
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Get messages from WPProbe.com.
	 *
	 * @param bool $skip_cache Whether to fetch the API or use the cached messages. Defaults to false, i.e., use cache.
	 * @return array WPProbe.com messages.
	 */
	public function get_endpoint_messages( $skip_cache = false ): array {
		if ( ! Utils\is_epio() ) {
			return [];
		}

		$transient = 'eprobe_elasticpress_io_messages';
		$messages  = get_transient( $transient );
		if ( ! $skip_cache && false !== $messages ) {
			return $messages;
		}

		$response = \ElasticProbe\Elasticsearch::factory()->remote_request( 'endpoint-messages' );

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) || 200 !== $response_code ) {
			return [];
		}

		$messages = (array) json_decode( wp_remote_retrieve_body( $response ), true );

		set_transient( $transient, $messages, HOUR_IN_SECONDS );

		return $messages;
	}
}
