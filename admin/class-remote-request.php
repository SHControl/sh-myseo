<?php

class WPSEO_Remote_Request {
	const METHOD_POST = 'post';
	const METHOD_GET  = 'get';
	protected $endpoint = '';
	protected $args = array(
		'blocking'  => false,
		'sslverify' => false,
		'timeout'   => 2,
	);
	protected $response_error;
	protected $response_body;
	public function __construct( $endpoint, array $args = array() ) {
		$this->endpoint = $endpoint;
		$this->args     = wp_parse_args( $this->args, $args );
	}
	public function set_body( $body ) {
		$this->args['body'] = $body;
	}
	public function send( $method = self::METHOD_POST ) {
		switch ( $method ) {
			case self::METHOD_POST:
				$response = $this->post();
				break;
			case self::METHOD_GET:
				$response = $this->get();
				break;
			default:
				$response = new WP_Error( 1, sprintf( __( 'Request method %1$s is not valid.', 'wordpress-seo' ), $method ) );
				break;
		}

		return $this->process_response( $response );
	}
	public function get_response_error() {
		return $this->response_error;
	}
	public function get_response_body() {
		return $this->response_body;
	}
	protected function process_response( $response ) {
		if ( $response instanceof WP_Error ) {
			$this->response_error = $response;
			return false;
		}
		$this->response_body = wp_remote_retrieve_body( $response );
		return ( wp_remote_retrieve_response_code( $response ) === 200 );
	}
	protected function post() {
		return wp_remote_post( $this->endpoint, $this->args );
	}
	protected function get() {
		return wp_remote_get( $this->endpoint, $this->args );
	}
}
