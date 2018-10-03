<?php

class WPSEO_Admin {
	const PAGE_IDENTIFIER = 'wpseo_dashboard';
	protected $admin_features;
	public function __construct() {
		$integrations = array();
		global $pagenow;
		$wpseo_menu = new WPSEO_Menu();
		$wpseo_menu->register_hooks();

		if ( is_multisite() ) {
			WPSEO_Options::maybe_set_multisite_defaults( false );
		}

		if ( WPSEO_Options::get( 'stripcategorybase' ) === true ) {
			add_action( 'created_category', array( $this, 'schedule_rewrite_flush' ) );
			add_action( 'edited_category', array( $this, 'schedule_rewrite_flush' ) );
			add_action( 'delete_category', array( $this, 'schedule_rewrite_flush' ) );
		}

		$this->admin_features = array(
			'google_search_console' => new WPSEO_GSC(),
			'dashboard_widget'      => new Yoast_Dashboard_Widget(),
		);

		if ( WPSEO_Metabox::is_post_overview( $pagenow ) || WPSEO_Metabox::is_post_edit( $pagenow ) ) {
			$this->admin_features['primary_category']       = new WPSEO_Primary_Term_Admin();
		}

		if ( filter_input( INPUT_GET, 'page' ) === 'wpseo_tools' && filter_input( INPUT_GET, 'tool' ) === null ) {
			new WPSEO_Recalculate_Scores();
		}

		add_filter( 'plugin_action_links_' . WPSEO_BASENAME, array( $this, 'add_action_link' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'config_page_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_global_style' ) );
		add_filter( 'user_contactmethods', array( $this, 'update_contactmethods' ), 10, 1 );
		add_action( 'after_switch_theme', array( $this, 'switch_theme' ) );
		add_action( 'switch_theme', array( $this, 'switch_theme' ) );
		add_filter( 'set-screen-option', array( $this, 'save_bulk_edit_options' ), 10, 3 );
		add_action( 'admin_init', array( 'WPSEO_Plugin_Conflict', 'hook_check_for_plugin_conflicts' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'map_manage_options_cap' ) );
		WPSEO_Sitemaps_Cache::register_clear_on_option_update( 'wpseo' );
		WPSEO_Sitemaps_Cache::register_clear_on_option_update( 'home' );

		if ( WPSEO_Utils::is_yoast_seo_page() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		if ( WPSEO_Utils::is_api_available() ) {
			$configuration = new WPSEO_Configuration_Page();
			$configuration->set_hooks();
			$configuration->catch_configuration_request();
		}

		$this->set_upsell_notice();
		$this->initialize_cornerstone_content();
		new Yoast_Modal();

		if ( WPSEO_Utils::is_plugin_network_active() ) {
			$integrations[] = new Yoast_Network_Admin();
		}

		$integrations[] = new WPSEO_Yoast_Columns();
		$integrations[] = new WPSEO_License_Page_Manager();
		$integrations[] = new WPSEO_Statistic_Integration();
		$integrations[] = new WPSEO_Capability_Manager_Integration( WPSEO_Capability_Manager_Factory::get() );
		$integrations[] = new WPSEO_Admin_Media_Purge_Notification();
		$integrations[] = new WPSEO_Admin_Gutenberg_Compatibility_Notification();
		$integrations[] = new WPSEO_Expose_Shortlinks();
		$integrations   = array_merge( $integrations, $this->initialize_seo_links() );
		foreach ( $integrations as $integration ) {
			$integration->register_hooks();
		}
	}

	public function schedule_rewrite_flush() {
		add_action( 'shutdown', 'flush_rewrite_rules' );
	}

	public function get_admin_features() {
		return $this->admin_features;
	}

	public function enqueue_assets() {
		if ( 'wpseo_licenses' === filter_input( INPUT_GET, 'page' ) ) {
			$asset_manager = new WPSEO_Admin_Asset_Manager();
			$asset_manager->enqueue_style( 'extensions' );
		}
	}

	public function get_manage_options_cap() {
		return apply_filters( 'wpseo_manage_options_capability', 'wpseo_manage_options' );
	}

	public function map_manage_options_cap() {
		$option_page = ! empty( $_POST['option_page'] ) ? $_POST['option_page'] : ''; // WPCS: CSRF ok.
		if ( strpos( $option_page, 'yoast_wpseo' ) === 0 ) {
			add_filter( 'option_page_capability_' . $option_page, array( $this, 'get_manage_options_cap' ) );
		}
	}

	public function bulk_edit_options() {
		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Posts', 'wordpress-seo' ),
			'default' => 10,
			'option'  => 'wpseo_posts_per_page',
		);
		add_screen_option( $option, $args );
	}

