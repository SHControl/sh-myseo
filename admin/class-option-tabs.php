<?php

class WPSEO_Option_Tabs {
	private $base;
	private $tabs = array();
	private $active_tab = '';
	public function __construct( $base, $active_tab = '' ) {
		$this->base = sanitize_title( $base );
		$tab              = filter_input( INPUT_GET, 'tab' );
		$this->active_tab = empty( $tab ) ? $active_tab : $tab;
	}
	public function get_base() {
		return $this->base;
	}
	public function add_tab( WPSEO_Option_Tab $tab ) {
		$this->tabs[] = $tab;
		return $this;
	}
	public function get_active_tab() {
		if ( empty( $this->active_tab ) ) {
			return null;
		}
		$active_tabs = array_filter( $this->tabs, array( $this, 'is_active_tab' ) );
		if ( ! empty( $active_tabs ) ) {
			$active_tabs = array_values( $active_tabs );
			if ( count( $active_tabs ) === 1 ) {
				return $active_tabs[0];
			}
		}
		return null;
	}
	public function is_active_tab( WPSEO_Option_Tab $tab ) {
		return ( $tab->get_name() === $this->active_tab );
	}
	public function get_tabs() {
		return $this->tabs;
	}
	public function display( Yoast_Form $yform ) {
		$formatter = new WPSEO_Option_Tabs_Formatter();
		$formatter->run( $this, $yform );
	}
}
