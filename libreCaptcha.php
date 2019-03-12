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
	if(!$result){die("Connection Failure");}
	curl_close($curl);
	return $result;
}

function libre_captcha_id($level = "easy", $media = "image", $input_type = "text"){
	$captcha_data = array('level' => $level, 'media' => $media, 'input_type' => $input_type);
	$get_data = libreAPI("http://localhost:8888/v1/captcha/", json_encode($captcha_data));
	$response = json_decode($get_data, TRUE);
	if($response)
		return $response['id'];
	else
		return -1;
}

function libre_get_answer($id, $answer){
	$answer_data = array('id' => $id, 'answer' => $answer);
	$get_answer = libreAPI("http://localhost:8888/v1/answer/", json_encode($answer_data));
	$answer = json_decode($get_answer, TRUE);
	return $answer['result'];
}

function libre_captcha_html(){
	$id = libre_captcha_id();
	$captcha_html = '<div id="LibreCaptcha">
                     <img id="captcha" src="http://localhost:8888/v1/media?id='.$id.'"><br>';
	$captcha_html .= '<input type="hidden" name="Libre_captcha_id" value='.$id.'>
                      <input type="text" name="Libre_captcha_answer"><br>
                      </div>';
    echo $captcha_html;
}

function libre_check_captcha(){
	if(!empty($_POST['Libre_captcha_answer'])){
		$validate = libre_get_answer($_POST['Libre_captcha_id'], $_POST['Libre_captcha_answer']);
		if($validate === "False")
			wp_die("Captcha Incorrect");
	} else {
		wp_die('Captcha empty');
	}
}


register_activation_hook(__FILE__, 'libre_install');
add_action('comment_form', 'libre_captcha_html', 10, 0);
add_filter('pre_comment_approved', 'libre_check_captcha', 5, 0);

?>
