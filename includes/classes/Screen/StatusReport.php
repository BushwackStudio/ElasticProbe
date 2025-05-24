<?php
/**
 * ElasticProbe Status Report class
 *
 * @since 4.4.0
 * @package elasticprobe
 */

namespace ElasticProbe\Screen;

use ElasticProbe\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Status Report class
 *
 * @package ElasticProbe
 */
class StatusReport {
	/**
	 * The formatted/processed reports.
	 *
	 * @since 4.5.0
	 * @var array
	 */
	protected $formatted_reports = [];

	/**
	 * Initialize class
	 */
	public function setup() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'admin_head', array( $this, 'admin_menu_count' ), 11 );
		add_action( 'wp_ajax_ep_load_groups', array( $this, 'action_wp_ajax_ep_load_groups' ) );
	}

	/**
	 * Enqueue script.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		if ( 'status-report' !== \ElasticProbe\Screen::factory()->get_current_screen() ) {
			return;
		}

		wp_enqueue_script(
			'eprobe_admin_status_report_scripts',
			EPROBE_URL . 'dist/js/status-report-script.js',
			Utils\get_asset_info( 'status-report-script', 'dependencies' ),
			Utils\get_asset_info( 'status-report-script', 'version' ),
			true
		);

		$reports = $this->get_formatted_reports();

		$plain_text_reports = [];

		foreach ( $reports as $report ) {
			$title                = $report['title'];
			$groups               = $report['groups'];
			$plain_text_reports[] = $this->render_copy_paste_report( $title, $groups, $report['isAjaxReport'] );
		}

		$plain_text_report = implode( "\n\n", $plain_text_reports );

		wp_localize_script(
			'eprobe_admin_status_report_scripts',
			'epStatusReport',
			[
				'plainTextReport' => $plain_text_report,
				'reports'         => $reports,
				'nonce'           => wp_create_nonce( 'ep-status-report-nonce' ),
			]
		);

		wp_enqueue_style(
			'eprobe_status_report_styles',
			EPROBE_URL . 'dist/css/status-report-script.css',
			[ 'wp-components', 'wp-edit-post' ],
			Utils\get_asset_info( 'status-report-script', 'version' )
		);
	}

	/**
	 * AJAX action to load an individual report group.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function action_wp_ajax_ep_load_groups(): void {
		if ( ! isset( $_POST['ep-status-report-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ep-status-report-nonce'] ) ), 'ep-status-report-nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce is not present.', 'elasticprobe' ) ], 403 );
		}

		if ( empty( $this->formatted_reports ) ) {
			$this->formatted_reports = $this->get_reports();
		}

		$post = sanitize_post( $_POST );
		$post = wp_unslash( $post );

		if ( empty( $this->formatted_reports[ $post['report'] ] ) ) {
			wp_send_json_error( [ 'message' => __( 'Status report not found.', 'elasticprobe' ) ], 404 );
		}

		$report = $this->formatted_reports[ $post['report'] ];

		if ( ! $report instanceof \ElasticProbe\StatusReport\AjaxReport ) {
			wp_send_json_error( [ 'message' => __( 'Report is not an AJAX report.', 'elasticprobe' ) ], 403 );
		}

		wp_send_json_success(
			[
				'groups'   => $report->get_groups_ajax(),
				'messages' => $report->get_messages(),
			],
			200
		);
	}

	/**
	 * Return all reports available
	 *
	 * @return array
	 */
	public function get_reports(): array {
		$reports = [];

		$query_logger = \ElasticProbe\get_container()->get( '\ElasticProbe\QueryLogger' );

		if ( $query_logger ) {
			$reports['failed-queries'] = new \ElasticProbe\StatusReport\FailedQueries( $query_logger );
		}

		if ( Utils\is_epio() ) {
			$reports['autosuggest'] = new \ElasticProbe\StatusReport\ElasticPressIo();
		}

		$reports['wordpress']    = new \ElasticProbe\StatusReport\WordPress();
		$reports['indexable']    = new \ElasticProbe\StatusReport\IndexableContent();
		$reports['elasticpress'] = new \ElasticProbe\StatusReport\ElasticPress();
		$reports['indices']      = new \ElasticProbe\StatusReport\Indices();
		$reports['last-sync']    = new \ElasticProbe\StatusReport\LastSync();
		$reports['features']     = new \ElasticProbe\StatusReport\Features();

		/**
		 * Filter the reports executed in the Status Report page.
		 *
		 * @since 4.4.0
		 * @hook eprobe_status_report_reports
		 * @param {array<Report>} $reports Array of reports
		 * @return {array<Report>} New array of reports
		 */
		$filtered_reports = apply_filters( 'eprobe_status_report_reports', $reports );

		// phpcs:disable WordPress.Security.NonceVerification
		$skipped_reports = isset( $_GET['ep-skip-reports'] ) ?
			array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['ep-skip-reports'] ) ) :
			[];
		// phpcs:enable WordPress.Security.NonceVerification

		$filtered_reports = array_filter(
			$filtered_reports,
			function ( $report_slug ) use ( $skipped_reports ) {
				return ! in_array( $report_slug, $skipped_reports, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		return $filtered_reports;
	}

	/**
	 * Process and format the reports, then store them in the `formatted_reports` attribute.
	 *
	 * @since 4.5.0
	 * @return array
	 */
	protected function get_formatted_reports(): array {
		if ( empty( $this->formatted_reports ) ) {
			$reports = $this->get_reports();

			$this->formatted_reports = array_map(
				function ( $report ) {
					return [
						'actions'      => $report->get_actions(),
						'groups'       => $report->get_groups(),
						'messages'     => $report->get_messages(),
						'title'        => $report->get_title(),
						'isAjaxReport' => $report instanceof \ElasticProbe\StatusReport\AjaxReport,
					];
				},
				$reports
			);
		}
		return $this->formatted_reports;
	}

	/**
	 * Render the copy & paste report
	 *
	 * @param string $title  Report title
	 * @param array  $groups Report groups
	 * @param bool   $is_ajax_report Whether the report is an AJAX report
	 *
	 * @return string
	 */
	protected function render_copy_paste_report( string $title, array $groups, bool $is_ajax_report = false ): string {
		$output = "## {$title} ##\n\n";

		if ( $is_ajax_report ) {
			$output .= $this->render_pending_generation();
			return $output;
		}

		foreach ( $groups as $group ) {
			$output .= "### {$group['title']} ###\n";
			foreach ( $group['fields'] as $slug => $field ) {
				$value = $field['value'] ?? '';

				$output .= "{$slug}: ";
				$output .= $this->render_value( $value );
				$output .= "\n";
			}
			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Render a value based on its type
	 *
	 * @param mixed $value The value
	 * @return string
	 */
	protected function render_value( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return var_export( $value, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return (string) $value;
	}

	/**
	 * Render a message when the report is pending generation
	 *
	 * @return string
	 */
	protected function render_pending_generation() {
		return __( 'Please generate a full report to see the content of this group.', 'elasticprobe' );
	}

	/**
	 * Display a badge in the admin menu if there's admin notices from
	 * ElasticProbe.com.
	 *
	 * @return void
	 */
	public function admin_menu_count() {
		global $menu, $submenu;

		$messages = \ElasticProbe\ElasticPressIo::factory()->get_endpoint_messages();

		if ( empty( $messages ) ) {
			return;
		}

		$count = count( $messages );
		$title = sprintf(
			/* translators: %d: Number of messages. */
			_n( '%s message from ElasticProbe.com', '%s messages from ElasticProbe.com', $count, 'elasticprobe' ),
			$count
		);

		foreach ( $menu as $key => $value ) {
			if ( 'elasticprobe' === $value[2] ) {
				$menu[ $key ][0] .= sprintf(
					' <span class="update-plugins"><span aria-hidden="true">%1$s</span><span class="screen-reader-text">%2$s</span></span>',
					esc_html( $count ),
					esc_attr( $title )
				);
			}
		}

		if ( ! isset( $submenu['elasticprobe'] ) ) {
			return;
		}

		foreach ( $submenu['elasticprobe'] as $key => $value ) {
			if ( 'elasticprobe-status-report' === $value[2] ) {
				$submenu['elasticprobe'][ $key ][0] .= sprintf(
					' <span class="menu-counter"><span aria-hidden="true">%1$s</span><span class="screen-reader-text">%2$s</span></span>',
					esc_html( $count ),
					esc_attr( $title )
				);
			}
		}
	}
}
