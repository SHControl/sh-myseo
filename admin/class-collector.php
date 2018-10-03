<?php

class WPSEO_Collector {
	protected $collections = array();
	public function add_collection( WPSEO_Collection $collection ) {
		$this->collections[] = $collection;
	}

	public function collect() {
		$data = array();
		foreach ( $this->collections as $collection ) {
			$data = array_merge( $data, $collection->get() );
		}
		return $data;
	}

	public function get_as_json() {
		return wp_json_encode( $this->collect() );
	}
}
