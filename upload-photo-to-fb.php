<?php
/*
Plugin Name: Upload photo to facebook
Plugin URI: http://myplugin.hostoi.com/plugin/wordpress-upload-photo-to-facebook/
Description: Upload photo on facebook using wordpress plugin.
Version: 1.0
Author: Sajid
License: GPL2
*/

ob_start();

add_action( 'admin_menu', 'uptf_menu' );

/* get server file size limit */
$maxFileSize = convertBytes( ini_get( 'upload_max_filesize' ) );

echo "<script type='text/javascript'>var allowedFileSize = '".$maxFileSize."';</script>";

/* enqueue the scripts */
function uptf_scripts() {
	// Load jQuery/Css
	wp_enqueue_script('jquery');
	wp_enqueue_script('validate-script', plugins_url( '/js/jquery.validate.js' , __FILE__ ) );
	wp_enqueue_script( 'uptf-script', plugins_url( '/js/uptf_scripts.js' , __FILE__ ) );
	wp_enqueue_style( 'uptf-style', plugins_url( '/css/uptf_style.css' , __FILE__ ) );
}

add_action( 'wp_enqueue_scripts', 'uptf_scripts' );

function uptf_menu() {
	add_options_page( 'uptf Options', 'Upload photo to fb', 'manage_options', 'upload-photo-to-fb', 'uptf_plugin_options' );
}

function uptf_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	//save the app_info in the wp_options table
	if(isset($_POST['uptf_admin_submit_btn'])) {
		$option_name = 'uptf_app_info' ;
		$new_value = $_POST['appid'].' '.$_POST['appsecret'] ;

		if ( get_option( $option_name ) != $new_value ) {
			update_option( $option_name, $new_value );
		} else {
			$deprecated = ' ';
			$autoload = 'no';
			add_option( $option_name, $new_value, $deprecated, $autoload );
		}

		echo '<div class="updated settings-error" id="setting-error-settings_updated"> 
		<p><strong>Settings saved.</strong></p></div>';
	}

	//fetch app info if it already exists in table
	$app_info = explode( " ", get_option('uptf_app_info') );
	?>
	<div class="wrap">
		<div class='icon32' id='icon-options-general'><br></div>
		<h2>Plugin Settings</h2>
		<form method='post'>
			<table class='form-table'>
				<tbody>
					<tr valign='top'>
						<th scope='row'>
							<label for='appid'>Facebook App Id</label>
						</th>
						<td>
							<input type='text' class='regular-text' id='appid' name='appid' value='<?php echo $app_info[0]; ?>'>
						</td>
					</tr>
					<tr valign='top'>
						<th scope='row'>
							<label for='appsecret'>Facebook App Secret</label>
						</th>
						<td>
							<input type='text' class='regular-text' id='appsecret' name='appsecret' value='<?php echo $app_info[1]; ?>'>
						</td>
					</tr>
				</tbody>	
			</table>
			<p class='submit'>
				<input id='submit' class='button-primary' type='submit' value='Save Changes' name='uptf_admin_submit_btn'>
			</p>
		</form>
	</div>
	<p>Please add the shortcode [uptf] in your post/page or you can add this line to use the plugin <code>if( function_exists(uploadphototofb) ) { uploadphototofb(); }</code> in your page template.</p>
	<?php
}

function uploadphototofb() {
	//here we start the session to store the code return by facebook
	session_start();

	//fetch app info from wp_options table
	$app_info = explode( " ", get_option('uptf_app_info') );

	$app_id = $app_info[0];
	$app_secret = $app_info[1];
	$post_login_url = current_page_url();

	$find_code_in_url = strpos($post_login_url, "?code");

	if($find_code_in_url) {
		$post_login_url = substr($post_login_url, 0, ($find_code_in_url));
	}

	//get the code parameter from the url
	if(isset($_REQUEST["code"])) {
		//store the code return by facebook in session variable
		$_SESSION['code'] = $_REQUEST['code'];
	}

	//Obtain the access_token with publish_stream permission
	if( empty($_SESSION['code']) ) {
		$dialog_url= "http://www.facebook.com/dialog/oauth?"
		. "client_id=" .  $app_id 
		. "&redirect_uri=" . urlencode( $post_login_url)
		.  "&scope=publish_stream";

		//login image path
		$login_img =  '<img src="' .plugins_url( 'images/fb.jpeg' , __FILE__ ). '" > ';

		echo "<div id='content'> Login to upload photo: <a href='$dialog_url'>$login_img</a> </div>";
	}
	else {
		if(empty($_SESSION['access_token'])) {
			set_time_limit(0);
			$token_url="https://graph.facebook.com/oauth/access_token?"
			. "client_id=" . $app_id 
			. "&redirect_uri=" . urlencode( $post_login_url)
			. "&client_secret=" . $app_secret
			."&code=".$_SESSION['code'];

			$c = curl_init();
			if(!(is_ssl())) {
				curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			}
	        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($c, CURLOPT_URL, $token_url);
	        $response = curl_exec($c);
	        if($response == false) {
	        	echo 'Curl error: ' . curl_error($c);
	        }
	        curl_close($c);

			$params = null;
			parse_str($response, $params);
			$_SESSION['access_token'] = $params['access_token'];
		}

		// Show photo upload form to user and post to the Graph URL
		$graph_url= "https://graph.facebook.com/me/photos?"
		. "access_token=" .$_SESSION['access_token'];
		?>
		<?php
		//upload a photo to facebook using curl
		if(isset($_POST['uptf_user_submit_btn'])) {

			set_time_limit(0);
			$source = $_FILES["source"]["name"];
			$upload = wp_upload_bits($_FILES['source']['name'], null, file_get_contents($_FILES['source']['tmp_name']));
			$source = $upload['file'];
			$message = $_POST['message'];
			$args = array("message" => $message);
			$args[basename($source)] = '@' . realpath($source);

			$url = $graph_url;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			if(!(is_ssl())) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
			$data = curl_exec($ch);
	    	if($data === false) {
	        	echo 'Curl error: ' . curl_error($ch);
	    	} else {
	    		$msg = '<div style="color:#468847;background-color:#dff0d8;border-color:#d6e9c6;padding:8px 35px 8px 14px;">Photo uploaded successfully.</div>';
	    	}
	  	}
	  	?>
	  	<div id='uptf' style='margin:10px;'>
			<?php
			if( isset($msg) ) :
				echo $msg;
			endif;
			?>
			<form enctype="multipart/form-data" action="" method="post" id="frmuptf" name="frmuptf">
				Please choose a photo: <input name="source" type="file" class="required" id="uptf_source" accept="jpg|jpeg|png|gif"><br/><br/>
				Say something about this photo: <input name="message" type="text" value=""><br/><br/>
				<input type="submit" name="uptf_user_submit_btn" value="Upload"/><br/>
			</form>
		</div>
	  	<?php
	}
}

//shortcode option
add_shortcode( 'uptf', 'uploadphototofb' );

//function used to return current page url
function current_page_url() {
	$pageURL = 'http';
	if( isset($_SERVER["HTTPS"]) ) {
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

function convertBytes( $value ) {
    if ( is_numeric( $value ) ) {
        return $value;
    } else {
        $value_length = strlen( $value );
        $qty = substr( $value, 0, $value_length - 1 );
        $unit = strtolower( substr( $value, $value_length - 1 ) );
        switch ( $unit ) {
            case 'k':
                $qty *= 1024;
                break;
            case 'm':
                $qty *= 1048576;
                break;
            case 'g':
                $qty *= 1073741824;
                break;
        }
        return $qty;
    }
}
?>