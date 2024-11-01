<?php
/* 
Plugin Name: SV KAMENT Comments
Plugin URI: http://svkament.ru
Description: Integrate SV KAMENT commenting platform
Version: 1.2 
Author: SV
Author URI: http://svkament.ru
License: GPL2 
*/  
global $kament_db_version;
$kament_db_version = "1.2";
define("KAMENT_COMMENTS_SERVER","svkament.ru");

load_plugin_textdomain('kament-comments', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) )
	require 'class-admin.php';
else
	require 'class-frontend.php';

// Add settings link on plugin page
function kament_plugin_link($links) {
  $settings_link = '<a href="options-general.php?page=kament-comments">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'kament_plugin_link' );


function kament_install() {
	global $wpdb;
	global $kament_db_version;

	$table_name = $wpdb->prefix . "kament_plain";

	$sql = "CREATE TABLE  $table_name (
		`page_name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
		`timestamp` TIMESTAMP NOT NULL,
		`data` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
		PRIMARY KEY (  `page_name` )
	);";


	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( "kament_db_version", $kament_db_version );
}

function kament_uninstall() {
	global $wpdb;

	$table_name = $wpdb->prefix . "kament_plain";
	$wpdb->query("DROP TABLE IF EXISTS $table_name");
}

register_activation_hook( __FILE__, 'kament_install' );
register_uninstall_hook( __FILE__, 'kament_uninstall' );
