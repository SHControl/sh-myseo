<?php

class WPSEO_Social_Admin extends WPSEO_Metabox {
	public function __construct() {
		self::translate_meta_boxes();
		add_filter( 'wpseo_save_metaboxes', array( $this, 'save_meta_boxes' ), 10, 1 );
		add_action( 'wpseo_save_compare_data', array( $this, 'og_data_compare' ), 10, 1 );
	}
	public static function translate_meta_boxes() {
		$title_text = __( 'If you don\'t want to use the post title for sharing the post on %s but instead want another title there, write it here.', 'wordpress-seo' );
		$description_text = __( 'If you don\'t want to use the meta description for sharing the post on %s but want another description there, write it here.', 'wordpress-seo' );
		$image_text = __( 'If you want to override the image used on %s for this post, upload / choose an image or add the URL here.', 'wordpress-seo' );
		$image_size_text = __( 'The recommended image size for %1$s is %2$s pixels.', 'wordpress-seo' );
		$social_networks = array(
			'opengraph' => __( 'Facebook', 'wordpress-seo' ),
			'twitter'   => __( 'Twitter', 'wordpress-seo' ),
		);
		$recommended_image_sizes = array(
			'opengraph' => sprintf( __( '%1$s by %2$s', 'wordpress-seo' ), '1200', '630' ),
			'twitter'   => sprintf( __( '%1$s by %2$s', 'wordpress-seo' ), '1024', '512' ),
		);
		foreach ( $social_networks as $network => $label ) {
			if ( true === WPSEO_Options::get( $network, false ) ) {
				self::$meta_fields['social'][ $network . '-title' ]['title']       = sprintf( __( '%s Title', 'wordpress-seo' ), $label );
				self::$meta_fields['social'][ $network . '-title' ]['description'] = sprintf( $title_text, $label );
				self::$meta_fields['social'][ $network . '-description' ]['title']       = sprintf( __( '%s Description', 'wordpress-seo' ), $label );
				self::$meta_fields['social'][ $network . '-description' ]['description'] = sprintf( $description_text, $label );
				self::$meta_fields['social'][ $network . '-image' ]['title']       = sprintf( __( '%s Image', 'wordpress-seo' ), $label );
				self::$meta_fields['social'][ $network . '-image' ]['description'] = sprintf( $image_text, $label ) . ' ' . sprintf( $image_size_text, $label, $recommended_image_sizes[ $network ] );
			}
		}
	}
	public function get_meta_section() {
		$tabs               = array();
		$social_meta_fields = $this->get_meta_field_defs( 'social' );
		$single             = true;
		$opengraph = WPSEO_Options::get( 'opengraph' );
		$twitter   = WPSEO_Options::get( 'twitter' );
		if ( $opengraph === true && $twitter === true ) {
			$single = null;
		}
		if ( $opengraph === true ) {
			$tabs[] = new WPSEO_Metabox_Form_Tab(
				'facebook',
				$this->get_social_tab_content( 'opengraph', $social_meta_fields ),
				'<span class="screen-reader-text">' . __( 'Facebook / Open Graph metadata', 'wordpress-seo' ) . '</span><span class="dashicons dashicons-facebook-alt"></span>',
				array(
					'link_aria_label' => __( 'Facebook / Open Graph metadata', 'wordpress-seo' ),
					'link_class'      => 'yoast-tooltip yoast-tooltip-se',
					'single'          => $single,
				)
			);
		}
		if ( $twitter === true ) {
			$tabs[] = new WPSEO_Metabox_Form_Tab(
				'twitter',
				$this->get_social_tab_content( 'twitter', $social_meta_fields ),
				'<span class="screen-reader-text">' . __( 'Twitter metadata', 'wordpress-seo' ) . '</span><span class="dashicons dashicons-twitter"></span>',
				array(
					'link_aria_label' => __( 'Twitter metadata', 'wordpress-seo' ),
					'link_class'      => 'yoast-tooltip yoast-tooltip-se',
					'single'          => $single,
				)
			);
		}
		return new WPSEO_Metabox_Tab_Section(
			'social',
			'<span class="screen-reader-text">' . __( 'Social', 'wordpress-seo' ) . '</span><span class="dashicons dashicons-share"></span>',
			$tabs,
			array(
				'link_aria_label' => __( 'Social', 'wordpress-seo' ),
				'link_class'      => 'yoast-tooltip yoast-tooltip-e',
			)
		);
	}
	private function get_social_tab_content( $medium, $meta_field_defs ) {
		$field_names = array(
			$medium . '-title',
			$medium . '-description',
			$medium . '-image',
		);
		$tab_content = $this->get_premium_notice( $medium );
		foreach ( $field_names as $field_name ) {
			$tab_content .= $this->do_meta_box( $meta_field_defs[ $field_name ], $field_name );
		}
		return $tab_content;
	}
	public function get_premium_notice( $network ) {
		$features = new WPSEO_Features();
		if ( $features->is_premium() ) {
			return '';
		}
		$network_name = __( 'Facebook', 'wordpress-seo' );
		if ( 'twitter' === $network ) {
			$network_name = __( 'Twitter', 'wordpress-seo' );
		}
		return sprintf(
			'<div class="notice inline yoast-notice yoast-notice-go-premium">
				<p>%1$s</p>
				<p><a href="%2$s" target="_blank">%3$s</a></p>
			</div>',
			sprintf(
				esc_html__( 'Do you want to preview what it will look like if people share this post on %1$s? You can, with %2$s.', 'wordpress-seo' ),
				esc_html( $network_name ),
				'<strong>MySEO Premium</strong>'
			),
			esc_url( WPSEO_Shortlinker::get( '//shct.me/myseo-home' ) ),
			sprintf(
				esc_html__( 'Find out why you should upgrade to %s', 'wordpress-seo' ),
				'MySEO Premium'
			)
		);
	}
	public function save_meta_boxes( $field_defs ) {
		return array_merge( $field_defs, $this->get_meta_field_defs( 'social' ) );
	}
	public function og_data_compare( $post ) {
		if (! empty( $_POST ) && ! empty( $post->ID ) && $post->post_status === 'publish' &&
			isset( $_POST['original_post_status'] ) && $_POST['original_post_status'] === 'publish'
		) {
			$fields_to_compare = array(
				'opengraph-title',
				'opengraph-description',
				'opengraph-image',
			);
			$reset_facebook_cache = false;
			foreach ( $fields_to_compare as $field_to_compare ) {
				$old_value = self::get_value( $field_to_compare, $post->ID );
				$new_value = self::get_post_value( self::$form_prefix . $field_to_compare );

				if ( $old_value !== $new_value ) {
					$reset_facebook_cache = true;
					break;
				}
			}
			unset( $field_to_compare, $old_value, $new_value );
			if ( $reset_facebook_cache ) {
				wp_remote_get(
					'https://graph.facebook.com/?id=' . get_permalink( $post->ID ) . '&scrape=true&method=post'
				);
			}
		}
	}
}
