<?php
//Usage


	private function save_attachements() {
		if( !empty( $this->resource->attachments ) ) {

			require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

			foreach( $this->resource->attachments as $attachment ) {

			}
		}
	}

class JDI_Attachment extends JDI_PluginObject {

	private $id;
	private $url;
	private $field;
	private $connection;
	private $path;
	private $root;
	private $parent_id;
	private $data_array = array();

	public function __construct( $parent_id, $path ) {
		$this->parent_id = $parent_id
		$this->path      = $this->root . $this->sanitize_file_path($path);
	}

	public static function set_root($path) {
		if (!is_dir($path)) {
			return false;
		}
		$this->root = $path;
		return true;
	}

	public function save() {
		if( !is_file($this->path) ) {
			$this->errors[] = "File not found for post ID={$this->parent_id}";
			return false;
		}
		
		$id = media_handle_sideload( $this->data_array, $this->parent_id );

		if( is_a($id, 'WP_Error') ) {
			$this->errors[] = "Upload error for post ID: {$this->parent_id}. Could not upload ".$file['name'];
			return false;
		}

		$this->id = $id;
		$this->_set_url();

		return true;
	}

	public function get($prop) {
		return $this->$prop;
	}

	public function set_meta_field($name) {
		$this->field = $name;
	}

	public function set_connection($type) {
		$this->connection = $type;
	}

	private function _set_url() {
		$this->url = wp_get_attachment_url($this->id);
		return $this->url;
	}

	private function _sanitize_file_path($path) {
		if( $path[0] == '/' ) {
			$path = substr($path, 1);
		}
		return $path;
	}

	private function _spoof_file_array() {
		$this->data_array["name"]     = basename( $this->path );
		$this->data_array["type"]     = wp_check_filetype( basename( $this->path ), null );
		$this->data_array["tmp_name"] = $this->path;
	}
}