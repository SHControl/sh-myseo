<?php

class WPSEO_Meta_Columns {
	private $analysis_seo;
	private $analysis_readability;
	public function __construct() {
		if ( apply_filters( 'wpseo_use_page_analysis', true ) === true ) {
			add_action( 'admin_init', array( $this, 'setup_hooks' ) );
		}
		$this->analysis_seo         = new WPSEO_Metabox_Analysis_SEO();
		$this->analysis_readability = new WPSEO_Metabox_Analysis_Readability();
	}
	public function setup_hooks() {
		$this->set_post_type_hooks();
		if ( $this->analysis_seo->is_enabled() ) {
			add_action( 'restrict_manage_posts', array( $this, 'posts_filter_dropdown' ) );
		}
		if ( $this->analysis_readability->is_enabled() ) {
			add_action( 'restrict_manage_posts', array( $this, 'posts_filter_dropdown_readability' ) );
		}
		add_filter( 'request', array( $this, 'column_sort_orderby' ) );
	}
	public function column_heading( $columns ) {
		if ( $this->display_metabox() === false ) {
			return $columns;
		}
		$added_columns = array();
		if ( $this->analysis_seo->is_enabled() ) {
			$added_columns['wpseo-score'] = '<span class="yoast-tooltip yoast-tooltip-n yoast-tooltip-alt" data-label="' . esc_attr__( 'SEO score', 'wordpress-seo' ) . '"><span class="yoast-column-seo-score yoast-column-header-has-tooltip"><span class="screen-reader-text">' . __( 'SEO score', 'wordpress-seo' ) . '</span></span></span>';
		}
		if ( $this->analysis_readability->is_enabled() ) {
			$added_columns['wpseo-score-readability'] = '<span class="yoast-tooltip yoast-tooltip-n yoast-tooltip-alt" data-label="' . esc_attr__( 'Readability score', 'wordpress-seo' ) . '"><span class="yoast-column-readability yoast-column-header-has-tooltip"><span class="screen-reader-text">' . __( 'Readability score', 'wordpress-seo' ) . '</span></span></span>';
		}
		$added_columns['wpseo-title']    = __( 'SEO Title', 'wordpress-seo' );
		$added_columns['wpseo-metadesc'] = __( 'Meta Desc.', 'wordpress-seo' );
		if ( $this->analysis_seo->is_enabled() ) {
			$added_columns['wpseo-focuskw'] = __( 'Focus KW', 'wordpress-seo' );
		}
		return array_merge( $columns, $added_columns );
	}
	public function column_content( $column_name, $post_id ) {
		if ( $this->display_metabox() === false ) {
			return;
		}
		switch ( $column_name ) {
			case 'wpseo-score':
				echo $this->parse_column_score( $post_id );
				return;
			case 'wpseo-score-readability':
				echo $this->parse_column_score_readability( $post_id );
				return;
			case 'wpseo-title':
				$post  = get_post( $post_id, ARRAY_A );
				$title = wpseo_replace_vars( $this->page_title( $post_id ), $post );
				$title = apply_filters( 'wpseo_title', $title );
				echo esc_html( $title );
				return;
			case 'wpseo-metadesc':
				$post         = get_post( $post_id, ARRAY_A );
				$metadesc_val = wpseo_replace_vars( WPSEO_Meta::get_value( 'metadesc', $post_id ), $post );
				$metadesc_val = apply_filters( 'wpseo_metadesc', $metadesc_val );
				if ( '' === $metadesc_val ) {
					echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">',
						esc_html__( 'Meta description not set.', 'wordpress-seo' ),
						'</span>';
					return;
				}
				echo esc_html( $metadesc_val );
				return;
			case 'wpseo-focuskw':
				$focuskw_val = WPSEO_Meta::get_value( 'focuskw', $post_id );
				if ( '' === $focuskw_val ) {
					echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">',
						esc_html__( 'Focus keyword not set.', 'wordpress-seo' ),
						'</span>';
					return;
				}
				echo esc_html( $focuskw_val );
				return;
		}
	}

