<?php

interface WPSEO_Capability_Manager {
	public function register( $capability, array $roles, $overwrite = false );
	public function add();
	public function remove();
	public function get_capabilities();
}
