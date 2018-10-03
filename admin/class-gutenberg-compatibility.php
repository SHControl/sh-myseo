<?php

class WPSEO_Gutenberg_Compatibility {
	const CURRENT_RELEASE = '3.5.0';
	const MINIMUM_SUPPORTED = '3.5.0';
	protected $current_version;
	public function __construct() {
		$this->current_version = $this->detect_installed_gutenberg_version();
	}
	public function is_installed() {
		return $this->current_version !== '';
	}
	public function is_below_minimum() {
		return version_compare( $this->current_version, $this->get_minimum_supported_version(), '<' );
	}
	public function get_installed_version() {
		return $this->current_version;
	}
	public function is_fully_compatible() {
		return version_compare( $this->current_version, $this->get_latest_release(), '>=' );
	}
	protected function get_latest_release() {
		return self::CURRENT_RELEASE;
	}
	protected function get_minimum_supported_version() {
		return self::MINIMUM_SUPPORTED;
	}
	protected function detect_installed_gutenberg_version() {
		if ( defined( 'GUTENBERG_VERSION' ) ) {
			return GUTENBERG_VERSION;
		}
		return '';
	}
}
