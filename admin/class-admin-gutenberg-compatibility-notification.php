<?php

class WPSEO_Admin_Gutenberg_Compatibility_Notification implements WPSEO_WordPress_Integration {
	private $notification_id = 'wpseo-outdated-gutenberg-plugin';
	private $compatibility_checker;
	private $notification_center;
	public function __construct() {
		$this->compatibility_checker = new WPSEO_Gutenberg_Compatibility();
		$this->notification_center   = Yoast_Notification_Center::get();
	}
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'manage_notification' ) );
	}
	public function manage_notification() {
		if ( ! $this->compatibility_checker->is_installed() || $this->compatibility_checker->is_fully_compatible() ) {
			$this->notification_center->remove_notification_by_id( $this->notification_id );
			return;
		}
		$this->add_notification();
	}

	private function add_notification() {
		$level = $this->compatibility_checker->is_below_minimum() ? Yoast_Notification::ERROR : Yoast_Notification::WARNING;
		$message = sprintf(
			__( '%1$s detected you are using version %2$s of %3$s, please update to the latest version to prevent compatibility issues.', 'wordpress-seo' ),
			'MySEO',
			$this->compatibility_checker->get_installed_version(),
			'Gutenberg'
		);
		$notification = new Yoast_Notification(
			$message,
			array(
				'id'   => $this->notification_id,
				'type' => $level,
				'priority' => 1,
			)
		);
		$this->notification_center->add_notification( $notification );
	}
}
