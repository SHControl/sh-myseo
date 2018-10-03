<?php

final class WPSEO_Admin_Asset_Yoast_Components_L10n {
	public function localize_script( $script_handle ) {
		wp_localize_script( $script_handle, 'wpseoYoastJSL10n', array(
			'yoast-components' => $this->get_translations( 'yoast-components' ),
			'wordpress-seo'    => $this->get_translations( 'wordpress-seojs' ),
		) );
	}

	protected function get_translations( $component ) {
		$locale = WPSEO_Utils::get_user_locale();
		$file = plugin_dir_path( WPSEO_FILE ) . 'languages/' . $component . '-' . $locale . '.json';
		if ( file_exists( $file ) ) {
			$file = file_get_contents( $file );
			if ( is_string( $file ) && $file !== '' ) {
				return json_decode( $file, true );
			}
		}
		return null;
	}
}
