<?php

final class WPSEO_Admin_Asset_Dev_Server_Location implements WPSEO_Admin_Asset_Location {
	const DEFAULT_URL = 'http://localhost:8080';
	private $url;
	public function __construct( $url = null ) {
		if ( $url === null ) {
			$url = self::DEFAULT_URL;
		}
		$this->url = $url;
	}

	public function get_url( WPSEO_Admin_Asset $asset, $type ) {
		if ( WPSEO_Admin_Asset::TYPE_CSS === $type ) {
			return $this->get_default_url( $asset, $type );
		}

		$asset_manager       = new WPSEO_Admin_Asset_Manager();
		$flat_version        = $asset_manager->flatten_version( WPSEO_VERSION );
		$version_less_source = str_replace( '-' . $flat_version, '', $asset->get_src() );
		if ( false !== strpos( $version_less_source, 'select2' ) ) {
			return $this->get_default_url( $asset, $type );
		}
		$path = sprintf( '%s%s.js', $asset->get_src(), $asset->get_suffix() );
		return trailingslashit( $this->url ) . $path;
	}

	public function get_default_url( WPSEO_Admin_Asset $asset, $type ) {
		$default_location = new WPSEO_Admin_Asset_SEO_Location( WPSEO_FILE );
		return $default_location->get_url( $asset, $type );
	}
}
