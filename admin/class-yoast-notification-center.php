<?php

class Yoast_Notification_Center {
	const STORAGE_KEY = 'yoast_notifications';
	private static $instance = null;
	private $notifications = array();
	private $new = array();
	private $resolved = 0;
	private $queued_transactions = array();
	private $notifications_retrieved = false;
	private function __construct() {
		add_action( 'init', array( $this, 'setup_current_notifications' ), 1 );
		add_action( 'all_admin_notices', array( $this, 'display_notifications' ) );
		add_action( 'wp_ajax_yoast_get_notifications', array( $this, 'ajax_get_notifications' ) );
		add_action( 'wpseo_deactivate', array( $this, 'deactivate_hook' ) );
		add_action( 'shutdown', array( $this, 'update_storage' ) );
	}
	
	public static function get() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public static function ajax_dismiss_notification() {
		$notification_center = self::get();
		$notification_id = filter_input( INPUT_POST, 'notification' );
		if ( empty( $notification_id ) ) {
			die( '-1' );
		}
		$notification = $notification_center->get_notification_by_id( $notification_id );
		if ( false === ( $notification instanceof Yoast_Notification ) ) {
			$notification = new Yoast_Notification( '', array(
				'id'            => $notification_id,
				'dismissal_key' => $notification_id,
			) );
		}
		if ( self::maybe_dismiss_notification( $notification ) ) {
			die( '1' );
		}
		die( '-1' );
	}
	
	public static function is_notification_dismissed( Yoast_Notification $notification, $user_id = null ) {
		$user_id       = ( ! is_null( $user_id ) ? $user_id : get_current_user_id() );
		$dismissal_key = $notification->get_dismissal_key();
		$current_value = get_user_option( $dismissal_key, $user_id );
		if ( ! empty( $current_value )
			&& metadata_exists( 'user', $user_id, $dismissal_key )
			&& update_user_option( $user_id, $dismissal_key, $current_value ) ) {
			delete_user_meta( $user_id, $dismissal_key );
		}
		return ! empty( $current_value );
	}
	
	public static function maybe_dismiss_notification( Yoast_Notification $notification, $meta_value = 'seen' ) {
		if ( ! $notification->is_persistent() ) {
			return false;
		}
		if ( self::is_notification_dismissed( $notification ) ) {
			return true;
		}
		$dismissal_key   = $notification->get_dismissal_key();
		$notification_id = $notification->get_id();
		$is_dismissing = ( $dismissal_key === self::get_user_input( 'notification' ) );
		if ( ! $is_dismissing ) {
			$is_dismissing = ( $notification_id === self::get_user_input( 'notification' ) );
		}
		if ( ! $is_dismissing ) {
			$is_dismissing = ( '1' === self::get_user_input( $dismissal_key ) );
		}
		if ( ! $is_dismissing ) {
			return false;
		}
		$user_nonce = self::get_user_input( 'nonce' );
		if ( false === wp_verify_nonce( $user_nonce, $notification_id ) ) {
			return false;
		}
		return self::dismiss_notification( $notification, $meta_value );
	}
	
	public static function dismiss_notification( Yoast_Notification $notification, $meta_value = 'seen' ) {
		return update_user_option( get_current_user_id(), $notification->get_dismissal_key(), $meta_value ) !== false;
	}
	
	public static function restore_notification( Yoast_Notification $notification ) {
		$user_id       = get_current_user_id();
		$dismissal_key = $notification->get_dismissal_key();
		$restored = delete_user_option( $user_id, $dismissal_key );
		if ( metadata_exists( 'user', $user_id, $dismissal_key ) ) {
			$restored = delete_user_meta( $user_id, $dismissal_key ) && $restored;
		}
		return $restored;
	}
	
