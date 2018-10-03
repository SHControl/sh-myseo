<?php

class Yoast_Modal {
	private static $config = array();
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_footer', array( $this, 'print_localized_config' ) );
	}

	public function enqueue_assets() {
		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$asset_manager->enqueue_script( 'yoast-modal' );
	}

	public function print_localized_config() {
		$config = self::$config;
		wp_localize_script( WPSEO_Admin_Asset_Manager::PREFIX . 'yoast-modal', 'yoastModalConfig', $config );
	}

	public static function add( $args ) {
		$defaults       = self::get_defaults();
		$single         = array_replace_recursive( $defaults, $args );
		self::$config[] = $single;
	}

	public function get_config() {
		return self::$config;
	}

	public static function get_defaults() {
		$config = array(
			'mountHook'      => '',
			'appElement'     => '#wpwrap',
			'openButtonIcon' => '',
			'intl'           => array(
				'locale'          => WPSEO_Utils::get_user_locale(),
				'open'            => __( 'Open', 'wordpress-seo' ),
				'modalAriaLabel'  => null,
				'heading'         => null,
				'closeIconButton' => __( 'Close', 'wordpress-seo' ),
				'closeButton'     => null,
			),
			'classes'        => array(
				'openButton'      => '',
				'closeIconButton' => '',
				'closeButton'     => '',
			),
			'content'        => null,
		);
		return $config;
	}
}
