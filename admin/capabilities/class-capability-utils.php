<?php

class WPSEO_Capability_Utils {
	public static function current_user_can( $capability ) {
		if ( $capability === 'wpseo_manage_options' ) {
			return self::has( $capability );
		}
		return self::has_any( array( 'wpseo_manage_options', $capability ) );
	}

	protected static function has_any( array $capabilities ) {
		foreach ( $capabilities as $capability ) {
			if ( self::has( $capability ) ) {
				return true;
			}
		}
		return false;
	}

	protected static function has( $capability ) {
		return current_user_can( $capability );
	}
}
