<?php

class WPSEO_Bulk_Description_List_Table extends WPSEO_Bulk_List_Table {
	protected $page_type = 'description';
	protected $settings = array(
		'singular' => 'wpseo_bulk_description',
		'plural'   => 'wpseo_bulk_descriptions',
		'ajax'     => true,
	);
	protected $target_db_field = 'metadesc';
	public function get_columns() {
		$columns = array(
			'col_existing_yoast_seo_metadesc' => __( 'Existing SEO Meta Description', 'wordpress-seo' ),
			'col_new_yoast_seo_metadesc'      => __( 'New SEO Meta Description', 'wordpress-seo' ),
		);
		return $this->merge_columns( $columns );
	}

	protected function parse_page_specific_column( $column_name, $record, $attributes ) {
		switch ( $column_name ) {
			case 'col_new_yoast_seo_metadesc':
				return sprintf(
					'<textarea id="%1$s" name="%1$s" class="wpseo-new-metadesc" data-id="%2$s" aria-labelledby="col_new_yoast_seo_metadesc"></textarea>',
					esc_attr( 'wpseo-new-metadesc-' . $record->ID ),
					esc_attr( $record->ID )
				);

			case 'col_existing_yoast_seo_metadesc':
				echo $this->parse_meta_data_field( $record->ID, $attributes );
				break;
		}
	}
}
