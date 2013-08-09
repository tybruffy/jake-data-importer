<?php
Class JDI_PostUploader extends JDI_PluginObject {
	private static $record_id_meta_name = 'upload_record_id';

	private $id = false;
	private $postdata;
	private $postmeta;
	private $connections;
	private $attachments;
	private $tags;

	private $errors = array();

	public function __construct( $resource ) {
		$this->resource = $resource;
	}

	public function save() {
		$this->resource->id = $this->save_post();
		
		if( !$this->resource->id ) {
			$this->errors[] = "Total Post Failure for post: " . $this->resource->id;
			return $this->errors;
		}

		$this->save_postmeta();
		$this->save_connections();
		$this->save_attachements();
		$this->save_tags();

		if( empty( $this->errors ) ) {
			return true;
		} else {
			return $this->errors;
		}
	}

	private function save_post() {
		$post_id = $this->post_exists();

		if ( $post_id ) {
			$this->resource->id         = $post_id;
			$this->resource->postdata['ID'] = $this->resource->id;
			wp_update_post( $this->resource->postdata );
		} else {
			$this->resource->id = wp_insert_post( $this->resource->postdata );
		}
		return $this->resource->id;
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
		if( ! empty($this->resource->postmeta) && $this->resource->id ) {
			foreach( $this->resource->postmeta as $meta_key => $meta_value ) {
				$meta_update_status = $this->save_single_postmeta( $meta_key, $meta_value );
				if( ! $meta_update_status ) {
					$this->errors = "$meta_key meta failed for post with ID={$this->resource->id}";
				}
			}
		}
	}

	private function save_single_postmeta( $meta_key, $meta_value ) {
		if( $this->resource->id ){
			$meta_updated_status = update_post_meta( $this->resource->id, $meta_key, $meta_value);
			return $meta_updated_status;
		}
		return false;
	}

	private function save_connections() {
		if( ! empty( $this->resource->connections ) ) {
			foreach( $this->resource->connections as $connection ) {
				try{
					$connection_status = p2p_type( $connection['type'] )->connect( $this->resource->id, $connection['to'], array(
						'date' => current_time('mysql')
					) );

					if( !$connection_status ) {
						$this->errors[] = "Failed to create connection with Post ID={$this->post_id}";
					}
				} catch(Exception $e) {
					$this->errors[] = "Failed to create connection with Post ID={$this->post_id}: Exception: " . $e->getMessage();
				}
			}
		}
	}

	private function save_attachements() {
		if( !empty( $this->resource->attachments ) ) {

			require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

			foreach( $this->resource->attachments as $attachment ) {
				$this->save_single_attachment( $attachment );
			}
		}
	}

	private function save_single_attachment( $attachment ) {
		$file_path = $attachment['file_path'];
		if( $file_path[0] == '/' ) {
			$file_path = substr( $attachment['file_path'], 1 );
		}

		if( is_file( __DIR__ . $file_path ) ){
			
			$file_path = __DIR__ . $file_path;

			$wp_filetype = wp_check_filetype( basename( $file_path ), null );
			
			$file             = array();
			$file["name"]     = basename( $file_path );
			$file["type"]     = $wp_filetype;
			$file["tmp_name"] = $file_path;
			$attachment_id    = media_handle_sideload( $file, $this->resource->id );

			if( is_a( $attachment_id, 'WP_Error') ) {
				$this->errors[] = "Upload error for post ID: {$this->resource->id}. Could not upload ".$file['name'];

				return false;
			}

			if ($attachment["field"] == "_thumbnail_id") {
				return update_post_meta( $this->resource->id, $attachment["field"], $attachment_id );
			} else {
				return update_post_meta( $this->resource->id, $attachment["field"], wp_get_attachment_url( $attachment_id ) );
			}
		} else {
			$this->errors[] = "File not found for post ID={$this->resource->id}";
		} 
		return false;
	}


	public function save_tags() {
		if( $this->resource->tags ) {
			$errors = array();
			foreach( $this->resource->tags as $tag ) {
				$saved = wp_set_object_terms( $this->resource->id, $tag["term"], $tag["taxonomy"] );
			}
		}
	}



	// TODO: Could all be combined into _get() function.
	
	/**
	 * Returns the ID of the resource.
	 * @return string Returns the id of the resource.
	 */
	public function id() {
		return $this->resource->id;
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