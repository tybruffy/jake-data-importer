<?php
class ResourceList {
	private $resources = null;
	private $errors = array();


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

	/**
	 * Create the ResourceList Object from the filepath provided.
	 * @param string $filepath Path to the CSV file.
	 */	
	public function __construct( $filepath ) {
		$this->filepath = $filepath;
	}


	/**
	 * Import the CSV to the database.  Initializing function.
	 * @return [type] [description]
	 */
	public function import() {
		$file_array = $this->_parse_file();
		$this->resources = $this->array_to_resource( $file_array );
		if( ! empty( $this->resources ) ) {
			$this->save_resources_to_db();
		}
		// echo "<pre>" . print_r($this->errors, true) . "</pre>";
	}


	/**
	 * Read the file into memory and convert each row to an array
	 * @return Array A numerically indexed array containing numerically indexed arrays of each row.
	 */
	private function _parse_file() {
		$resources_info = array();

		ini_set("auto_detect_line_endings", true);
		$handle  = fopen( $this->filepath, 'r' );
		while( $resource_data = fgetcsv( $handle ) ) {
			array_push( $resources_info, $resource_data );
		}
		fclose( $handle );
		return $resources_info;
	}


	/**
	 * Maps a numbered array of keys to a numbered array of values
	 * @param  array $keys         Array containing the keys.
	 * @param  array $values_array Array containing the values to map.
	 * @return array               Array containing the mapped keys and values.
	 */
	private function _map_array_keys($keys, $values_array) {
		$keyed_array = array();
			foreach ($values_array as $index => $value) {
				$key = $keys[$index];
				$keyed_array[$key] = $value;
			}
		return $keyed_array;
	}


	/**
	 * Convert the raw array of data rows into Resource objects
	 * @param  array $raw_array The array of rows to create.
	 * @return array            An array of Resource objects.
	 */
	private function array_to_resource( $raw_array ) {
		$headers       = array_shift( $raw_array );
		$keyed_array   = array();
		$resource_list = array();

	
		foreach ($raw_array as $index => $array) {
			$row = $this->_map_array_keys($headers, $array);
			$keyed_array[$index] = $row;
		}

		foreach( $keyed_array as $row ) {
			$resource = new Resource( $row );
			array_push( $resource_list, $resource );
		}
		return $resource_list;
	}



	/**
	 * Creates new PostUploader objects and saves the Resource to the database.
	 * @return [type] [description]
	 */
	private function save_resources_to_db() {
		foreach( $this->resources as $resource ) {
			
			$post = new PostUploader( 
				$resource->id(),
				$resource->postdata(), 
				$resource->postmeta(), 
				$resource->connections(),
				$resource->attachments(), 
				$resource->tags() 
			);
			
			// $status = $post->save();
			
			if ( $status !== true ) {
				array_push( $this->errors, $status );
			}
		}
	}
}