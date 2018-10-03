<?php

class Yoast_OnPage_Ajax {
	public function __construct() {
		add_action( 'wp_ajax_wpseo_dismiss_onpageorg', array( $this, 'dismiss_notice' ) );
	}
	public function dismiss_notice() {
		check_ajax_referer( 'wpseo-dismiss-onpageorg' );
		$this->save_dismissed();
		wp_die( 'true' );
	}
	private function save_dismissed() {
		update_user_meta( get_current_user_id(), WPSEO_OnPage::USER_META_KEY, 1 );
	}
}
