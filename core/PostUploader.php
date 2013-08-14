<?php
Class JDI_PostUploader extends JDI_PluginObject {
	private static $record_id_meta_name = 'upload_record_id';

	private $id = false;
	private $postdata;
	private $postmeta;
	private $connections;
	private $attachments;
	private $terms;

	private $errors = array();

	public function __construct( $resource ) {
		self::plugin_info();
		$this->resource = $resource;
		$this->resource->postmeta[self::$record_id_meta_name] = $this->resource->id;
	}

	public function save() {
		$this->id = $this->save_post();
		
		if( !$this->id ) {
			$this->errors[] = "Total Post Failure for post: " . $this->id;
			return $this->errors;
		}

		$this->save_postmeta();
		$this->save_connections();
		$this->save_attachements();
		$this->save_connected_media();
		$this->save_terms();

		if( empty( $this->errors ) ) {
			return true;
		} else {
			return $this->errors;
		}
	}

	private function save_post() {
		$post_id = $this->post_exists();

		if ( $post_id ) {
			$this->id                       = $post_id;
			$this->resource->postdata['ID'] = $this->id;
			wp_update_post( $this->resource->postdata );
		} else {
			$this->id = wp_insert_post( $this->resource->postdata );
		}
		return $this->id;
	}

	private function post_exists() {
		$post_id = false;
		$args    =	array( 
			'meta_key'   => self::$record_id_meta_name, 
			'meta_value' => $this->_upload_record_id(), 
			'post_type'  => $this->_post_type() ,
		); 

		$posts = get_posts( $args );

		if ( !empty($posts) ) {
			$post_id = array_pop( $posts )->ID;
		}

		return $post_id;
	}

	private function save_postmeta() {
		if( ! empty($this->resource->postmeta) && $this->id ) {
			foreach( $this->resource->postmeta as $meta_key => $meta_value ) {
				$meta_update_status = $this->save_single_postmeta( $meta_key, $meta_value );
				if( ! $meta_update_status ) {
					$this->errors = "$meta_key meta failed for post with ID={$this->id}";
				}
			}
		}
	}

	private function save_single_postmeta( $meta_key, $meta_value ) {
		if( $this->id ){
			$meta_updated_status = update_post_meta( $this->id, $meta_key, $meta_value);
			return $meta_updated_status;
		}
		return false;
	}

	private function save_connections() {
		foreach( $this->resource->connections as $connection ) {
			$connection = $this->save_single_connection($connection['type'], $this->id, $connection['to']);
		}
	}

	private function save_connected_media() {
		foreach( $this->resource->connected_media as $connection ) {
			$attachment = $this->save_single_attachment($connection);
			$connection["to"] = $attachment;
			$connection = $this->save_single_connection($connection);
		}
	}

	private function save_single_connection( $connection ) {
		try{
			$p2p = p2p_type( $connection['type'] );
			$connection = $p2p->connect( $this->id, $connection['to'], array('date' => current_time('mysql')) );

			if( !$connection ) {
				$this->errors[] = "Failed to create connection with Post ID = {$this->id}";
			}
		} catch(Exception $e) {
			$this->errors[] = "Failed to create connection with Post ID = {$this->id}: Exception: " . $e->getMessage();
		}
	}

	private function save_attachements() {
		if( !empty( $this->resource->attachments ) ) {

			require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

			foreach( $this->resource->attachments as $attachment ) {
				$this->save_single_attachment($attachment);
			}
		}
	}

	private function save_single_attachment( $attachment ) {
		$attachment->save();

		if ($attachment->field == "_thumbnail_id") {
			return $this->save_single_postmeta($attachment->get('field'), $attachment->get('id'));
		} elseif ($attachment->field) {
			return $this->save_single_postmeta($attachment->get('field'), $attachment->get('url'));
		} elseif ($attachment->connection) {
			return $this->save_single_connection($attachment->get('connection'), $this->id, $attachment->get('id'));
		}
		return $attachment->id;
	}


	public function save_terms() {
		if( $this->resource->terms ) {
			$errors = array();
			foreach( $this->resource->terms as $term ) {
				$saved = wp_set_object_terms( $this->id, $term["term"], $term["taxonomy"] );
			}
		}
	}



	// TODO: Could all be combined into _get() function
	
	/**
	 * Returns the ID of the resource.
	 * @return string Returns the id of the resource.
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * Retrieves the non-WordPress upload ID for the resource.
	 * @return string The Unique identifier for the resource.
	 */
	private function _upload_record_id() {
		return $this->resource->postmeta[ self::$record_id_meta_name ];
	}

	/**
	 * Retrieves the post type for this resource.
	 * @return string A string containing the post type for the resource.
	 */
	private function _post_type() {
		return $this->resource->postdata[ 'post_type' ];
	}

}