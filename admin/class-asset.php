<?php

class WPSEO_Admin_Asset {
	const TYPE_JS = 'js';
	const TYPE_CSS = 'css';
	const NAME = 'name';
	const SRC = 'src';
	const DEPS = 'deps';
	const VERSION = 'version';
	const MEDIA = 'media';
	const RTL = 'rtl';
	const IN_FOOTER = 'in_footer';
	protected $name;
	protected $src;
	protected $deps;
	protected $version;
	protected $media;
	protected $in_footer;
	protected $rtl;
	protected $suffix;
	public function __construct( array $args ) {
		if ( ! isset( $args['name'] ) ) {
			throw new InvalidArgumentException( 'name is a required argument' );
		}
		if ( ! isset( $args['src'] ) ) {
			throw new InvalidArgumentException( 'src is a required argument' );
		}
		$args = array_merge( array(
			'deps'      => array(),
			'version'   => WPSEO_VERSION,
			'in_footer' => true,
			'rtl'       => true,
			'media'     => 'all',
			'suffix'    => WPSEO_CSSJS_SUFFIX,
		), $args );

		$this->name      = $args['name'];
		$this->src       = $args['src'];
		$this->deps      = $args['deps'];
		$this->version   = $args['version'];
		$this->media     = $args['media'];
		$this->in_footer = $args['in_footer'];
		$this->rtl       = $args['rtl'];
		$this->suffix    = $args['suffix'];
	}
	public function get_name() {
		return $this->name;
	}
	public function get_src() {
		return $this->src;
	}
	public function get_deps() {
		return $this->deps;
	}
	public function get_version() {
		return $this->version;
	}
	public function get_media() {
		return $this->media;
	}
	public function is_in_footer() {
		return $this->in_footer;
	}
	public function has_rtl() {
		return $this->rtl;
	}
	public function get_suffix() {
		return $this->suffix;
	}
	public function get_url( $type, $plugin_file ) {
		_deprecated_function( __CLASS__ . '::get_url', '6.2', 'WPSEO_Admin_Asset_SEO_Location::get_url' );
		$asset_location = new WPSEO_Admin_Asset_SEO_Location( $plugin_file );
		return $asset_location->get_url( $this, $type );
	}
}
