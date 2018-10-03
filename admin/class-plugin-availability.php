<?php

class WPSEO_Plugin_Availability {
	protected $plugins = array();
	public function register() {
		$this->register_yoast_plugins();
		$this->register_yoast_plugins_status();
	}
	protected function register_yoast_plugins() {
		$this->plugins = array(
			'yoast-seo-premium' => array(
				'url'          => WPSEO_Shortlinker::get( '//shct.me/myseo-plugin' ),
				'title'        => 'MySEO Premium',
				'description'  => sprintf(
					__( 'The premium version of %1$s with more features & support.', 'wordpress-seo' ),
					'MySEO'
				),
				'installed'    => false,
				'slug'         => 'wordpress-seo-premium/wp-seo-premium.php',
				'version_sync' => true,
				'premium'      => true,
			),

			'video-seo-for-wordpress-seo-by-yoast' => array(
				'url'          => WPSEO_Shortlinker::get( '//shct.me/video-seo' ),
				'title'        => 'Video SEO',
				'description'  => __( 'Optimize your videos to show them off in search results and get more clicks!', 'wordpress-seo' ),
				'installed'    => false,
				'slug'         => 'wpseo-video/video-seo.php',
				'version_sync' => true,
				'premium'      => true,
			),

			'yoast-news-seo' => array(
				'url'          => WPSEO_Shortlinker::get( '//shct.me/news-seo' ),
				'title'        => 'News SEO',
				'description'  => __( 'Are you in Google News? Increase your traffic from Google News by optimizing for it!', 'wordpress-seo' ),
				'installed'    => false,
				'slug'         => 'wpseo-news/wpseo-news.php',
				'version_sync' => true,
				'premium'      => true,
			),

			'local-seo-for-yoast-seo' => array(
				'url'          => WPSEO_Shortlinker::get( '//shct.me/local-seo' ),
				'title'        => 'Local SEO',
				'description'  => __( 'Rank better locally and in Google Maps, without breaking a sweat!', 'wordpress-seo' ),
				'installed'    => false,
				'slug'         => 'wordpress-seo-local/local-seo.php',
				'version_sync' => true,
				'premium'      => true,
			),

			'yoast-woocommerce-seo' => array(
				'url'           => WPSEO_Shortlinker::get( '//shct.me/woocommerce-seo' ),
				'title'         => 'WooCommerce SEO',
				'description'   => sprintf(
					__( 'Seamlessly integrate WooCommerce with %1$s and get extra features!', 'wordpress-seo' ),
					'MySEO'
				),
				'_dependencies' => array(
					'WooCommerce' => array(
						'slug' => 'woocommerce/woocommerce.php',
					),
				),
				'installed'     => false,
				'slug'          => 'wpseo-woocommerce/wpseo-woocommerce.php',
				'version_sync'  => true,
				'premium'       => true,
			),

			'yoast-acf-analysis' => array(
				'url'           => '//shct.me/acf-content-analysis-for-yoast-seo/',
				'title'         => 'ACF Content Analysis for MySEO',
				'description'   => sprintf(
					__( 'Seamlessly integrate %2$s with %1$s for the content analysis!', 'wordpress-seo' ),
					'MySEO',
					'Advanced Custom Fields'
				),
				'installed'     => false,
				'slug'          => 'acf-content-analysis-for-yoast-seo/yoast-acf-analysis.php',
				'_dependencies' => array(
					'Advanced Custom Fields' => array(
						'slug' => 'advanced-custom-fields/acf.php',
					),
				),
				'version_sync'  => false,
			),

			'yoastseo-amp' => array(
				'url'           => '//shct.me/glue-for-yoast-seo-amp/',
				'title'         => 'MySEO AMP Glue',
				'description'   => sprintf(
					__( 'Seamlessly integrate %1$s into your AMP pages!', 'wordpress-seo' ), 'MySEO'
				),
				'installed'     => false,
				'slug'          => 'glue-for-yoast-seo-amp/yoastseo-amp.php',
				'_dependencies' => array(
					'AMP' => array(
						'slug' => 'amp/amp.php',
					),
				),
				'version_sync'  => false,
			),
		);
	}

	protected function register_yoast_plugins_status() {
		foreach ( $this->plugins as $name => $plugin ) {
			$plugin_slug = $plugin['slug'];
			$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
			if ( file_exists( $plugin_path ) ) {
				$plugin_data                         = get_plugin_data( $plugin_path, false, false );
				$this->plugins[ $name ]['installed'] = true;
				$this->plugins[ $name ]['version']   = $plugin_data['Version'];
				$this->plugins[ $name ]['active']    = is_plugin_active( $plugin_slug );
			}
		}
	}
	protected function plugin_exists( $plugin ) {
		return isset( $this->plugins[ $plugin ] );
	}
	public function get_plugins() {
		return $this->plugins;
	}
	public function get_plugin( $plugin ) {
		if ( ! $this->plugin_exists( $plugin ) ) {
			return array();
		}
		return $this->plugins[ $plugin ];
	}
	public function get_version( $plugin ) {
		if ( ! isset( $plugin['version'] ) ) {
			return '';
		}
		return $plugin['version'];
	}
	public function has_dependencies( $plugin ) {
		return ( isset( $plugin['_dependencies'] ) && ! empty( $plugin['_dependencies'] ) );
	}
	public function get_dependencies( $plugin ) {
		if ( ! $this->has_dependencies( $plugin ) ) {
			return array();
		}
		return $plugin['_dependencies'];
	}
	public function dependencies_are_satisfied( $plugin ) {
		if ( ! $this->has_dependencies( $plugin ) ) {
			return true;
		}
		$dependencies           = $this->get_dependencies( $plugin );
		$installed_dependencies = array_filter( $dependencies, array( $this, 'is_dependency_available' ) );
		return count( $installed_dependencies ) === count( $dependencies );
	}
	public function is_installed( $plugin ) {
		if ( empty( $plugin ) ) {
			return false;
		}
		return $this->is_available( $plugin );
	}
	public function get_installed_plugins() {
		$installed = array();
		foreach ( $this->plugins as $plugin_key => $plugin ) {
			if ( $this->is_installed( $plugin ) ) {
				$installed[ $plugin_key ] = $plugin;
			}
		}
		return $installed;
	}
	public function is_available( $plugin ) {
		return isset( $plugin['installed'] ) && $plugin['installed'] === true;
	}
	public function is_dependency_available( $dependency ) {
		return in_array( $dependency['slug'], array_keys( get_plugins() ), true );
	}
	public function get_dependency_names( $plugin ) {
		if ( ! $this->has_dependencies( $plugin ) ) {
			return array();
		}
		return array_keys( $plugin['_dependencies'] );
	}
	public function get_plugins_with_dependencies() {
		return array_filter( $this->plugins, array( $this, 'has_dependencies' ) );
	}
	public function is_active( $plugin ) {
		return is_plugin_active( $plugin );
	}
	public function is_premium( $plugin ) {
		return isset( $plugin['premium'] ) && $plugin['premium'] === true;
	}
}
