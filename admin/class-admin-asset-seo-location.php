<?php

final class WPSEO_Admin_Asset_SEO_Location implements WPSEO_Admin_Asset_Location {
	protected $plugin_file;
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	public function get_url( WPSEO_Admin_Asset $asset, $type ) {
		$path = $this->get_path( $asset, $type );
		if ( empty( $path ) ) {
			return '';
		}

		if ( YOAST_ENVIRONMENT !== 'development' && ! $asset->get_suffix() ) {
			$plugin_path = plugin_dir_path( $this->plugin_file );
			if ( ! file_exists( $plugin_path . $path ) ) {
				WPSEO_Utils::javascript_console_notification(
					'Development Files',
					sprintf(
						__( 'You are trying to load non-minified files. These are only available in our development package. Check out %1$s to see all the source files.', 'wordpress-seo' ),
						'//github.com/SHControl/myseo'
					),
					true
				);
				$path = $this->get_path( $asset, $type, '.min' );
			}
		}

		return plugins_url( $path, $this->plugin_file );
	}

	protected function get_path( WPSEO_Admin_Asset $asset, $type, $force_suffix = null ) {
		$relative_path = '';
		$rtl_suffix    = '';
		$suffix = ( $force_suffix === null ) ? $asset->get_suffix() : $force_suffix;
		switch ( $type ) {
			case WPSEO_Admin_Asset::TYPE_JS:
				$relative_path = 'js/dist/' . $asset->get_src() . $suffix . '.js';
				break;

			case WPSEO_Admin_Asset::TYPE_CSS:
				if ( function_exists( 'is_rtl' ) && is_rtl() && $asset->has_rtl() ) {
					$rtl_suffix = '-rtl';
				}
				$relative_path = 'css/dist/' . $asset->get_src() . $rtl_suffix . $suffix . '.css';
				break;
		}
		return $relative_path;
	}
}
