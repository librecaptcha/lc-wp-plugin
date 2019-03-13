<?php
/*
Plugin Name: Libre-Captcha
Plugin URI: https://github.com/librecaptcha/lc-wp-plugin/
Description: An open source solution to CAPTCHAS
Author: Rahul Rudragoudar
Author URI: https://github.com/rr83019 
Version: 0.1
*/

function libre_install(){
	if(is_multisite()){
        wp_die('Libre-Captcha does not yet support WordPress MultiSite.');
    }
	defined( 'ABSPATH' ) or die('Access Unauthorized');
}

function libreAPI($url, $data){
    $curl = curl_init();

    if ($data)
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    else
        curl_setopt($curl, CURLOPT_POST, 1);

	curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($curl);
	if(!$result){die("Connection_failure");}
	curl_close($curl);
	return $result;
}

function libre_captcha_id($lc_url,$level = "easy", $media = "image", $input_type = "text"){
	$captcha_data = array('level' => $level, 'media' => $media, 'input_type' => $input_type);
	$get_data = libreAPI($lc_url.'/v1/captcha/', json_encode($captcha_data));
	$response = json_decode($get_data, TRUE);
	if($response)
		return $response['id'];
	else
		return -1;
}

function libre_get_media($id, $lc_url){
	$media_data = array('id' => $id);
	$image = libreAPI($lc_url.'/v1/media/', json_encode($media_data));
	return $image;
}

function libre_get_answer($id, $answer, $lc_url){
	$answer_data = array('id' => $id, 'answer' => $answer);
	$get_answer = libreAPI($lc_url.'/v1/answer/', json_encode($answer_data));
	$answer = json_decode($get_answer, TRUE);
	return $answer['result'];
}

function libre_set_lc_url(){
	if(get_option('libre_url')){
		$lc_url = get_option('libre_url');
	} else {
		$lc_url = 'http://localhost:8888';
	}
	return $lc_url;
}

function libre_captcha_html(){
	$lc_url = libre_set_lc_url();
	$id = libre_captcha_id($lc_url);
	if(get_option('libre_url_visible') === '1'){
		$img_tag = '<img id="captcha" src='.get_site_url(null, '?lc_id='.$id).'>';
	} else {
		$img_tag = '<img id="captcha" src="'.$lc_url.'/v1/media?id='.$id.'">';
	}
	$captcha_html = '<div id="LibreCaptcha">
                     '.$img_tag.'<br>';
	$captcha_html .= '<input type="hidden" name="Libre_captcha_id" value='.$id.'>
                      <input type="text" name="Libre_captcha_answer"><br>
                      </div>';
    echo $captcha_html;
}

function libre_check_captcha(){
	$lc_url = libre_set_lc_url();
	if(!empty($_POST['Libre_captcha_answer'])){
		$validate = libre_get_answer($_POST['Libre_captcha_id'], $_POST['Libre_captcha_answer'],$lc_url);
		if($validate === "False")
			wp_die("Captcha Incorrect");
	} else {
		wp_die('Captcha empty');
	}
}

function libre_private_url(){
	$lc_url = libre_set_lc_url();
	if(isset($_GET['lc_id'])){
		$id = $_GET['lc_id'];
		$image = file_get_contents($lc_url.'/v1/media?id='.$id);
		echo $image;
	}
}

function libre_admin_page(){
	add_menu_page('libre_captcha','Libre-Captcha','manage_options','libre_captcha','libre_admin_settings','dashicons-admin-generic',110);
	register_setting('libre_captcha_options', 'libre_url');
	register_setting('libre_captcha_options', 'libre_url_visible');
	add_settings_section('libre_url_options','URL options','libre_url_options','libre_captcha');
	add_settings_field('lc-server-url','URL', 'libre_url_settings','libre_captcha','libre_url_options');
	add_settings_field('lc-url-visible','Private URL','libre_url_visible','libre_captcha','libre_url_options');
}

function libre_admin_settings(){
	require_once plugin_dir_path(__FILE__) . 'templates/libreAdmin.php';
}

function libre_settings_link($links){
	$settings_link = '<a href="admin.php?page=libre_captcha">Settings</a>';
	array_push($links, $settings_link);
	return $links;
}

function libre_url_options(){
	echo "Manage Libre-Captcha Server URL";
}

function libre_url_settings(){
	echo '<input type="text" name="libre_url" value="'.esc_attr(get_option('libre_url')).'" placeholder="http://localhost:8888"/>';
}

function libre_url_visible(){
	echo '<input type="checkbox" name="libre_url_visible" value="1" '.checked(1, get_option('libre_url_visible',1),false).'/>';
}


register_activation_hook(__FILE__, 'libre_install');
add_action('admin_menu','libre_admin_page');
add_action('init','libre_private_url',5,0);
add_filter('plugin_action_links_'.plugin_basename(__FILE__),'libre_settings_link');
add_action('comment_form', 'libre_captcha_html', 10, 0);
add_filter('pre_comment_approved', 'libre_check_captcha', 5, 0);

?>