	public function column_sort( $columns ) {
		if ( $this->display_metabox() === false ) {
			return $columns;
		}
		$columns['wpseo-metadesc'] = 'wpseo-metadesc';
		if ( $this->analysis_seo->is_enabled() ) {
			$columns['wpseo-focuskw'] = 'wpseo-focuskw';
		}
		return $columns;
	}

	public function column_hidden( $result, $option, $user ) {
		global $wpdb;
		if ( $user->has_prop( $wpdb->get_blog_prefix() . $option ) || $user->has_prop( $option ) ) {
			return $result;
		}
		if ( ! is_array( $result ) ) {
			$result = array();
		}
		array_push( $result, 'wpseo-title', 'wpseo-metadesc' );
		if ( $this->analysis_seo->is_enabled() ) {
			array_push( $result, 'wpseo-focuskw' );
		}
		return $result;
	}
	public function posts_filter_dropdown() {
		if ( ! $this->can_display_filter() ) {
			return;
		}
		$ranks = WPSEO_Rank::get_all_ranks();
		echo '<label class="screen-reader-text" for="wpseo-filter">' . esc_html__( 'Filter by SEO Score', 'wordpress-seo' ) . '</label>';
		echo '<select name="seo_filter" id="wpseo-filter">';
		echo $this->generate_option( '', __( 'All SEO Scores', 'wordpress-seo' ) );
		foreach ( $ranks as $rank ) {
			$selected = selected( $this->get_current_seo_filter(), $rank->get_rank(), false );
			echo $this->generate_option( $rank->get_rank(), $rank->get_drop_down_label(), $selected );
		}
		echo '</select>';
	}

