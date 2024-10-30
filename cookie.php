<?php
/*
Plugin Name: Cookies by JM
Plugin URI: https://www.jesmadsen.com/wordpress-plugins
Description: A simple yet advanced Cookie Wordpress plugin with unlimited cookies, statistics and real time editing.
Version: 1.0
Author: Jes Madsen
Author URI: https://www.jesmadsen.com/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
Text Domain: jm-cookies
Domain Path: /languages
*/

/*
 * Define plugin variables
 */
define("JM_COOKIES_BASE", __FILE__);
define("JM_COOKIES_PLUGIN_DIR_URL", plugin_dir_url(__FILE__));
define("JM_COOKIES_PLUGIN_DIR_PATH", plugin_dir_path( __FILE__ ));
define('JM_COOKIES_PLUGIN_VERSION', '0.2');
define('JM_COOKIES_PLUGIN_DATABASE_VERSION', '0.3');

/*
 * Excecute admin class
 */
if (is_admin()) {
  require JM_COOKIES_PLUGIN_DIR_PATH.'admin/extends-wp-list.php';
 	require JM_COOKIES_PLUGIN_DIR_PATH.'admin/class.php';
  if( class_exists('jmCookiesAdmin') ) {
    // $objAdmin = new jmCookiesAdmin;
    jmCookiesAdmin::get_instance();
  }
}

/*
 * Excecute frontend class
 */
require JM_COOKIES_PLUGIN_DIR_PATH.'frontend/class.php';
if( class_exists('jmCookiesFrontend') ) {
  $objFrontend = new jmCookiesFrontend;
}
?>
