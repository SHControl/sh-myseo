<?php

class Yoast_Dashboard_Widget {
	const CACHE_TRANSIENT_KEY = 'wpseo-dashboard-totals';
	protected $asset_manager;
	protected $statistics;
	public function __construct( WPSEO_Statistics $statistics = null ) {
		if ( null === $statistics ) {
			$statistics = new WPSEO_Statistics();
		}
		$this->statistics    = $statistics;
		$this->asset_manager = new WPSEO_Admin_Asset_Manager();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_assets' ) );
		add_action( 'admin_init', array( $this, 'queue_dashboard_widget' ) );
	}

	public function queue_dashboard_widget() {
		if ( $this->show_widget() ) {
			add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		}
	}

	public function add_dashboard_widget() {
		add_filter( 'postbox_classes_dashboard_wpseo-dashboard-overview', array( $this, 'wpseo_dashboard_overview_class' ) );
		wp_add_dashboard_widget(
			'wpseo-dashboard-overview',
			sprintf( __( '%s Posts Overview', 'wordpress-seo' ), 'MySEO' ),
			array( $this, 'display_dashboard_widget' )
		);
	}

	public function wpseo_dashboard_overview_class( $classes ) {
		$classes[] = 'yoast wpseo-dashboard-overview';
		return $classes;
	}

	public function display_dashboard_widget() {
		echo '<div id="yoast-seo-dashboard-widget"></div>';
	}

	public function enqueue_dashboard_stylesheets() {
		_deprecated_function( __METHOD__, 'WPSEO 5.5', 'This method is deprecated, please use the <code>enqueue_dashboard_assets</code> method.' );

		if ( ! $this->is_dashboard_screen() ) {
			return;
		}

		$this->asset_manager->enqueue_style( 'wp-dashboard' );
	}

	public function enqueue_dashboard_assets() {
		if ( ! $this->is_dashboard_screen() ) {
			return;
		}
		wp_localize_script( WPSEO_Admin_Asset_Manager::PREFIX . 'dashboard-widget', 'wpseoDashboardWidgetL10n', $this->localize_dashboard_script() );
		$yoast_components_l10n = new WPSEO_Admin_Asset_Yoast_Components_L10n();
		$yoast_components_l10n->localize_script( WPSEO_Admin_Asset_Manager::PREFIX . 'dashboard-widget' );
		$this->asset_manager->enqueue_script( 'dashboard-widget' );
		$this->asset_manager->enqueue_style( 'wp-dashboard' );
	}

	public function localize_dashboard_script() {
		return array(
			'feed_header'      => sprintf(
				__( 'Latest News on %1$s', 'wordpress-seo' ),
				'MySEO SHControl Limited'
			),
			'feed_footer'      => __( 'Read more like this on our SEO blog', 'wordpress-seo' ),
			'ryte_header'      => sprintf(
				__( 'Indexability check by %1$s', 'wordpress-seo' ),
				'Ryte'
			),
			'ryte_fetch'       => __( 'Fetch the current status', 'wordpress-seo' ),
			'ryte_analyze'     => __( 'Analyze entire site', 'wordpress-seo' ),
			'ryte_fetch_url'   => esc_attr( add_query_arg( 'wpseo-redo-onpage', '1' ) ) . '#wpseo-dashboard-overview',
			'ryte_landing_url' => WPSEO_Shortlinker::get( '//shct.me/rytelp' ),
		);
	}

	private function is_dashboard_screen() {
		$current_screen = get_current_screen();
		return ( $current_screen instanceof WP_Screen && $current_screen->id === 'dashboard' );
	}

	private function show_widget() {
		$analysis_seo = new WPSEO_Metabox_Analysis_SEO();
		return $analysis_seo->is_enabled() && current_user_can( 'edit_posts' );
	}
}
