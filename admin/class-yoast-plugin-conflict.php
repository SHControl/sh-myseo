<?php

class Yoast_Plugin_Conflict {
	protected $plugins = array();
	protected $all_active_plugins = array();
	protected $active_plugins = array();
	protected static $instance;
	public static function get_instance( $class_name = '' ) {
		if ( is_null( self::$instance ) ) {
			if ( ! is_string( $class_name ) || $class_name === '' ) {
				$class_name = __CLASS__;
			}
			self::$instance = new $class_name();
		}
		return self::$instance;
	}
	protected function __construct() {
		$this->all_active_plugins = get_option( 'active_plugins' );
		if ( filter_input( INPUT_GET, 'action' ) === 'deactivate' ) {
			$this->remove_deactivated_plugin();
		}
		$this->search_active_plugins();
	}
	public function check_for_conflicts( $plugin_section ) {
		static $sections_checked;
		if ( $sections_checked === null ) {
			$sections_checked = array();
		}
		if ( ! in_array( $plugin_section, $sections_checked, true ) ) {
			$sections_checked[] = $plugin_section;
			$has_conflicts      = ( ! empty( $this->active_plugins[ $plugin_section ] ) );
			return $has_conflicts;
		}
		return false;
	}
	public function get_conflicting_plugins_as_string( $plugin_section ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = $this->active_plugins[ $plugin_section ];
		$plugin_names = array();
		foreach ( $plugins as $plugin ) {
			$name = WPSEO_Utils::get_plugin_name( $plugin );
			if ( ! empty( $name ) ) {
				$plugin_names[] = '<em>' . $name . '</em>';
			}
		}
		unset( $plugins, $plugin );
		if ( ! empty( $plugin_names ) ) {
			return implode( ' &amp; ', $plugin_names );
		}
	}
	public function check_plugin_conflicts( $plugin_sections ) {
		foreach ( $plugin_sections as $plugin_section => $readable_plugin_section ) {
			if ( $this->check_for_conflicts( $plugin_section ) ) {
				$this->set_error( $plugin_section, $readable_plugin_section );
			}
		}
		$sections = array_keys( $plugin_sections );
		$all_plugin_sections = array_keys( $this->plugins );
		$inactive_sections = array_diff( $all_plugin_sections, $sections );
		if ( ! empty( $inactive_sections ) ) {
			foreach ( $inactive_sections as $section ) {
				array_walk( $this->plugins[ $section ], array( $this, 'clear_error' ) );
			}
		}
		foreach ( $sections as $section ) {
			$inactive_plugins = $this->plugins[ $section ];
			if ( isset( $this->active_plugins[ $section ] ) ) {
				$inactive_plugins = array_diff( $this->plugins[ $section ], $this->active_plugins[ $section ] );
			}
			array_walk( $inactive_plugins, array( $this, 'clear_error' ) );
		}
	}
	protected function set_error( $plugin_section, $readable_plugin_section ) {
		$notification_center = Yoast_Notification_Center::get();
		foreach ( $this->active_plugins[ $plugin_section ] as $plugin_file ) {
			$plugin_name = WPSEO_Utils::get_plugin_name( $plugin_file );
			$error_message = '';
			$error_message .= '<p>' . sprintf( __( 'The %1$s plugin might cause issues when used in conjunction with %2$s.', 'wordpress-seo' ), '<em>' . $plugin_name . '</em>', 'MySEO' ) . '</p>';
			$error_message .= '<p>' . sprintf( $readable_plugin_section, 'MySEO', $plugin_name ) . '</p>';
			$error_message .= '<a class="button button-primary" href="' . wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $plugin_file . '&amp;plugin_status=all', 'deactivate-plugin_' . $plugin_file ) . '">' . sprintf( __( 'Deactivate %s', 'wordpress-seo' ), WPSEO_Utils::get_plugin_name( $plugin_file ) ) . '</a> ';
			$identifier = $this->get_notification_identifier( $plugin_file );
			$notification_center->add_notification(
				new Yoast_Notification(
					$error_message,
					array(
						'type' => Yoast_Notification::ERROR,
						'id'   => 'wpseo-conflict-' . $identifier,
					)
				)
			);
		}
	}
	public function clear_error( $plugin_file ) {
		$identifier = $this->get_notification_identifier( $plugin_file );
		$notification_center = Yoast_Notification_Center::get();
		$notification_center->remove_notification_by_id( 'wpseo-conflict-' . $identifier );
	}
	protected function search_active_plugins() {
		foreach ( $this->plugins as $plugin_section => $plugins ) {
			$this->check_plugins_active( $plugins, $plugin_section );
		}
	}
	protected function check_plugins_active( $plugins, $plugin_section ) {
		foreach ( $plugins as $plugin ) {
			if ( $this->check_plugin_is_active( $plugin ) ) {
				$this->add_active_plugin( $plugin_section, $plugin );
			}
		}
	}
	protected function check_plugin_is_active( $plugin ) {
		return in_array( $plugin, $this->all_active_plugins, true );
	}
	protected function add_active_plugin( $plugin_section, $plugin ) {
		if ( ! array_key_exists( $plugin_section, $this->active_plugins ) ) {
			$this->active_plugins[ $plugin_section ] = array();
		}
		if ( ! in_array( $plugin, $this->active_plugins[ $plugin_section ], true ) ) {
			$this->active_plugins[ $plugin_section ][] = $plugin;
		}
	}
	protected function find_plugin_category( $plugin ) {
		foreach ( $this->plugins as $plugin_section => $plugins ) {
			if ( in_array( $plugin, $plugins, true ) ) {
				return $plugin_section;
			}
		}
	}
	private function remove_deactivated_plugin() {
		$deactivated_plugin = filter_input( INPUT_GET, 'plugin' );
		$key_to_remove      = array_search( $deactivated_plugin, $this->all_active_plugins, true );
		if ( $key_to_remove !== false ) {
			unset( $this->all_active_plugins[ $key_to_remove ] );
		}
	}
	private function get_notification_identifier( $plugin_file ) {
		return md5( $plugin_file );
	}
}
