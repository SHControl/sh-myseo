<?php

class WPSEO_Recalculate_Scores {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'recalculate_assets' ) );
		add_action( 'admin_footer', array( $this, 'modal_box' ), 20 );
	}
	public function recalculate_assets() {
		$asset_manager = new WPSEO_Admin_Asset_Manager();
		$asset_manager->enqueue_script( 'recalculate' );
	}
	public function modal_box() {
		add_thickbox();
		$progress = sprintf(
			esc_html__( '%1$s of %2$s done.', 'wordpress-seo' ),
			'<span id="wpseo_count">0</span>',
			'<strong id="wpseo_count_total">0</strong>'
		);

		?>
		<div id="wpseo_recalculate" class="hidden">
			<p><?php esc_html_e( 'Recalculating SEO scores for all pieces of content with a focus keyword.', 'wordpress-seo' ); ?></p>
			<div id="wpseo_progressbar"></div>
			<p><?php echo $progress; ?></p>
		</div>
		<?php
	}
}
