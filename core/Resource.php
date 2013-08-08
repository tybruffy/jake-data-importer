<?php
class Resource {
	private $id;
	private $postdata    = array();
	private $postmeta    = array();
	private $connections = array();
	private $attachments = array();
	private $tags        = array();

	/**
	 * List of fields to be put into the post_data array.
	 * @var array
	 */
	private $post_fields = array(          
			'menu_order',
			'comment_status',
			'ping_status',
			'pinged',
			'post_author',
			'post_category',
			'post_content',
			'post_date',
			'post_date_gmt',
			'post_excerpt',
			'post_name',
			'post_parent',
			'post_password',
			'post_status',
			'post_title',
			'post_type',
			'tags_input',
			'to_ping',
			'tax_input',
		);

	public function __construct( $fields ) {
		foreach( $fields as $field_name => $field_value ) {
			$this->_parse_resource_fields( $field_name, $field_value );
		}

		echo "<pre>" . print_r($this, true) . "</pre>";

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

	private function _parse_resource_fields( $key, $value ) {

		if( $key == 'upload_id' ) {
			
			$this->id = $value;

		} elseif( in_array( $key, $this->post_fields ) ) {
		
			$this->postdata[$key] = $value;

		} elseif( preg_match( "/attachment\[[a-zA-Z-_]+\]/", $key) ) {
			$this->attachments[] = $this->assign_attachment($key, $value);

		} elseif( preg_match( "/tag\[[a-zA-Z-_]+\]/", $key ) ) {

			$this->tags[] = $this->assign_term($key, $value);

		} elseif( preg_match( "/connection\[[a-zA-Z-_]+\]\[[\d]+\]/", $key ) ) {

			$this->connections[] = $this->create_p2p_connection($key, $value);

		} else {

			$this->postmeta[$key] = $resource_array['postmeta'][$key] = $value;

		}
	}

	private function assign_attachment($key, $value) {
		if ($value) {
			$matches = array();
			preg_match( "/attachment\[([a-zA-Z-_]+)\]/", $key, $matches );

			return array(
				"field"     => $matches[1],
				"file_path" => trim($value),
			);
		}
	}

	private function assign_term($key, $value) {
		$matches = array();
		preg_match( "/tag\[([a-zA-Z-_]+)\]/", $key, $matches );

		return array(
			'taxonomy' => $matches[1],
			'term'     => $value
		);	
	}

	private function create_p2p_connection($key, $value) {
		$matches = array();
		preg_match( "/connection\[([a-zA-Z-_]+)\]\[([\d]+)\]/", $key, $matches );
		
		if( ! empty( $value ) ) {
			return array(
				'type' => $matches[1],
				'to'   => $matches[2]
			);
		}
	}
}
