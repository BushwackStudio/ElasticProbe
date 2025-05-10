<?php
/**
 * Header template for ElasticProbe settings page
 *
 * @since  2.1
 * @package elasticprobe
 */

use ElasticProbe\Elasticsearch;
use ElasticProbe\Screen;
use ElasticProbe\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$base_url     = ( defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK ) ? admin_url( 'network/admin.php?page=' ) : admin_url( 'admin.php?page=' );
$is_sync_page = 'sync' === Screen::factory()->get_current_screen();
?>

<div class="ep-header-menu">
	<a href="<?php echo esc_url( $base_url . 'elasticprobe' ); ?>"><img width="150" src="<?php echo esc_url( plugins_url( '/images/logo.svg', dirname( __DIR__ ) ) ); ?>"></a>

	<?php if ( Utils\is_top_level_admin_context() ) : ?>
		<div class="icons">
			<span class="sync-status"></span>
			<?php if ( $is_sync_page ) : ?>

				<a class="dashicons resume-sync dashicons-controls-play" title ="<?php esc_attr_e( 'Resume Sync', 'elasticprobe' ); ?>" aria-label="<?php esc_attr_e( 'Resume Sync', 'elasticprobe' ); ?>"></a>
				<a class="dashicons cancel-sync dashicons-no" title="<?php esc_attr_e( 'Cancel Sync', 'elasticprobe' ); ?>" aria-label="<?php esc_attr_e( 'Cancel Sync', 'elasticprobe' ); ?>"></a>
			<?php endif; ?>
			<?php if ( Elasticsearch::factory()->get_elasticsearch_version() && defined( 'EP_DASHBOARD_SYNC' ) && EP_DASHBOARD_SYNC && ! $is_sync_page ) : ?>
				<a
					class="dashicons start-sync dashicons-update"
					title="<?php esc_attr_e( 'Sync Page', 'elasticprobe' ); ?>"
					aria-label="<?php esc_attr_e( 'Sync Page', 'elasticprobe' ); ?>"
					<?php echo ( $is_sync_page ) ? '' : 'href="' . esc_url( $base_url . 'elasticprobe-sync' ) . '"'; ?>
				></a>
			<?php endif; ?>
			<a href="<?php echo esc_url( $base_url . 'elasticprobe-settings' ); ?>" class="dashicons dashicons-admin-generic" title="<?php esc_attr_e( 'Settings Page', 'elasticprobe' ); ?>" aria-label="<?php esc_attr_e( 'Settings Page', 'elasticprobe' ); ?>"></a>
		</div>

		<div class="progress-bar"></div>
	<?php endif; ?>
</div>

<hr id="ep-wp-header-end" class="wp-header-end">
