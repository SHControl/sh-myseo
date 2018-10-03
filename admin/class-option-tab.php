<?php

class WPSEO_Option_Tab {
	private $name;
	private $label;
	private $arguments;
	public function __construct( $name, $label, array $arguments = array() ) {
		$this->name      = sanitize_title( $name );
		$this->label     = $label;
		$this->arguments = $arguments;
	}
	public function get_name() {
		return $this->name;
	}
	public function get_label() {
		return $this->label;
	}
	public function get_video_url() {
		return $this->get_argument( 'video_url' );
	}
	public function has_save_button() {
		return (bool) $this->get_argument( 'save_button', true );
	}
	public function get_opt_group() {
		return $this->get_argument( 'opt_group' );
	}
	protected function get_argument( $variable, $default = '' ) {
		return array_key_exists( $variable, $this->arguments ) ? $this->arguments[ $variable ] : $default;
	}
}
