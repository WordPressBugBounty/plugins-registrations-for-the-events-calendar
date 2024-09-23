<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}

class RTEC_Venue_Query {

	private $args;

	private $venues;

	/**
	 * @var Tribe__Repository__Interface
	 */
	private $repository;

	public function __construct( $repository, $args ) {
		$this->repository = $repository;
		$this->args = $args;
	}

	public function init_query() {
		if ( ! empty( $this->args['event'] ) ) {
			$this->repository->by( 'event', $this->args['event'] );
		}

		$this->venues = $this->repository->all();
	}

	public function get_venues() {
		return $this->venues;
	}
}
