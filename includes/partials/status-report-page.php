<?php
/**
 * Template for WPProbe Status Report
 *
 * @since 4.4.0
 * @package wpprobe
 */

defined( 'ABSPATH' ) || exit;

$status_report = \WPProbe\Screen::factory()->status_report;

require_once __DIR__ . '/header.php';
?>
<div id="ep-status-reports" class="wrap"></div>
