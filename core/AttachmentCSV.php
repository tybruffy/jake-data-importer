<?php
class AttachmentCSV {
	private $resources = null;
	private $errors;

	public function __construct( $filepath ) {
		$this->errors = array();
		$this->filepath = $filepath;
	}

	public function import() {
		$this->parse_resources();
		$this->save_resources_to_db();

		echo "<pre>" . print_r($this->errors, true) . "</pre>";
	}

	private function parse_resources() {
		$csv_handle = fopen( $this->filepath, 'r' );
		$header_info = fgetcsv( $csv_handle );
		$resources_info = array();
		$resources_info[0] = $header_info;
		while( $resource_data = fgetcsv( $csv_handle ) ) {
			array_push( $resources_info, $resource_data );
		}
		fclose( $csv_handle );

		$this->resources = $this->record_to_resource( $resources_info );
	}

	private function record_to_resource( $raw_array ) {
		$post_fields = array(          
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
			'tax_input'  
		);

		$output_array = array();

		$headers = array_shift( $raw_array );

		foreach( $raw_array as $entry ){
			$resource_array = array(
				'postdata' => array(),
				'postmeta' => array(),
				'attachments' => array(),
				'tags' => array(),
				'connections' => array()
			);

			foreach( $entry as $entry_index => $entry_value ) {
				if( $headers[$entry_index] == 'upload_id' ) {
					$resource_array['id'] = $entry_value;
				} elseif( in_array( $headers[$entry_index], $post_fields ) ) {
					// if title is a postdata param
					$resource_array['postdata'][$headers[$entry_index]] = $entry_value;

				} elseif( $headers[$entry_index] == 'attachment' ) {
					// if title is _attachment
					$resource_array['attachments'][] = trim($entry_value);

				} elseif( preg_match( "/tag\[[a-zA-Z-_]+\]/", $headers[$entry_index] ) ) {
					// if title is a tag field
					$matches = array();
					preg_match( "/tag\[([a-zA-Z-_]+)\]/", $headers[$entry_index], $matches );

					$resource_array['tags'][] = array(
						'taxonomy' => $matches[1],
						'term' => $entry_value
						);

				} elseif( preg_match( "/connection\[[a-zA-Z-_]+\]\[[\d]+\]/", $headers[$entry_index] ) ) {
					$matches = array();
					preg_match( "/connection\[([a-zA-Z-_]+)\]\[([\d]+)\]/", $headers[$entry_index], $matches );
					
					if( ! empty( $entry_value ) ) {
						$resource_array['connections'][] = array(
							'type' => $matches[1],
							'to' => $matches[2]
							);
					}
				} else {
					$resource_array['postmeta'][$headers[$entry_index]] = $entry_value;
				} 
			}
			$resource = new Attachment( $resource_array );
			array_push( $output_array, $resource );
		}
		return $output_array;
	}

	private function save_resources_to_db() {
		if( ! empty( $this->resources ) ) {
			foreach( $this->resources as $resource ) {
				$new_post = new AttachmentUploader($resource->id(), $resource->postdata(), $resource->postmeta(), $resource->connections(), $resource->attachments(), $resource->tags() );
				$new_post_save_status = $new_post->save();
				if( $new_post_save_status !== true ) array_push( $this->errors, $new_post_save_status );
			}
		}
	}
}