	public function clear_dismissal( $notification ) {
		global $wpdb;
		if ( $notification instanceof Yoast_Notification ) {
			$dismissal_key = $notification->get_dismissal_key();
		}
		if ( is_string( $notification ) ) {
			$dismissal_key = $notification;
		}
		if ( empty( $dismissal_key ) ) {
			return false;
		}
		$deleted = delete_metadata( 'user', 0, $wpdb->get_blog_prefix() . $dismissal_key, '', true );
		$deleted = delete_metadata( 'user', 0, $dismissal_key, '', true ) || $deleted;
		return $deleted;
	}
	
	public function setup_current_notifications() {
		$this->retrieve_notifications_from_storage();
		foreach ( $this->queued_transactions as $transaction ) {
			list( $callback, $args ) = $transaction;
			call_user_func_array( $callback, $args );
		}
		$this->queued_transactions = array();
	}
	
	public function add_notification( Yoast_Notification $notification ) {

		$callback = array( $this, __METHOD__ );
		$args     = func_get_args();
		if ( $this->queue_transaction( $callback, $args ) ) {
			return;
		}
		if ( ! $notification->display_for_current_user() ) {
			return;
		}
		$notification_id = $notification->get_id();
		if ( $notification_id !== '' ) {
			$present_notification = $this->get_notification_by_id( $notification_id );
			if ( ! is_null( $present_notification ) ) {
				$this->remove_notification( $present_notification, false );
			}
			if ( is_null( $present_notification ) ) {
				$this->new[] = $notification_id;
			}
		}
		$this->notifications[] = $notification;
	}

	public function get_notification_by_id( $notification_id ) {
		foreach ( $this->notifications as & $notification ) {
			if ( $notification_id === $notification->get_id() ) {
				return $notification;
			}
		}
		return null;
	}

	public function display_notifications( $echo_as_json = false ) {
		if ( function_exists( 'is_network_admin' ) && is_network_admin() ) {
			return;
		}
		$sorted_notifications = $this->get_sorted_notifications();
		$notifications        = array_filter( $sorted_notifications, array( $this, 'is_notification_persistent' ) );
		if ( empty( $notifications ) ) {
			return;
		}
		array_walk( $notifications, array( $this, 'remove_notification' ) );
		$notifications = array_unique( $notifications );
		if ( $echo_as_json ) {
			$notification_json = array();
			foreach ( $notifications as $notification ) {
				$notification_json[] = $notification->render();
			}
			echo wp_json_encode( $notification_json );
			return;
		}
		foreach ( $notifications as $notification ) {
			echo $notification;
		}
	}

	public function remove_notification( Yoast_Notification $notification, $resolve = true ) {
		$callback = array( $this, __METHOD__ );
		$args     = func_get_args();
		if ( $this->queue_transaction( $callback, $args ) ) {
			return;
		}
		$index = false;
		if ( $notification->is_persistent() ) {
			foreach ( $this->notifications as $current_index => $present_notification ) {
				if ( $present_notification->get_id() === $notification->get_id() ) {
					$index = $current_index;
					break;
				}
			}
		} else {
			$index = array_search( $notification, $this->notifications, true );
		}
		if ( false === $index ) {
			return;
		}
		if ( $notification->is_persistent() && $resolve ) {
			$this->resolved++;
			$this->clear_dismissal( $notification );
		}
		unset( $this->notifications[ $index ] );
		$this->notifications = array_values( $this->notifications );
	}

	public function remove_notification_by_id( $notification_id, $resolve = true ) {
		$notification = $this->get_notification_by_id( $notification_id );
		if ( $notification === null ) {
			return;
		}
		$this->remove_notification( $notification, $resolve );
	}

	public function get_notification_count( $dismissed = false ) {
		$notifications = $this->get_notifications();
		$notifications = array_filter( $notifications, array( $this, 'filter_persistent_notifications' ) );
		if ( ! $dismissed ) {
			$notifications = array_filter( $notifications, array( $this, 'filter_dismissed_notifications' ) );
		}
		return count( $notifications );
	}

	public function get_resolved_notification_count() {
		return $this->resolved;
	}

