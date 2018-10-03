<?php

class Yoast_Network_Settings_API {
	private $registered_settings = array();
	private $whitelist_options = array();
	private static $instance = null;

	public function register_setting( $option_group, $option_name, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'group'             => $option_group,
			'sanitize_callback' => null,
		) );
		if ( ! isset( $this->whitelist_options[ $option_group ] ) ) {
			$this->whitelist_options[ $option_group ] = array();
		}
		$this->whitelist_options[ $option_group ][] = $option_name;
		if ( ! empty( $args['sanitize_callback'] ) ) {
			add_filter( "sanitize_option_{$option_name}", array( $this, 'filter_sanitize_option' ), 10, 2 );
		}
		if ( array_key_exists( 'default', $args ) ) {
			add_filter( "default_site_option_{$option_name}", array( $this, 'filter_default_option' ), 10, 2 );
		}
		$this->registered_settings[ $option_name ] = $args;
	}

	public function get_registered_settings() {
		return $this->registered_settings;
	}

	public function get_whitelist_options( $option_group ) {
		if ( ! isset( $this->whitelist_options[ $option_group ] ) ) {
			return array();
		}
		return $this->whitelist_options[ $option_group ];
	}

	public function filter_sanitize_option( $value, $option ) {
		if ( empty( $this->registered_settings[ $option ] ) ) {
			return $value;
		}
		return call_user_func( $this->registered_settings[ $option ]['sanitize_callback'], $value );
	}

	public function filter_default_option( $default, $option ) {
		if ( $default !== false ) {
			return $default;
		}
		if ( empty( $this->registered_settings[ $option ] ) ) {
			return $default;
		}
		return $this->registered_settings[ $option ]['default'];
	}

	public function meets_requirements() {
		return is_multisite();
	}

	public static function get() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
