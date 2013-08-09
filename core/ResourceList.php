<?php
Class JDI_ResourceList extends JDI_PluginObject {
	private $resources = null;
	private $errors = array();

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
		$file_array      = $this->_parse_file();
		$keyed_array     = $this->_create_keyed_array( $file_array );
		$this->resources = $this->array_to_resource( $keyed_array );

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
	 * Convert the raw file data into a set of keyed arrays.
	 * @param  array $raw_data The raw data
	 * @return array           The keyed data
	 */
	private function _create_keyed_array($raw_data) {
		$headers = array_shift($raw_data);
		$cleaned = array();
		foreach ($raw_data as $index => $array) {
			$row = $this->_map_array_keys($headers, $array);
			$cleaned[$index] = $row;
		}
		return $cleaned;
	}

	/**
	 * Convert the keyed array of data rows into Resource objects
	 * @param  array $raw_array The array of rows to create.
	 * @return array            An array of Resource objects.
	 */
	private function array_to_resource($keyed_array) {
		$resource_list = array();

		foreach( $keyed_array as $row ) {
			$resource = new JDI_Resource( $row );
			$resource_list[] = $resource;
		}
		return $resource_list;
	}



	/**
	 * Creates new PostUploader objects and saves the Resource to the database.
	 * @return [type] [description]
	 */
	private function save_resources_to_db() {
		foreach( $this->resources as $resource ) {
			
			$post = new JDI_PostUploader( $resource );
			
			$status = $post->save();
			
			if ( $status !== true ) {
				array_push( $this->errors, $status );
			}
		}
	}
}