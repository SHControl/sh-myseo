<?php

final class WPSEO_Admin_Asset_Analysis_Worker_Location implements WPSEO_Admin_Asset_Location {
	private $asset_location;
	private $asset;

	public function __construct( $flat_version = '', $name = 'analysis-worker' ) {
		if ( $flat_version === '' ) {
			$asset_manager = new WPSEO_Admin_Asset_Manager();
			$flat_version  = $asset_manager->flatten_version( WPSEO_VERSION );
		}
		$this->asset_location = WPSEO_Admin_Asset_Manager::create_default_location();
		$this->asset          = new WPSEO_Admin_Asset( array(
			'name' => $name,
			'src'  => 'wp-seo-' . $name . '-' . $flat_version,
		) );
	}

	public function get_asset() {
		return $this->asset;
	}

	public function get_url( WPSEO_Admin_Asset $asset, $type ) {
		return $this->asset_location->get_url( $asset, $type );
	}
}
