<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}

class RTEC_Events_Query {

	private $args;

	private $show_hidden;

	private $query;

	private $posts;

	private $repository;

	public function __construct( $repository, $args, $show_hidden = true ) {
		$this->repository = $repository;
		$this->args = $args;
		$this->show_hidden = $show_hidden;
	}

	public function init_query() {
		$start_date = ! empty( $this->args['start_date'] ) ? $this->args['start_date'] : date( 'Y-m-d H:i:s' );
		if ( ! empty( $this->args['in_series'] ) ) {
			$this->repository = $this->repository->where( 'in_series', $this->args['in_series'] );
		}
		if ( ! empty( $this->args['author'] ) ) {
			$this->repository = $this->repository->where( 'author', $this->args['author'] );
		}

		if ( ! empty( $this->args['s'] ) ) {
			$this->repository = $this->repository->where( 'search', $this->args['s'] );
		}

		if ( ! empty( $this->args['offset'] ) ) {
			$this->repository = $this->repository->offset( (int) $this->args['offset'] );
		}
		if ( ! empty( $this->args['meta_query'] ) ) {
			$this->repository = $this->repository->by( 'meta_query', $this->args['meta_query'] );
		}
		if ( ! empty( $this->args['past'] ) ) {
			$this->repository = $this->repository->order( 'desc' );
			$end_date = ! empty( $this->args['end_date'] ) ? $this->args['end_date'] : date( 'Y-m-d H:i:s' );

			$this->repository = $this->repository->where( 'end_date', $end_date );
		} elseif ( empty( $this->args['end_date'] ) ) {
			$this->repository = $this->repository->where( 'start_date', $start_date );
		}

		if ( ! empty( $this->args['tax_query'] ) ) {
			$this->repository = $this->repository->where( 'event_category', $this->args['tax_query'][0]['terms'] );
		}

		if ( ! empty( $this->args['posts_per_page'] ) ) {
			$this->repository->per_page( absint( $this->args['posts_per_page'] ) );
		}

		$final_query = $this->repository->get_query();
		$final_query->get_posts();
		if ( method_exists( 'TEC\Events\Custom_Tables\V1\WP_Query\Custom_Tables_Query', 'from_wp_query' ) ){
			$final_query = TEC\Events\Custom_Tables\V1\WP_Query\Custom_Tables_Query::from_wp_query( $final_query );
		}

		$this->posts = $final_query->get_posts();
	}

	public function get_query() {
		return $this->query;
	}

	public function get_posts() {
		return $this->posts;
	}
}
