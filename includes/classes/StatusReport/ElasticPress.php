<?php
/**
 * ElasticProbe report class
 *
 * @since 4.4.0
 * @package elasticprobe
 */

namespace ElasticProbe\StatusReport;

use ElasticProbe\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * ElasticProbe report class
 *
 * @package ElasticProbe
 */
class ElasticPress extends Report {

	/**
	 * Return the report title
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'ElasticProbe', 'elasticprobe' );
	}

	/**
	 * Return the report fields
	 *
	 * @return array
	 */
	public function get_groups(): array {
		return [
			$this->get_basic_settings(),
			$this->get_timeouts(),
		];
	}

	/**
	 * Process ElasticProbe's basic settings.
	 *
	 * @return array
	 */
	protected function get_basic_settings(): array {
		$is_epio = Utils\is_epio();

		$fields = [];

		$fields['host'] = [
			'label' => $is_epio ? __( 'WPProbe.com Host URL', 'elasticprobe' ) : __( 'Elasticsearch Host URL', 'elasticprobe' ),
			'value' => Utils\get_host(),
		];

		$fields['index_prefix'] = [
			'label' => __( 'Index Prefix', 'elasticprobe' ),
			'value' => Utils\get_index_prefix(),
		];

		$fields['language'] = [
			'label' => __( 'Elasticsearch Language', 'elasticprobe' ),
			'value' => Utils\get_language(),
		];

		$fields['per_page'] = [
			'label' => __( 'Content Items per Index Cycle', 'elasticprobe' ),
			'value' => \ElasticProbe\IndexHelper::factory()->get_index_default_per_page(),
		];

		$fields['network_active'] = [
			'label' => __( 'Network Active', 'elasticprobe' ),
			'value' => is_multisite() && defined( 'EPROBE_IS_NETWORK' ) && EPROBE_IS_NETWORK,
		];

		return [
			'title'  => __( 'Settings', 'elasticprobe' ),
			'fields' => $fields,
		];
	}

	/**
	 * Process ElasticProbe timeouts.
	 *
	 * @return array
	 */
	protected function get_timeouts() {
		$default_request_timeout   = 5;
		$fields['request_timeout'] = [
			'label' => sprintf(
				/* translators: default time */
				__( 'Default Requests Timeout (default: %s)', 'elasticprobe' ),
				$default_request_timeout
			),
			'value' => apply_filters( 'http_request_timeout', $default_request_timeout, Utils\get_host() ),
		];

		$default_index_document_timeout   = 15;
		$fields['index_document_timeout'] = [
			'label' => sprintf(
				/* translators: default time */
				__( 'Index Document Request Timeout (default: %s)', 'elasticprobe' ),
				$default_index_document_timeout
			),
			'value' => apply_filters( 'eprobe_index_document_timeout', $default_index_document_timeout ),
		];

		$default_bulk_request_timeout   = 30;
		$fields['bulk_request_timeout'] = [
			'label' => sprintf(
				/* translators: default time */
				__( 'Default Requests Timeout (default: %s)', 'elasticprobe' ),
				$default_bulk_request_timeout
			),
			'value' => apply_filters( 'eprobe_bulk_request_timeout', $default_bulk_request_timeout ),
		];

		return [
			'title'  => __( 'Timeouts', 'elasticprobe' ),
			'fields' => $fields,
		];
	}
}
