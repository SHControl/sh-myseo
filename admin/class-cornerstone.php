<?php

class WPSEO_Cornerstone {
	const META_NAME = 'is_cornerstone';
	const FIELD_NAME = 'yoast_wpseo_is_cornerstone';
	public function register_hooks() {
		global $pagenow;
		if ( ! $this->page_contains_cornerstone_content_field( $pagenow ) ) {
			return;
		}
		add_action( 'save_post', array( $this, 'save_meta_value' ) );
		add_filter( 'wpseo_cornerstone_post_types', array( 'WPSEO_Post_Type', 'filter_attachment_post_type' ) );
	}
	public function save_meta_value( $post_id ) {
		$is_cornerstone_content = $this->is_cornerstone_content();
		if ( $is_cornerstone_content ) {
			$this->update_meta( $post_id, $is_cornerstone_content );
			return;
		}
		$this->delete_meta( $post_id );
	}
	protected function is_cornerstone_content() {
		return filter_input( INPUT_POST, self::FIELD_NAME ) === 'true';
	}
	protected function page_contains_cornerstone_content_field( $page ) {
		return WPSEO_Metabox::is_post_edit( $page );
	}
	protected function update_meta( $post_id, $is_cornerstone_content ) {
		WPSEO_Meta::set_value( self::META_NAME, $is_cornerstone_content, $post_id );
	}
	protected function delete_meta( $post_id ) {
		WPSEO_Meta::delete( self::META_NAME, $post_id );
	}
}
