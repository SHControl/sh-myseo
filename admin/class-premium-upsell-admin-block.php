<?php

class WPSEO_Premium_Upsell_Admin_Block {
	protected $hook;
	protected $identifier = 'premium_upsell_admin_block';
	public function __construct( $hook ) {
		$this->hook = $hook;
	}
	public function register_hooks() {
		if ( ! $this->is_hidden() ) {
			add_action( $this->hook, array( $this, 'render' ) );
		}
	}
	public function render() {
		$url = WPSEO_Shortlinker::get( '//shct.me/myseo-home' );
		$arguments = array(
			'<strong>' . esc_html__( 'Multiple keywords', 'wordpress-seo' ) . '</strong>: ' . esc_html__( 'Increase your SEO reach', 'wordpress-seo' ),
			'<strong>' . esc_html__( 'No more dead links', 'wordpress-seo' ) . '</strong>: ' . esc_html__( 'Easy redirect manager', 'wordpress-seo' ),
			'<strong>' . esc_html__( 'Superfast internal linking suggestions', 'wordpress-seo' ) . '</strong>',
			'<strong>' . esc_html__( 'Social media preview', 'wordpress-seo' ) . '</strong>: ' . esc_html__( 'Facebook & Twitter', 'wordpress-seo' ),
			'<strong>' . esc_html__( '24/7 support', 'wordpress-seo' ) . '</strong>',
			'<strong>' . esc_html__( 'No ads!', 'wordpress-seo' ) . '</strong>',
		);
		$arguments_html = implode( '', array_map( array( $this, 'get_argument_html' ), $arguments ) );
		$class = $this->get_html_class();
		$dismiss_msg = sprintf( __( 'Dismiss %s upgrade motivation', 'wordpress-seo' ), 'MySEO Premium' );
		$upgrade_msg = sprintf( __( 'Find out why you should upgrade to %s &raquo;', 'wordpress-seo' ), 'MySEO Premium' );
		echo '<div class="' . esc_attr( $class ) . '">';
		printf(
			'<a href="%1$s" style="" class="alignright %2$s" aria-label="%3$s">X</a>',
			esc_url( add_query_arg( array( $this->get_query_variable_name() => 1 ) ) ),
			esc_attr( $class . '--close' ),
			esc_attr( $dismiss_msg )
		);
		echo '<div>';
		echo '<h2 class="' . esc_attr( $class . '--header' ) . '">' . esc_html__( 'Go premium!', 'wordpress-seo' ) . '</h2>';
		echo '<ul class="' . esc_attr( $class . '--motivation' ) . '">' . $arguments_html . '</ul>';
		echo '<p><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $upgrade_msg ) . '</a><br />';
		echo '</div>';
		echo '</div>';
	}
	protected function get_argument_html( $argument ) {
		$class = $this->get_html_class();
		return sprintf(
			'<li><div class="%1$s">%2$s</div></li>',
			esc_attr( $class . '--argument' ),
			$argument
		);
	}
	protected function is_hidden() {
		$transient_name = $this->get_option_name();
		$hide = (bool) get_user_option( $transient_name );
		if ( ! $hide ) {
			$query_variable_name = $this->get_query_variable_name();
			if ( filter_input( INPUT_GET, $query_variable_name, FILTER_VALIDATE_INT ) === 1 ) {
				update_user_option( get_current_user_id(), $transient_name, true );
				$hide = true;
			}
		}
		return $hide;
	}
	protected function get_option_name() {
		return 'yoast_promo_hide_' . $this->identifier;
	}
	protected function get_query_variable_name() {
		return 'yoast_promo_hide_' . $this->identifier;
	}
	protected function get_html_class() {
		return 'yoast_' . $this->identifier;
	}
}
