<?php

class WPSEO_Meta_Table_Accessible {
	const ACCESSIBLE   = '0';
	const INACCESSBILE = '1';
	public static function is_accessible() {
		$value = get_transient( self::transient_name() );
		if ( false === $value ) {
			return self::check_table();
		}
		return $value === self::ACCESSIBLE;
	}
	public static function set_inaccessible() {
		set_transient( self::transient_name(), self::INACCESSBILE, HOUR_IN_SECONDS );
	}
	public static function cleanup() {
		delete_transient( self::transient_name() );
	}
	protected static function set_accessible() {
		set_transient( self::transient_name(), self::ACCESSIBLE, YEAR_IN_SECONDS );
	}
	protected static function check_table() {
		global $wpdb;
		$storage = new WPSEO_Meta_Storage();
		$query   = $wpdb->prepare( 'SHOW TABLES LIKE %s', $storage->get_table_name() );
		if ( $wpdb->get_var( $query ) !== $storage->get_table_name() ) {
			self::set_inaccessible();
			return false;
		}
		self::set_accessible();
		return true;
	}
	protected static function transient_name() {
		return 'wpseo_meta_table_inaccessible';
	}
	public static function check_table_is_accessible() {
		_deprecated_function( __FUNCTION__, '6.0', __CLASS__ . '::is_accessible' );
		return self::is_accessible();
	}
}