	public function posts_filter_dropdown_readability() {
		if ( ! $this->can_display_filter() ) {
			return;
		}
		$ranks = WPSEO_Rank::get_all_readability_ranks();
		echo '<label class="screen-reader-text" for="wpseo-readability-filter">' . esc_html__( 'Filter by Readability Score', 'wordpress-seo' ) . '</label>';
		echo '<select name="readability_filter" id="wpseo-readability-filter">';
		echo $this->generate_option( '', __( 'All Readability Scores', 'wordpress-seo' ) );
		foreach ( $ranks as $rank ) {
			$selected = selected( $this->get_current_readability_filter(), $rank->get_rank(), false );
			echo $this->generate_option( $rank->get_rank(), $rank->get_drop_down_readability_labels(), $selected );
		}
		echo '</select>';
	}
	protected function generate_option( $value, $label, $selected = '' ) {
		return '<option ' . $selected . ' value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
	}
	protected function determine_seo_filters( $seo_filter ) {
		if ( $seo_filter === WPSEO_Rank::NO_FOCUS ) {
			return $this->create_no_focus_keyword_filter();
		}
		if ( $seo_filter === WPSEO_Rank::NO_INDEX ) {
			return $this->create_no_index_filter();
		}
		$rank = new WPSEO_Rank( $seo_filter );
		return $this->create_seo_score_filter( $rank->get_starting_score(), $rank->get_end_score() );
	}
	protected function determine_readability_filters( $readability_filter ) {
		$rank = new WPSEO_Rank( $readability_filter );
		return $this->create_readability_score_filter( $rank->get_starting_score(), $rank->get_end_score() );
	}
	protected function get_keyword_filter( $keyword_filter ) {
		return array(
			'post_type' => get_query_var( 'post_type', 'post' ),
			'key'       => WPSEO_Meta::$meta_prefix . 'focuskw',
			'value'     => sanitize_text_field( $keyword_filter ),
		);
	}
	protected function is_valid_filter( $filter ) {
		return ! empty( $filter ) && is_string( $filter );
	}
	protected function collect_filters() {
		$active_filters = array();
		$seo_filter             = $this->get_current_seo_filter();
		$readability_filter     = $this->get_current_readability_filter();
		$current_keyword_filter = $this->get_current_keyword_filter();
		if ( $this->is_valid_filter( $seo_filter ) ) {
			$active_filters = array_merge(
				$active_filters,
				$this->determine_seo_filters( $seo_filter )
			);
		}
		if ( $this->is_valid_filter( $readability_filter ) ) {
			$active_filters = array_merge(
				$active_filters,
				$this->determine_readability_filters( $readability_filter )
			);
		}
		if ( $this->is_valid_filter( $current_keyword_filter ) ) {
			$active_filters = array_merge(
				$active_filters,
				$this->get_keyword_filter( $current_keyword_filter )
			);
		}
		return $active_filters;
	}
	public function column_sort_orderby( $vars ) {
		$collected_filters = $this->collect_filters();
		if ( isset( $vars['orderby'] ) ) {
			$vars = array_merge( $vars, $this->filter_order_by( $vars['orderby'] ) );
		}
		return $this->build_filter_query( $vars, $collected_filters );
	}
	protected function get_meta_robots_query_values() {
		return array(
			'relation' => 'OR',
			array(
				'key'     => WPSEO_Meta::$meta_prefix . 'meta-robots-noindex',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => WPSEO_Meta::$meta_prefix . 'meta-robots-noindex',
				'value'   => '1',
				'compare' => '!=',
			),
		);
	}
	protected function determine_score_filters( $score_filters ) {
		if ( count( $score_filters ) > 1 ) {
			return array_merge( array( 'relation' => 'AND' ), $score_filters );
		}
		return $score_filters;
	}
	public function get_current_post_type() {
		return filter_input( INPUT_GET, 'post_type' );
	}
	public function get_current_seo_filter() {
		return filter_input( INPUT_GET, 'seo_filter' );
	}
	public function get_current_readability_filter() {
		return filter_input( INPUT_GET, 'readability_filter' );
	}
	public function get_current_keyword_filter() {
		return filter_input( INPUT_GET, 'seo_kw_filter' );
	}
	protected function build_filter_query( $vars, $filters ) {
		if ( count( $filters ) === 0 ) {
			return $vars;
		}
		$result               = array( 'meta_query' => array() );
		$result['meta_query'] = array_merge( $result['meta_query'], array( $this->determine_score_filters( $filters ) ) );
		$current_seo_filter = $this->get_current_seo_filter();
		if ( $this->is_valid_filter( $current_seo_filter ) && ! in_array( $current_seo_filter, array( WPSEO_Rank::NO_INDEX, WPSEO_Rank::NO_FOCUS ), true ) ) {
			$result['meta_query'] = array_merge( $result['meta_query'], array( $this->get_meta_robots_query_values() ) );
		}
		return array_merge( $vars, $result );
	}
	protected function create_readability_score_filter( $low, $high ) {
		return array(
			array(
				'key'     => WPSEO_Meta::$meta_prefix . 'content_score',
				'value'   => array( $low, $high ),
				'type'    => 'numeric',
				'compare' => 'BETWEEN',
			),
		);
	}
	protected function create_seo_score_filter( $low, $high ) {
		return array(
			array(
				'key'     => WPSEO_Meta::$meta_prefix . 'linkdex',
				'value'   => array( $low, $high ),
				'type'    => 'numeric',
				'compare' => 'BETWEEN',
			),
		);
	}
	protected function create_no_index_filter() {
		return array(
			array(
				'key'     => WPSEO_Meta::$meta_prefix . 'meta-robots-noindex',
				'value'   => '1',
				'compare' => '=',
			),
		);
	}
	protected function create_no_focus_keyword_filter() {
		return array(
			array(
				'key'     => WPSEO_Meta::$meta_prefix . 'meta-robots-noindex',
				'value'   => 'needs-a-value-anyway',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => WPSEO_Meta::$meta_prefix . 'linkdex',
				'value'   => 'needs-a-value-anyway',
				'compare' => 'NOT EXISTS',
			),
		);
	}
	protected function is_indexable( $post_id ) {
		if ( ! empty( $post_id ) && ! $this->uses_default_indexing( $post_id ) ) {
			return WPSEO_Meta::get_value( 'meta-robots-noindex', $post_id ) === '2';
		}
		$post = get_post( $post_id );
		if ( is_object( $post ) ) {
			return WPSEO_Options::get( 'noindex-' . $post->post_type, false ) === false;
		}
		return true;
	}
	protected function uses_default_indexing( $post_id ) {
		return WPSEO_Meta::get_value( 'meta-robots-noindex', $post_id ) === '0';
	}
	private function filter_order_by( $order_by ) {
		switch ( $order_by ) {
			case 'wpseo-metadesc':
				return array(
					'meta_key' => WPSEO_Meta::$meta_prefix . 'metadesc',
					'orderby'  => 'meta_value',
				);
			case 'wpseo-focuskw':
				return array(
					'meta_key' => WPSEO_Meta::$meta_prefix . 'focuskw',
					'orderby'  => 'meta_value',
				);
		}

		return array();
	}
	private function parse_column_score( $post_id ) {
		if ( ! $this->is_indexable( $post_id ) ) {
			$rank  = new WPSEO_Rank( WPSEO_Rank::NO_INDEX );
			$title = __( 'Post is set to noindex.', 'wordpress-seo' );
			WPSEO_Meta::set_value( 'linkdex', 0, $post_id );
			return $this->render_score_indicator( $rank, $title );
		}
		if ( WPSEO_Meta::get_value( 'focuskw', $post_id ) === '' ) {
			$rank  = new WPSEO_Rank( WPSEO_Rank::NO_FOCUS );
			$title = __( 'Focus keyword not set.', 'wordpress-seo' );
			return $this->render_score_indicator( $rank, $title );
		}
		$score = (int) WPSEO_Meta::get_value( 'linkdex', $post_id );
		$rank  = WPSEO_Rank::from_numeric_score( $score );
		$title = $rank->get_label();
		return $this->render_score_indicator( $rank, $title );
	}
	private function parse_column_score_readability( $post_id ) {
		$score = (int) WPSEO_Meta::get_value( 'content_score', $post_id );
		$rank  = WPSEO_Rank::from_numeric_score( $score );
		return $this->render_score_indicator( $rank );
	}
	private function set_post_type_hooks() {
		$post_types = WPSEO_Post_Type::get_accessible_post_types();
		if ( ! is_array( $post_types ) || $post_types === array() ) {
			return;
		}
		foreach ( $post_types as $post_type ) {
			if ( $this->display_metabox( $post_type ) === false ) {
				continue;
			}
			add_filter( 'manage_' . $post_type . '_posts_columns', array( $this, 'column_heading' ), 10, 1 );
			add_action( 'manage_' . $post_type . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
			add_action( 'manage_edit-' . $post_type . '_sortable_columns', array( $this, 'column_sort' ), 10, 2 );
			$filter = sprintf( 'get_user_option_%s', sprintf( 'manage%scolumnshidden', 'edit-' . $post_type ) );
			add_filter( $filter, array( $this, 'column_hidden' ), 10, 3 );
		}
		unset( $post_type );
	}
	private function display_metabox( $post_type = null ) {
		$current_post_type = sanitize_text_field( $this->get_current_post_type() );
		if ( ! isset( $post_type ) && ! empty( $current_post_type ) ) {
			$post_type = $current_post_type;
		}
		return WPSEO_Utils::is_metabox_active( $post_type, 'post_type' );
	}
	private function page_title( $post_id ) {
		$fixed_title = WPSEO_Meta::get_value( 'title', $post_id );
		if ( $fixed_title !== '' ) {
			return $fixed_title;
		}
		$post = get_post( $post_id );
		if ( is_object( $post ) && WPSEO_Options::get( 'title-' . $post->post_type, '' ) !== '' ) {
			$title_template = WPSEO_Options::get( 'title-' . $post->post_type );
			$title_template = str_replace( ' %%page%% ', ' ', $title_template );
			return wpseo_replace_vars( $title_template, $post );
		}
		return wpseo_replace_vars( '%%title%%', $post );
	}
	private function render_score_indicator( $rank, $title = '' ) {
		if ( empty( $title ) ) {
			$title = $rank->get_label();
		}
		return '<div aria-hidden="true" title="' . esc_attr( $title ) . '" class="wpseo-score-icon ' . esc_attr( $rank->get_css_class() ) . '"></div><span class="screen-reader-text">' . $title . '</span>';
	}
	public function can_display_filter() {
		if ( $GLOBALS['pagenow'] === 'upload.php' ) {
			return false;
		}
		if ( $this->display_metabox() === false ) {
			return false;
		}
		$screen = get_current_screen();
		if ( null === $screen ) {
			return false;
		}
		return WPSEO_Post_Type::is_post_type_accessible( $screen->post_type );
	}
}
