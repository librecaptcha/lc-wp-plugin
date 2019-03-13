<h1>Libre Captcha</h1>
<?php settings_errors(); ?>
<form method="POST" action="options.php">
	<?php settings_fields('libre_captcha_options'); ?>
	<?php do_settings_sections('libre_captcha'); ?>
	<?php submit_button(); ?>
</form>