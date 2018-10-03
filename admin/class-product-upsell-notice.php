<?php

class WPSEO_Product_Upsell_Notice {
	const USER_META_DISMISSED = 'wpseo-remove-upsell-notice';
	const OPTION_NAME = 'wpseo';
	protected $options;
	public function __construct() {
		$this->options = $this->get_options();
	}
	public function initialize() {
		if ( $this->is_notice_dismissed() ) {
			$this->remove_notification();
			return;
		}

		if ( $this->should_add_notification() ) {
			$this->add_notification();
		}
	}

	public function set_upgrade_notice() {
		if ( $this->has_first_activated_on() ) {
			return;
		}
		$this->set_first_activated_on();
		$this->add_notification();
	}

	public function dismiss_notice_listener() {
		if ( filter_input( INPUT_GET, 'yoast_dismiss' ) !== 'upsell' ) {
			return;
		}
		$this->dismiss_notice();
		wp_redirect( admin_url( 'admin.php?page=wpseo_dashboard' ) );
		exit;
	}

	protected function should_add_notification() {
		return ( $this->options['first_activated_on'] < strtotime( '-2weeks' ) );
	}

	protected function has_first_activated_on() {
		return $this->options['first_activated_on'] !== false;
	}

	protected function set_first_activated_on() {
		$this->options['first_activated_on'] = strtotime( '-2weeks' );
		$this->save_options();
	}

	protected function add_notification() {
		$notification_center = Yoast_Notification_Center::get();
		$notification_center->add_notification( $this->get_notification() );
	}

	protected function remove_notification() {
		$notification_center = Yoast_Notification_Center::get();
		$notification_center->remove_notification( $this->get_notification() );
	}

	protected function get_premium_upsell_section() {
		$features = new WPSEO_Features();
		if ( $features->is_free() ) {
			return sprintf(
				__( 'By the way, did you know we also have a %1$sPremium plugin%2$s? It offers advanced features, like a redirect manager and support for multiple keywords. It also comes with 24/7 personal support.', 'wordpress-seo' ),
				"<a href='" . WPSEO_Shortlinker::get( '//shct.me/premium-notification' ) . "'>",
				'</a>'
			);
		}

		return '';
	}

	protected function get_notification() {
		$message = sprintf(
			__( 'We\'ve noticed you\'ve been using %1$s for some time now; we hope you love it! We\'d be thrilled if you could %2$sgive us a 5 stars rating on WordPress.org%3$s!', 'wordpress-seo' ),
			'MySEO',
			'<a href="' . WPSEO_Shortlinker::get( '//shct.me/rate-yoast-seo' ) . '">',
			'</a>'
		) . "\n\n";
		$message .= sprintf(
			__( 'If you are experiencing issues, %1$splease file a bug report%2$s and we\'ll do our best to help you out.', 'wordpress-seo' ),
			'<a href="' . WPSEO_Shortlinker::get( '//shct.me/bugreport' ) . '">',
			'</a>'
		) . "\n\n";
		$message .= $this->get_premium_upsell_section() . "\n\n";
		$message .= sprintf(
			__( '%1$sPlease don\'t show me this notification anymore%2$s', 'wordpress-seo' ),
			'<a class="button" href="' . admin_url( '?page=' . WPSEO_Admin::PAGE_IDENTIFIER . '&yoast_dismiss=upsell' ) . '">',
			'</a>'
		);

		$notification = new Yoast_Notification(
			$message,
			array(
				'type'         => Yoast_Notification::WARNING,
				'id'           => 'wpseo-upsell-notice',
				'capabilities' => 'wpseo_manage_options',
				'priority'     => 0.8,
			)
		);

		return $notification;
	}

	protected function is_notice_dismissed() {
		return get_user_meta( get_current_user_id(), self::USER_META_DISMISSED, true ) === '1';
	}

	protected function dismiss_notice() {
		update_user_meta( get_current_user_id(), self::USER_META_DISMISSED, true );
	}

	protected function get_options() {
		return get_option( self::OPTION_NAME );
	}

	protected function save_options() {
		update_option( self::OPTION_NAME, $this->options );
	}
}
