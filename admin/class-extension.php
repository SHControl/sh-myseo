<?php

class WPSEO_Extension {
	protected $config = array();
	public function __construct( array $config ) {
		$this->config = $config;
	}
	public function get_title() {
		return $this->config['title'];
	}
	public function get_buy_url() {
		return $this->config['buyUrl'];
	}
	public function get_info_url() {
		return $this->config['infoUrl'];
	}
	public function get_image() {
		return $this->config['image'];
	}
	public function get_buy_button() {
		if ( isset( $this->config['buy_button'] ) ) {
			return $this->config['buy_button'];
		}
		return $this->get_title();
	}
	public function get_benefits() {
		return $this->config['benefits'];
	}
}
