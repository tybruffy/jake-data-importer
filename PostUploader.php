<?php
Class PostUploader {
	private static $record_id_meta_name = 'upload_record_id';

	private $id = false;
	private $postdata;
	private $postmeta;
	private $connections;
	private $attachments;
	private $tags;

	private $errors = array();

	/**
	 * Creates a new PostUploader instace from provided data arrays.
	 * @param [type] $upload_record_id [description]
	 * @param [type] $postdata         [description]
	 * @param [type] $postmeta         [description]
	 * @param [type] $connections      [description]
	 * @param [type] $attachments      [description]
	 * @param [type] $tags             [description]
	 */
	public function __construct( $upload_record_id, $postdata, $postmeta = null, $connections = null, $attachments = null, $tags = null) {

		$this->postdata      = $postdata;
		$this->postmeta      = $postmeta;
		$this->postmeta[self::$record_id_meta_name] = $upload_record_id;
		$this->connections   = $connections;
		$this->attachments   = $attachments;
		$this->tags          = $tags;

		// Strip out wordpress IDs from postdata
		if( isset( $this->postdata['ID'] ) ) {
			unset( $this->postdata['ID'] );
		}

		// Set post_status if not explicitly set.
		if( ! isset( $this->postdata['post_status'] ) ) {
			$this->postdata['post_status'] = 'publish';
		}

	}

	public function save() {
		$this->id = $this->save_post();
		
		if( !$this->id ) {
			array_push( $this->errors, "Total Post Failure" );
			return $this->errors;
		}

		$this->save_postmeta();
		$this->save_post_connections();
		$this->save_post_attachements();
		$this->save_tags();

		if( empty( $this->errors ) ) {
			return true;
		} else {
			return $this->errors;
		}
	}

	public function id() {
			return $this->id;
	}

	private function save_post() {
		$post_id = $this->post_exists();
		if ( $post_id ) {
			$this->id             = $post_id;
			$this->postdata['ID'] = $this->id;
			wp_update_post( $this->postdata );
		} else {
			$this->id = wp_insert_post( $this->postdata );
		}
		return $this->id;
	}

	private function post_exists() {
		$post_id = false;
		$args    =	array( 
				'meta_key'   => self::$record_id_meta_name, 
				'meta_value' => $this->upload_record_id(), 
				'post_type'  => $this->post_type() ,
			); 

		$posts = get_posts( $args );

		if ( !empty($posts) ) {
			$post_id = array_pop( $posts )->ID;
		}

		return $post_id;
	}

	/**
	 * Retrieves the non-WordPress upload ID for a record
	 * @return string The Unique identifier for this post.
	 */
	private function upload_record_id() {
		return $this->postmeta[ self::$record_id_meta_name ];
	}

	private function post_type() {
		return $this->postdata[ 'post_type' ];
	}

	private function save_postmeta() {
		if( ! empty($this->postmeta) && $this->id ) {
			foreach( $this->postmeta as $meta_key => $meta_value ) {
				$meta_update_status = $this->save_single_postmeta( $meta_key, $meta_value );
				if( ! $meta_update_status ) array_push( $this->errors, "$meta_key meta failed for post with ID={$this->id}");
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

	private function save_post_connections() {
		if( ! empty( $this->connections ) ) {
			foreach( $this->connections as $connection ) {
				try{
					$connection_status = p2p_type( $connection['type'] )->connect( $this->id, $connection['to'], array(
						'date' => current_time('mysql')
					) );
					if( ! $connection_status ) array_push( $this->errors, "Failed to create connection with Post ID={$this->post_id}");
				} catch(Exception $e) {
					array_push( $this->errors, "Failed to create connection with Post ID={$this->post_id}: Exception: " . $e->getMessage() );
				}
			}
		}
	}

	private function save_post_attachements() {
		if( !empty( $this->attachments ) ) {

			require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

			foreach( $this->attachments as $attachment ) {
				$this->save_post_attachment( $attachment );
			}
		}
	}

	private function save_post_attachment( $attachment ) {
		// Standardize filename
		$file_path = $attachment['file_path'];
		if( $file_path[0] == '/' ) {
			$file_path = substr( $attachment['file_path'], 1 );
		}

		// Check if file exists
		// MUST find better way to set file directory.  AJAX FILE BROWSER Update time.
		if( is_file( __DIR__ . "/elgin/" . $file_path ) ){
			
			$file_path = __DIR__ . "/elgin/" . $file_path;

			$wp_filetype = wp_check_filetype( basename( $file_path ), null );
			
			$file             = array();
			$file["name"]     = basename( $file_path );
			$file["type"]     = $wp_filetype;
			$file["tmp_name"] = $file_path;
			$attachment_id    = media_handle_sideload( $file, $this->id );

			if( is_a( $attachment_id, 'WP_Error') ) {
				array_push( $this->errors, "Upload error for post ID={$this->id}. Could not upload ".$file['name']);

				
				return false;
			}


			// return update_post_meta( $this->id, '_thumbnail_id', $attach_id );
			return update_post_meta( $this->id, $attachment["field"], $attachment_id );
		} else {
			array_push( $this->errors, "File not found for post ID={$this->id}" );
		} 
		return false;
	}

	public function save_tags() {
		if($this->tags){
			foreach( $this->tags as $tag ) {
				
				$status = wp_set_object_terms( $this->id, $tag["term"], $tag["taxonomy"] );

				if (!is_array($status)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}
}