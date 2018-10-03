<?php

class WPSEO_Multiple_Keywords_Modal {
	public function get_translations() {
		return array(
			'title'                    => __( 'Would you like to add another keyword?', 'wordpress-seo' ),
			'intro'                    => sprintf(
				__( 'Great news: you can, with %1$s!', 'wordpress-seo' ),
				'{{link}}MySEO Premium{{/link}}'
			),
			'link'                     => WPSEO_Shortlinker::get( '//shct.me/pe-premium-page' ),
			'other'                    => sprintf(
				__( 'Other benefits of %s for you:', 'wordpress-seo' ),
				'MySEO Premium'
			),
			'buylink'                  => WPSEO_Shortlinker::get( '//shct.me/add-keywords-popup' ),
			'buy'                      => sprintf(
				__( 'Get %s now!', 'wordpress-seo' ),
				'MySEO Premium'
			),
			'small'                    => __( '1 year free updates and upgrades included!', 'wordpress-seo' ),
			'a11yNotice.opensInNewTab' => __( '(Opens in a new browser tab)', 'wordpress-seo' ),
		);
	}
	public function get_translations_for_js() {
		$translations = $this->get_translations();
		return array(
			'locale' => WPSEO_Utils::get_user_locale(),
			'intl'   => $translations,
		);
	}
	public function enqueue_translations() {
		wp_localize_script( WPSEO_Admin_Asset_Manager::PREFIX . 'admin-global-script', 'yoastMultipleKeywordsModalL10n', $this->get_translations_for_js() );
	}
}
