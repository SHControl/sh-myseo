<?php

class Yoast_Alerts {
	const ADMIN_PAGE = 'wpseo_dashboard';
	private static $notification_count = 0;
	private static $errors = array();
	private static $active_errors = array();
	private static $dismissed_errors = array();
	private static $warnings = array();
	private static $active_warnings = array();
	private static $dismissed_warnings = array();
	public function __construct() {
		$this->add_hooks();
	}
	private function add_hooks() {
		$page = filter_input( INPUT_GET, 'page' );
		if ( self::ADMIN_PAGE === $page ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}
		add_action( 'admin_init', array( __CLASS__, 'collect_alerts' ), 99 );
		add_action( 'wp_ajax_yoast_dismiss_alert', array( $this, 'ajax_dismiss_alert' ) );
		add_action( 'wp_ajax_yoast_restore_alert', array( $this, 'ajax_restore_alert' ) );
	}
	public function enqueue_assets() {
		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$asset_manager->enqueue_style( 'alerts' );
	}
	public function ajax_dismiss_alert() {
		$notification = $this->get_notification_from_ajax_request();
		if ( $notification ) {
			$notification_center = Yoast_Notification_Center::get();
			$notification_center->maybe_dismiss_notification( $notification );
			$this->output_ajax_response( $notification->get_type() );
		}
		wp_die();
	}
	public function ajax_restore_alert() {
		$notification = $this->get_notification_from_ajax_request();
		if ( $notification ) {
			$notification_center = Yoast_Notification_Center::get();
			$notification_center->restore_notification( $notification );
			$this->output_ajax_response( $notification->get_type() );
		}
		wp_die();
	}
	private function output_ajax_response( $type ) {
		$html = $this->get_view_html( $type );
		echo wp_json_encode(
			array(
				'html'  => $html,
				'total' => self::get_active_alert_count(),
			)
		);
	}
	private function get_view_html( $type ) {
		switch ( $type ) {
			case 'error':
				$view = 'errors';
				break;
			case 'warning':
			default:
				$view = 'warnings';
				break;
		}
		self::collect_alerts();
		$alerts_data = self::get_template_variables();
		ob_start();
		include WPSEO_PATH . 'admin/views/partial-alerts-' . $view . '.php';
		$html = ob_get_clean();
		return $html;
	}
	private function get_notification_from_ajax_request() {
		$notification_center = Yoast_Notification_Center::get();
		$notification_id     = filter_input( INPUT_POST, 'notification' );
		return $notification_center->get_notification_by_id( $notification_id );
	}
	public static function show_overview_page() {
		$alerts_data = self::get_template_variables();
		include WPSEO_PATH . 'admin/views/alerts-dashboard.php';
	}
	public static function collect_alerts() {
		$notification_center = Yoast_Notification_Center::get();
		$notifications            = $notification_center->get_sorted_notifications();
		self::$notification_count = count( $notifications );
		self::$errors           = array_filter( $notifications, array( __CLASS__, 'filter_error_alerts' ) );
		self::$dismissed_errors = array_filter( self::$errors, array( __CLASS__, 'filter_dismissed_alerts' ) );
		self::$active_errors    = array_diff( self::$errors, self::$dismissed_errors );
		self::$warnings           = array_filter( $notifications, array( __CLASS__, 'filter_warning_alerts' ) );
		self::$dismissed_warnings = array_filter( self::$warnings, array( __CLASS__, 'filter_dismissed_alerts' ) );
		self::$active_warnings    = array_diff( self::$warnings, self::$dismissed_warnings );
	}
	public static function get_template_variables() {
		return array(
			'metrics'  => array(
				'total'    => self::$notification_count,
				'active'   => self::get_active_alert_count(),
				'errors'   => count( self::$errors ),
				'warnings' => count( self::$warnings ),
			),
			'errors'   => array(
				'dismissed' => self::$dismissed_errors,
				'active'    => self::$active_errors,
			),
			'warnings' => array(
				'dismissed' => self::$dismissed_warnings,
				'active'    => self::$active_warnings,
			),
		);
	}
	public static function get_active_alert_count() {
		return ( count( self::$active_errors ) + count( self::$active_warnings ) );
	}
	private static function filter_error_alerts( Yoast_Notification $notification ) {
		return $notification->get_type() === 'error';
	}
	private static function filter_warning_alerts( Yoast_Notification $notification ) {
		return $notification->get_type() !== 'error';
	}
	private static function filter_dismissed_alerts( Yoast_Notification $notification ) {
		return Yoast_Notification_Center::is_notification_dismissed( $notification );
	}
}
