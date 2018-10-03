<?php

class WPSEO_Admin_Asset_Manager {
	protected $asset_location;
	const PREFIX = 'yoast-seo-';
	private $prefix;

	public function __construct( WPSEO_Admin_Asset_Location $asset_location = null, $prefix = self::PREFIX ) {
		if ( $asset_location === null ) {
			$asset_location = self::create_default_location();
		}
		$this->asset_location = $asset_location;
		$this->prefix         = $prefix;
	}

	public function enqueue_script( $script ) {
		wp_enqueue_script( $this->prefix . $script );
	}

	public function enqueue_style( $style ) {
		wp_enqueue_style( $this->prefix . $style );
	}

	public function register_script( WPSEO_Admin_Asset $script ) {
		wp_register_script(
			$this->prefix . $script->get_name(),
			$this->asset_location->get_url( $script, WPSEO_Admin_Asset::TYPE_JS ),
			$script->get_deps(),
			$script->get_version(),
			$script->is_in_footer()
		);
	}

	public function register_style( WPSEO_Admin_Asset $style ) {
		wp_register_style(
			$this->prefix . $style->get_name(),
			$this->asset_location->get_url( $style, WPSEO_Admin_Asset::TYPE_CSS ),
			$style->get_deps(),
			$style->get_version(),
			$style->get_media()
		);
	}

	public function register_assets() {
		$this->register_scripts( $this->scripts_to_be_registered() );
		$this->register_styles( $this->styles_to_be_registered() );
	}

	public function register_scripts( $scripts ) {
		foreach ( $scripts as $script ) {
			$script = new WPSEO_Admin_Asset( $script );
			$this->register_script( $script );
		}
	}

	public function register_styles( $styles ) {
		foreach ( $styles as $style ) {
			$style = new WPSEO_Admin_Asset( $style );
			$this->register_style( $style );
		}
	}

	public function special_styles() {
		$flat_version = $this->flatten_version( WPSEO_VERSION );
		return array(
			'inside-editor' => new WPSEO_Admin_Asset( array(
				'name' => 'inside-editor',
				'src'  => 'inside-editor-' . $flat_version,
			) ),
		);
	}

	public function flatten_version( $version ) {
		$parts = explode( '.', $version );
		if ( count( $parts ) === 2 && preg_match( '/^\d+$/', $parts[1] ) === 1 ) {
			$parts[] = '0';
		}
		return implode( '', $parts );
	}

	public static function create_default_location() {
		if ( defined( 'YOAST_SEO_DEV_SERVER' ) && YOAST_SEO_DEV_SERVER ) {
			$url = defined( 'YOAST_SEO_DEV_SERVER_URL' ) ? YOAST_SEO_DEV_SERVER_URL : WPSEO_Admin_Asset_Dev_Server_Location::DEFAULT_URL;
			return new WPSEO_Admin_Asset_Dev_Server_Location( $url );
		}
		return new WPSEO_Admin_Asset_SEO_Location( WPSEO_FILE );
	}

