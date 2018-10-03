<?php

class WPSEO_Bulk_Title_Editor_List_Table extends WPSEO_Bulk_List_Table {
	protected $page_type = 'title';
	protected $settings = array(
		'singular' => 'wpseo_bulk_title',
		'plural'   => 'wpseo_bulk_titles',
		'ajax'     => true,
	);

	protected $target_db_field = 'title';
	public function get_columns() {
		$columns = array(
			'col_existing_yoast_seo_title' => sprintf( __( 'Existing %1$s Title', 'wordpress-seo' ), 'MySEO' ),
			'col_new_yoast_seo_title'      => sprintf( __( 'New %1$s Title', 'wordpress-seo' ), 'MySEO' ),
		);
		return $this->merge_columns( $columns );
	}

	protected function parse_page_specific_column( $column_name, $record, $attributes ) {
		$meta_data = ( ! empty( $this->meta_data[ $record->ID ] ) ) ? $this->meta_data[ $record->ID ] : array();
		switch ( $column_name ) {
			case 'col_existing_yoast_seo_title':
				echo $this->parse_meta_data_field( $record->ID, $attributes );
				break;

			case 'col_new_yoast_seo_title':
				return sprintf(
					'<input type="text" id="%1$s" name="%1$s" class="wpseo-new-title" data-id="%2$s" aria-labelledby="col_new_yoast_seo_title" />',
					'wpseo-new-title-' . $record->ID,
					$record->ID
				);
		}

		unset( $meta_data );
	}
}
