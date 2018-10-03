<?php

class WPSEO_Admin_Help_Panel {
	private $id;
	private $help_button_text;
	private $help_content;
	private $wrapper;
	public function __construct( $id, $help_button_text, $help_content, $wrapper = '' ) {
		$this->id               = $id;
		$this->help_button_text = $help_button_text;
		$this->help_content     = $help_content;
		$this->wrapper          = $wrapper;
	}

	public function get_button_html() {
		if ( ! $this->id || ! $this->help_button_text || ! $this->help_content ) {
			return '';
		}
		return sprintf(
			' <button type="button" class="yoast_help yoast-help-button dashicons" id="%1$s-help-toggle" aria-expanded="false" aria-controls="%1$s-help"><span class="yoast-help-icon" aria-hidden="true"></span><span class="screen-reader-text">%2$s</span></button>',
			esc_attr( $this->id ),
			$this->help_button_text
		);
	}

	public function get_panel_html() {
		if ( ! $this->id || ! $this->help_button_text || ! $this->help_content ) {
			return '';
		}
		$wrapper_start = '';
		$wrapper_end   = '';
		if ( 'has-wrapper' === $this->wrapper ) {
			$wrapper_start = '<div class="yoast-seo-help-container">';
			$wrapper_end   = '</div>';
		}
		return sprintf(
			'%1$s<p id="%2$s-help" class="yoast-help-panel">%3$s</p>%4$s',
			$wrapper_start,
			esc_attr( $this->id ),
			$this->help_content,
			$wrapper_end
		);
	}
}
