<?php

class WPSEO_Capability_Manager_Integration implements WPSEO_WordPress_Integration {
	public $manager;
	public function __construct( WPSEO_Capability_Manager $manager ) {
		$this->manager = $manager;
	}

	public function register_hooks() {
		add_filter( 'members_get_capabilities', array( $this, 'get_capabilities' ) );
		add_action( 'members_register_cap_groups', array( $this, 'action_members_register_cap_group' ) );
		add_filter( 'ure_capabilities_groups_tree', array( $this, 'filter_ure_capabilities_groups_tree' ) );
		add_filter( 'ure_custom_capability_groups', array( $this, 'filter_ure_custom_capability_groups' ), 10, 2 );
	}

	public function get_capabilities( array $caps = array() ) {
		if ( ! did_action( 'wpseo_register_capabilities' ) ) {
			do_action( 'wpseo_register_capabilities' );
		}
		return array_merge( $caps, $this->manager->get_capabilities() );
	}

	public function action_members_register_cap_group() {
		if ( ! function_exists( 'members_register_cap_group' ) ) {
			return;
		}
		members_register_cap_group( 'wordpress-seo',
			array(
				'label'      => esc_html__( 'MySEO', 'wordpress-seo' ),
				'caps'       => $this->get_capabilities(),
				'icon'       => 'dashicons-admin-plugins',
				'diff_added' => true,
			)
		);
	}

	public function filter_ure_capabilities_groups_tree( $groups = array() ) {
		$groups = (array) $groups;
		$groups['wordpress-seo'] = array(
			'caption' => 'Yoast SEO',
			'parent'  => 'custom',
			'level'   => 3,
		);
		return $groups;
	}

	public function filter_ure_custom_capability_groups( $groups = array(), $cap_id = '' ) {
		if ( in_array( $cap_id, $this->get_capabilities(), true ) ) {
			$groups   = (array) $groups;
			$groups[] = 'wordpress-seo';
		}
		return $groups;
	}
}
