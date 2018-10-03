<?php

class WPSEO_Plugin_Compatibility {
	protected $current_wpseo_version;
	protected $availability_checker;
	protected $installed_plugins;
	public function __construct( $version, $availability_checker = null ) {
		$this->current_wpseo_version = $this->get_major_minor_version( $version );
		$this->availability_checker  = $this->retrieve_availability_checker( $availability_checker );
		$this->installed_plugins     = $this->availability_checker->get_installed_plugins();
	}
	private function retrieve_availability_checker( $checker ) {
		if ( is_null( $checker ) || ! is_object( $checker ) ) {
			$checker = new WPSEO_Plugin_Availability();
			$checker->register();
		}
		return $checker;
	}
	public function get_installed_plugins() {
		return $this->installed_plugins;
	}
	public function get_installed_plugins_compatibility() {
		foreach ( $this->installed_plugins as $key => $plugin ) {
			$this->installed_plugins[ $key ]['compatible'] = $this->is_compatible( $key );
		}
		return $this->installed_plugins;
	}
	public function is_compatible( $plugin ) {
		$plugin = $this->availability_checker->get_plugin( $plugin );
		if ( ! isset( $plugin['version_sync'] ) || $plugin['version_sync'] !== true ) {
			return true;
		}
		$plugin_version = $this->availability_checker->get_version( $plugin );
		return $this->get_major_minor_version( $plugin_version ) === $this->current_wpseo_version;
	}
	protected function get_major_minor_version( $version ) {
		return substr( $version, 0, 3 );
	}
}
