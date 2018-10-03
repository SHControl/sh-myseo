<?php

class Yoast_Notification {
	const MATCH_ALL = 'all';
	const MATCH_ANY = 'any';
	const ERROR = 'error';
	const WARNING = 'warning';
	const UPDATED = 'updated';
	private $options = array();
	private $defaults = array(
		'type'             => self::UPDATED,
		'id'               => '',
		'nonce'            => null,
		'priority'         => 0.5,
		'data_json'        => array(),
		'dismissal_key'    => null,
		'capabilities'     => array(),
		'capability_check' => self::MATCH_ALL,
		'yoast_branding'   => false,
	);

	private $message;
	private $id;
	public function __construct( $message, $options = array() ) {
		$this->message = $message;
		$this->options = $this->normalize_options( $options );
	}

	public function get_id() {
		return $this->options['id'];
	}

	public function get_nonce() {
		if ( $this->options['id'] && empty( $this->options['nonce'] ) ) {
			$this->options['nonce'] = wp_create_nonce( $this->options['id'] );
		}

		return $this->options['nonce'];
	}

	public function refresh_nonce() {
		if ( $this->options['id'] ) {
			$this->options['nonce'] = wp_create_nonce( $this->options['id'] );
		}
	}

	public function get_type() {
		return $this->options['type'];
	}

	public function get_priority() {
		return $this->options['priority'];
	}

	public function get_dismissal_key() {
		if ( empty( $this->options['dismissal_key'] ) ) {
			return $this->options['id'];
		}

		return $this->options['dismissal_key'];
	}

	public function is_persistent() {
		$id = $this->get_id();

		return ! empty( $id );
	}

	public function display_for_current_user() {
		if ( ! $this->is_persistent() ) {
			return true;
		}

		return $this->match_capabilities();
	}

	public function match_capabilities() {
		if ( is_multisite() && is_super_admin() ) {
			return true;
		}

		$capabilities = apply_filters( 'wpseo_notification_capabilities', $this->options['capabilities'], $this );

		if ( ! is_array( $capabilities ) ) {
			$capabilities = (array) $capabilities;
		}

		$capability_check = apply_filters( 'wpseo_notification_capability_check', $this->options['capability_check'], $this );

		if ( ! in_array( $capability_check, array( self::MATCH_ALL, self::MATCH_ANY ), true ) ) {
			$capability_check = self::MATCH_ALL;
		}

		if ( ! empty( $capabilities ) ) {

			$has_capabilities = array_filter( $capabilities, array( $this, 'has_capability' ) );

			switch ( $capability_check ) {
				case self::MATCH_ALL:
					return $has_capabilities === $capabilities;
				case self::MATCH_ANY:
					return ! empty( $has_capabilities );
			}
		}

		return true;
	}

	private function has_capability( $capability ) {
		return current_user_can( $capability );
	}

	public function to_array() {
		return array(
			'message' => $this->message,
			'options' => $this->options,
		);
	}

	public function __toString() {
		return $this->render();
	}

	public function render() {
		$attributes = array();
		$classes = array(
			'yoast-alert',
		);

		if ( ! $this->is_persistent() ) {
			$classes[] = 'notice';
			$classes[] = $this->get_type();
		}

		if ( ! empty( $classes ) ) {
			$attributes['class'] = implode( ' ', $classes );
		}

		array_walk( $attributes, array( $this, 'parse_attributes' ) );

		$message = null;
		if ( $this->options['yoast_branding'] ) {
			$message = $this->wrap_yoast_seo_icon( $this->message );
		}

		if ( $message === null ) {
			$message = wpautop( $this->message );
		}

		return '<div ' . implode( ' ', $attributes ) . '>' . $message . '</div>' . PHP_EOL;
	}

	private function wrap_yoast_seo_icon( $message ) {
		$out  = sprintf(
			'<img src="%1$s" height="%2$d" width="%3$d" class="yoast-seo-icon" />',
			esc_url( plugin_dir_url( WPSEO_FILE ) . 'images/Yoast_SEO_Icon.svg' ),
			60,
			60
		);
		$out .= '<div class="yoast-seo-icon-wrap">';
		$out .= $message;
		$out .= '</div>';

		return $out;
	}

	public function get_json() {
		if ( empty( $this->options['data_json'] ) ) {
			return '';
		}

		return wp_json_encode( $this->options['data_json'] );
	}

	private function normalize_options( $options ) {
		$options = wp_parse_args( $options, $this->defaults );
		$options['priority'] = min( 1, max( 0, $options['priority'] ) );

		if ( empty( $options['capabilities'] ) || array() === $options['capabilities'] ) {
			$options['capabilities'] = array( 'wpseo_manage_options' );
		}

		return $options;
	}

	private function parse_attributes( & $value, $key ) {
		$value = sprintf( '%s="%s"', $key, esc_attr( $value ) );
	}
}
