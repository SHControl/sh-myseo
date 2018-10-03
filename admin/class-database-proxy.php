<?php

class WPSEO_Database_Proxy {
	protected $table_name;
	protected $suppress_errors = true;
	protected $is_multisite_table = false;
	protected $last_suppressed_state;
	protected $database;
	public function __construct( $database, $table_name, $suppress_errors = true, $is_multisite_table = false ) {
		$this->table_name         = $table_name;
		$this->suppress_errors    = (bool) $suppress_errors;
		$this->is_multisite_table = (bool) $is_multisite_table;
		$this->database           = $database;
		$table_prefix = $this->get_table_prefix();
		if ( ! empty( $table_prefix ) && strpos( $this->table_name, $table_prefix ) === 0 ) {
			$this->table_prefix = substr( $this->table_name, strlen( $table_prefix ) );
		}
		if ( ! $this->is_table_registered() ) {
			$this->register_table();
		}
	}

	public function insert( array $data, $format = null ) {
		$this->pre_execution();
		$result = $this->database->insert( $this->get_table_name(), $data, $format );
		$this->post_execution();
		return $result;
	}
	public function update( array $data, array $where, $format = null, $where_format = null ) {
		$this->pre_execution();
		$result = $this->database->update( $this->get_table_name(), $data, $where, $format, $where_format );
		$this->post_execution();
		return $result;
	}

	public function upsert( array $data, array $where = null, $format = null, $where_format = null ) {
		if ( $where_format !== null ) {
			_deprecated_argument( __METHOD__, '7.7.0', 'The where_format argument is deprecated' );
		}
		$this->pre_execution();
		$update  = array();
		$keys    = array();
		$columns = array_keys( $data );
		foreach ( $columns as $column ) {
			$keys[]   = '`' . $column . '`';
			$update[] = sprintf( '`%1$s` = VALUES(`%1$s`)', $column );
		}
		$query = sprintf(
			'INSERT INTO `%1$s` (%2$s) VALUES ( %3$s ) ON DUPLICATE KEY UPDATE %4$s',
			$this->get_table_name(),
			implode( ', ', $keys ),
			implode( ', ', array_fill( 0, count( $data ), '%s' ) ),
			implode( ', ', $update )
		);
		$result = $this->database->query(
			$this->database->prepare(
				$query,
				array_values( $data )
			)
		);
		$this->post_execution();
		return $result;
	}

	public function delete( array $where, $format = null ) {
		$this->pre_execution();
		$result = $this->database->delete( $this->get_table_name(), $where, $format );
		$this->post_execution();
		return $result;
	}

	public function get_results( $query ) {
		$this->pre_execution();
		$results = $this->database->get_results( $query );
		$this->post_execution();
		return $results;
	}

	public function create_table( array $columns, array $indexes = array() ) {
		$create_table = sprintf( '
				CREATE TABLE IF NOT EXISTS %1$s ( %2$s ) %3$s',
			$this->get_table_name(),
			implode( ',', array_merge( $columns, $indexes ) ),
			$this->database->get_charset_collate()
		);
		$this->pre_execution();
		$is_created = (bool) $this->database->query( $create_table );
		$this->post_execution();
		return $is_created;
	}

	public function has_error() {
		return ( $this->database->last_error !== '' );
	}

	protected function pre_execution() {
		if ( $this->suppress_errors ) {
			$this->last_suppressed_state = $this->database->suppress_errors();
		}
	}

	protected function post_execution() {
		if ( $this->suppress_errors ) {
			$this->database->suppress_errors( $this->last_suppressed_state );
		}
	}

	public function get_table_name() {
		return $this->get_table_prefix() . $this->table_name;
	}

	protected function get_table_prefix() {
		if ( $this->is_multisite_table ) {
			return $this->database->base_prefix;
		}
		return $this->database->get_blog_prefix();
	}

	protected function register_table() {
		$table_name      = $this->table_name;
		$full_table_name = $this->get_table_name();
		$this->database->$table_name = $full_table_name;
		if ( $this->is_multisite_table ) {
			$this->database->ms_global_tables[] = $table_name;
			return;
		}
		$this->database->tables[] = $table_name;
	}

	protected function is_table_registered() {
		if ( $this->is_multisite_table ) {
			return in_array( $this->table_name, $this->database->ms_global_tables, true );
		}
		return in_array( $this->table_name, $this->database->tables, true );
	}
}
