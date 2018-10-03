<?php

final class WPSEO_Capability_Manager_VIP extends WPSEO_Abstract_Capability_Manager {
	public function add() {
		$role_capabilities = array();
		foreach ( $this->capabilities as $capability => $roles ) {
			$role_capabilities = $this->get_role_capabilities( $role_capabilities, $capability, $roles );
		}
		foreach ( $role_capabilities as $role => $capabilities ) {
			wpcom_vip_add_role_caps( $role, $capabilities );
		}
	}

	public function remove() {
		$roles = wp_roles()->get_names();
		$roles = array_keys( $roles );
		$role_capabilities = array();
		foreach ( array_keys( $this->capabilities ) as $capability ) {
			$role_capabilities = $this->get_role_capabilities( $role_capabilities, $capability, $roles );
		}
		foreach ( $role_capabilities as $role => $capabilities ) {
			wpcom_vip_remove_role_caps( $role, $capabilities );
		}
	}

	protected function get_role_capabilities( $role_capabilities, $capability, $roles ) {
		$filtered_roles = $this->filter_roles( $capability, $roles );
		foreach ( $filtered_roles as $role ) {
			if ( ! isset( $add_role_caps[ $role ] ) ) {
				$role_capabilities[ $role ] = array();
			}
			$role_capabilities[ $role ][] = $capability;
		}
		return $role_capabilities;
	}
}
