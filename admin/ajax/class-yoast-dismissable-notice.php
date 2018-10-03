<?php

class Yoast_Dismissable_Notice_Ajax {
	const FOR_USER = 'user_meta';
	const FOR_NETWORK = 'site_option';
	const FOR_SITE = 'option';
	private $notice_name;
	private $notice_type;
	public function __construct( $notice_name, $notice_type = self::FOR_USER ) {
		$this->notice_name = $notice_name;
		$this->notice_type = $notice_type;

		add_action( 'wp_ajax_wpseo_dismiss_' . $notice_name, array( $this, 'dismiss_notice' ) );
	}
	public function dismiss_notice() {
		check_ajax_referer( 'wpseo-dismiss-' . $this->notice_name );

		$this->save_dismissed();

		wp_die( 'true' );
	}
	private function save_dismissed() {
		if ( $this->notice_type === self::FOR_SITE ) {
			update_option( 'wpseo_dismiss_' . $this->notice_name, 1 );

			return;
		}

		if ( $this->notice_type === self::FOR_NETWORK ) {
			update_site_option( 'wpseo_dismiss_' . $this->notice_name, 1 );

			return;
		}

		update_user_meta( get_current_user_id(), 'wpseo_dismiss_' . $this->notice_name, 1 );
	}
}
