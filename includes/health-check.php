<?php
/**
 * Health check
 *
 * @package elasticprobe
 * @since   3.6.0
 */

namespace ElasticProbe;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$health_checks = [
	new HealthCheck\HealthCheckElasticsearch(),
];

foreach ( $health_checks as $health_check ) {
	$health_check->register_test();
}
