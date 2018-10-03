<?php

class WPSEO_License_Page_Manager implements WPSEO_WordPress_Integration {
	const VERSION_LEGACY = '1';
	const VERSION_BACKWARDS_COMPATIBILITY = '2';
	public function register_hooks() {
		add_filter( 'http_response', array( $this, 'handle_response' ), 10, 3 );
		if ( $this->get_version() === self::VERSION_BACKWARDS_COMPATIBILITY ) {
			add_filter( 'yoast-license-valid', '__return_true' );
			add_filter( 'yoast-show-license-notice', '__return_false' );
			add_action( 'admin_init', array( $this, 'validate_extensions' ), 15 );
		} else {
			add_action( 'admin_init', array( $this, 'remove_faulty_notifications' ), 15 );
		}
	}
	public function validate_extensions() {
		if ( filter_input( INPUT_GET, 'page' ) === WPSEO_Admin::PAGE_IDENTIFIER ) {
			apply_filters( 'yoast-active-extensions', array() );
		}
		$extension_list = new WPSEO_Extensions();
		$extensions     = $extension_list->get();
		$notification_center = Yoast_Notification_Center::get();
		foreach ( $extensions as $product_name ) {
			$notification = $this->create_notification( $product_name );
			if ( $extension_list->is_installed( $product_name ) && ! $extension_list->is_valid( $product_name ) ) {
				$notification_center->add_notification( $notification );
				continue;
			}
			$notification_center->remove_notification( $notification );
		}
	}
	public function remove_faulty_notifications() {
		$extension_list = new WPSEO_Extensions();
		$extensions     = $extension_list->get();
		$notification_center = Yoast_Notification_Center::get();
		foreach ( $extensions as $product_name ) {
			$notification = $this->create_notification( $product_name );
			$notification_center->remove_notification( $notification );
		}
	}
	public function handle_response( array $response, $request_arguments, $url ) {
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code === 200 && $this->is_expected_endpoint( $url ) ) {
			$response_data = $this->parse_response( $response );
			$this->detect_version( $response_data );
		}
		return $response;
	}
	public function get_license_page() {
		return 'licenses';
	}
	protected function get_version() {
		return get_option( $this->get_option_name(), self::VERSION_BACKWARDS_COMPATIBILITY );
	}
	protected function get_option_name() {
		return 'wpseo_license_server_version';
	}
	protected function detect_version( $response ) {
		if ( ! empty( $response['serverVersion'] ) ) {
			$this->set_version( $response['serverVersion'] );
		}
	}
	protected function set_version( $server_version ) {
		update_option( $this->get_option_name(), $server_version );
	}
	protected function parse_response( $response ) {
		$response = json_decode( wp_remote_retrieve_body( $response ), true );
		$response = maybe_unserialize( $response );
		return $response;
	}
	protected function is_expected_endpoint( $url ) {
		$url_parts = wp_parse_url( $url );
		$is_yoast_com = ( in_array( $url_parts['host'], array( 'shcontrol.net', 'api.shcontrol.net' ), true ) );
		$is_edd_api   = ( isset( $url_parts['path'] ) && $url_parts['path'] === '/myseo-api' );
		return $is_yoast_com && $is_edd_api;
	}
	protected function create_notification( $product_name ) {
		$notification_options = array(
			'type'         => Yoast_Notification::ERROR,
			'id'           => 'wpseo-dismiss-' . sanitize_title_with_dashes( $product_name, null, 'save' ),
			'capabilities' => 'wpseo_manage_options',
		);
		$notification = new Yoast_Notification(
			sprintf(
				__( 'You are not receiving updates or support! Fix this problem by adding this site and enabling %1$s for it in %2$s.', 'wordpress-seo' ),
				$product_name,
				'<a href="' . WPSEO_Shortlinker::get( '//shct.me/myseo-home' ) . '" target="_blank">MySEO Login</a>'
			),
			$notification_options
		);
		return $notification;
	}
}
