<?php

class WPSEO_Admin_Recommended_Replace_Vars {
	protected $recommended_replace_vars = array(
		'page'                     => array( 'sitename', 'title', 'sep', 'primary_category' ),
		'post'                     => array( 'sitename', 'title', 'sep', 'primary_category' ),
		'homepage'                 => array( 'sitename', 'sitedesc', 'sep' ),
		'custom_post_type'         => array( 'sitename', 'title', 'sep' ),
		'category'                 => array( 'sitename', 'term_title', 'sep' ),
		'post_tag'                 => array( 'sitename', 'term_title', 'sep' ),
		'post_format'              => array( 'sitename', 'term_title', 'sep', 'page' ),
		'term-in-custom-taxomomy'  => array( 'sitename', 'term_title', 'sep' ),
		'author_archive'           => array( 'sitename', 'title', 'sep', 'page' ),
		'date_archive'             => array( 'sitename', 'sep', 'date', 'page' ),
		'custom-post-type_archive' => array( 'sitename', 'title', 'sep', 'page' ),
		'search'                   => array( 'sitename', 'searchphrase', 'sep', 'page' ),
		'404'                      => array( 'sitename', 'sep' ),
	);

	public function determine_for_term( $taxonomy ) {
		$recommended_replace_vars = $this->get_recommended_replacevars();
		if ( array_key_exists( $taxonomy, $recommended_replace_vars ) ) {
			return $taxonomy;
		}
		return 'term-in-custom-taxomomy';
	}

	public function determine_for_post( $post ) {
		if ( $post instanceof WP_Post === false ) {
			return 'post';
		}
		if ( $post->post_type === 'page' && $this->is_homepage( $post ) ) {
			return 'homepage';
		}
		$recommended_replace_vars = $this->get_recommended_replacevars();
		if ( array_key_exists( $post->post_type, $recommended_replace_vars ) ) {
			return $post->post_type;
		}
		return 'custom_post_type';
	}

	public function determine_for_post_type( $post_type, $fallback = 'custom_post_type' ) {
		$page_type                   = $post_type;
		$recommended_replace_vars    = $this->get_recommended_replacevars();
		$has_recommended_replacevars = $this->has_recommended_replace_vars( $recommended_replace_vars, $page_type );
		if ( ! $has_recommended_replacevars ) {
			return $fallback;
		}
		return $page_type;
	}

	public function determine_for_archive( $name, $fallback = 'custom-post-type_archive' ) {
		$page_type                   = $name . '_archive';
		$recommended_replace_vars    = $this->get_recommended_replacevars();
		$has_recommended_replacevars = $this->has_recommended_replace_vars( $recommended_replace_vars, $page_type );
		if ( ! $has_recommended_replacevars ) {
			return $fallback;
		}
		return $page_type;
	}

	public function get_recommended_replacevars_for( $page_type ) {
		$recommended_replace_vars     = $this->get_recommended_replacevars();
		$has_recommended_replace_vars = $this->has_recommended_replace_vars( $recommended_replace_vars, $page_type );
		if ( ! $has_recommended_replace_vars ) {
			return array();
		}
		return $recommended_replace_vars[ $page_type ];
	}

	public function get_recommended_replacevars() {
		$recommended_replace_vars = apply_filters( 'wpseo_recommended_replace_vars', $this->recommended_replace_vars );
		if ( ! is_array( $recommended_replace_vars ) ) {
			return $this->recommended_replace_vars;
		}
		return $recommended_replace_vars;
	}

	private function has_recommended_replace_vars( $recommended_replace_vars, $page_type ) {
		if ( ! isset( $recommended_replace_vars[ $page_type ] ) ) {
			return false;
		}
		if ( ! is_array( $recommended_replace_vars[ $page_type ] ) ) {
			return false;
		}
		return true;
	}

	private function is_homepage( $post ) {
		if ( $post instanceof WP_Post === false ) {
			return false;
		}
		$post_id       = (int) $post->ID;
		$page_on_front = (int) get_option( 'page_on_front' );
		return get_option( 'show_on_front' ) === 'page' && $page_on_front === $post_id;
	}
}
