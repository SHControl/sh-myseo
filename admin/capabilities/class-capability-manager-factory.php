<?php

class WPSEO_Capability_Manager_Factory {
	public static function get() {
		static $manager = null;
		if ( $manager === null ) {
			if ( function_exists( 'wpcom_vip_add_role_caps' ) ) {
				$manager = new WPSEO_Capability_Manager_VIP();
			}
			if ( ! function_exists( 'wpcom_vip_add_role_caps' ) ) {
				$manager = new WPSEO_Capability_Manager_WP();
			}
		}
		return $manager;
	}
}