	public function save_bulk_edit_options( $status, $option, $value ) {
		if ( 'wpseo_posts_per_page' === $option && ( $value > 0 && $value < 1000 ) ) {
			return $value;
		}

		return $status;
	}

	public function add_action_link( $links, $file ) {
		if ( WPSEO_BASENAME === $file && WPSEO_Capability_Utils::current_user_can( 'wpseo_manage_options' ) ) {
			$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE_IDENTIFIER ) ) . '">' . __( 'Settings', 'wordpress-seo' ) . '</a>';
			array_unshift( $links, $settings_link );
		}

		if ( class_exists( 'WPSEO_Product_Premium' ) ) {
			$product_premium   = new WPSEO_Product_Premium();
			$extension_manager = new WPSEO_Extension_Manager();

			if ( $extension_manager->is_activated( $product_premium->get_slug() ) ) {
				return $links;
			}
		}

		$premium_link = '<a href="' . esc_url( WPSEO_Shortlinker::get( '//shct.me/reasons-to-upgrade' ) ) . '">' . __( 'Premium Support', 'wordpress-seo' ) . '</a>';
		array_unshift( $links, $premium_link );
		$faq_link = '<a href="' . esc_url( WPSEO_Shortlinker::get( '//shct.me/myseo-faq' ) ) . '">' . __( 'FAQ', 'wordpress-seo' ) . '</a>';
		array_unshift( $links, $faq_link );

		return $links;
	}

	public function config_page_scripts() {
		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$asset_manager->enqueue_script( 'admin-global-script' );
		wp_localize_script( WPSEO_Admin_Asset_Manager::PREFIX . 'admin-global-script', 'wpseoAdminGlobalL10n', $this->localize_admin_global_script() );
	}

	public function enqueue_global_style() {
		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$asset_manager->enqueue_style( 'admin-global' );
	}

	public function update_contactmethods( $contactmethods ) {
		$contactmethods['googleplus'] = __( 'Google+', 'wordpress-seo' );
		$contactmethods['twitter'] = __( 'Twitter username (without @)', 'wordpress-seo' );
		$contactmethods['facebook'] = __( 'Facebook profile URL', 'wordpress-seo' );
		return $contactmethods;
	}
	
	public function switch_theme() {
		$users = get_users( array( 'who' => 'authors' ) );
		if ( is_array( $users ) && $users !== array() ) {
			foreach ( $users as $user ) {
				update_user_meta( $user->ID, '_yoast_wpseo_profile_updated', time() );
			}
		}
	}

	private function localize_admin_global_script() {
		return array(
			'variable_warning'        => sprintf( __( 'Warning: the variable %s cannot be used in this template. See the help center for more info.', 'wordpress-seo' ), '<code>%s</code>' ),
			'dismiss_about_url'       => $this->get_dismiss_url( 'wpseo-dismiss-about' ),
			'dismiss_tagline_url'     => $this->get_dismiss_url( 'wpseo-dismiss-tagline-notice' ),
			'help_video_iframe_title' => __( 'Yoast SEO video tutorial', 'wordpress-seo' ),
			'scrollable_table_hint'   => __( 'Scroll to see the table content.', 'wordpress-seo' ),
		);
	}

	private function get_dismiss_url( $dismiss_param ) {
		$arr_params = array(
			$dismiss_param => '1',
			'nonce'        => wp_create_nonce( $dismiss_param ),
		);
		return esc_url( add_query_arg( $arr_params ) );
	}

	protected function set_upsell_notice() {
		$upsell = new WPSEO_Product_Upsell_Notice();
		$upsell->dismiss_notice_listener();
		$upsell->initialize();
	}

	public function check_php_version() {
	}

	protected function on_dashboard_page() {
		return 'index.php' === $GLOBALS['pagenow'];
	}

	protected function initialize_cornerstone_content() {
		if ( ! WPSEO_Options::get( 'enable_cornerstone_content' ) ) {
			return;
		}
		$cornerstone = new WPSEO_Cornerstone();
		$cornerstone->register_hooks();
		$cornerstone_filter = new WPSEO_Cornerstone_Filter();
		$cornerstone_filter->register_hooks();
	}

	protected function initialize_seo_links() {
		$integrations = array();
		$link_table_compatibility_notifier = new WPSEO_Link_Compatibility_Notifier();
		$link_table_accessible_notifier    = new WPSEO_Link_Table_Accessible_Notifier();
		if ( ! WPSEO_Options::get( 'enable_text_link_counter' ) ) {
			$link_table_compatibility_notifier->remove_notification();
			return $integrations;
		}

		$integrations[] = new WPSEO_Link_Cleanup_Transient();
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			$link_table_compatibility_notifier->add_notification();
			return $integrations;
		}
		$link_table_compatibility_notifier->remove_notification();
		if ( ! WPSEO_Link_Table_Accessible::is_accessible() ) {
			WPSEO_Link_Table_Accessible::cleanup();
		}

		if ( ! WPSEO_Meta_Table_Accessible::is_accessible() ) {
			WPSEO_Meta_Table_Accessible::cleanup();
		}

		if ( ! WPSEO_Link_Table_Accessible::is_accessible() || ! WPSEO_Meta_Table_Accessible::is_accessible() ) {
			$link_table_accessible_notifier->add_notification();

			return $integrations;
		}

		$link_table_accessible_notifier->remove_notification();
		$integrations[] = new WPSEO_Link_Columns( new WPSEO_Meta_Storage() );
		$integrations[] = new WPSEO_Link_Reindex_Dashboard();
		$integrations[] = new WPSEO_Link_Notifier();
		add_filter( 'wpseo_link_count_post_types', array( 'WPSEO_Post_Type', 'filter_attachment_post_type' ) );

		return $integrations;
	}

	/********************** DEPRECATED METHODS **********************/

	public function register_settings_page() {
		_deprecated_function( __METHOD__, 'WPSEO 5.5.0' );
	}

	public function register_network_settings_page() {
		_deprecated_function( __METHOD__, 'WPSEO 5.5.0' );
	}

	public function load_page() {
		_deprecated_function( __METHOD__, 'WPSEO 5.5.0' );
	}

	public function network_config_page() {
		_deprecated_function( __METHOD__, 'WPSEO 5.5.0' );
	}

	public function filter_settings_pages( array $pages ) {
		_deprecated_function( __METHOD__, 'WPSEO 5.5.0' );
	}

	public function remove_stopwords_from_slug() {
		_deprecated_function( __METHOD__, 'WPSEO 7.0' );
	}

	public function filter_stopwords_from_slug() {
		_deprecated_function( __METHOD__, 'WPSEO 7.0' );
	}

	public function title_metas_help_tab() {
		_deprecated_function( __METHOD__, '5.6.0' );
		$screen = get_current_screen();
		$screen->set_help_sidebar( '
			<p><strong>' . __( 'For more information:', 'wordpress-seo' ) . '</strong></p>
			<p><a target="_blank" href="//shct.me/wordpress-seo/#titles">' . __( 'Title optimization', 'wordpress-seo' ) . '</a></p>
			<p><a target="_blank" href="//shct.me/google-page-title/">' . __( 'Why Google won\'t display the right page title', 'wordpress-seo' ) . '</a></p>'
		);
		$screen->add_help_tab(
			array(
				'id'      => 'basic-help',
				'title'   => __( 'Template explanation', 'wordpress-seo' ),
				'content' => "\n\t\t<h2>" . __( 'Template explanation', 'wordpress-seo' ) . "</h2>\n\t\t" . '<p>' .
					sprintf(
						__( 'The title &amp; metas settings for %1$s are made up of variables that are replaced by specific values from the page when the page is displayed. The tabs on the left explain the available variables.', 'wordpress-seo' ),
						'MySEO'
					) .
					'</p><p>' . __( 'Note that not all variables can be used in every template.', 'wordpress-seo' ) . '</p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'title-vars',
				'title'   => __( 'Basic Variables', 'wordpress-seo' ),
				'content' => "\n\t\t<h2>" . __( 'Basic Variables', 'wordpress-seo' ) . "</h2>\n\t\t" . WPSEO_Replace_Vars::get_basic_help_texts(),
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'title-vars-advanced',
				'title'   => __( 'Advanced Variables', 'wordpress-seo' ),
				'content' => "\n\t\t<h2>" . __( 'Advanced Variables', 'wordpress-seo' ) . "</h2>\n\t\t" . WPSEO_Replace_Vars::get_advanced_help_texts(),
			)
		);
	}
}
