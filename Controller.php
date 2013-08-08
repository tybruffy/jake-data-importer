<?php

Class JDI_Controller extends JDI_PluginObject {

	private $style;
	private $errors = array();

	function __construct() {
		require_once( "core/ResourceList.php" );
		require_once( "core/Resource.php" );
		require_once( "core/PostUploader.php" );
		require_once( "SettingsHtml.php" );
		require_once( "Processor.php" );

		add_action( "admin_menu", array($this, "backend_init") );
	}

	public function backend_init() {
		add_options_page( 'Jake Data Importer', 'Data Importer', 'manage_options', 'data-importer-menu', array($this, 'backend_render') );
	}

	public function backend_render() {
		$this->settings = new JDI_SettingsHtml();
		$this->settings->display();
	}
}