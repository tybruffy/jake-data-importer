<?php
class Attachment {
	private $id;
	private $postdata;
	private $postmeta;
	private $connections;
	private $attachments;
	private $tags;

	public function __construct( $resource_data ) {
		$fields = array(
			'id',
			'postdata',
			'postmeta',
			'connections',
			'attachments', 
			'tags'
		);

		foreach( $fields as $field ) {
			$this->set_if_key_exists( $resource_data, $field );
		}
	}

	public function id() {
		return $this->id;
	}

	public function postdata() {
		return $this->postdata;
	}

	public function postmeta() {
		return $this->postmeta;
	}

	public function connections() {
		return $this->connections;
	}

	public function attachments() {
		return $this->attachments;
	}

	public function tags() {
		return $this->tags;
	}

	private function set_if_key_exists( $array, $key ) {
		if( isset( $array[ $key ] ) ) {
			$this->$key = $array[ $key ];
		}
	}
}