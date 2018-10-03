<?php

final class WPSEO_Capability_Manager_WP extends WPSEO_Abstract_Capability_Manager {
	public function add() {
		foreach ( $this->capabilities as $capability => $roles ) {
			$filtered_roles = $this->filter_roles( $capability, $roles );
			$wp_roles = $this->get_wp_roles( $filtered_roles );
			foreach ( $wp_roles as $wp_role ) {
				$wp_role->add_cap( $capability );
			}
		}
	}

	public function remove() {
		$roles = wp_roles()->get_names();
		$roles = array_keys( $roles );
		foreach ( $this->capabilities as $capability => $_roles ) {
			$registered_roles = array_unique( array_merge( $roles, $this->capabilities[ $capability ] ) );
			$filtered_roles = $this->filter_roles( $capability, $registered_roles );
			$wp_roles = $this->get_wp_roles( $filtered_roles );
			foreach ( $wp_roles as $wp_role ) {
				$wp_role->remove_cap( $capability );
			}
		}
	}
}
