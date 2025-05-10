<?php
/**
 * Template for ElasticProbe Status Report
 *
 * @since 4.4.0
 * @package elasticprobe
 */

defined( 'ABSPATH' ) || exit;

$status_report = \ElasticProbe\Screen::factory()->status_report;

require_once __DIR__ . '/header.php';
?>
<div id="ep-status-reports" class="wrap"></div>
