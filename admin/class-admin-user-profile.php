<?php

class WPSEO_Admin_User_Profile {
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'user_profile' ) );
		add_action( 'edit_user_profile', array( $this, 'user_profile' ) );
		add_action( 'personal_options_update', array( $this, 'process_user_option_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'process_user_option_update' ) );
		add_action( 'update_user_meta', array( $this, 'clear_author_sitemap_cache' ), 10, 3 );
	}

	public function clear_author_sitemap_cache( $meta_id, $object_id, $meta_key ) {
		if ( '_yoast_wpseo_profile_updated' === $meta_key ) {
			WPSEO_Sitemaps_Cache::clear( array( 'author' ) );
		}
	}

	private function filter_input_post( $var_name ) {
		$val = filter_input( INPUT_POST, $var_name );
		if ( $val ) {
			return WPSEO_Utils::sanitize_text_field( $val );
		}
		return '';
	}

	public function process_user_option_update( $user_id ) {
		update_user_meta( $user_id, '_yoast_wpseo_profile_updated', time() );
		$nonce_value = $this->filter_input_post( 'wpseo_nonce' );
		if ( empty( $nonce_value ) ) { // Submit from alternate forms.
			return;
		}
		check_admin_referer( 'wpseo_user_profile_update', 'wpseo_nonce' );
		update_user_meta( $user_id, 'wpseo_title', $this->filter_input_post( 'wpseo_author_title' ) );
		update_user_meta( $user_id, 'wpseo_metadesc', $this->filter_input_post( 'wpseo_author_metadesc' ) );
		update_user_meta( $user_id, 'wpseo_noindex_author', $this->filter_input_post( 'wpseo_noindex_author' ) );
		update_user_meta( $user_id, 'wpseo_content_analysis_disable', $this->filter_input_post( 'wpseo_content_analysis_disable' ) );
		update_user_meta( $user_id, 'wpseo_keyword_analysis_disable', $this->filter_input_post( 'wpseo_keyword_analysis_disable' ) );
	}

	public function user_profile( $user ) {
		wp_nonce_field( 'wpseo_user_profile_update', 'wpseo_nonce' );
		require_once WPSEO_PATH . 'admin/views/user-profile.php';
	}
}
