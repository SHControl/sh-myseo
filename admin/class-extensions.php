<?php

class WPSEO_Extensions {
	protected $extensions = array(
		'SHControl MySEO Premium' => array(
			'slug'       => 'yoast-seo-premium',
			'identifier' => 'wordpress-seo-premium',
			'classname'  => 'WPSEO_Premium',
		),
		'News SEO' => array(
			'slug'       => 'news-seo',
			'identifier' => 'wpseo-news',
			'classname'  => 'WPSEO_News',
		),
		'WooCommerce SEO' => array(
			'slug'       => 'woocommerce-yoast-seo',
			'identifier' => 'wpseo-woocommerce',
			'classname'  => 'Yoast_WooCommerce_SEO',
		),
		'Video SEO' => array(
			'slug'       => 'video-seo-for-wordpress',
			'identifier' => 'wpseo-video',
			'classname'  => 'WPSEO_Video_Sitemap',
		),
		'Local SEO' => array(
			'slug'       => 'local-seo-for-wordpress',
			'identifier' => 'wpseo-local',
			'classname'  => 'WPSEO_Local_Core',
		),
		'Local SEO for WooCommerce' => array(
			'slug'       => 'local-seo-for-woocommerce',
			'identifier' => 'wpseo-local-woocommerce',
			'classname'  => 'WPSEO_Local_WooCommerce',
		),
	);

	public function get() {
		return array_keys( $this->extensions );
	}

	public function is_valid( $extension ) {
		$extensions = new WPSEO_Extension_Manager();
		return $extensions->is_activated( $this->extensions[ $extension ]['identifier'] );
	}

	public function invalidate( $extension ) {
		delete_option( $this->get_option_name( $extension ) );
		delete_site_option( $this->get_option_name( $extension ) );
	}

	public function is_installed( $extension ) {
		return class_exists( $this->extensions[ $extension ]['classname'] );
	}

	protected function get_option_name( $extension ) {
		return sanitize_title_with_dashes( $this->extensions[ $extension ]['slug'] . '_', null, 'save' ) . 'license';
	}
}
