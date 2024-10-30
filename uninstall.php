<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}
/*
* Uninstall plugin
 */
function jm_cookies_plugin_uninstall() {
  Global $wpdb;

  delete_option("jm_cookie_plugin_version");
  delete_option("jm_cookie_plugin_database_version");

  $table_name_cookies = $wpdb->prefix.'jm_cookies';
  $table_name_cookies_tracking = $wpdb->prefix.'jm_cookies_tracking';

  // Remove the cookies tracking table
  $sql = "DROP TABLE IF EXISTS `".$table_name_cookies_tracking."`";
  $wpdb->query($sql);

  // Remove the cookies table
  $sql = "DROP TABLE IF EXISTS `".$table_name_cookies."`";
  $wpdb->query($sql);

}
jm_cookies_plugin_uninstall();
