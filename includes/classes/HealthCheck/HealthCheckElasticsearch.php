<?php
/**
 * Elasticsearch health check
 *
 * @since  3.6.0
 * @package elasticprobe
 */

namespace ElasticProbe\HealthCheck;

use ElasticProbe\Elasticsearch;
use ElasticProbe\HealthCheck;
use ElasticProbe\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	// @codeCoverageIgnoreStart
	exit; // Exit if accessed directly.
	// @codeCoverageIgnoreEnd
}

/**
 * HealthCheckElasticsearch class
 */
class HealthCheckElasticsearch extends HealthCheck {

	/**
	 * Create Elasticsearch health check.
	 */
	public function __construct() {
		$this->test_name = 'elasticpress-health-check-elasticsearch';
		$this->async     = true;
	}

	/**
	 * Runs the test.
	 *
	 * @return array Data about the result of the test.
	 */
	public function run() {
		$result = [
			'label'       => esc_html__( 'Your site can connect to Elasticsearch.', 'elasticprobe' ),
			'status'      => 'good',
			'badge'       => [
				'label' => esc_html__( 'ElasticProbe', 'elasticprobe' ),
				'color' => 'green',
			],
			'description' => esc_html__( 'You can have a fast and flexible search and query engine for WordPress using ElasticProbe.', 'elasticprobe' ),
			'actions'     => '',
			'test'        => $this->test_name,
		];

		$host = Utils\get_host();

		$elasticpress_settings_url = defined( 'EPROBE_IS_NETWORK' ) && EPROBE_IS_NETWORK ? admin_url( 'network/admin.php?page=elasticprobe-settings' ) : admin_url( 'admin.php?page=elasticprobe-settings' );

		if ( empty( $host ) ) {
			$result['label']          = esc_html__( 'Your site could not connect to Elasticsearch', 'elasticprobe' );
			$result['status']         = 'critical';
			$result['badge']['color'] = 'red';
			$result['description']    = esc_html__( 'The Elasticsearch host is not set.', 'elasticprobe' );
			$result['actions']        = sprintf(
				'<p><a href="%s">%s</a></p>',
				esc_url( $elasticpress_settings_url ),
				esc_html__( 'Add a host', 'elasticprobe' )
			);
		} elseif ( ! Elasticsearch::factory()->get_elasticsearch_version( true ) ) {
			$result['label']          = esc_html__( 'Your site could not connect to Elasticsearch', 'elasticprobe' );
			$result['status']         = 'critical';
			$result['badge']['color'] = 'red';
			$result['actions']        = sprintf(
				'<p><a href="%s">%s</a></p>',
				esc_url( $elasticpress_settings_url ),
				esc_html__( 'Update your settings', 'elasticprobe' )
			);

			if ( Utils\is_epio() ) {
				$result['description'] = esc_html__( 'Check if your credentials to WPProbe.com host are correct.', 'elasticprobe' );
			} else {
				$result['description'] = esc_html__( 'Check if your Elasticsearch host URL is correct and you have the right access to the host.', 'elasticprobe' );
			}
		}

		return $result;
	}
}