	public function get_sorted_notifications() {
		$notifications = $this->get_notifications();
		if ( empty( $notifications ) ) {
			return array();
		}
		usort( $notifications, array( $this, 'sort_notifications' ) );
		return $notifications;
	}

	public function ajax_get_notifications() {
		$echo = filter_input( INPUT_POST, 'version' ) === '2';
		$this->display_notifications( $echo );
		exit;
	}

	public function deactivate_hook() {
		$this->clear_notifications();
	}

	public function update_storage() {
		$notifications = $this->get_notifications();
		$notifications = apply_filters( 'yoast_notifications_before_storage', $notifications );
		if ( empty( $notifications ) ) {
			$this->remove_storage();
			return;
		}
		$notifications = array_map( array( $this, 'notification_to_array' ), $notifications );
		update_user_option( get_current_user_id(), self::STORAGE_KEY, $notifications );
	}

	public function get_notifications() {

		return $this->notifications;
	}

	public function get_new_notifications() {
		return array_map( array( $this, 'get_notification_by_id' ), $this->new );
	}
	
	private static function get_user_input( $key ) {
		$filter_input_type = INPUT_GET;
		if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			$filter_input_type = INPUT_POST;
		}
		return filter_input( $filter_input_type, $key );
	}
	
	private function retrieve_notifications_from_storage() {
		if ( $this->notifications_retrieved ) {
			return;
		}
		$this->notifications_retrieved = true;
		$stored_notifications = get_user_option( self::STORAGE_KEY, get_current_user_id() );
		if ( empty( $stored_notifications ) ) {
			return;
		}
		if ( is_array( $stored_notifications ) ) {
			$notifications = array_map( array( $this, 'array_to_notification' ), $stored_notifications );
			$notifications = array_values( array_filter( $notifications, array( $this, 'filter_notification_current_user' ) ) );
			$this->notifications = $notifications;
		}
	}
	
	private function sort_notifications( Yoast_Notification $a, Yoast_Notification $b ) {
		$a_type = $a->get_type();
		$b_type = $b->get_type();
		if ( $a_type === $b_type ) {
			return WPSEO_Utils::calc( $b->get_priority(), 'compare', $a->get_priority() );
		}
		if ( 'error' === $a_type ) {
			return -1;
		}
		if ( 'error' === $b_type ) {
			return 1;
		}
		return 0;
	}
	
	private function remove_storage() {
		delete_user_option( get_current_user_id(), self::STORAGE_KEY );
	}
	
	private function clear_notifications() {
		$this->notifications           = array();
		$this->notifications_retrieved = false;
	}
	
	private function filter_persistent_notifications( Yoast_Notification $notification ) {
		return $notification->is_persistent();
	}
	
	private function filter_dismissed_notifications( Yoast_Notification $notification ) {
		return ! $this->maybe_dismiss_notification( $notification );
	}
	
	private function notification_to_array( Yoast_Notification $notification ) {
		$notification_data = $notification->to_array();
		if ( isset( $notification_data['nonce'] ) ) {
			unset( $notification_data['nonce'] );
		}
		return $notification_data;
	}
	
	private function array_to_notification( $notification_data ) {
		if ( isset( $notification_data['options']['nonce'] ) ) {
			unset( $notification_data['options']['nonce'] );
		}
		return new Yoast_Notification(
			$notification_data['message'],
			$notification_data['options']
		);
	}
	
	private function filter_notification_current_user( Yoast_Notification $notification ) {
		return $notification->display_for_current_user();
	}
	
	private function is_notification_persistent( Yoast_Notification $notification ) {
		return ! $notification->is_persistent();
	}
	
	private function queue_transaction( $callback, $args ) {
		if ( $this->notifications_retrieved ) {
			return false;
		}
		$this->add_transaction_to_queue( $callback, $args );
		return true;
	}
	
	private function add_transaction_to_queue( $callback, $args ) {
		$this->queued_transactions[] = array( $callback, $args );
	}
}
