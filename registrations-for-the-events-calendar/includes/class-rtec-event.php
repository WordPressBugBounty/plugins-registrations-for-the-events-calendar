<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}

class RTEC_Event {

	private $event_meta;

	private $post_id;

	public function __construct( $post_id ) {
		$this->event_meta  = rtec_get_event_meta( $post_id );
		$this->post_id     = $this->event_meta['post_id'];
	}

	public function get_meta() {
		return $this->event_meta;
	}

	public function get_post_id() {
		return $this->post_id;
	}
}