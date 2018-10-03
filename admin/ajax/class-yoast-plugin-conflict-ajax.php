<?php

class Yoast_Plugin_Conflict_Ajax {
	private $option_name = 'wpseo_dismissed_conflicts';
	private $dismissed_conflicts = array();
	public function __construct() {
		add_action( 'wp_ajax_wpseo_dismiss_plugin_conflict', array( $this, 'dismiss_notice' ) );
	}
	public function dismiss_notice() {
		check_ajax_referer( 'dismiss-plugin-conflict' );
		$conflict_data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$this->dismissed_conflicts = $this->get_dismissed_conflicts( $conflict_data['section'] );
		$this->compare_plugins( $conflict_data['plugins'] );
		$this->save_dismissed_conflicts( $conflict_data['section'] );
		wp_die( 'true' );
	}

	private function get_dismissed_option() {
		return get_user_meta( get_current_user_id(), $this->option_name, true );
	}

	private function get_dismissed_conflicts( $plugin_section ) {
		$dismissed_conflicts = $this->get_dismissed_option();
		if ( is_array( $dismissed_conflicts ) && array_key_exists( $plugin_section, $dismissed_conflicts ) ) {
			return $dismissed_conflicts[ $plugin_section ];
		}
		return array();
	}

	private function save_dismissed_conflicts( $plugin_section ) {
		$dismissed_conflicts = $this->get_dismissed_option();
		$dismissed_conflicts[ $plugin_section ] = $this->dismissed_conflicts;
		update_user_meta( get_current_user_id(), $this->option_name, $dismissed_conflicts );
	}

	public function compare_plugins( array $posted_plugins ) {
		foreach ( $posted_plugins as $posted_plugin ) {
			$this->compare_plugin( $posted_plugin );
		}
	}

	private function compare_plugin( $posted_plugin ) {
		if ( ! in_array( $posted_plugin, $this->dismissed_conflicts, true ) ) {
			$this->dismissed_conflicts[] = $posted_plugin;
		}
	}
}
