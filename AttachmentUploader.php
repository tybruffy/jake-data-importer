<?php
Class AttachmentUploader {
  private static $record_id_meta_name = 'upload_record_id';

  private $id = false;
  private $postdata;
  private $postmeta;
  private $connections;
  private $attachments;
  private $tags;

  private $errors;

  public function __construct( $upload_record_id, $postdata, $postmeta = null, $connections = null, $attachments = null, $tags = null) {
    $this->errors = array();

    $this->postdata = $postdata;
    $this->postmeta = $postmeta;
    $this->postmeta[self::$record_id_meta_name] = $upload_record_id;
    $this->connections = $connections;
    $this->attachments = $attachments;
    $this->tags = $tags;

    // Strip out wordpress IDs from postdata... cuz that'd be danger time
    if( isset( $this->postdata['ID'] ) ) unset( $this->postdata['ID'] );
    

    if( ! isset( $this->postdata['post_status'] ) ) {
      $this->postdata['post_status'] = 'publish';
    }
  }

  public function save() {
    $this->id = $this->save_post();
    if( ! $this->id ) {
      array_push( $this->errors, "Total Post Failure" );
      return $this->errors;
    }

    $this->save_postmeta();
    $this->save_post_connections();
    // $this->save_post_attachements();
    // $this->save_tags();

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
    $existing_post_id = $this->find_existing_post();
    if( $existing_post_id ) {
      echo "Skipping " . $this->upload_record_id() . "\n";
      // Do nothing for now.
      //array_push($this->errors, 'exists');
      // $this->id = $existing_post_id;
      // wp_update_attachment_metadata( $this->id, $this->postdata );
    } else {
      echo "Processing " . $this->upload_record_id() . "\n";
      // Deal with attachment
      if( ! empty( $this->attachments ) ){
        if( is_file( __DIR__ . "/attachments/" . $this->attachments[0] ) ){
          $filepath = __DIR__ . "/attachments/" . $this->attachments[0];
          $wp_filetype = wp_check_filetype(basename($filepath), null );
          $wp_upload_dir = wp_upload_dir();

          $this->postdata['guid'] = $wp_upload_dir['url'] . '/' . basename( $filepath );
          $this->postdata['post_mime_type'] = $wp_filetype['type'];
          $this->postdata['post_status'] = 'inherit';
          
          $attachment = $wp_upload_dir['path'] . "/" . basename($filepath);
          rename( $filepath, $attachment );
        }
      }
      $this->id = wp_insert_attachment( $this->postdata, $attachment );

      $attach_data = wp_generate_attachment_metadata( $this->id, $attachment );
      wp_update_attachment_metadata( $this->id, $attach_data );
    }
    return $this->id;
  }

  private function find_existing_post() {
    $existing_post_id = false;
    $existing_posts = get_posts( array( 'meta_key' => self::$record_id_meta_name, 'meta_value' => $this->upload_record_id(), 'post_type' => $this->post_type() ) );
    if ( ! empty( $existing_posts ) ) :
      $existing_post_id = array_pop( $existing_posts )->ID;
    endif;

    return $existing_post_id;
  }

  private function upload_record_id() {
    return $this->postmeta[ self::$record_id_meta_name ];
  }

  private function post_type() {
    return 'attachment';
  }

  private function save_postmeta() {
    if( ! empty($this->postmeta) && $this->id ) {
      foreach( $this->postmeta as $meta_key => $meta_value ) {
        $meta_update_status = $this->save_single_postmeta( $meta_key, $meta_value );
        if( ! $meta_update_status ) array_push( $this->errors, "$meta_key meta failed for post with ID={$this->post_id}");
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

  // private function save_post_attachements() {
  //   if( ! empty( $this->attachments ) ) {
  //     require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
  //     require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
  //     require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

  //     foreach( $this->attachments as $attachment ) {
  //       $this->save_post_attachment( $attachment );
  //     }
  //   }
  // }

  // private function save_post_attachment( $attachment ) {
  //   if( $attachment[0] == '/' ) {
  //     $attachment = substr( $attachment, 1 );
  //   }

  //   if( is_file( __DIR__ . "/attachments/" . $attachment ) ){
  //     $filepath = __DIR__ . "/attachments/" . $attachment;
  //     $wp_filetype = wp_check_filetype( basename( $filepath ), null );
  //     $aFile["name"] = basename( $filepath );
  //     $aFile["type"] = $wp_filetype;
  //     $aFile["tmp_name"] = $filepath;

  //     $attach_id = media_handle_sideload( $aFile, $this->id );
  //     if( is_a( $attach_id, 'WP_Error') ) {
  //       array_push( $this->errors, "Image upload error for post ID={$this->id}" );
  //       return false;
  //     }
  //     return update_post_meta( $this->id, '_thumbnail_id', $attach_id );
  //   } else {
  //     array_push( $this->errors, "Image file not found for post ID={$this->id}" );
  //   } 
  //   return false;
  // }

  // private function save_tags() {
  //   if($this->tags){
  //     return wp_set_object_terms( $this->id, $this->tags, 'post_tag' );
  //   }
  //   return false;
  // }
}
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

function import_attachments() {
  /*
  $errors = array();

  $postdata = array(             
    'post_content' => 'This is post content.',         
    'post_status' => 'publish',    
    'post_title' => 'Post Title 12',     
    'post_type' => 'colors'
  );
  $postmeta = null;
  $connections = array(
    array(
      'type' => 'colors_to_product_lines',
      'to' => 11
    )
  );
  $attachments = array( 'new_jake_bg.jpg' );
  $tags = null;

  $new_post = new PostUploader(2, $postdata, $postmeta, $connections, $attachments, $tags );
  $new_post_save_status = $new_post->save();
  if( $new_post_save_status !== true ) array_push( $errors, $new_post_save_status );

  echo "<pre>" . print_r( $errors, true) . "</pre>";
  */
 require_once(ABSPATH . 'wp-admin/includes/image.php');
 $csv = new AttachmentCSV( __DIR__ . "/resources/attachments.csv" );
 $csv->import();
}

if( ! empty( $_GET['l-import-attachments'] ) ) {
  //add_action( 'init', 'import_attachments', 100 );
}
