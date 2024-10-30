<?php
if( !class_exists('jmCookiesFrontend') )
{
	class jmCookiesFrontend {

    function __construct() {

			// Load the javascripts and stylesheets
			add_action( 'wp_enqueue_scripts', array($this, 'jm_cookie_enqueue') );

			// Initialize the script
      add_action( 'init', array($this, 'jm_cookie_check') );

			// Register the tracking ajax function
			add_action( 'wp_ajax_jm_cookies_frontend_tracking_ajax', array($this, 'jm_cookies_frontend_tracking_ajax') );

			add_action( 'wp_ajax_jm_cookies_set_cookie_ajax', array($this, 'jm_cookies_set_cookie_ajax') );

    }

		/*
		* Load script styles and script
		*/
		function jm_cookie_enqueue() {
			wp_enqueue_style( 'jm_cookies_style', JM_COOKIES_PLUGIN_DIR_URL.'frontend/css/style.css' );
			wp_enqueue_script ( 'jm_cookies_scripts', JM_COOKIES_PLUGIN_DIR_URL.'frontend/js/scripts.js', array('jquery'), '', true );
			// Pass the admin-ajax.php url to the javascript file in the object "jm_cookies_ajax_object"
			wp_localize_script( 'jm_cookies_scripts', 'jm_cookies_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}

		/*
		* Let's check if there's any cookies in the jar.
		 */
    function jm_cookie_check()
    {
			global $wpdb, $cookie_output;

			$table_name = $wpdb->prefix.'jm_cookies';

      // Fetch cookies
			$sq = "
			SELECT 			*
			FROM 				".$table_name."
			WHERE 			status = '1'
			";
			$cookie = $wpdb->get_results($sq);
			if($wpdb->last_error !== '')
			{
				$wpdb->print_error();
			}
			else
			{
				if (count($cookie)>0)
				{
					$cookie_output = array();
					for($i=0;$i<count($cookie);$i++)
					{
						// If expire is empty then set it to 10 years ahead
						if (empty($cookie[$i]->expire)) $expire = time() + (3600*24*385*10);
						else $expire = strtotime($cookie[$i]->expire);

						// Define cookie name
						$cookie_name = sanitize_title($cookie[$i]->name);

						// If the cookie is not already set
						if (empty($_COOKIE[$cookie_name]) || (!empty($_COOKIE[$cookie_name]) && $cookie[$i]->expire<date("Y-m-d")))
						{

							// By default all users will be shown the cookies
							$access = true;

							/*
							The user roles function will be used in next version

							// Logged in user role(s)
			        $user = wp_get_current_user();

							// if (is_user_logged_in()) echo '<pre>'.print_r(current($user->roles), true).'</pre>';

							// Convert the user roles from db into an array
							$userroles = array();
							// if (!empty($cookie[$i]->user_roles)) $userroles = implode(',',$cookie[$i]->user_roles);

							// If the cookie has user roles defined then check for user roles
			        // if (in_array( $user->roles, $userroles ))
							*/

							// If the Only logged in users option is checked and the user is NOT logged in then dont display the cookie
							if (!empty($cookie[$i]->only_logged_in) && !is_user_logged_in()) $access = false;

							if ($access)
			        {
			          // If cookie expire is above today or it´s empty then output cookie
								if ($cookie[$i]->expire>date("Y-m-d") || empty($_COOKIE[$cookie_name])) $cookie_output[$cookie[$i]->id] = $cookie[$i];

								// If the cookie exporation is below now, then remove the cookie
								if ($cookie[$i]->expire<date("Y-m-d") && !empty($_COOKIE[$cookie_name])) setcookie($cookie_name, true, $expire, "/", $_SERVER['SERVER_NAME'], 1);

			        }
			      }
					}
					// If there´s a cookie availabel then insert it in the WP footer
					if (count($cookie_output)>0) add_action('wp_footer', array($this, 'jm_cookies_footer'));
				}
			}
    }

		/*
		* Big cookie design
		 */
		function cookie_design_big($cookie) {
			$output = '<div class="jm-cookie-message-layer-content">
				<span><'.$cookie->headline_size.'>'.$cookie->headline.'</'.$cookie->headline_size.'></span>';
				// If show close button is not checked
				if (empty($cookie->hide_close_button))
				{
					$output .= '
					<a href="" class="jm-cookie-close" data-accept="'.$cookie->close_accept.'"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" style="width: '.$cookie->close_button_size.'; height: '.$cookie->close_button_size.'; shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
					viewBox="0 0 500 500">
						<g id="UrTavla">
							<circle fill="'.$cookie->close_button_background_color.'" cx="250" cy="250" r="245"></circle>
							<text x="50%" y="50%" text-anchor="middle" font-size="400" dy=".35em" fill="'.$cookie->close_button_text_color.'">X</text>
						</g>
					</svg></a>';
				}
				$output .= '
				<div>'. $cookie->content.'</div>';
				// If button text is not defined
				if (!empty($cookie->button))
				{
					$output .= '
					<a href="'.esc_url($cookie->url).'" class="jm-cookie-button" style="background-color: '.$cookie->button_background_color.'; color: '.$cookie->button_text_color.'; border-radius: '.$cookie->button_border_radius.'px;" data-accept="'.$cookie->button_accept.'">'.$cookie->button.'</a>';
				}
				$output .= '
			</div>';

			return $output;
		}

		/*
		* Small cookie design
		 */
		function cookie_design_small($cookie) {
			$output = '<div class="jm-cookie-message-layer-content">
				<span>'.$cookie->headline.'</span>
				<a href="" class="jm-cookie-close jm-cookie-accept" data-accept="'.$cookie->close_accept.'"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"  style="width: '.$cookie->close_button_size.'; height: '.$cookie->close_button_size.'; shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
				viewBox="0 0 500 500">
					<g id="UrTavla">
						<circle fill="'.$cookie->close_button_background_color.'" cx="250" cy="250" r="245"></circle>
						<text x="50%" y="50%" text-anchor="middle" font-size="400" dy=".35em" fill="'.$cookie->close_button_text_color.'">X</text>
					</g>
				</svg></a>
			</div>';

			return $output;
		}

		/*
    * Insert the cookie divs in the WP footer
     */
		function jm_cookies_footer($cookie)
		{
			Global $cookie_output;

			// echo '<pre>'.print_r($cookie_output, true).'</pre>';

			if (count($cookie_output)>0)
			{
				// print_r( $cookie_output );
				foreach($cookie_output AS $k => $v)
				{
					// If the headline size is empty then set it to bold, just to make sure.
					if (empty($cookie_output[$k]->headline_size)) $cookie_output[$k]->headline_size = 'strong';

					// Tracking var
					$tracking='';
					if (!empty($cookie_output[$k]->tracking)) $tracking = ' cookie_tracking';

					echo '<div class="jm-cookie-message-layer '.$cookie_output[$k]->design.' '.$cookie_output[$k]->heading.$tracking.'" data-id="'.$cookie_output[$k]->id.'" style="background-color: '.$cookie_output[$k]->background_color.'; color: '.$cookie_output[$k]->text_color.'; border-radius: '.$cookie_output[$k]->border_radius.'px;">';

					// Heading output
					if ($cookie_output[$k]->design=="hidden") echo '';
					elseif ($cookie_output[$k]->design=="big") echo $this->cookie_design_big($cookie_output[$k]);
					else echo $this->cookie_design_small($cookie_output[$k]);

					echo '</div>';
				}
			}
		}

		/*
		* Set cookie on user accept
		 */
		function jm_cookies_set_cookie_ajax() {
			// print_r($_POST);

			Global $wpdb, $cookie_output;

			// If expire is empty then set it to 10 years ahead
			if (empty($cookie[$i]->expire)) $expire = time() + (3600*24*385*10);
			else $expire = strtotime($cookie[$i]->expire);

			// Define cookie name
			$cookie_name = sanitize_title($cookie_output[$_POST['id']]->name);

			if ($_POST['accept'])
			{
				// Tracking accept
				$table_name = $wpdb->prefix.'jm_cookies_tracking';
				$wpdb->show_errors();
				$wpdb->insert($table_name,array('cookie_id'=>$_POST['id'],'type'=>'1', 'created'=>current_time('mysql', 1)),array('%d','%d','%s'));
				if($wpdb->last_error !== '')
				{
					$wpdb->print_error();
				}

				// Set cookie
				setcookie($cookie_name, true, $expire, "/", $_SERVER['SERVER_NAME'], 1);

			}
			wp_die();
		}

		/*
		* Tracking ajax function
		*/
		function jm_cookies_frontend_tracking_ajax() {
			Global $wpdb;

			// Track impression
			$table_name = $wpdb->prefix.'jm_cookies_tracking';
			$wpdb->show_errors();
			$wpdb->insert($table_name,array('cookie_id'=>$_POST['id'],'created'=>current_time('mysql', 1)),array('%d','%s'));
			if($wpdb->last_error !== '')
			{
				$wpdb->print_error();
			}

			wp_die();
		}
  }
}

?>
