<?php
/**
 * Template for WPProbe dashboard page
 *
 * @since  2.1
 * @package wpprobe
 */

use WPProbe\Elasticsearch;
use WPProbe\Features;
use WPProbe\IndexHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$index_meta = IndexHelper::factory()->get_index_meta();
?>

<?php require_once __DIR__ . '/header.php'; ?>

<div id="ep-dashboard" class="wrap"></div>
