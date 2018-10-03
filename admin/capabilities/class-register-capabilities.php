<?php

class WPSEO_Register_Capabilities implements WPSEO_WordPress_Integration {
	public function register_hooks() {
		add_action( 'wpseo_register_capabilities', array( $this, 'register' ) );
		if ( is_multisite() ) {
			add_action( 'user_has_cap', array( $this, 'filter_user_has_wpseo_manage_options_cap' ), 10, 4 );
		}
	}

	public function register() {
		$manager = WPSEO_Capability_Manager_Factory::get();
		$manager->register( 'wpseo_bulk_edit', array( 'editor', 'wpseo_editor', 'wpseo_manager' ) );
		$manager->register( 'wpseo_edit_advanced_metadata', array( 'wpseo_editor', 'wpseo_manager' ) );
		$manager->register( 'wpseo_manage_options', array( 'administrator', 'wpseo_manager' ) );
	}

	public function filter_user_has_wpseo_manage_options_cap( $allcaps, $caps, $args, $user ) {
		if ( ! in_array( 'wpseo_manage_options', $caps, true ) ) {
			return $allcaps;
		}
		if ( empty( $allcaps['wpseo_manage_options'] ) ) {
			return $allcaps;
		}
		if ( empty( $allcaps['delete_users'] ) ) {
			return $allcaps;
		}
		$options = WPSEO_Options::get_instance();
		if ( $options->get( 'access' ) === 'superadmin' && ! is_super_admin( $user->ID ) ) {
			unset( $allcaps['wpseo_manage_options'] );
		}
		return $allcaps;
	}
}
