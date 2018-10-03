<?php

class WPSEO_Premium_Popup {
	private $identifier = '';
	private $heading_level = '';
	private $title = '';
	private $content = '';
	private $url = '';
	public function __construct( $identifier, $heading_level, $title, $content, $url ) {
		$this->identifier    = $identifier;
		$this->heading_level = $heading_level;
		$this->title         = $title;
		$this->content       = $content;
		$this->url           = $url;
	}
	public function get_premium_message( $popup = true ) {
		if ( defined( 'WPSEO_PREMIUM_FILE' ) ) {
			return '';
		}
		$assets_uri = trailingslashit( plugin_dir_url( WPSEO_FILE ) );
		$cta_text = sprintf( __( 'Get %s now!', 'wordpress-seo' ), 'MySEO Premium' );
		$classes  = '';
		if ( $popup ) {
			$classes = ' hidden';
		}
		$micro_copy = __( '1 year free updates and upgrades included!', 'wordpress-seo' );
		$popup = <<<EO_POPUP
<div id="wpseo-{$this->identifier}-popup" class="wpseo-premium-popup wp-clearfix$classes">
	<img class="alignright wpseo-premium-popup-icon" src="{$assets_uri}images/Yoast_SEO_Icon.svg" width="150" height="150" alt="SHControl MySEO"/>
	<{$this->heading_level} id="wpseo-contact-support-popup-title" class="wpseo-premium-popup-title">{$this->title}</{$this->heading_level}>
	{$this->content}
	<a id="wpseo-{$this->identifier}-popup-button" class="button button-primary" href="{$this->url}" target="_blank" rel="noreferrer noopener">{$cta_text}</a><br/>
	<small>{$micro_copy}</small>
</div>
EO_POPUP;
		return $popup;
	}
}
