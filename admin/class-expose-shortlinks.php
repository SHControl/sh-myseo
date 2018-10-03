<?php

class WPSEO_Expose_Shortlinks implements WPSEO_WordPress_Integration {
	private $shortlinks = array(
		'shortlinks.focus_keyword_info' 							=> '//shct.me/focus-keyword',
		'shortlinks.snippet_preview_info' 							=> '//shct.me/snippet-preview',
		'shortlinks.cornerstone_content_info' 						=> '//shct.me/1i9',
		'shortlinks.upsell.sidebar.focus_keyword_synonyms_link' 	=> '//shct.me/textlink-synonyms-popup-sidebar',
		'shortlinks.upsell.sidebar.focus_keyword_synonyms_button' 	=> '//shct.me/keyword-synonyms-popup-sidebar',
		'shortlinks.upsell.sidebar.focus_keyword_additional_link' 	=> '//shct.me/textlink-keywords-popup-sidebar',
		'shortlinks.upsell.sidebar.focus_keyword_additional_button' => '//shct.me/add-keywords-popup-sidebar',
		'shortlinks.upsell.sidebar.additional_link' 				=> '//shct.me/textlink-keywords-sidebar',
		'shortlinks.upsell.sidebar.additional_button' 				=> '//shct.me/add-keywords-sidebar',
		'shortlinks.upsell.metabox.go_premium' 						=> '//shct.me/pe-premium-page',
		'shortlinks.upsell.metabox.focus_keyword_synonyms_link' 	=> '//shct.me/textlink-synonyms-popup-metabox',
		'shortlinks.upsell.metabox.focus_keyword_synonyms_button' 	=> '//shct.me/keyword-synonyms-popup',
		'shortlinks.upsell.metabox.focus_keyword_additional_link' 	=> '//shct.me/textlink-keywords-popup-metabox',
		'shortlinks.upsell.metabox.focus_keyword_additional_button' => '//shct.me/add-keywords-popup',
		'shortlinks.upsell.metabox.additional_link' 				=> '//shct.me/textlink-keywords-metabox',
		'shortlinks.upsell.metabox.additional_button' 				=> '//shct.me/add-keywords-metabox',
		'shortlinks.readability_analysis_info'                      => '//shct.me/readability-analysis',
	);
	public function register_hooks() {
		add_filter( 'wpseo_admin_l10n', array( $this, 'expose_shortlinks' ) );
	}
	public function expose_shortlinks( $input ) {
		foreach ( $this->shortlinks as $key => $shortlink ) {
			$input[ $key ] = WPSEO_Shortlinker::get( $shortlink );
		}
		return $input;
	}
}
