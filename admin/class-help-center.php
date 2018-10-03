<?php

class WPSEO_Help_Center {
	private $tabs;
	private $identifier = 'yoast-help-center-container';
	protected $help_center_items = array();
	protected $premium_support;
	public function __construct( $unused, $option_tabs, $premium_support = false ) {
		$this->premium_support = $premium_support;
		$tabs = new WPSEO_Option_Tabs( '' );
		if ( $option_tabs instanceof WPSEO_Option_Tabs ) {
			$tabs = $option_tabs;
		}
		if ( $option_tabs instanceof WPSEO_Option_Tab ) {
			$tabs = new WPSEO_Option_Tabs( '', $option_tabs->get_name() );
			$tabs->add_tab( $option_tabs );
		}
		$this->tabs = $tabs;
	}
	public function localize_data() {
		$this->add_contact_support_item();
		$this->enqueue_localized_data( $this->format_data( $this->tabs->get_tabs() ) );
	}
	protected function format_data( array $tabs ) {
		$formatted_data = array( 'tabs' => array() );
		foreach ( $tabs as $tab ) {
			$formatted_data['tabs'][ $tab->get_name() ] = array(
				'label'    => $tab->get_label(),
				'videoUrl' => $tab->get_video_url(),
				'id'       => $tab->get_name(),
			);
		}
		$active_tab = $this->tabs->get_active_tab();
		$active_tab = ( null === $active_tab ) ? $tabs[0] : $active_tab;
		$formatted_data['mountId']    = $this->identifier;
		$formatted_data['initialTab'] = $active_tab->get_name();
		$is_premium = WPSEO_Utils::is_yoast_seo_premium();
		$formatted_data['isPremium']     = $is_premium;
		$formatted_data['pluginVersion'] = WPSEO_VERSION;
		$formatted_data['shouldDisplayContactForm'] = $this->premium_support;
		$formatted_data['translations'] = self::get_translated_texts();
		$formatted_data['videoDescriptions'] = array();
		if ( $is_premium === false ) {
			$formatted_data['videoDescriptions'][] = array(
				'title'       => __( 'Need some help?', 'wordpress-seo' ),
				'description' => __( 'Go Premium and our experts will be there for you to answer any questions you might have about the setup and use of the plugin.', 'wordpress-seo' ),
				'link'        => WPSEO_Shortlinker::get( '//shct.me/premium' ),
				'linkText'    => __( 'Get MySEO Premium now »', 'wordpress-seo' ),
			);
			$formatted_data['videoDescriptions'][] = array(
				'title'       => __( 'Want to be a Yoast SEO Expert?', 'wordpress-seo' ),
				'description' => __( 'Follow our Yoast SEO for WordPress training and become a certified MySEO Expert!', 'wordpress-seo' ),
				'link'        => WPSEO_Shortlinker::get( '//shct.me/wordpress-training' ),
				'linkText'    => __( 'Enroll in the MySEO for WordPress training »', 'wordpress-seo' ),
			);
		}
		$formatted_data['contactSupportParagraphs'] = array(
			array(
				'image'   => array(
					'src'    => esc_url( plugin_dir_url( WPSEO_FILE ) . 'images/support-team.svg' ),
					'width'  => 100,
					'height' => 100,
					'alt'    => '',
				),
				'content' => null,
			),
			array(
				'image'   => null,
				'content' => __( 'If you have a problem that you can\'t solve with our video tutorials or knowledge base, you can send a message to our support team. They can be reached 24/7.', 'wordpress-seo' ),
			),
			array(
				'image'   => null,
				'content' => __( 'Support requests you create here are sent directly into our support system, which is secured with 256 bit SSL, so communication is 100% secure.', 'wordpress-seo' ),
			),
		);
		$formatted_data['extraTabs'] = $this->get_extra_tabs();
		return $formatted_data;
	}
	protected function get_extra_tabs() {
		$help_center_items = apply_filters( 'wpseo_help_center_items', $this->help_center_items );
		return array_map( array( $this, 'format_helpcenter_tab' ), $help_center_items );
	}
	protected function format_helpcenter_tab( WPSEO_Help_Center_Item $item ) {
		return array(
			'identifier' => $item->get_identifier(),
			'label'      => $item->get_label(),
			'content'    => $item->get_content(),
		);
	}
	protected function enqueue_localized_data( $data ) {
		wp_localize_script( WPSEO_Admin_Asset_Manager::PREFIX . 'help-center', 'wpseoHelpCenterData', $data );
		$yoast_components_l10n = new WPSEO_Admin_Asset_Yoast_Components_L10n();
		$yoast_components_l10n->localize_script( WPSEO_Admin_Asset_Manager::PREFIX . 'help-center' );
	}
	public function mount() {
		echo '<div id="' . esc_attr( $this->identifier ) . '">' . esc_html__( 'Loading help center.', 'wordpress-seo' ) . '</div>';
	}
	private function add_contact_support_item() {
		$popup_title   = sprintf( __( 'Email support is a %s feature', 'wordpress-seo' ), 'MySEO Premium' );
		$popup_content = '<p class="yoast-measure">' . __( 'Go Premium and our experts will be there for you to answer any questions you might have about the set-up and use of the plug-in!', 'wordpress-seo' ) . '</p>';
		$popup_content .= '<p>' . sprintf( __( 'Other benefits of %1$s for you:', 'wordpress-seo' ), 'MySEO Premium' ) . '</p>';
		$popup_content .= '<ul class="wpseo-premium-advantages-list">';
		$popup_content .= '<li>' . sprintf( __( '%1$sNo more dead links%2$s: easy redirect manager', 'wordpress-seo' ), '', '') . '</li>';
		$popup_content .= '<li>' . __( 'Superfast internal links suggestions', 'wordpress-seo' ) . '</li>';
		$popup_content .= '<li>' . sprintf( __( '%1$sSocial media preview%2$s: Facebook &amp; Twitter', 'wordpress-seo' ), '', '') . '</li>';
		$popup_content .= '<li>' . __( '24/7 support', 'wordpress-seo' ) . '</li>';
		$popup_content .= '<li>' . __( 'No ads!', 'wordpress-seo' ) . '</li>';
		$popup_content .= '</ul>';
		$premium_popup                    = new WPSEO_Premium_Popup( 'contact-support', 'h2', $popup_title, $popup_content, WPSEO_Shortlinker::get( '//www.shcontrol.net/contact' ) );
		$contact_support_help_center_item = new WPSEO_Help_Center_Item(
			'contact-support',
			__( 'Get support', 'wordpress-seo' ),
			array( 'content' => $premium_popup->get_premium_message( false ) ),
			'dashicons-email-alt'
		);

		$this->help_center_items[] = $contact_support_help_center_item;
	}

