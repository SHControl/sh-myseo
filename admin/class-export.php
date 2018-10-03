<?php

class WPSEO_Export {
	const ZIP_FILENAME = 'yoast-seo-settings-export.zip';
	const INI_FILENAME = 'settings.ini';
	const NONCE_ACTION = 'wpseo_export';
	const NONCE_NAME   = 'wpseo_export_nonce';
	private $export = '';
	private $error = '';
	public $export_zip_url = '';
	public $success;
	private $include_taxonomy;
	private $dir = array();
	public function __construct( $include_taxonomy = false ) {
		$this->include_taxonomy = $include_taxonomy;
		$this->dir              = wp_upload_dir();
		$this->export_settings();
	}
	public function has_error() {
		return ( $this->error !== '' );
	}
	public function set_error_hook() {
		$message = sprintf( __( 'Error creating %1$s export: ', 'wordpress-seo' ), 'MySEO' ) . $this->error;
		printf(
			'<div class="notice notice-error"><p>%1$s</p></div>',
			$message
		);
	}
	private function export_settings() {
		$this->export_header();
		foreach ( WPSEO_Options::get_option_names() as $opt_group ) {
			$this->write_opt_group( $opt_group );
		}
		$this->taxonomy_metadata();
		if ( ! $this->write_settings_file() ) {
			$this->error = __( 'Could not write settings to file.', 'wordpress-seo' );
			return;
		}
		if ( $this->zip_file() ) {
			exit;
		}
	}
	private function export_header() {
		$header = sprintf(
			esc_html__( 'This is a settings export file for the %1$s plugin by %2$s', 'wordpress-seo' ),
			'MySEO',
			'shcontrol.net'
		);
		$this->write_line( '; ' . $header . ' - ' . esc_url( WPSEO_Shortlinker::get( '//shct.me/myseo-home' ) ) );
		if ( $this->include_taxonomy ) {
			$this->write_line( '; ' . __( 'This export includes taxonomy metadata', 'wordpress-seo' ) );
		}
	}
	private function write_line( $line, $newline_first = false ) {
		if ( $newline_first ) {
			$this->export .= PHP_EOL;
		}
		$this->export .= $line . PHP_EOL;
	}

	private function write_opt_group( $opt_group ) {
		$this->write_line( '[' . $opt_group . ']', true );
		$options = get_option( $opt_group );
		if ( ! is_array( $options ) ) {
			return;
		}
		foreach ( $options as $key => $elem ) {
			if ( is_array( $elem ) ) {
				$count = count( $elem );
				for ( $i = 0; $i < $count; $i ++ ) {
					$this->write_setting( $key . '[]', $elem[ $i ] );
				}
			} else {
				$this->write_setting( $key, $elem );
			}
		}
	}

	private function write_setting( $key, $val ) {
		if ( is_string( $val ) ) {
			$val = '"' . $val . '"';
		}
		$this->write_line( $key . ' = ' . $val );
	}
	private function taxonomy_metadata() {
		if ( $this->include_taxonomy ) {
			$taxonomy_meta = get_option( 'wpseo_taxonomy_meta' );
			if ( is_array( $taxonomy_meta ) ) {
				$this->write_line( '[wpseo_taxonomy_meta]', true );
				$this->write_setting( 'wpseo_taxonomy_meta', urlencode( wp_json_encode( $taxonomy_meta ) ) );
			} else {
				$this->write_line( '; ' . __( 'No taxonomy metadata found', 'wordpress-seo' ), true );
			}
		}
	}
	private function write_settings_file() {
		$handle = fopen( $this->dir['path'] . '/' . self::INI_FILENAME, 'w' );
		if ( ! $handle ) {
			return false;
		}
		$res = fwrite( $handle, $this->export );
		if ( ! $res ) {
			return false;
		}
		fclose( $handle );
		return true;
	}
	private function zip_file() {
		$is_zip_created = $this->create_zip();
		$this->remove_settings_ini();
		if ( ! $is_zip_created ) {
			$this->error = __( 'Could not zip settings-file.', 'wordpress-seo' );
			return false;
		}
		$this->serve_settings_export();
		$this->remove_zip();
		return true;
	}

	private function create_zip() {
		chdir( $this->dir['path'] );
		$zip = new PclZip( './' . self::ZIP_FILENAME );
		if ( 0 === $zip->create( './' . self::INI_FILENAME ) ) {
			return false;
		}

		return file_exists( self::ZIP_FILENAME );
	}
	private function serve_settings_export() {
		if ( ob_get_contents() ) {
			ob_clean();
		}
		header( 'Content-Type: application/octet-stream; charset=utf-8' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Disposition: attachment; filename=' . self::ZIP_FILENAME );
		header( 'Content-Length: ' . filesize( self::ZIP_FILENAME ) );
		readfile( self::ZIP_FILENAME );
	}
	private function remove_settings_ini() {
		unlink( './' . self::INI_FILENAME );
	}
	private function remove_zip() {
		unlink( './' . self::ZIP_FILENAME );
	}
}
