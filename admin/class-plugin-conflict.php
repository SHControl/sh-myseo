<?php

class WPSEO_Plugin_Conflict extends Yoast_Plugin_Conflict {
	protected $plugins = array(
		'open_graph'   => array(
			'2-click-socialmedia-buttons/2-click-socialmedia-buttons.php',
			'add-link-to-facebook/add-link-to-facebook.php',         // Add Link to Facebook.
			'add-meta-tags/add-meta-tags.php',                       // Add Meta Tags.
			'easy-facebook-share-thumbnails/esft.php',               // Easy Facebook Share Thumbnail.
			'facebook/facebook.php',                                 // Facebook (official plugin).
			'facebook-awd/AWD_facebook.php',                         // Facebook AWD All in one.
			'facebook-featured-image-and-open-graph-meta-tags/fb-featured-image.php',
			'facebook-meta-tags/facebook-metatags.php',              // Facebook Meta Tags.
			'wonderm00ns-simple-facebook-open-graph-tags/wonderm00n-open-graph.php',
			'facebook-revised-open-graph-meta-tag/index.php',        // Facebook Revised Open Graph Meta Tag.
			'facebook-thumb-fixer/_facebook-thumb-fixer.php',        // Facebook Thumb Fixer.
			'facebook-and-digg-thumbnail-generator/facebook-and-digg-thumbnail-generator.php',
			'network-publisher/networkpub.php',                      // Network Publisher.
			'nextgen-facebook/nextgen-facebook.php',                 // NextGEN Facebook OG.
			'opengraph/opengraph.php',                               // Open Graph.
			'open-graph-protocol-framework/open-graph-protocol-framework.php',
			'seo-facebook-comments/seofacebook.php',                 // SEO Facebook Comments.
			'sexybookmarks/sexy-bookmarks.php',                      // Shareaholic.
			'shareaholic/sexy-bookmarks.php',                        // Shareaholic.
			'sharepress/sharepress.php',                             // SharePress.
			'simple-facebook-connect/sfc.php',                       // Simple Facebook Connect.
			'social-discussions/social-discussions.php',             // Social Discussions.
			'social-sharing-toolkit/social_sharing_toolkit.php',     // Social Sharing Toolkit.
			'socialize/socialize.php',                               // Socialize.
			'only-tweet-like-share-and-google-1/tweet-like-plusone.php',
			'wordbooker/wordbooker.php',                             // Wordbooker.
			'wpsso/wpsso.php',                                       // WordPress Social Sharing Optimization.
			'wp-caregiver/wp-caregiver.php',                         // WP Caregiver.
			'wp-facebook-like-send-open-graph-meta/wp-facebook-like-send-open-graph-meta.php',
			'wp-facebook-open-graph-protocol/wp-facebook-ogp.php',   // WP Facebook Open Graph protocol.
			'wp-ogp/wp-ogp.php',                                     // WP-OGP.
			'zoltonorg-social-plugin/zosp.php',                      // Zolton.org Social Plugin.
		),
		'xml_sitemaps' => array(
			'google-sitemap-plugin/google-sitemap-plugin.php',
			'xml-sitemaps/xml-sitemaps.php',
			'bwp-google-xml-sitemaps/bwp-simple-gxs.php',
			'google-sitemap-generator/sitemap.php',
			'xml-sitemap-feed/xml-sitemap.php',
			'google-monthly-xml-sitemap/monthly-xml-sitemap.php',
			'simple-google-sitemap-xml/simple-google-sitemap-xml.php',
			'another-simple-xml-sitemap/another-simple-xml-sitemap.php',
			'xml-maps/google-sitemap.php',
			'google-xml-sitemap-generator-by-anton-dachauer/adachauer-google-xml-sitemap.php',
			'wp-xml-sitemap/wp-xml-sitemap.php',
			'sitemap-generator-for-webmasters/sitemap.php',
			'xml-sitemap-xml-sitemapcouk/xmls.php',
			'sewn-in-xml-sitemap/sewn-xml-sitemap.php',
			'rps-sitemap-generator/rps-sitemap-generator.php',
		),
		'cloaking' => array(
			'rs-head-cleaner/rs-head-cleaner.php',
			'rs-head-cleaner-lite/rs-head-cleaner-lite.php',
		),
		'seo' => array(
			'all-in-one-seo-pack/all_in_one_seo_pack.php',           // All in One SEO Pack.
			'seo-ultimate/seo-ultimate.php',                         // SEO Ultimate.
		),
	);
	public static function get_instance( $class_name = __CLASS__ ) {
		return parent::get_instance( $class_name );
	}
	public static function hook_check_for_plugin_conflicts( $plugin = false ) {
		$instance = self::get_instance();
		if ( $plugin && is_string( $plugin ) ) {
			$instance->add_active_plugin( $instance->find_plugin_category( $plugin ), $plugin );
		}
		$plugin_sections = array();
		if ( WPSEO_Options::get( 'opengraph' ) ) {
			$plugin_sections['open_graph'] = __( 'Both %1$s and %2$s create OpenGraph output, which might make Facebook, Twitter, LinkedIn and other social networks use the wrong texts and images when your pages are being shared.', 'wordpress-seo' )
				. '<br/><br/>'
				. '<a class="button" href="' . admin_url( 'admin.php?page=wpseo_social#top#facebook' ) . '">'
				. sprintf( __( 'Configure %1$s\'s OpenGraph settings', 'wordpress-seo' ), 'MySEO' )
				. '</a>';
		}
		if ( WPSEO_Options::get( 'enable_xml_sitemap' ) ) {
			$plugin_sections['xml_sitemaps'] = __( 'Both %1$s and %2$s can create XML sitemaps. Having two XML sitemaps is not beneficial for search engines and might slow down your site.', 'wordpress-seo' )
				. '<br/><br/>'
				. '<a class="button" href="' . admin_url( 'admin.php?page=wpseo_dashboard#top#features' ) . '">'
				. sprintf( __( 'Toggle %1$s\'s XML Sitemap', 'wordpress-seo' ), 'MySEO' )
				. '</a>';
		}
		$plugin_sections['cloaking'] = __( 'The plugin %2$s changes your site\'s output and in doing that differentiates between search engines and normal users, a process that\'s called cloaking. We highly recommend that you disable it.', 'wordpress-seo' );
		$plugin_sections['seo'] = __( 'Both %1$s and %2$s manage the SEO of your site. Running two SEO plugins at the same time is detrimental.', 'wordpress-seo' );
		$instance->check_plugin_conflicts( $plugin_sections );
	}
}
