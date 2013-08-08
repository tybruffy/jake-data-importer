<?php

Class JDI_Controller extends JDI_PluginObject {

	private $style;
	private $errors = array();

	function __construct() {
		require_once( "ResourceList.php" );
		require_once( "Resource.php" );
		require_once( "PostUploader.php" );
		require_once( "SettingsHtml.php" );

		add_action( "admin_menu", array($this, "backend_init") );
	}

	public function backend_init() {
		add_options_page( 'Jake Data Importer', 'Data Importer', 'manage_options', 'data-importer-menu', array($this, 'backend_render') );
	}

	public function backend_render() {
		$settings = new JDI_SettingsHtml();

		if ( isset($_POST["jdi-import"])) {
			if ( empty($_FILES["post-list"]["name"]) ) {
				$settings->add_message("error", "Please select a file to upload.");
			} elseif ( filetype($_FILES["post-list"]["tmp_name"]) != "csv" ) {
				$settings->add_message("error", "Please only upload a .csv file.");
			} else {
				$this->import();
			}
		}
		
		$this->import();
		$settings->display();

	}

	private function import() { 
		$csv = new ResourceList( __DIR__ . "/elgin/elg_brochures.csv" );
		$csv->import();
	}

}