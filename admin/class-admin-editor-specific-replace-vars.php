<?php

class WPSEO_Admin_Editor_Specific_Replace_Vars {
	protected $replacement_variables = array(
		'page'                     => array( 'id', 'pt_single', 'pt_plural', 'parent_title' ),
		'post'                     => array( 'id', 'term404', 'pt_single', 'pt_plural' ),
		'custom_post_type'         => array( 'id', 'term404', 'pt_single', 'pt_plural', 'parent_title' ),
		'custom-post-type_archive' => array( 'pt_single', 'pt_plural' ),
		'category'                 => array( 'term_title', 'term_description', 'category_description', 'parent_title' ),
		'post_tag'                 => array( 'term_title', 'term_description', 'tag_description' ),
		'post_format'              => array(),
		'term-in-custom-taxonomy'  => array( 'term_title', 'term_description', 'category_description', 'parent_title' ),
		'search'                   => array( 'searchphrase' ),
	);

	public function __construct() {
		$this->add_for_page_types(
			array( 'page', 'post', 'custom_post_type' ),
			WPSEO_Custom_Fields::get_custom_fields()
		);

		$this->add_for_page_types(
			array( 'post', 'term-in-custom-taxonomies' ),
			WPSEO_Custom_Taxonomies::get_custom_taxonomies()
		);
	}

	public function get() {
		$replacement_variables = apply_filters(
			'wpseo_editor_specific_replace_vars',
			$this->replacement_variables
		);

		if ( ! is_array( $replacement_variables ) ) {
			$replacement_variables = $this->replacement_variables;
		}
		return array_filter( $replacement_variables, 'is_array' );
	}

	public function get_generic( $replacement_variables ) {
		$shared_variables = array_diff(
			$this->extract_names( $replacement_variables ),
			$this->get_unique_replacement_variables()
		);

		return array_values( $shared_variables );
	}

	public function determine_for_term( $taxonomy ) {
		$replacement_variables = $this->get();
		if ( array_key_exists( $taxonomy, $replacement_variables ) ) {
			return $taxonomy;
		}
		return 'term-in-custom-taxonomy';
	}

	public function determine_for_post( $post ) {
		if ( $post instanceof WP_Post === false ) {
			return 'post';
		}

		$replacement_variables = $this->get();
		if ( array_key_exists( $post->post_type, $replacement_variables ) ) {
			return $post->post_type;
		}
		return 'custom_post_type';
	}

	public function determine_for_post_type( $post_type, $fallback = 'custom_post_type' ) {
		if ( ! $this->has_for_page_type( $post_type ) ) {
			return $fallback;
		}

		return $post_type;
	}

	public function determine_for_archive( $name, $fallback = 'custom-post-type_archive' ) {
		$page_type = $name . '_archive';
		if ( ! $this->has_for_page_type( $page_type ) ) {
			return $fallback;
		}
		return $page_type;
	}

	protected function add_for_page_types( array $page_types, array $replacement_variables_to_add ) {
		if ( empty( $replacement_variables_to_add ) ) {
			return;
		}
		$replacement_variables_to_add = array_fill_keys( $page_types, $replacement_variables_to_add );
		$replacement_variables        = $this->replacement_variables;
		$this->replacement_variables = array_merge_recursive( $replacement_variables, $replacement_variables_to_add );
	}

	protected function extract_names( $replacement_variables ) {
		$extracted_names = array();
		foreach ( $replacement_variables as $replacement_variable ) {
			if ( empty( $replacement_variable['name'] ) ) {
				continue;
			}
			$extracted_names[] = $replacement_variable['name'];
		}
		return $extracted_names;
	}

	protected function has_for_page_type( $page_type ) {
		$replacement_variables = $this->get();
		return ( ! empty( $replacement_variables[ $page_type ] ) && is_array( $replacement_variables[ $page_type ] ) );
	}

	protected function get_unique_replacement_variables() {
		$merged_replacement_variables = call_user_func_array( 'array_merge', $this->get() );
		return array_unique( $merged_replacement_variables );
	}
}
