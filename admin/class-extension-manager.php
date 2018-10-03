<?php

class WPSEO_Extension_Manager {
	const TRANSIENT_CACHE_KEY = 'wpseo_license_active_extensions';
	protected $extensions = array();
	static protected $active_extensions;
	public function add( $extension_name, WPSEO_Extension $extension = null ) {
		$this->extensions[ $extension_name ] = $extension;
	}
	public function remove( $extension_name ) {
		if ( array_key_exists( $extension_name, $this->extensions ) ) {
			unset( $this->extensions[ $extension_name ] );
		}
	}
	public function get( $extension_name ) {
		if ( array_key_exists( $extension_name, $this->extensions ) ) {
			return $this->extensions[ $extension_name ];
		}
		return null;
	}
	public function get_all() {
		return $this->extensions;
	}
	public function is_activated( $extension_name ) {
		if ( self::$active_extensions === null ) {
			$current_page = $this->get_current_page();
			$exclude_cache = ( $current_page === 'wpseo_licenses' || $current_page === 'wpseo_dashboard' );
			if ( ! $exclude_cache ) {
				self::$active_extensions = $this->get_cached_extensions();
			}
			if ( ! is_array( self::$active_extensions ) ) {
				self::$active_extensions = $this->retrieve_active_extensions();
				$this->set_cached_extensions( self::$active_extensions );
			}
		}
		return in_array( $extension_name, self::$active_extensions, true );
	}
	protected function retrieve_active_extensions() {
		return (array) apply_filters( 'yoast-active-extensions', array() );
	}
	protected function get_current_page() {
		return filter_input( INPUT_GET, 'page' );
	}
	protected function get_cached_extensions() {
		return get_transient( self::TRANSIENT_CACHE_KEY );
	}
	protected function set_cached_extensions( $extensions, $duration = DAY_IN_SECONDS ) {
		set_transient( self::TRANSIENT_CACHE_KEY, $extensions, $duration );
	}
}
