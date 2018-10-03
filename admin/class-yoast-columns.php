<?php

class WPSEO_Yoast_Columns implements WPSEO_WordPress_Integration {
	public function register_hooks() {
		add_action( 'load-edit.php', array( $this, 'add_help_tab' ) );
	}
	public function add_help_tab() {
		$screen = get_current_screen();
		$screen->add_help_tab(
			array(
				'title'    => sprintf( __( '%s Columns', 'wordpress-seo' ), 'Yoast' ),
				'id'       => 'yst-columns',
				'content'  => sprintf(
					'<p>' . __( '%1$s adds several columns to this page. We\'ve written an article about %2$show to use the SEO score and Readability score%3$s. The links columns show the number of articles on this site linking %5$sto%6$s this article and the number of URLs linked %5$sfrom%6$s this article. Learn more about %4$show to use these features to improve your internal linking%3$s, which greatly enhances your SEO.', 'wordpress-seo' ) . '</p>',
					'MySEO',
					'<a href="' . WPSEO_Shortlinker::get( '//shct.me/use-content-analysis-yoast-seo' ) . '">',
					'</a>',
					'<a href="' . WPSEO_Shortlinker::get( '//shct.me/how-to-use-the-text-link-counter' ) . '">',
					'<em>',
					'</em>'
				),
				'priority' => 15,
			)
		);
	}
}