	public static function get_translated_texts() {
		return array(
			'locale'                             => WPSEO_Utils::get_user_locale(),
			'videoTutorial'                      => __( 'Video tutorial', 'wordpress-seo' ),
			'knowledgeBase'                      => __( 'Knowledge base', 'wordpress-seo' ),
			'getSupport'                         => __( 'Get support', 'wordpress-seo' ),
			'algoliaSearcher.loadingPlaceholder' => __( 'Loading...', 'wordpress-seo' ),
			'algoliaSearcher.errorMessage'       => __( 'Something went wrong. Please try again later.', 'wordpress-seo' ),
			'searchBar.headingText'              => __( 'Search the Yoast Knowledge Base for answers to your questions:', 'wordpress-seo' ),
			'searchBar.placeholderText'          => __( 'Type here to search...', 'wordpress-seo' ),
			'searchBar.buttonText'               => __( 'Search', 'wordpress-seo' ),
			'searchResultDetail.openButton'      => __( 'View in KB', 'wordpress-seo' ),
			'searchResultDetail.openButtonLabel' => __( 'Open the knowledge base article in a new window or read it in the iframe below', 'wordpress-seo' ),
			'searchResultDetail.backButton'      => __( 'Go back', 'wordpress-seo' ),
			'searchResultDetail.backButtonLabel' => __( 'Go back to the search results', 'wordpress-seo' ),
			'searchResultDetail.iframeTitle'     => __( 'Knowledge base article', 'wordpress-seo' ),
			'searchResultDetail.searchResult'    => __( 'Search result', 'wordpress-seo' ),
			'searchResult.noResultsText'         => __( 'No results found.', 'wordpress-seo' ),
			'searchResult.foundResultsText'      => sprintf( __( 'Number of results found: %s', 'wordpress-seo' ), '{ resultsCount }' ),
			'searchResult.searchResultsHeading'  => __( 'Search results', 'wordpress-seo' ),
			'a11yNotice.opensInNewTab'           => __( '(Opens in a new browser tab)', 'wordpress-seo' ),
			'contactSupport.button'              => __( 'New support request', 'wordpress-seo' ),
			'helpCenter.buttonText'              => __( 'Need help?', 'wordpress-seo' ),
		);
	}

	public function output_help_center() {
		_deprecated_function( 'WPSEO_Help_Center::output_help_center', 'WPSEO 5.6.0', 'WPSEO_Help_Center::mount()' );
		$this->mount();
	}
}