	protected function scripts_to_be_registered() {
		$select2_language = 'en';
		$user_locale      = WPSEO_Utils::get_user_locale();
		$language         = WPSEO_Utils::get_language( $user_locale );
		if ( file_exists( WPSEO_PATH . "js/dist/select2/i18n/{$user_locale}.js" ) ) {
			$select2_language = $user_locale;
		} elseif ( file_exists( WPSEO_PATH . "js/dist/select2/i18n/{$language}.js" ) ) {
			$select2_language = $language;
		}

		$flat_version = $this->flatten_version( WPSEO_VERSION );
		$backport_wp_dependencies = array( self::PREFIX . 'react-dependencies' );
		if ( $this->should_load_gutenberg_assets() ) {
			$backport_wp_dependencies[] = 'wp-element';
			$backport_wp_dependencies[] = 'wp-data';
			$backport_wp_dependencies[] = 'wp-components';
			if ( wp_script_is( 'tinymce-latest', 'registered' ) && isset( $_GET['classic-editor'] ) ) {
				wp_deregister_script( 'tinymce-latest' );
				wp_register_script( 'tinymce-latest', includes_url( 'js/tinymce/' ) . 'wp-tinymce.php', array( 'jquery' ), false, true );
			}
		} else {
			if ( wp_script_is( 'lodash', 'registered' ) ) {
				$backport_wp_dependencies[] = 'lodash';
			} else {
				if ( ! wp_script_is( self::PREFIX . 'lodash', 'registered' ) ) {
					wp_register_script( self::PREFIX . 'lodash-base', plugins_url( 'js/vendor/lodash.min.js', WPSEO_FILE ), array(), false, true );
					wp_register_script( self::PREFIX . 'lodash', plugins_url( 'js/vendor/lodash-noconflict.js', WPSEO_FILE ), array( self::PREFIX . 'lodash-base' ), false, true );
				}
				$backport_wp_dependencies[] = self::PREFIX . 'lodash';
			}
		}

		$babel_polyfill = 'wp-polyfill-ecmascript';
		if ( ! wp_script_is( 'wp-polyfill-ecmascript', 'registered' ) ) {
			$babel_polyfill = self::PREFIX . 'babel-polyfill';
		}

		return array(
			array(
				'name' => 'react-dependencies',
				'src'  => 'commons-' . $flat_version,
				'deps' => array( $babel_polyfill ),
			),
			array(
				'name' => 'search-appearance',
				'src'  => 'search-appearance-' . $flat_version,
				'deps' => array(
					self::PREFIX . 'react-dependencies',
					self::PREFIX . 'components',
				),
			),
			array(
				'name' => 'wp-globals-backport',
				'src'  => 'wp-seo-wp-globals-backport-' . $flat_version,
				'deps' => $backport_wp_dependencies,
			),
			array(
				'name' => 'yoast-modal',
				'src'  => 'wp-seo-modal-' . $flat_version,
				'deps' => array(
					'jquery',
					self::PREFIX . 'wp-globals-backport',
					self::PREFIX . 'components',
				),
			),
			array(
				'name' => 'help-center',
				'src'  => 'wp-seo-help-center-' . $flat_version,
				'deps' => array(
					'jquery',
					self::PREFIX . 'wp-globals-backport',
					self::PREFIX . 'components',
				),
			),
			array(
				'name' => 'admin-script',
				'src'  => 'wp-seo-admin-' . $flat_version,
				'deps' => array(
					'jquery',
					'jquery-ui-core',
					'jquery-ui-progressbar',
					self::PREFIX . 'select2',
					self::PREFIX . 'select2-translations',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name' => 'admin-media',
				'src'  => 'wp-seo-admin-media-' . $flat_version,
				'deps' => array(
					'jquery',
					'jquery-ui-core',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name' => 'network-admin-script',
				'src'  => 'wp-seo-network-admin-' . $flat_version,
				'deps' => array( 'jquery' ),
			),
			array(
				'name' => 'bulk-editor',
				'src'  => 'wp-seo-bulk-editor-' . $flat_version,
				'deps' => array(
					'jquery',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name' => 'admin-global-script',
				'src'  => 'wp-seo-admin-global-' . $flat_version,
				'deps' => array(
					'jquery',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name'      => 'metabox',
				'src'       => 'wp-seo-metabox-' . $flat_version,
				'deps'      => array(
					'jquery',
					self::PREFIX . 'select2',
					self::PREFIX . 'select2-translations',
					self::PREFIX . 'wp-globals-backport',
				),
				'in_footer' => false,
			),
			array(
				'name' => 'featured-image',
				'src'  => 'wp-seo-featured-image-' . $flat_version,
				'deps' => array(
					'jquery',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name'      => 'admin-gsc',
				'src'       => 'wp-seo-admin-gsc-' . $flat_version,
				'deps'      => array(
					self::PREFIX . 'react-dependencies',
				),
				'in_footer' => false,
			),
			array(
				'name' => 'post-scraper',
				'src'  => 'wp-seo-post-scraper-' . $flat_version,
				'deps' => array(
					self::PREFIX . 'replacevar-plugin',
					self::PREFIX . 'shortcode-plugin',
					'wp-util',
					'wp-api',
					self::PREFIX . 'wp-globals-backport',
					self::PREFIX . 'analysis',
					self::PREFIX . 'react-dependencies',
					self::PREFIX . 'components',
				),
			),
			array(
				'name' => 'term-scraper',
				'src'  => 'wp-seo-term-scraper-' . $flat_version,
				'deps' => array(
					self::PREFIX . 'replacevar-plugin',
					self::PREFIX . 'wp-globals-backport',
					self::PREFIX . 'analysis',
					self::PREFIX . 'components',
				),
			),
			array(
				'name' => 'replacevar-plugin',
				'src'  => 'wp-seo-replacevar-plugin-' . $flat_version,
				'deps' => array(
					self::PREFIX . 'react-dependencies',
					self::PREFIX . 'analysis',
					self::PREFIX . 'components',
				),
			),
			array(
				'name' => 'shortcode-plugin',
				'src'  => 'wp-seo-shortcode-plugin-' . $flat_version,
				'deps' => array(
					self::PREFIX . 'react-dependencies',
					self::PREFIX . 'analysis',
				),
			),
			array(
				'name' => 'recalculate',
				'src'  => 'wp-seo-recalculate-' . $flat_version,
				'deps' => array(
					'jquery',
					'jquery-ui-core',
					'jquery-ui-progressbar',
					self::PREFIX . 'analysis',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name' => 'primary-category',
				'src'  => 'wp-seo-metabox-category-' . $flat_version,
				'deps' => array(
					'jquery',
					'wp-util',
					self::PREFIX . 'react-dependencies',
					self::PREFIX . 'wp-globals-backport',
				),
			),
			array(
				'name'    => 'select2',
				'src'     => 'select2/select2.full',
				'suffix'  => '.min',
				'deps'    => array(
					'jquery',
				),
				'version' => '4.0.3',
			),
			array(
				'name'    => 'select2-translations',
				'src'     => 'select2/i18n/' . $select2_language,
				'deps'    => array(
					'jquery',
					self::PREFIX . 'select2',
				),
				'version' => '4.0.3',
				'suffix'  => '',
			),
			array(
				'name' => 'configuration-wizard',
				'src'  => 'configuration-wizard-' . $flat_version,
				'deps' => array(
					'jquery',
					self::PREFIX . 'wp-globals-backport',
					self::PREFIX . 'components',
				),
			),
			array(
				'name' => 'reindex-links',
				'src'  => 'wp-seo-reindex-links-' . $flat_version,
				'deps' => array(
					'jquery',
					'jquery-ui-core',
					'jquery-ui-progressbar',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name' => 'edit-page-script',
				'src'  => 'wp-seo-edit-page-' . $flat_version,
				'deps' => array(
					'jquery',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name'      => 'quick-edit-handler',
				'src'       => 'wp-seo-quick-edit-handler-' . $flat_version,
				'deps'      => array(
					'jquery',
					self::PREFIX . 'react-dependencies',
				),
				'in_footer' => true,
			),
			array(
				'name' => 'api',
				'src'  => 'wp-seo-api-' . $flat_version,
				'deps' => array(
					'wp-api',
					'jquery',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name' => 'dashboard-widget',
				'src'  => 'wp-seo-dashboard-widget-' . $flat_version,
				'deps' => array(
					self::PREFIX . 'api',
					'jquery',
					self::PREFIX . 'wp-globals-backport',
					self::PREFIX . 'components',
				),
			),
			array(
				'name' => 'filter-explanation',
				'src'  => 'wp-seo-filter-explanation-' . $flat_version,
				'deps' => array(
					'jquery',
					self::PREFIX . 'react-dependencies',
				),
			),
			array(
				'name' => 'analysis',
				'src'  => 'analysis-' . $flat_version,
			),
			array(
				'name' => 'components',
				'src'  => 'components-' . $flat_version,
				'deps' => array( self::PREFIX . 'analysis' ),
			),
			array(
				'name' => 'structured-data-blocks',
				'src'  => 'wp-seo-structured-data-blocks-' . $flat_version,
				'deps' => array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
			),
			array(
				'name' => 'babel-polyfill',
				'src'  => 'babel-polyfill-' . $flat_version,
			),
		);
	}

	protected function styles_to_be_registered() {
		$flat_version = $this->flatten_version( WPSEO_VERSION );
		return array(
			array(
				'name' => 'admin-css',
				'src'  => 'yst_plugin_tools-' . $flat_version,
				'deps' => array( self::PREFIX . 'toggle-switch' ),
			),
			array(
				'name' => 'toggle-switch',
				'src'  => 'toggle-switch-' . $flat_version,
			),
			array(
				'name' => 'dismissible',
				'src'  => 'wpseo-dismissible-' . $flat_version,
			),
			array(
				'name' => 'alerts',
				'src'  => 'alerts-' . $flat_version,
			),
			array(
				'name' => 'edit-page',
				'src'  => 'edit-page-' . $flat_version,
			),
			array(
				'name' => 'featured-image',
				'src'  => 'featured-image-' . $flat_version,
			),
			array(
				'name' => 'metabox-css',
				'src'  => 'metabox-' . $flat_version,
				'deps' => array(
					self::PREFIX . 'select2',
				),
			),
			array(
				'name' => 'wp-dashboard',
				'src'  => 'dashboard-' . $flat_version,
			),
			array(
				'name' => 'scoring',
				'src'  => 'yst_seo_score-' . $flat_version,
			),
			array(
				'name' => 'adminbar',
				'src'  => 'adminbar-' . $flat_version,
			),
			array(
				'name' => 'primary-category',
				'src'  => 'metabox-primary-category-' . $flat_version,
			),
			array(
				'name'    => 'select2',
				'src'     => 'select2/select2',
				'suffix'  => '.min',
				'version' => '4.0.1',
				'rtl'     => false,
			),
			array(
				'name' => 'admin-global',
				'src'  => 'admin-global-' . $flat_version,
			),
			array(
				'name' => 'yoast-components',
				'src'  => 'yoast-components-' . $flat_version,
			),
			array(
				'name' => 'extensions',
				'src'  => 'yoast-extensions-' . $flat_version,
			),
			array(
				'name' => 'filter-explanation',
				'src'  => 'filter-explanation-' . $flat_version,
			),
			array(
				'name' => 'search-appearance',
				'src'  => 'search-appearance-' . $flat_version,
			),
			array(
				'name' => 'structured-data-blocks',
				'src'  => 'structured-data-blocks-' . $flat_version,
				'deps' => array( 'wp-edit-blocks' ),
			),
		);
	}

	protected function should_load_gutenberg_assets() {
		if ( ! function_exists( 'gutenberg_register_scripts_and_styles' ) ) {
			return false;
		}

		if ( isset( $_GET['classic-editor'] ) ) {
			return false;
		}

		if ( function_exists( 'classic_editor_is_gutenberg_active' ) && classic_editor_is_gutenberg_active() ) {
			return false;
		}
		return true;
	}
}
