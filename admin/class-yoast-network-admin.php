<?php

class Yoast_Network_Admin implements WPSEO_WordPress_Integration, WPSEO_WordPress_AJAX_Integration {
	const UPDATE_OPTIONS_ACTION = 'yoast_handle_network_options';
	const RESTORE_SITE_ACTION = 'yoast_restore_site';
	public function get_site_choices( $include_empty = false, $show_title = false ) {
		$choices = array();
		if ( $include_empty ) {
			$choices['-'] = __( 'None', 'wordpress-seo' );
		}
		$sites = get_sites( array(
			'deleted'    => 0,
			'network_id' => get_current_network_id(),
		) );
		foreach ( $sites as $site ) {
			$site_name = $site->domain . $site->path;
			if ( $show_title ) {
				$site_name = $site->blogname . ' (' . $site->domain . $site->path . ')';
			}
			$choices[ $site->blog_id ] = $site->blog_id . ': ' . $site_name;
			$site_states = $this->get_site_states( $site );
			if ( ! empty( $site_states ) ) {
				$choices[ $site->blog_id ] .= ' [' . implode( ', ', $site_states ) . ']';
			}
		}
		return $choices;
	}
	public function get_site_states( $site ) {
		$available_states = array(
			'public'   => __( 'public', 'wordpress-seo' ),
			'archived' => __( 'archived', 'wordpress-seo' ),
			'mature'   => __( 'mature', 'wordpress-seo' ),
			'spam'     => __( 'spam', 'wordpress-seo' ),
			'deleted'  => __( 'deleted', 'wordpress-seo' ),
		);
		$site_states = array();
		foreach ( $available_states as $state_slug => $state_label ) {
			if ( $site->$state_slug === '1' ) {
				$site_states[ $state_slug ] = $state_label;
			}
		}
		return $site_states;
	}

	public function handle_update_options_request() {
		$option_group = filter_input( INPUT_POST, 'network_option_group', FILTER_SANITIZE_STRING );
		$this->verify_request( "{$option_group}-network-options" );
		$whitelist_options = Yoast_Network_Settings_API::get()->get_whitelist_options( $option_group );
		if ( empty( $whitelist_options ) ) {
			add_settings_error( $option_group, 'settings_updated', __( 'You are not allowed to modify unregistered network settings.', 'wordpress-seo' ), 'error' );
			$this->terminate_request();
			return;
		}
		foreach ( $whitelist_options as $option_name ) {
			$value = null;
			if ( isset( $_POST[ $option_name ] ) ) {
				$value = wp_unslash( $_POST[ $option_name ] );
			}
			WPSEO_Options::update_site_option( $option_name, $value );
		}
		$settings_errors = get_settings_errors();
		if ( empty( $settings_errors ) ) {
			add_settings_error( $option_group, 'settings_updated', __( 'Settings Updated.', 'wordpress-seo' ), 'updated' );
		}
		$this->terminate_request();
	}
	public function handle_restore_site_request() {
		$this->verify_request( 'wpseo-network-restore', 'restore_site_nonce' );
		$option_group = 'wpseo_ms';
		$site_id = ! empty( $_POST[ $option_group ]['site_id'] ) ? (int) $_POST[ $option_group ]['site_id'] : 0; 
		if ( ! $site_id ) {
			add_settings_error( $option_group, 'settings_updated', __( 'No site has been selected to restore.', 'wordpress-seo' ), 'error' );
			$this->terminate_request();
			return;
		}
		$site = get_site( $site_id );
		if ( ! $site ) {
			add_settings_error( $option_group, 'settings_updated', sprintf( __( 'Site with ID %d not found.', 'wordpress-seo' ), $site_id ), 'error' );
		} else {
			WPSEO_Options::reset_ms_blog( $site_id );
			add_settings_error( $option_group, 'settings_updated', sprintf( __( '%s restored to default SEO settings.', 'wordpress-seo' ), esc_html( $site->blogname ) ), 'updated' );
		}
		$this->terminate_request();
	}
	public function settings_fields( $option_group ) {
		?>
		<input type="hidden" name="network_option_group" value="<?php echo esc_attr( $option_group ); ?>" />
		<input type="hidden" name="action" value="<?php echo esc_attr( self::UPDATE_OPTIONS_ACTION ); ?>" />
		<?php
		wp_nonce_field( "$option_group-network-options" );
	}
	public function enqueue_assets() {
		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$asset_manager->enqueue_script( 'network-admin-script' );
		wp_localize_script( WPSEO_Admin_Asset_Manager::PREFIX . 'network-admin-script', 'wpseoNetworkAdminGlobalL10n', array(
			'success_prefix' => __( 'Success: %s', 'wordpress-seo' ),
			'error_prefix'   => __( 'Error: %s', 'wordpress-seo' ),
		) );
	}
	public function register_hooks() {
		if ( ! $this->meets_requirements() ) {
			return;
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_action_' . self::UPDATE_OPTIONS_ACTION, array( $this, 'handle_update_options_request' ) );
		add_action( 'admin_action_' . self::RESTORE_SITE_ACTION, array( $this, 'handle_restore_site_request' ) );
	}
	public function register_ajax_hooks() {
		add_action( 'wp_ajax_' . self::UPDATE_OPTIONS_ACTION, array( $this, 'handle_update_options_request' ) );
		add_action( 'wp_ajax_' . self::RESTORE_SITE_ACTION, array( $this, 'handle_restore_site_request' ) );
	}
	public function meets_requirements() {
		return is_multisite() && is_network_admin();
	}
	public function verify_request( $action, $query_arg = '_wpnonce' ) {
		$has_access = current_user_can( 'wpseo_manage_network_options' );
		if ( wp_doing_ajax() ) {
			check_ajax_referer( $action, $query_arg );
			if ( ! $has_access ) {
				wp_die( -1, 403 );
			}
			return;
		}
		check_admin_referer( $action, $query_arg );
		if ( ! $has_access ) {
			wp_die( __( 'You are not allowed to perform this action.', 'wordpress-seo' ) );
		}
	}
	public function terminate_request() {
		if ( wp_doing_ajax() ) {
			$settings_errors = get_settings_errors();
			if ( ! empty( $settings_errors ) && $settings_errors[0]['type'] === 'updated' ) {
				wp_send_json_success( $settings_errors, 200 );
			}
			wp_send_json_error( $settings_errors, 400 );
		}
		$this->persist_settings_errors();
		$this->redirect_back( array( 'settings-updated' => 'true' ) );
	}
	protected function persist_settings_errors() {
		set_transient( 'settings_errors', get_settings_errors(), 30 );
	}
	protected function redirect_back( $query_args = array() ) {
		$sendback = wp_get_referer();
		if ( ! empty( $query_args ) ) {
			$sendback = add_query_arg( $query_args, $sendback );
		}
		wp_safe_redirect( $sendback );
		exit;
	}
}
