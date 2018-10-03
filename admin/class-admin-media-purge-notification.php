<?php

class WPSEO_Admin_Media_Purge_Notification implements WPSEO_WordPress_Integration {
	private $notification_id = 'wpseo_media_purge';
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'manage_notification' ) );
		add_filter( 'wpseo_option_tab-metas_media', array( $this, 'output_hidden_setting' ) );
		if ( WPSEO_Utils::is_yoast_seo_page() && filter_input( INPUT_GET, 'dismiss' ) === $this->notification_id ) {
			WPSEO_Options::set( 'is-media-purge-relevant', false );
		}
	}
	public function output_hidden_setting( $input ) {
		$form = Yoast_Form::get_instance();
		$form->hidden( 'is-media-purge-relevant' );
		return $input;
	}
	public function manage_notification() {
		$this->remove_notification();
	}
	private function get_notification() {
		$content = sprintf(
			__( 'Your site\'s settings currently allow attachment URLs on your site to exist. Please read %1$sthis post about a potential issue%2$s with attachment URLs and check whether you have the correct setting for your site.', 'wordpress-seo' ),
			'<a href="' . esc_url( WPSEO_Shortlinker::get( '//shct.me/media-attachment-urls' ) ) . '" rel="noopener noreferrer" target="_blank">',
			'</a>'
		);
		$content .= '<br><br>';
		$content .= sprintf(
			__( 'If you know what this means and you do not want to see this message anymore, you can %1$sdismiss this message%2$s.', 'wordpress-seo' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=wpseo_dashboard&dismiss=' . $this->notification_id ) ) . '">',
			'</a>'
		);
		return new Yoast_Notification(
			$content,
			array(
				'type'         => Yoast_Notification::ERROR,
				'id'           => $this->notification_id,
				'capabilities' => 'wpseo_manage_options',
				'priority'     => 1,
			)
		);
	}

	private function add_notification() {
		$notification_center = Yoast_Notification_Center::get();
		$notification_center->add_notification( $this->get_notification() );
	}

	private function remove_notification() {
		$notification_center = Yoast_Notification_Center::get();
		$notification_center->remove_notification( $this->get_notification() );
	}
}
