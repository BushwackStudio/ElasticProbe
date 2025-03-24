<?php
/**
 * Test screen class.
 *
 * @package elasticpress
 */

namespace WPProbeTest;

use WPProbe;

/**
 * Screen test class
 */
class TestScreen extends BaseTestCase {

	/**
	 * Setup each test.
	 *
	 * @since 2.2
	 */
	public function set_up() {
		global $wpdb;
		parent::set_up();
		$wpdb->suppress_errors();

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $admin_id );

		wp_set_current_user( $admin_id );

		WPProbe\Elasticsearch::factory()->delete_all_indices();
		WPProbe\Indexables::factory()->get( 'post' )->put_mapping();

		WPProbe\Indexables::factory()->get( 'post' )->sync_manager->reset_sync_queue();

		$this->setup_test_post_type();

		global $hook_suffix;
		$hook_suffix = 'sites.php';

		set_current_screen();
	}

	/**
	 * Run after each test
	 *
	 * @group screen
	 * @since  3.0
	 */
	public function tear_down() {
		parent::tear_down();

		// phpcs:disable
		if ( isset( $_GET['page'] ) ) {
			unset( $_GET['page'] );
		}

		if ( isset( $_GET['install_complete'] ) ) {
			unset( $_GET['install_complete'] );
		}

		if ( isset( $_GET['do_sync'] ) ) {
			unset( $_GET['do_sync'] );
		}

		if ( isset( $_GET['ep_sync_nonce'] ) ) {
			unset( $_GET['ep_sync_nonce'] );
		}
		// phpcs:enable
	}

	/**
	 * Current screen should always be false when not on an EP page
	 *
	 * @group screen
	 * @since 3.0
	 */
	public function testDetermineScreenNotEP() {
		$_GET['page'] = '';

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		$this->assertEquals( null, WPProbe\Screen::factory()->get_current_screen() );

		$_GET['page'] = 'elasticpress';

		set_current_screen( 'front' );

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		WPProbe\Screen::factory()->determine_screen();

		// This will be 'install' for single site, but null for multisite.
		$this->assertSame( 'install', WPProbe\Screen::factory()->get_current_screen() );
	}

	/**
	 * Test install true on settings
	 *
	 * @group screen
	 * @since  3.0
	 */
	public function testDetermineScreenSettingsInstallTrue() {
		$set_install_status = function () {
			return true;
		};

		add_filter( 'ep_install_status', $set_install_status );

		$_GET['page'] = 'wpprobe-settings';

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		$this->assertEquals( 'settings', WPProbe\Screen::factory()->get_current_screen() );
	}

	/**
	 * Test install status of 1 on settings
	 *
	 * @group screen
	 * @since  3.0
	 */
	public function testDetermineScreenSettingsInstall1() {
		$set_install_status = function () {
			return 1;
		};

		add_filter( 'ep_install_status', $set_install_status );

		$_GET['page'] = 'wpprobe-settings';

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		$this->assertEquals( 'install', WPProbe\Screen::factory()->get_current_screen() );
	}

	/**
	 * Test install status of 2 on settings
	 *
	 * @group screen
	 * @since  3.0
	 */
	public function testDetermineScreenSettingsInstall2() {
		$set_install_status = function () {
			return 2;
		};

		add_filter( 'ep_install_status', $set_install_status );

		$_GET['page'] = 'wpprobe-settings';

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		$this->assertEquals( 'settings', WPProbe\Screen::factory()->get_current_screen() );
	}

	/**
	 * Test install status true on dashboard
	 *
	 * @group screen
	 * @since  3.0
	 */
	public function testDetermineScreenDashboardInstallTrue() {
		$set_install_status = function () {
			return true;
		};

		add_filter( 'ep_install_status', $set_install_status );

		$_GET['page'] = 'elasticpress';

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		$this->assertEquals( 'dashboard', WPProbe\Screen::factory()->get_current_screen() );
	}

	/**
	 * Test install status 1 on dashboard
	 *
	 * @group screen
	 * @since  3.0
	 */
	public function testDetermineScreenDashboardInstall1() {
		$set_install_status = function () {
			return 1;
		};

		add_filter( 'ep_install_status', $set_install_status );

		$_GET['page'] = 'elasticpress';

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		$this->assertEquals( 'install', WPProbe\Screen::factory()->get_current_screen() );
	}

	/**
	 * Test install status 2 on dashboard
	 *
	 * @group screen
	 * @since  3.0
	 */
	public function testDetermineScreenDashboardInstall2() {
		$set_install_status = function () {
			return 1;
		};

		add_filter( 'ep_install_status', $set_install_status );

		$_GET['page'] = 'elasticpress';

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		$this->assertEquals( 'install', WPProbe\Screen::factory()->get_current_screen() );
	}

	/**
	 * Test install status true with install complete on dashboard
	 *
	 * @group screen
	 * @since  3.0
	 */
	public function testDetermineScreenDashboardInstallComplete() {
		$set_install_status = function () {
			return true;
		};

		add_filter( 'ep_install_status', $set_install_status );

		$_GET['page']             = 'elasticpress';
		$_GET['install_complete'] = 1;

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		$this->assertEquals( 'install', WPProbe\Screen::factory()->get_current_screen() );
	}

	/**
	 * Test install status 3 on dashboard doing a sync
	 *
	 * @group screen
	 * @since  3.0
	 */
	public function testDetermineScreenDashboardInstall3DoSync() {
		$set_install_status = function () {
			return 3;
		};

		add_filter( 'ep_install_status', $set_install_status );

		$_GET['page']          = 'elasticpress';
		$_GET['do_sync']       = 1;
		$_GET['ep_sync_nonce'] = wp_create_nonce( 'ep_sync_nonce' );

		WPProbe\Installer::factory()->calculate_install_status();
		WPProbe\Screen::factory()->determine_screen();

		$this->assertEquals( 'dashboard', WPProbe\Screen::factory()->get_current_screen() );
	}
}
