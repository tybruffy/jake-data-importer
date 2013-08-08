<?php
/*
	Plugin Name: Jake Data Importer
	Plugin URI: http://thejakegroup.com
	Description: Import complicated custom post types with support for custom fields and Posts to Posts.
	Author: Lawson Kurtz & Tyler Bruffy
	Version: 0.1
	Author URI: http://thejakegroup.com/
*/

if( !defined('DS') ) {
	define('DS', DIRECTORY_SEPARATOR);
}

require_once( "Controller.php" );

class JDI_PluginObject {
	const DB_VERSION    = "1.0";
	const PREFIX        = "jdi";

	public static $plugin_url;
	public static $plugin_path;
	public static $template_url;
	public static $template_path;

	function plugin_info() {
		self::$template_path = get_stylesheet_directory();
		self::$template_url  = get_stylesheet_directory_uri();
		self::$plugin_path   = plugin_dir_path(__FILE__);
		self::$plugin_url    = plugins_url('', __FILE__);
	}

	protected function _get_wp_option( $option ) {
		return get_option( self::PREFIX.$option );
	}

	protected function _set_wp_option( $option, $value ) {
		return update_option( self::PREFIX.$option, $value );
	}
}

new JDI_Controller();

