<?php

class WPSEO_Help_Center_Item {
	private $identifier;
	private $label;
	private $dashicon;
	private $args = array();
	public function __construct( $identifier, $label, $args = array(), $dashicon = '' ) {
		$this->identifier = $identifier;
		$this->label      = $label;
		$this->dashicon   = $dashicon;
		$this->args       = $args;
	}
	public function get_label() {
		return $this->label;
	}
	public function get_identifier() {
		return $this->identifier;
	}
	public function get_dashicon() {
		return $this->dashicon;
	}
	public function get_content() {
		if ( ! empty( $this->args['content'] ) ) {
			return $this->args['content'];
		}
		if ( ! empty( $this->args['callback'] ) ) {
			return call_user_func_array( $this->args['callback'], array( $this ) );
		}
		if ( ! empty( $this->args['view'] ) ) {
			$view = $this->args['view'];
			if ( substr( $view, - 4 ) === '.php' ) {
				$view = substr( $view, 0, - 4 );
			}
			if ( ! empty( $this->args['view_arguments'] ) ) {
				extract( $this->args['view_arguments'] );
			}
			include WPSEO_PATH . 'admin/views/' . $view . '.php';
		}
		return '';
	}
}
