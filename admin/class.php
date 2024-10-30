<?php
/*
 * The admin class
 *
 * 1. construct
 * 2.
 */
if( !class_exists('jmCookiesAdmin') )
{
	class jmCookiesAdmin {

    // class instance
    static $instance;

    // Cookie WP_List_Table object
    public $list_obj;

    function __construct() {

			// Initialize the script
			add_action('init', array( $this, 'jm_cookies_init'), 1);

			// On plugin install
      register_activation_hook( JM_COOKIES_BASE, array($this, 'jm_cookies_plugin_install') );

			// On plugin deactivation
      // register_deactivation_hook( JM_COOKIES_BASE, array($this, 'jm_cookies_plugin_uninstall') );

			// Register the admin menu
      add_action( 'admin_menu', array($this, 'jm_plugin_admin_menu') );

			// Remove sub menu parts from the admin menu
			add_filter( 'submenu_file', array($this, 'jm_cookies_admin_submenu_filter') );

			// Load the javascripts and stylesheets
			add_action( 'admin_enqueue_scripts', array($this, 'jm_cookie_admin_enqueue') );

			// If theres a new version then update the plugin
			add_action('init', array($this, 'jm_cookies_plugin_update') );

			// Save or update the cookies
			add_action( 'admin_post_cookie_form', array($this, 'jm_cookie_form_action') );

			// Delete cookies
			add_action( 'init', array($this, 'jm_cookie_delete'), 2);

			// register ajax function
			add_action( 'wp_ajax_jm_cookies_tracking_ajax', array($this, 'jm_cookies_tracking_ajax') );



    }

		/*
		* Stuff to init when the plugin loads
		 */
		function jm_cookies_init() {
			// If sessions hasen't been started lets do it
    	if(!session_id()) {
				@session_cache_limiter('private, must-revalidate'); //private_no_expire
		    @session_cache_expire(0);
		    @session_start();
    	}

			// Change title on add cookie page when we are in edit mode
			if (!empty($_GET['cookie'])) add_filter('admin_title', function() {
				 return 'Edit cookie &lsaquo; '.get_bloginfo('name').' ';
			}, 10, 2);
		}

		/*
		* Install the plugin
		 */
    function jm_cookies_plugin_install() {

			Global $wpdb;

			$table_name = $wpdb->prefix.'jm_cookies';
			$wpdb->show_errors();
  		// Create Cookie Table

			$sql = "
			CREATE TABLE IF NOT EXISTS ".$table_name."  (
			  id bigint(20) NOT NULL AUTO_INCREMENT,

				status tinyint(2) DEFAULT '0',
				tracking tinyint(2) DEFAULT '0',
				close_accept tinyint(2) DEFAULT '0',
				button_accept tinyint(2) DEFAULT '0',
				view_accept tinyint(2) DEFAULT '0',
				hide_close_button tinyint(2) DEFAULT '0',
				show_on_page varchar(255) DEFAULT NULL,
				display_duration tinyint(4) DEFAULT '0',
				expire datetime DEFAULT NULL,
				only_logged_in tinyint(2) DEFAULT '0',
				user_roles varchar(255) DEFAULT NULL,
				dock tinyint(2) DEFAULT '0',

				container_class varchar(255) DEFAULT NULL,
			  name varchar(255) DEFAULT NULL,
			  headline varchar(255) DEFAULT NULL,
				headline_size varchar(10) DEFAULT NULL,
				content text,
				url varchar(255) DEFAULT NULL,
				external_url varchar(255) DEFAULT NULL,
				button varchar(255) DEFAULT NULL,

				design varchar(5) DEFAULT NULL,
				heading varchar(15) DEFAULT NULL,
				background_color varchar(30) DEFAULT NULL,
				text_color varchar(30) DEFAULT NULL,
				border_radius tinyint(4) DEFAULT '0',
				shadow_color varchar(30) DEFAULT NULL,
				shadow_size tinyint(4) DEFAULT '0',
				button_background_color varchar(30) DEFAULT NULL,
				button_text_color varchar(30) DEFAULT NULL,
				button_border_radius tinyint(4) DEFAULT '5',
				close_button_size tinyint(4) DEFAULT '25',
				close_button_background_color varchar(30) DEFAULT NULL,
				close_button_text_color varchar(30) DEFAULT NULL,
				transition varchar(12) DEFAULT NULL,
			  created datetime DEFAULT NULL,
			  PRIMARY KEY (id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			if($wpdb->last_error !== '')
			{
				$wpdb->print_error();
				// add_option("jm_cookie_plugin_debug", print_r($wpdb->print_error(), true));
			}


			$tracking_table_name = $wpdb->prefix.'jm_cookies_tracking';
			$wpdb->show_errors();
  		// Create Cookie tracking Table
  		if($wpdb->get_var("SHOW TABLES LIKE '".$tracking_table_name."'") != $tracking_table_name) {
  			$sql = "CREATE TABLE ".$tracking_table_name."  (
  					  id bigint(20) NOT NULL AUTO_INCREMENT,
							cookie_id bigint(20) DEFAULT '0',
							type tinyint(4) DEFAULT '0',
  					  created datetime DEFAULT NULL,
  					  PRIMARY KEY (id)
  				);";

  				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  				dbDelta($sql);

					if($wpdb->last_error !== '')
					{
						$wpdb->print_error();
					}
					else
					{
						// Indexes for table `wp_prefix_jm_cookies_tracking`
						$sql = "
						ALTER TABLE `".$tracking_table_name."`
					  ADD PRIMARY KEY (`id`),
					  ADD KEY `cookie_id` (`cookie_id`);
		  			";

	  				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	  				dbDelta($sql);

						if($wpdb->last_error !== '')
						{
							$wpdb->print_error();
						}

						// Constraints for table `wp_prefix_jm_cookies_tracking`
						$sql = "
						ALTER TABLE `".$tracking_table_name."`
						  ADD CONSTRAINT `".$tracking_table_name."_ibfk_1` FOREIGN KEY (`cookie_id`) REFERENCES `".$table_name."` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
						COMMIT;
						";
						require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	  				dbDelta($sql);

						if($wpdb->last_error !== '')
						{
							$wpdb->print_error();
						}

					}

  		}

			/* Set plugin version */
      update_option("jm_cookie_plugin_version", JM_COOKIES_PLUGIN_VERSION);

			/* Set plugin database version */
      update_option("jm_cookie_plugin_database_version", JM_COOKIES_PLUGIN_DATABASE_VERSION);
    }



		/*
		* Update plugin
		 */
		function jm_cookies_plugin_update() {

			Global $wpdb;

			// If this database version is under 0.1 then add the url and button columns
			/*
			if(get_option('jm_cookie_plugin_database_version')<'0.2'){
				$table_name = $wpdb->prefix.'jm_cookies';
				$wpdb->show_errors();
				$wpdb->query("ALTER TABLE $table_name
					ADD COLUMN `button` VARCHAR(255) NOT NULL AFTER `content`,
					ADD COLUMN `url` varchar(255) NOT NULL AFTER `content`
				");
				if($wpdb->last_error !== '')
				{
					$wpdb->print_error();
				}
			}
			*/

			/* Update plugin version */
      update_option("jm_cookie_plugin_version", JM_COOKIES_PLUGIN_VERSION);

			/* Set plugin database version */
      update_option("jm_cookie_plugin_database_version", JM_COOKIES_PLUGIN_DATABASE_VERSION);

		}

    /*
    * Define the admin menu for the plugin
     */
    function jm_plugin_admin_menu() {

			global $objAdmin;

			$hook = add_menu_page(
				'jm-cookies',
				'JM Cookies',
				'manage_options',
				'jm-cookies',
				array($this, 'cookies_list'),
				JM_COOKIES_PLUGIN_DIR_URL.'admin/images/wp-icon.png'
			);

			$hook = add_submenu_page(
				'jm-cookies',
				'All cookies',
				'Cookie List',
				'manage_options',
				'jm-cookies',
				array($this, 'cookies_list')
			);
			add_action( "load-$hook", [ $this, 'screen_option' ] );

			add_submenu_page(
				'jm-cookies',
				'Add Cookie',
				'',
				'manage_options',
				'cookie',
				array($this, 'add_cookie')
			);


			add_submenu_page(
				'jm-cookies',
				'Tacking',
				'',
				'manage_options',
				'tracking',
				array($this, 'tracking')
			);

			add_submenu_page(
				'jm-cookies',
				'FAQ',
				'FAQ',
				'manage_options',
				'cookie-faq',
				array($this, 'faq')
			);
    }

		/*
		* Remove unwanted items from the admin menu
		 */
		function jm_cookies_admin_submenu_filter( $submenu_file ) {

		    global $plugin_page;

		    $hidden_submenus = array(
		        'cookie' => true,
						'tracking' => true
		    );

		    // Select another submenu item to highlight (optional).
		    if ( $plugin_page && isset( $hidden_submenus[ $plugin_page ] ) ) {
		        // $submenu_file = 'submenu_to_highlight';
		    }

		    // Hide the submenu.
		    foreach ( $hidden_submenus as $submenu => $unused ) {
		        remove_submenu_page( 'jm-cookies', $submenu );
		    }

		    return $submenu_file;
		}


		/*
		* Load the admin page scripts
		 */
		function jm_cookie_admin_enqueue($hook) {

			// Only enqueue these on the cookie page
			if($hook == 'jm-cookies_page_cookie' && $_GET['page']=="cookie")
			{
				wp_enqueue_style( 'jm_cookies_admin_style_frontend', JM_COOKIES_PLUGIN_DIR_URL.'frontend/css/style.css' );

				wp_enqueue_media();

        wp_enqueue_style( 'wp-color-picker' ); // Add the color picker css file
				wp_enqueue_script( 'wp-color-picker-alpha', JM_COOKIES_PLUGIN_DIR_URL.'/admin/assets/alpha-color-picker/wp-color-picker-alpha.min.js', array( 'wp-color-picker' ), '1.0.0', true );

				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'jquery-ui-spinner' );
				wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );
				wp_enqueue_style( 'jquery-ui' );

				wp_enqueue_style( 'jm_cookies_admin_style_select2', JM_COOKIES_PLUGIN_DIR_URL.'admin/assets/select2/css/select2.min.css' );
				wp_enqueue_script ( 'jm_cookies_admin_script_select2', JM_COOKIES_PLUGIN_DIR_URL.'admin/assets/select2/js/select2.min.js', array('jquery'), '', true );
			}

			// Only enqueue these on the cookie tracking page
			if($hook == 'jm-cookies_page_tracking' && !empty($_GET['cookie'])) {
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );
	    	wp_enqueue_style( 'jquery-ui' );

				wp_enqueue_style( 'jm_cookies_admin_style_morris', JM_COOKIES_PLUGIN_DIR_URL.'admin/assets/morris/morris.css' );
				wp_enqueue_script ( 'jm_cookies_admin_script_morris', JM_COOKIES_PLUGIN_DIR_URL.'admin/assets/morris/morris.min.js', array('jquery'), '', true );
				wp_enqueue_script ( 'jm_cookies_admin_script_raphael', JM_COOKIES_PLUGIN_DIR_URL.'admin/assets/morris/raphael.min.js', array('jquery'), '', true );
				wp_enqueue_script ( 'jm_cookies_admin_script_stats', JM_COOKIES_PLUGIN_DIR_URL.'admin/js/statistics.js', array('jquery'), '', true );
				wp_enqueue_script ( 'jm_cookies_admin_script_monthpicker', JM_COOKIES_PLUGIN_DIR_URL.'admin/assets/jquery.mtz.monthpicker.js', array('jquery'), '', true );
			}

			wp_enqueue_script ( 'jm_cookie_admin_script', JM_COOKIES_PLUGIN_DIR_URL.'admin/js/scripts.js', array('jquery', 'wp-color-picker'), '', true );
			wp_enqueue_style( 'jm_cookie_admin_style', JM_COOKIES_PLUGIN_DIR_URL.'admin/css/style.css', false, '1.0.0' );

		}

		/*
		* Show the plugin menu
		 */
		function jm_cookies_admin_menu() {
			$output = '
			<h2 class="nav-tab-wrapper">
					<a href="?page=jm-cookies" class="nav-tab ';
					$_GET['page'] == 'jm-cookies' ? $output .= 'nav-tab-active' : '';
					$output .= '">Cookie '.__('List').'</a>
					<a href="?page=cookie" class="nav-tab ';
					$_GET['page'] == 'cookie' && empty($_GET['cookie']) ? $output .= 'nav-tab-active' : '';
					$output .= '">'.__('Add').' cookie</a>
					<a href="?page=cookie-faq" class="nav-tab ';
					$_GET['page'] == 'cookie-faq' ? $output .= 'nav-tab-active' : '';
					$output .= '">'.__('FAQ').'</a>';

					if ($_GET['cookie']) {
						$output .= '<a href="?page=cookie&action=edit&cookie='.$_GET['cookie'].'" class="nav-tab ';
						$_GET['page'] == 'cookie' && $_GET['action'] == 'edit' ? $output .= 'nav-tab-active' : '';
						$output .= '">'.__('Edit').' Cookie</a>
						<a href="?page=tracking&cookie='.$_GET['cookie'].'" class="nav-tab ';
						$_GET['page'] == 'tracking' && !empty($_GET['cookie']) ? $output .= 'nav-tab-active' : '';
						$output .= '">'.__('Tracking').'</a>';
					}
					$output .= '
			</h2>
			<br>
			';

			return $output;
		}


    /*
    * The list of cookies added
    */
    function cookies_list() {
			// $_SESSION['jm_cookies_test'] = 'test';
			$this->list_obj->prepare_items();

      $this->plugin_admin_header('<small>Cookie list</small> <a href="'.get_site_url().'/wp-admin/admin.php?page=cookie" class="page-title-action">'.__('Add new').'</a>','This is the list of cookies added.');

			if (!empty($_SESSION['jm_cookies_message']))
			{
				echo '<div id="message" class="updated"><p>'.$_SESSION['jm_cookies_message'].'</p></div>';
				unset($_SESSION['jm_cookies_message']);
			}

			echo '<div class="meta-box-sortables ui-sortable"><form method="post">';
			$this->list_obj->display();
			echo '</form></div>';

      $this->plugin_admin_footer();

    }

		/**
		 * Delete a cookie.
		 *
		 * @param int $id ID
		 */
		public static function delete_cookie( $id ) {
			global $wpdb;
			$wpdb->show_errors();
			$wpdb->delete(
				"{$wpdb->prefix}jm_cookies",
				array( 'id' => $id ),
				[ '%d' ]
			);
			if($wpdb->last_error !== '')
			{
				$wpdb->print_error();
				die;
			}
		}

		/*
		* Catch delete cookie requests
		 */
		function jm_cookie_delete() {
			//Detect when a bulk action is being triggered...
			if ( $_GET['action'] == 'delete-cookie' ) {

				// In our file that handles the request, verify the nonce.
				$nonce = esc_attr( $_REQUEST['_wpnonce'] );

				if ( ! wp_verify_nonce( $nonce, 'jm_delete_cookie' ) ) {
					die( 'Go get a life!' );
				}
				else {

					self::delete_cookie( absint( $_GET['cookie'] ) );

					$_SESSION['jm_cookies_message'] = 'Cookie was deleted';

					// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
					wp_redirect( esc_url_raw($_SERVER['HTTP_REFERER']) );
					exit;
				}

			}

			// If the delete bulk action is triggered
			if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
					 || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
			) {

				$delete_ids = esc_sql( $_POST['bulk-delete'] );

				// loop over the array of record IDs and delete them
				foreach ( $delete_ids as $id ) {
					self::delete_cookie( $id );

				}

				$_SESSION['jm_cookies_message'] = count($delete_ids).' Cookies was deleted';

				// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
							// add_query_arg() return the current url
						 wp_redirect( esc_url_raw(add_query_arg()) );
				// header("Location: ".esc_attr( $_REQUEST['page'] ));
				exit;
			}

		}

		/**
		 * Screen options
		 */
		public function screen_option() {

			$option = 'per_page';
			$args   = [
				'label'   => 'Cookies',
				'default' => 2,
				'option'  => 'cookies_per_page',
				'label' => 'Cookies'
			];

			add_screen_option( $option, $args );

			$this->list_obj = new Cookie_List();
		}

    /*
    * Add cookie
     */
    function add_cookie() {

			global $wpdb;

			// Define table name
			$table_name = $wpdb->prefix.'jm_cookies';

			$description = 'Activate the preview and style your cookie while looking at it. you will find the cookie preview at the bottom of the page.<br />';



			$content = '';

			if ($_GET['cookie'])
			{
				$sq = "
    		SELECT 			*
    		FROM 				".$table_name."
				WHERE 			id = '".(int)$_GET['cookie']."'
    		";
    		$cookie = $wpdb->get_results($sq);
				if($wpdb->last_error !== '')
				{
					$wpdb->print_error();
				}
				else
				{
					$cookie = $cookie[0];
					$title = __('Edit cookie');
					$description .= __('Edit the cookie by filling out all fields marked with a *.', 'jmcookies');
					$edit_input = '<input type="hidden" name="edit" value="'.$_GET['cookie'].'" />';
					$button = __('Edit');
					// $content .= print_r($cookie, true);
				}
			}
			else {
				$title = __('Add cookie');
				$description .= __('Add a new cookie by filling out all fields marked with a *.');
				$edit_input = '';
				$button = __('Add');
			}

			if (!empty($_SESSION['jm_cookies_notice']))
			{
				$content .= '<div id="notice" class="error"><p>'.$_SESSION['jm_cookies_notice'].'</p></div>';
				unset($_SESSION['jm_cookies_notice']);
			}

  		if (!empty($_SESSION['jm_cookies_message']))
			{
				$content .= '<div id="message" class="updated"><p>'.$_SESSION['jm_cookies_message'].'</p></div>';
				unset($_SESSION['jm_cookies_message']);
			}

			// Fetch all user roles
			global $wp_roles;
      $roles = $wp_roles->get_names();

			// Define Headline size
			$headline_size = array(
				'H1' => __('Heading').' 1',
				'H2' => 'Heading 2',
				'H3' => 'Heading 3',
				'H4' => 'Heading 4',
				'strong' => __('Bold')
			);

			$placements = array(
				'hidden' => __('Hidden'),
				'fullscreen' => __('Fullscreen'),
				'topleft' => __('Top').' '.__('Left'),
				'top' => __('Top'),
				'topright' => __('Top').' '.__('Right'),
				'right' => __('Right'),
				'bottomleft' => __('Bottom').' '.__('Left'),
				'bottom' => __('Bottom'),
				'bottomright' => __('Bottom').' '.__('Right'),
				'left' => __('Left'),
			);

			$transitions = array(
				'fadeOut' => __('Fadeout'),
				'slideDown' => __('SlideDown'),
				'slideUp' => __('SlideUp'),
			);

			$pages = get_pages( array('post_type' => 'page','post_status' => 'publish') );

			$image = ' button">Upload image';
			$image_size = '150x150'; // it would be better to use thumbnail size here (150x150 or so)
			$display = 'none'; // display state ot the "Remove image" button

			if( $image_attributes = wp_get_attachment_image_src( $value, $image_size ) ) {

				// $image_attributes[0] - image URL
				// $image_attributes[1] - image width
				// $image_attributes[2] - image height

				$image = '"><img src="' . $image_attributes[0] . '" style="max-width:15%;display:block;" />';
				$display = 'inline-block';

			}

			$option_name = 'header_img';

			$this->plugin_admin_header('<small>'.$title.'</small>',$description);
			echo '<form name="cookieform" id="cookieform" method="post" action="'.esc_url( admin_url('admin-post.php') ).'" >';


			$content .= '
			<input type="hidden" name="action" value="cookie_form">
			<input type="hidden" name="nonce" value="'.wp_create_nonce(basename(__FILE__)).'"/>
			'.$edit_input.'

			<table class="form-table">
				<tr>
					<th scope="row"><label for="cookie_status">Active cookie</label></th>
					<td><input type="checkbox" name="cookie_status" id="cookie_status" value="1" ';
					if ($cookie->status) $content .= 'checked';
					$content .= ' /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_tracking">Tracking</label></th>
					<td><input type="checkbox" name="cookie_tracking" id="cookie_tracking" value="1" ';
					if ($cookie->tracking) $content .= 'checked';
					$content .= ' /></td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">Do you want to track the cookie?</div></span></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_close_accept">Accept cookie on close click</label></th>
					<td><input type="checkbox" name="cookie_close_accept" id="cookie_close_accept" value="1" ';
					if ($cookie->close_accept) $content .= 'checked';
					$content .= ' /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_button_accept">Accept cookie on button click</label></th>
					<td><input type="checkbox" name="cookie_button_accept" id="cookie_button_accept" value="1" ';
					if ($cookie->button_accept) $content .= 'checked';
					$content .= ' /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_view_accept">Accept cookie on view</label></th>
					<td><input type="checkbox" name="cookie_view_accept" id="cookie_view_accept" value="1" ';
					if ($cookie->view_accept) $content .= 'checked';
					$content .= ' /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_hide_close_button">Hide close button</label></th>
					<td><input type="checkbox" name="cookie_hide_close_button" id="cookie_hide_close_button" value="1" class="cookie_preview_action" ';
					if ($cookie->hide_close_button) $content .= 'checked';
					$content .= ' /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_show_on_page">Show on page</label></th>
					<td><select name="cookie_show_on_page" id="cookie_show_on_page">
						<option value="">'.__('All').'</option>
						';
					  foreach ( $pages as $page ) {
					  	$content .= '<option value="' . get_page_link( $page->ID ) . '"';
							if ($cookie->show_on_page==get_page_link( $page->ID )) $content .= ' selected';
							$content .= '>'.$page->post_title.'</option>';
					  }
						$content .= '
					</select></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_display_duration">Display duration</label></th>
					<td><input type="number" min="0" name="cookie_display_duration" id="cookie_display_duration" value="'.$cookie->display_duration.'" /> seconds</td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">If set to zero it will not be effected.</div></span></td>
				</tr>
				<tr>
					<th scope="row"><label>Cookie Expiration</label></th>
					<td><input type="number" min="-1" name="cookie_expire" id="cookie_expire" class="" value="'.sanitize_text_field($cookie->expire).'" /></td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">If left empty it will never expire (10 years). If you set the date to before now, the next time the users visits your website the cookie will be removed and will not be displayed.</div></span></td>
				</tr>
				<!--
				<tr>
					<th scope="row"><label>Cookie Expiration</label></th>
					<td><input type="text" name="cookie_expire" id="cookie_expire" class="datepicker" value="'.sanitize_text_field($cookie->expire).'" readonly /> <a href="" class="reset-datepicker">Reset</a></td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">If left empty it will never expire (10 years). If you set the date to before now, the next time the users visits your website the cookie will be removed and will not be displayed.</div></span></td>
				</tr>
				<tr>
					<th scope="row"><label>User roles to be effected</label></th>
					<td><select name="cookie_user_role[]" class="select2 large-text" multiple="multiple">
						';
						foreach ($roles AS $role)
						{
							$content .= '<option value="'.$role.'"';
							if (strpos($cookie->user_roles, $role) !== false) $content .= ' selected';
							$content .= '>
								'.$role.'
							</option>';
						}
						$content .= '
					</select></td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">If you select all roles, all looged in users will be effected. If none selected all roles will be effected.</div></span></td>
				</tr>
				-->
				<tr>
					<th scope="row"><label for="cookie_only_logged_in">Show only to logged in users</label></th>
					<td><input type="checkbox" name="cookie_only_logged_in" id="cookie_only_logged_in" value="1" ';
					if ($cookie->only_logged_in) $content .= 'checked';
					$content .= ' /></td>
				</tr>
				<!--
				<tr>
					<th scope="row"><label for="cookie_dock">Dock Cookie</label></th>
					<td><input type="checkbox" name="cookie_dock" id="cookie_dock" value="1" ';
					if ($cookie->dock) $content .= 'checked';
					$content .= ' /></td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">Leave a docking icon to expand the cookie notification after closing it.</div></span></td>
				</tr>
				-->
				<tr>
					<td>&nbsp;</td>
					<td style="text-align: right"><input type="submit" class="button-primary" name="button" id="button" value="'.sanitize_text_field($button).' Cookie" /></td>
				</tr>
			</table>
			';

			$this->plugin_admin_box('Cookie '.__('settings'),$content);

      $content = '
      <table class="form-table">
				<tr>
					<th scope="row"><label for="cookie_container_class">Cookie container</label></th>
					<td><input type="text" name="cookie_container_class" id="cookie_container_class" class="large-text" value="'.sanitize_text_field($cookie->container_class).'" /></td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">Wrap the cookie in a container class that matches your designs content width.</div></span></td>
				</tr>
				<tr>
					<th scope="row"><label>Cookie Name *</label></th>
					<td><input type="text" name="cookie_name" id="cookie_name" class="large-text" value="'.sanitize_text_field($cookie->name).'" required';
					if (!empty($_GET['cookie'])) $content .= ' readonly';
					$content .= ' /></td>
					<td>';
					if (!empty($_GET['cookie'])) $content .= '<span class="jm-cookie-badge tooltip">?<div class="tooltiptext">Cookie name cant be changed</div></span>';
					$content .= '</td>
				</tr>
				<tr>
					<th scope="row"><label>Cookie Headline</label></th>
					<td><input type="text" name="cookie_headline" id="cookie_headline" class="large-text" value="'.sanitize_text_field($cookie->headline).'" /></td>
				</tr>
				<tr>
					<th scope="row"><label>Headline size</label></th>
					<td><select name="cookie_headline_size" id="cookie_headline_size" class="cookie_preview_action">
						<option value="">'.__('None').'</option>
						';
						foreach ($headline_size AS $key => $val)
						{
							$content .= '
							<option value="'.$key.'"';
							if ($cookie->headline_size==$key) $content .= ' selected';
							$content .= '>'.$val.'</option>
							';
						}
						$content .= '
					</select></td>
					<td></td>
				</tr>
				<tr>
					<th scope="row"><label>Content</label></th>
					<td><textarea name="cookie_content" id="cookie_content" rows="3" class="large-text code">'.sanitize_text_field($cookie->content).'</textarea></td>
				</tr>
				<tr>
					<th scope="row"><label>Cookie Button Url</label></th>
					<td><select name="cookie_url" id="cookie_url">
						<option value="">'.__('All').'</option>
						';
					  foreach ( $pages as $page ) {
					  	$content .= '<option value="' . get_page_link( $page->ID ) . '"';
							if ($cookie->url==get_page_link( $page->ID )) $content .= ' selected';
							$content .= '>'.$page->post_title.'</option>';
					  }
						$content .= '
					</select></td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">Link to read about the cookie</div></span></td>
				</tr>
				<tr>
					<th scope="row"><label for="jm_cookie_external_url">Cookie Button external link</label></th>
					<td><input type="text" name="jm_cookie_external_url" id="jm_cookie_external_url" class="large-text" value="'.sanitize_text_field($cookie->external_url).'" /></td>
					<td></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_button">Cookie Button Text</label></th>
					<td><input type="text" name="cookie_button" id="cookie_button" class="large-text cookie_preview_action" value="'.sanitize_text_field($cookie->button).'" /></td>
					<td></td>
				</tr>
				<!--
				<tr>
					<th scope="row"><label>Upload image</label></th>
					<td>
						<a href="#" class="cookie_upload_image_button' . $image . '</a>
						<input type="hidden" name="cookie_image" id="cookie_image" value="' . $value . '" />
						<a href="#" class="misha_remove_image_button" style="display:inline-block;display:' . $display . '">Remove image</a>
					</td>
				</tr>
				-->
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right"><input type="submit" class="button-primary" name="button" id="button" value="'.sanitize_text_field($button).' Cookie" /></td>
        </tr>
      </table>
      ';

			$this->plugin_admin_box('Cookie '.__('content'),$content);

			$content = '

			<table class="form-table">
				<tr>
					<th scope="row"><label>Cookie design *</label></th>
					<td>
						<fieldset>
						<label><input type="radio" name="cookie_design" id="cookie_design_1" class="cookie_preview_action" value="big" ';
							if ($cookie->design=='big' || $cookie->design=="") $content .= ' checked';
							$content .= ' /> '.__('Big').'</label><br />
							<label><input type="radio" name="cookie_design" id="cookie_design_2" class="cookie_preview_action" value="small" ';
							if ($cookie->design=='small') $content .= ' checked';
							$content .= ' /> '.__('Small').'</label>
						</fieldset>
					</td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">For small cookie designs, only the headline and close button will be shown.</div></span></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_heading">Cookie placement *</label></th>
					<td>
						<div class="placements">
							<div class="jm-cookies-placement">
								<label class="jm-cookies-placement-inner" title="Top left"><input type="radio" align="top" name="cookie_heading" value="topleft" class="cookie_preview_action"';
								if ($cookie->heading=='topleft') $content .= ' checked';
								$content .= ' /></label>
							</div>
							<div class="jm-cookies-placement">
								<label class="jm-cookies-placement-inner" title="Top"><input type="radio" name="cookie_heading" value="top" class="cookie_preview_action"';
								if ($cookie->heading=='top') $content .= ' checked';
								$content .= ' /></label>
							</div>
							<div class="jm-cookies-placement">
								<label class="jm-cookies-placement-inner" title="Top Right"><input type="radio" name="cookie_heading" value="topright" class="cookie_preview_action"';
								if ($cookie->heading=='topright') $content .= ' checked';
								$content .= ' /></label>
							</div>

							<div class="jm-cookies-placement">
								<label class="jm-cookies-placement-inner" title="Left"><input type="radio" name="cookie_heading" value="left" class="cookie_preview_action"';
								if ($cookie->heading=='left') $content .= ' checked';
								$content .= ' /></label>
							</div>
							<div class="jm-cookies-placement">
								<label class="jm-cookies-placement-inner" title="Fullscreen"><input type="radio" name="cookie_heading" value="fullscreen" class="cookie_preview_action"';
								if ($cookie->heading=='fullscreen') $content .= ' checked';
								$content .= ' /></label>
							</div>
							<div class="jm-cookies-placement">
								<label class="jm-cookies-placement-inner" title="Right"><input type="radio" name="cookie_heading" value="right" class="cookie_preview_action"';
								if ($cookie->heading=='right') $content .= ' checked';
								$content .= ' /></label>
							</div>

							<div class="jm-cookies-placement">
								<label class="jm-cookies-placement-inner" title="Bottom left"><input type="radio" name="cookie_heading" value="bottomleft" class="cookie_preview_action"';
								if ($cookie->heading=='bottomleft') $content .= ' checked';
								$content .= ' /></label>
							</div>
							<div class="jm-cookies-placement">
								<label class="jm-cookies-placement-inner" title="Bottom"><input type="radio" name="cookie_heading" value="bottom" class="cookie_preview_action"';
								if ($cookie->heading=='bottom' || $cookie->heading=='') $content .= ' checked';
								$content .= ' /></label>
							</div>
							<div class="jm-cookies-placement">
								<label class="jm-cookies-placement-inner" title="Bottom Right"><input type="radio" align="bottom" name="cookie_heading" value="bottomright" class="cookie_preview_action"';
								if ($cookie->heading=='bottomright') $content .= ' checked';
								$content .= ' /></label>
							</div>
						</div>
					</td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">Where do you want your cookie message placed on the page?</div></span></td>
				</tr>
				<!--
				<tr>
					<th scope="row"><label for="cookie_heading">Cookie placement *</label></th>
					<td><select name="cookie_heading" id="cookie_heading" class="cookie_preview_action" required>
						<option value="">'.__('Select').'</option>
						';
						foreach ($placements AS $key => $val)
						{
							$content .= '
							<option value="'.$key.'"';
							if ($cookie->heading==$key) $content .= ' selected';
							$content .= '>'.$val.'</option>
							';
						}
						$content .= '
					</select></td>
					<td><span class="jm-cookie-badge tooltip">?<div class="tooltiptext">Where do you want your cookie message placed on the page?</div></span></td>
				</tr>
				-->
				<tr>
					<th scope="row" for="cookie_background_color"><label>Cookie Background Color</label></th>
					<td><input type="text" name="cookie_background_color" id="cookie_background_color" class="color-picker cookie_preview_action" data-alpha="true" value="'.sanitize_text_field($cookie->background_color).'" readonly required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_text_color">Cookie Text Color</label></th>
					<td><input type="text" name="cookie_text_color" id="cookie_text_color" class="color-picker cookie_preview_action" value="'.sanitize_text_field($cookie->text_color).'" readonly required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_border_radius">Cookie border radius</label></th>
					<td><input type="number" name="cookie_border_radius" id="cookie_border_radius" class="cookie_spinner cookie_preview_action" value="'.sanitize_text_field($cookie->border_radius).'" readonly /></td>
				</tr>
				<tr>
					<th scope="row" for="cookie_shadow_color"><label>Cookie shadow Color</label></th>
					<td><input type="text" name="cookie_shadow_color" id="cookie_shadow_color" class="color-picker cookie_preview_action" data-alpha="true" value="'.sanitize_text_field($cookie->shadow_color).'" readonly required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_shadow_size">Cookie shadow size</label></th>
					<td><input type="number" name="cookie_shadow_size" id="cookie_shadow_size" class="cookie_spinner cookie_preview_action" value="'.sanitize_text_field($cookie->shadow_size).'" readonly /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_button_background_color">Button background Color</label></th>
					<td><input type="text" name="cookie_button_background_color" id="cookie_button_background_color" class="color-picker cookie_preview_action" value="'.sanitize_text_field($cookie->button_background_color).'" readonly /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_button_text_color">Button text Color</label></th>
					<td><input type="text" name="cookie_button_text_color" id="cookie_button_text_color" class="color-picker cookie_preview_action" value="'.sanitize_text_field($cookie->button_text_color).'" readonly /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_button_border_radius">Button border radius</label></th>
					<td><input type="number" name="cookie_button_border_radius" id="cookie_button_border_radius" class="cookie_spinner cookie_preview_action" value="'.sanitize_text_field($cookie->button_border_radius).'" readonly /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_close_button_size">Close Button size</label></th>
					<td><input type="number" name="cookie_close_button_size" id="cookie_close_button_size" class="cookie_preview_action" value="'.sanitize_text_field($cookie->close_button_size).'" readonly /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_close_button_background_color">Close button background Color</label></th>
					<td><input type="text" name="cookie_close_button_background_color" id="cookie_close_button_background_color" class="color-picker cookie_preview_action" value="'.sanitize_text_field($cookie->close_button_background_color).'" readonly /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_close_button_text_color">Close button text Color</label></th>
					<td><input type="text" name="cookie_close_button_text_color" id="cookie_close_button_text_color" class="color-picker cookie_preview_action" value="'.sanitize_text_field($cookie->close_button_text_color).'" readonly /></td>
				</tr>
				<!--
				<tr>
					<th scope="row"><label for="cookie_transition">Cookie transition</label></th>
					<td><select name="cookie_transition" id="cookie_transition" class="cookie_preview_action">
					<option value="">'.__('Select').'</option>
					';
					foreach ($transitions AS $key => $val)
					{
						$content .= '
						<option value="'.$key.'"';
						if ($cookie->transition==$key) $content .= ' selected';
						$content .= '>'.$val.'</option>
						';
					}
					$content .= '
				</select>
					</td>
				</tr>
				-->
				<tr>
					<td>&nbsp;</td>
					<td style="text-align: right"><a href="'.get_site_url().'/wp-admin/admin.php?page=jm-cookies" class="button-secondary">Cookie '.__('List').'</a> <input type="submit" class="button-primary" name="button" id="button" value="'.$button.' Cookie" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="cookie_preview_checkbox">Preview cookie design</label></th>
					<td><input type="checkbox" name="cookie_preview_checkbox" id="cookie_preview_checkbox" class="cookie_preview_action" value="1" /></td>
				</tr>
				</table>

				<div id="cookie_preview" class="jm-cookie-message-layer">
					<div class="jm-cookie-message-layer-content">
						<span id="preview_headline"></span>
						<a href="" class="jm-cookie-close"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
						viewBox="0 0 500 500">
						  <g>
						    <circle style="" cx="250" cy="250" r="245"></circle>
						    <text x="50%" y="50%" text-anchor="middle" font-size="400" dy=".35em">X</text>
						  </g>
						</svg></a>
						<div id="preview_content"></div>
						<div id="preview_button"></div>
					</div>
				</div>
			';

			$this->plugin_admin_box('Cookie '.__('design'),$content);
			echo '</form>';
      $this->plugin_admin_footer();
    }

		function jm_cookie_form_action() {
			Global $wpdb;

			// Define table name
			$table_name = $wpdb->prefix.'jm_cookies';

			if (!empty($_REQUEST['nonce'])) {
        if (wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {

					// Validate input
					$input_error = '';
					if (empty($_POST['cookie_name'])) $input_error .= __('Cookie name has to be filled', 'jmcookies').'<br />';

					if (empty($input_error))
					{

						$roles = '';
						if (!empty($_POST['cookie_user_role'])) $roles = implode(",",$_POST['cookie_user_role']);

						$expire = $_POST['cookie_expire'];
						if (empty($_POST['cookie_expire'])) $expire = NULL;

						if (!empty($_POST['edit']))
						{
							$wpdb->show_errors();
							// Insert data into DB
							$wpdb->update($table_name,
							  array(
									'status' => (int)$_POST['cookie_status'],
									'tracking' => (int)$_POST['cookie_tracking'],
									'close_accept' => (int)$_POST['cookie_close_accept'],
									'button_accept' => (int)$_POST['cookie_button_accept'],
									'view_accept' => $_POST['cookie_view_accept'],
									'design' => $_POST['cookie_design'],
									'hide_close_button' => $_POST['cookie_hide_close_button'],
									'show_on_page' => $_POST['cookie_show_on_page'],
									'only_logged_in' => $_POST['cookie_only_logged_in'],
									'name' => $_POST['cookie_name'],
									'headline' => $_POST['cookie_headline'],
									'headline_size' => $_POST['cookie_headline_size'],
									'content' => $_POST['cookie_content'],
									'url' => $_POST['cookie_url'],
									'external_url' => $_POST['cookie_external_url'],
									'button' => $_POST['cookie_button'],
									'expire' => $expire,
									'heading' => $_POST['cookie_heading'],
									'user_roles' => $roles,
									'background_color' => $_POST['cookie_background_color'],
									'text_color' => $_POST['cookie_text_color'],
									'border_radius' => $_POST['cookie_border_radius'],
									'button_background_color' => $_POST['cookie_button_background_color'],
									'button_text_color' => $_POST['cookie_button_text_color'],
									'button_border_radius' => (int)$_POST['cookie_button_border_radius'],
									'close_button_size' => (int)$_POST['cookie_close_button_size'],
									'close_button_background_color' => $_POST['cookie_close_button_background_color'],
									'close_button_text_color' => $_POST['cookie_close_button_text_color'],
									'display_duration' => $_POST['cookie_display_duration']
								),
								array("id" => $_POST['edit'])
							);

							// Validate DB result
							if($wpdb->last_error !== '')
							{
								// $wpdb->print_error();
								// die;
								$_SESSION['jm_cookies_notice'] = __('Cookie was not updated!', 'jmcookies');
							}
							else {
								$_SESSION['jm_cookies_message'] = __('Cookie was successfully updated', 'jmcookies');
								// https://codex.wordpress.org/Function_Reference/wp_handle_upload
								// wp_handle_upload( $file, $overrides, $time );
							}

							// $url = esc_url_raw( admin_url( '/admin.php?page=add_cookie&action=edit&cookie='.$_POST['edit'] ) );
						}
						else
						{

							$wpdb->show_errors();
							// Insert data into DB
							$wpdb->query( $wpdb->prepare("
									INSERT INTO $table_name
									( status, tracking, close_accept, button_accept, view_accept, design, show_on_page, hide_close_button, only_logged_in, name, headline, headline_size, content, url, external_url, button, expire, heading, user_roles, background_color, text_color, border_radius, button_background_color, button_text_color, button_border_radius, close_button_size, close_button_background_color, close_button_text_color, display_duration, created )
									VALUES ( %d, %d, %d, %d, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %d, %d, %s, %s, %s, NOW() )
								",
							  array(
									(int)$_POST['cookie_status'],
									(int)$_POST['cookie_tracking'],
									(int)$_POST['cookie_close_accept'],
									(int)$_POST['cookie_button_accept'],
									$_POST['cookie_view_accept'],
									$_POST['cookie_design'],
									$_POST['cookie_show_on_page'],
									$_POST['cookie_hide_close_button'],
									$_POST['cookie_only_logged_in'],
									$_POST['cookie_name'],
									$_POST['cookie_headline'],
									$_POST['cookie_headline_size'],
									$_POST['cookie_content'],
									$_POST['cookie_url'],
									$_POST['cookie_external_url'],
									$_POST['cookie_button'],
									$expire,
									$_POST['cookie_heading'],
									$roles,
									$_POST['cookie_background_color'],
									$_POST['cookie_text_color'],
									$_POST['cookie_border_radius'],
									$_POST['cookie_button_background_color'],
									$_POST['cookie_button_text_color'],
									$_POST['cookie_button_border_radius'],
									$_POST['cookie_close_button_size'],
									$_POST['cookie_close_button_background_color'],
									$_POST['cookie_close_button_text_color'],
									$_POST['cookie_display_duration'],
								)
							));

							// Validate DB result
							if($wpdb->last_error !== '')
							{
								// $wpdb->print_error();
								// die;
								$_SESSION['jm_cookies_notice'] = __('Cookie was not saved!', 'jmcookies').$wpdb->print_error();
							}
							else {
								$_SESSION['jm_cookies_message'] = __('Cookie was successfully saved', 'jmcookies');
								$url = esc_url_raw( admin_url( '/admin.php?page=cookie&action=edit&cookie='.$wpdb->insert_id ) );
							}
						}
					}
					else $_SESSION['jm_cookies_notice'] = $input_error;
				}
				else $_SESSION['jm_cookies_notice'] = __('No action was taken!', 'jmcookies');
			}

			if (empty($url)) $url = $_SERVER['HTTP_REFERER'];

			wp_safe_redirect( $url );
			exit;
		}

		function tracking()
		{
			$description = 'This tracking graph shows how many visitors have seen the cookie and how many have accepted it.';

			$this->plugin_admin_header('<small>Cookie tracking</small>',$description);
			$this->plugin_admin_box('<input type="hidden" value="'.$_GET['cookie'].'" id="cookie_id" /><span class="dashicons dashicons-chart-bar" style="line-height: 1.4;"></span> Tracking '._('a month').' <input type="text" name="date" id="month_stats_date" value="'.date("Y-m").'" class="form-control monthdatepicker text-center" readonly />','<div id="month_stats" class="statistic_container"></div>');
			$this->plugin_admin_box('<span class="dashicons dashicons-chart-bar" style="line-height: 1.4;"></span> Tracking '._('a day').' <input type="text" name="date" id="day_stats_date" value="'.date("Y-m-d").'" class="form-control daydatepicker text-center" readonly />','<div id="day_stats" class="statistic_container"></div>');
			$this->plugin_admin_footer();
		}

		function jm_cookies_tracking_ajax() {
			global $wpdb;

			$table_name = $wpdb->prefix.'jm_cookies_tracking';

			if ($_GET['type']=='month')
			{
				// Amount of days in this month
				$amount = cal_days_in_month(CAL_GREGORIAN, date("m", strtotime($_GET['date'])), date("Y", strtotime($_GET['date'])));

				// First day of month
				// echo date("Y-m-01", strtotime($_GET['m']));
			}
			else
			{
				// Hours a day
				$amount = 24;
			}

			//Get the first set of data you want to graph from the database
			for($i=0;$i<$amount;$i++)
			{
				$sql = "
				SELECT		COUNT(IF(type=0,1,NULL)) AS amount,
									COUNT(IF(type=1,1,NULL)) AS accepted
				FROM			".$table_name." e
				WHERE			cookie_id = '".(int)$_GET['cookie_id']."'
				";
				if ($_GET['type']=="day")
				{
					// One day
					$sql .= "AND created >= '".date("Y-m-d", strtotime($_GET['date']))." ".date("H:i:s", strtotime("+ ".($i)." hour", 0))."' ";
					$sql .= "AND created <= '".date("Y-m-d", strtotime($_GET['date']))." ".date("H:i:s", strtotime("+ ".($i)." hour", +3599))."' ";
				}
				elseif ($_GET['type']=='month')
				{
					// One month
					$sql .= "AND DATE(created) = '".date("Y-m-d", strtotime("+ ".($i)." days", strtotime(date("Y-m-01", strtotime($_GET['date'])))))."' ";
				}

				// echo $sql."<br />";
				$wpdb->show_errors();
				$r = $wpdb->get_results($sql);
				if($wpdb->last_error !== '')
				{
					$wpdb->print_error();
				}
				else
				{
			    if ($_GET['type']=='month') $x = date("Y-m-d", strtotime("+ ".($i)." days", strtotime(date("Y-m-01", strtotime($_GET['date'])))));
			    else $x = date("H", strtotime("+ ".($i+1)." hour", -3600)).':00';
					//
					$data[] = array (
						"date" => $x,
						"Amount" => (int)$r[0]->amount,
						"Accepted" => (int)$r[0]->accepted,
					);
				}
			}

			//now we can JSON encode our data
			echo json_encode($data);
			wp_die();
		}

		/*
		* FAQ
		 */
		function faq() {
			$content = '
			JM Cookies is a easy to use, yet advanced and very customiziable, if you require a simple cookie notice or a way to track user behavior.
			You can style the cookie notice as you please and set advanced options like tracking for each cookie.<br />
			<br />
			<h1>Features</h1>
			<ul>
				<li>
				Unlimited cookie schedules
				</li>
				<li>
				Realtime design preview in backend
				</li>
				<li>
				Accepts option on either button or close icon
				</li>
				<li>
				Display duration
				</li>
				<li>
				Option to show the notification on the first page only
				</li>
				<li>
				Customizable design and placement
				</li>
				<li>
				Responsive
				</li>
				<li>
				Tracking on impressions and accepted cookies
				</li>
				<li>
				Button link to internal or external page
				</li>
			</ul>
			<br />


			<h1>The Cookie list</h1>
			From the Cookie list you can select to edit the cookie, See tracking for the cookies and delete a cookie.<br />
			<br />

			<h1>Cookie Settings</h1>

			<h4>Roles</h4>
			If you select all roles, all looged in users will be effected. If none selected all roles will be effected.<br />
			<br />

			<h3>Cookie expiration</h3>
			If you want to remove a cookie or force it to expire, you can set the expiration date to a date before today and set the cookie as an invincible cookie.
			And at the next visit to your site the cookie will be forced to expire for the users that already got the cookie.<br />
			<br />

			<h1>Cookie content</h1>

			If the button text is not define, the button will not be shown.<br />
			<br />

			<h1>Styling the cookie</h1>
			Styling the cookie by using stylesheet is very easy.<br />
			<br />

			<h3>What\'s the difference between a big and a small cookie?</h3>
			In the small design only the headline and the close button will be shown. Clicking the close button will accept the cookie.<br />
			<br />

			<h3>Placement</h3>
			An invincible cookie will always be accepted on entering the website.


			';

			$description = '
			Find the answers to unlock the features of the JM Cookies Wordpress plugin here.
			';

			$this->plugin_admin_header('<small>FAQ</small>',$description);
			$this->plugin_admin_box('RTFM',$content);
			$this->plugin_admin_footer();
		}


    function plugin_admin_header($title,$description) {
      echo '
      <div class="wrap">
        <h1>'.$title.'</h1>
        <p>'.$description.'</p>
				'.$this->jm_cookies_admin_menu().'
        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">

      ';
    }

    function plugin_admin_box($title,$content,$right='') {
      echo '
      <div class="postbox">
        <div class="handlediv" style="white-space: nowrap; width: 100px; padding-top: 6px;">'.$right.'</div>
        <h3 class="hndle"><span>'.$title.'</span></h3>
        <div class="inside">
          '.$content.'
        </div>
      </div>
      ';
    }

     function plugin_admin_footer() {
 			?>
        </div>
        <div id="postbox-container-1" class="postbox-container">
          <div class="meta-box-sortables">
           			<div class="postbox">
           				<div class="handlediv"><br></div>
           				<h3 class="hndle" style="text-align: center;"><span>Developer</span></h3>
           				<div class="inside">
           					<div style="text-align: center; margin: auto">
           						I build Wordpress plugins and themes because I love Wordpress.<br />
           						<a href="https://www.jesmadsen.com" target="_blank">Jes Madsen</a>
           					</div>
           				</div>
           			</div>
           			<div class="postbox">
           				<div class="handlediv"><br></div>
           				<h3 class="hndle" style="text-align: center;"><span>Plugin</span></h3>
           				<div class="inside">
           					<div style="text-align: center; margin: auto">
           						Plugin version <?php echo get_option("jm_cookie_plugin_version");?> <br />
           					</div>
           				</div>
           			</div>
           			<div class="postbox">
           				<div class="handlediv"><br></div>
           				<h3 class="hndle" style="text-align: center;">
           					<span>Support Plugin</span>
           				</h3>
           				<div class="inside">
           					<div style="text-align: center; margin: auto">
           						<ul>
           							<li>Support the plugin <a href="https://www.jesmadsen.com/wordpress-plugins/wordpress-cookie-plugin/">WordPress listing</a></li>
           							<li></li>
           						</ul>
           					</div>
           				</div>
           			</div>
              </div>
            </div>
          </div>
        </div>
      </div>
 			<?php
 		}

		/** Singleton instance */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
  }
}

?>
