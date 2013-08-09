<?php

Class JDI_Processor extends JDI_PluginObject {

	public $data_array = array();
	public $file_array = array();
	
	function __construct( $settings, $data_array, $file_array ) {
		self::plugin_info();
		$this->settings   = $settings;
		$this->data_array = $data_array;
		$this->file_array = $file_array;

		if ( isset($this->data_array["jdi-import"]) ) {
			$this->try_import();
		}
		
		// Testing
		$this->ResourceList = new JDI_ResourceList( __DIR__ . "/test-data/data.csv" );
		$this->ResourceList->import();
	}

	public function try_import() {
		if ( empty($this->file_array["post-list"]["name"]) ) {
			$this->settings->add_message("error", "Please select a file to upload.");
		} elseif ( filetype($this->file_array["post-list"]["tmp_name"]) != "csv" ) {
			$this->settings->add_message("error", "Please only upload a .csv file.");
		} else {
			$this->import();
		}
	}

	private function import() {
		$this->ResourceList = new JDI_ResourceList( $this->file_array["post-list"]["name"] );
		$this->ResourceList->import();
	}
}
