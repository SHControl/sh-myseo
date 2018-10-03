<?php

class WPSEO_Suggested_Plugins implements WPSEO_WordPress_Integration {
	protected $availability_checker;
	protected $notification_center;
	public function __construct( WPSEO_Plugin_Availability $availability_checker, Yoast_Notification_Center $notification_center ) {
		$this->availability_checker = $availability_checker;
		$this->notification_center  = $notification_center;
	}
	public function register_hooks() {
		add_action( 'admin_init', array( $this->availability_checker, 'register' ) );
		add_action( 'admin_init', array( $this, 'add_notifications' ) );
	}
	public function add_notifications() {
		$checker = $this->availability_checker;
		$plugins = $checker->get_plugins_with_dependencies();
		foreach ( $plugins as $plugin_name => $plugin ) {
			if ( ! $checker->dependencies_are_satisfied( $plugin ) ) {
				continue;
			}
			$dependency_names = $checker->get_dependency_names( $plugin );
			$notification     = $this->get_yoast_seo_suggested_plugins_notification( $plugin_name, $plugin, $dependency_names[0] );
			if ( ! WPSEO_Utils::is_yoast_seo_premium() && ( ! $checker->is_installed( $plugin ) || ! $checker->is_active( $plugin['slug'] ) ) ) {
				$this->notification_center->add_notification( $notification );
				continue;
			}
			$this->notification_center->remove_notification( $notification );
		}
	}
	protected function get_yoast_seo_suggested_plugins_notification( $name, $plugin, $dependency_name ) {
		$message = $this->create_install_suggested_plugin_message( $plugin, $dependency_name );
		if ( $this->availability_checker->is_installed( $plugin ) && ! $this->availability_checker->is_active( $plugin['slug'] ) ) {
			$message = $this->create_activate_suggested_plugin_message( $plugin, $dependency_name );
		}
		return new Yoast_Notification(
			$message,
			array(
				'id'           => 'wpseo-suggested-plugin-' . $name,
				'type'         => Yoast_Notification::WARNING,
				'capabilities' => array( 'install_plugins' ),
			)
		);
	}
	protected function create_install_suggested_plugin_message( $suggested_plugin, $third_party_plugin ) {
		$message      = __( '%1$s and %2$s can work together a lot better by adding a helper plugin. Please install %3$s to make your life better. %4$s.', 'wordpress-seo' );
		$install_link = WPSEO_Admin_Utils::get_install_link( $suggested_plugin );

		return sprintf(
			$message,
			'MySEO',
			$third_party_plugin,
			$install_link,
			$this->create_more_information_link( $suggested_plugin['url'], $suggested_plugin['title'] )
		);
	}

	protected function create_more_information_link( $url, $name ) {
		return sprintf(
			'<a href="%s" aria-label="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			$url,
			sprintf( __( 'More information about %1$s', 'wordpress-seo' ), $name ),
			__( 'More information', 'wordpress-seo' )
		);
	}

	protected function create_activate_suggested_plugin_message( $suggested_plugin, $third_party_plugin ) {
		$message        = __( '%1$s and %2$s can work together a lot better by adding a helper plugin. Please activate %3$s to make your life better.', 'wordpress-seo' );
		$activation_url = WPSEO_Admin_Utils::get_activation_url( $suggested_plugin['slug'] );

		return sprintf(
			$message,
			'MySEO',
			$third_party_plugin,
			sprintf( '<a href="%s">%s</a>', $activation_url, $suggested_plugin['title'] )
		);
	}
}
