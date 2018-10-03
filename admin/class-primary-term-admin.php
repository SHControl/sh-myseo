<?php

class WPSEO_Primary_Term_Admin {
	public function __construct() {
		add_filter( 'wpseo_content_meta_section_content', array( $this, 'add_input_fields' ) );
		add_action( 'admin_footer', array( $this, 'wp_footer' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'save_post', array( $this, 'save_primary_terms' ) );
		$primary_term = new WPSEO_Frontend_Primary_Category();
		$primary_term->register_hooks();
	}
	protected function get_current_id() {
		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $post_id ) && isset( $GLOBALS['post_ID'] ) ) {
			$post_id = filter_var( $GLOBALS['post_ID'], FILTER_SANITIZE_NUMBER_INT );
		}
		return $post_id;
	}
	public function add_input_fields( $content ) {
		$taxonomies = $this->get_primary_term_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			$content .= $this->primary_term_field( $taxonomy->name );
			$content .= wp_nonce_field( 'save-primary-term', WPSEO_Meta::$form_prefix . 'primary_' . $taxonomy->name . '_nonce', false, false );
		}
		return $content;
	}

	protected function primary_term_field( $taxonomy_name ) {
		return sprintf(
			'<input class="yoast-wpseo-primary-term" type="hidden" id="%1$s" name="%2$s" value="%3$s" />',
			esc_attr( $this->generate_field_id( $taxonomy_name ) ),
			esc_attr( $this->generate_field_name( $taxonomy_name ) ),
			esc_attr( $this->get_primary_term( $taxonomy_name ) )
		);
	}
	protected function generate_field_id( $taxonomy_name ) {
		return 'yoast-wpseo-primary-' . $taxonomy_name;
	}
	protected function generate_field_name( $taxonomy_name ) {
		return WPSEO_Meta::$form_prefix . 'primary_' . $taxonomy_name . '_term';
	}
	public function wp_footer() {
		$taxonomies = $this->get_primary_term_taxonomies();
		if ( ! empty( $taxonomies ) ) {
			$this->include_js_templates();
		}
	}
	public function enqueue_assets() {
		global $pagenow;
		if ( ! WPSEO_Metabox::is_post_edit( $pagenow ) ) {
			return;
		}
		$taxonomies = $this->get_primary_term_taxonomies();
		if ( empty( $taxonomies ) ) {
			return;
		}
		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$asset_manager->enqueue_style( 'primary-category' );
		$asset_manager->enqueue_script( 'primary-category' );
		$mapped_taxonomies = $this->get_mapped_taxonomies_for_js( $taxonomies );
		$data = array(
			'taxonomies' => $mapped_taxonomies,
		);
		wp_localize_script( WPSEO_Admin_Asset_Manager::PREFIX . 'primary-category', 'wpseoPrimaryCategoryL10n', $data );
	}
	public function save_primary_terms( $post_id ) {
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}
		$taxonomies = $this->get_primary_term_taxonomies( $post_id );
		foreach ( $taxonomies as $taxonomy ) {
			$this->save_primary_term( $post_id, $taxonomy );
		}
	}
	protected function get_primary_term( $taxonomy_name ) {
		$primary_term = new WPSEO_Primary_Term( $taxonomy_name, $this->get_current_id() );
		return $primary_term->get_primary_term();
	}
	protected function get_primary_term_taxonomies( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = $this->get_current_id();
		}
		$taxonomies = wp_cache_get( 'primary_term_taxonomies_' . $post_id, 'wpseo' );
		if ( false !== $taxonomies ) {
			return $taxonomies;
		}
		$taxonomies = $this->generate_primary_term_taxonomies( $post_id );
		wp_cache_set( 'primary_term_taxonomies_' . $post_id, $taxonomies, 'wpseo' );
		return $taxonomies;
	}
	protected function include_js_templates() {
		include_once WPSEO_PATH . 'admin/views/js-templates-primary-term.php';
	}
	protected function save_primary_term( $post_id, $taxonomy ) {
		$primary_term = filter_input( INPUT_POST, WPSEO_Meta::$form_prefix . 'primary_' . $taxonomy->name . '_term', FILTER_SANITIZE_NUMBER_INT );
		if ( null !== $primary_term && check_admin_referer( 'save-primary-term', WPSEO_Meta::$form_prefix . 'primary_' . $taxonomy->name . '_nonce' ) ) {
			$primary_term_object = new WPSEO_Primary_Term( $taxonomy->name, $post_id );
			$primary_term_object->set_primary_term( $primary_term );
		}
	}
	protected function generate_primary_term_taxonomies( $post_id ) {
		$post_type      = get_post_type( $post_id );
		$all_taxonomies = get_object_taxonomies( $post_type, 'objects' );
		$all_taxonomies = array_filter( $all_taxonomies, array( $this, 'filter_hierarchical_taxonomies' ) );
		$taxonomies = (array) apply_filters( 'wpseo_primary_term_taxonomies', $all_taxonomies, $post_type, $all_taxonomies );
		return $taxonomies;
	}
	protected function get_mapped_taxonomies_for_js( $taxonomies ) {
		return array_map( array( $this, 'map_taxonomies_for_js' ), $taxonomies );
	}
	private function map_taxonomies_for_js( $taxonomy ) {
		$primary_term = $this->get_primary_term( $taxonomy->name );
		if ( empty( $primary_term ) ) {
			$primary_term = '';
		}
		$terms = get_terms( $taxonomy->name );
		return array(
			'title'         => $taxonomy->labels->singular_name,
			'name'          => $taxonomy->name,
			'primary'       => $primary_term,
			'singularLabel' => $taxonomy->labels->singular_name,
			'fieldId'       => $this->generate_field_id( $taxonomy->name ),
			'restBase'      => ( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name,
			'terms'         => array_map( array( $this, 'map_terms_for_js' ), $terms ),
		);
	}
	private function map_terms_for_js( $term ) {
		return array(
			'id'   => $term->term_id,
			'name' => $term->name,
		);
	}
	private function filter_hierarchical_taxonomies( $taxonomy ) {
		return (bool) $taxonomy->hierarchical;
	}
}
