<?php

class WPSEO_Admin_Utils {
	public static function get_install_url( $slug ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return '';
		}
		return wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=' . dirname( $slug ) ),
			'install-plugin_' . dirname( $slug )
		);
	}

	public static function get_activation_url( $slug ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return '';
		}
		return wp_nonce_url(
			self_admin_url( 'plugins.php?action=activate&plugin_status=all&paged=1&s&plugin=' . $slug ),
			'activate-plugin_' . $slug
		);
	}

	public static function get_install_link( $plugin ) {
		$install_url = self::get_install_url( $plugin['slug'] );
		if ( $install_url === '' || ( isset( $plugin['premium'] ) && $plugin['premium'] === true ) ) {
			return $plugin['title'];
		}
		return sprintf(
			'<a href="%s">%s</a>',
			$install_url,
			$plugin['title']
		);
	}

	public static function is_supported_php_version_installed() {
		return true;
	}
}
