<?php

abstract class WPSEO_Abstract_Capability_Manager implements WPSEO_Capability_Manager {
	protected $capabilities = array();
	public function register( $capability, array $roles, $overwrite = false ) {
		if ( $overwrite || ! isset( $this->capabilities[ $capability ] ) ) {
			$this->capabilities[ $capability ] = $roles;
			return;
		}
		$this->capabilities[ $capability ] = array_merge( $roles, $this->capabilities[ $capability ] );
		$this->capabilities[ $capability ] = array_unique( $this->capabilities[ $capability ] );
	}

	public function get_capabilities() {
		return array_keys( $this->capabilities );
	}

	protected function get_wp_roles( array $roles ) {
		$wp_roles = array_map( 'get_role', $roles );
		return array_filter( $wp_roles );
	}

	protected function filter_roles( $capability, array $roles ) {
		$filtered = apply_filters( $capability . '_roles', $roles );
		if ( ! is_array( $filtered ) ) {
			return array();
		}
		return $filtered;
	}
}
