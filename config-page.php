<?php
function band_session_master_options() {
	$hidden_field_name = 'bsmaster_submit_hidden';
	$option_name_of = array(
		"client_id"     => "bsmaster_google_client_id",
		"client_secret" => "bsmaster_google_client_secret",
		"access_token" => "bsmaster_google_access_token",
	);

	$option_value_of = array();
	foreach ($option_name_of as $opt_field => $opt_id) {
		$option_value_of[$opt_field] = get_option($opt_id);
	}

	if( isset($_POST[ $hidden_field_name ]) 
	    && $_POST[ $hidden_field_name ] == 'Y' ) {
		foreach ($option_name_of as $opt_field => $opt_id) {
			update_option($opt_id,
				      stripslashes($_POST[$opt_field]) );
			$option_value_of[$opt_field] = stripslashes($_POST[$opt_field]);
		}
		// Put an settings updated message on the screen

?>
<div class="updated"><p><strong><?php _e('settings saved.', 'menu-test' ); ?></strong></p></div>
<?php

	}

?>

<div class="wrap">
  <h2>Band Session Master Configuration</h2>

  <form name="bsm-main-form" method="post" action="">
  <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

  <p>Google Client ID: 
    <input type="text" name="client_id" value="<?php echo $option_value_of['client_id']; ?>" size="60">
  </p>

  <p>Google Client Secret: 
    <input type="text" name="client_secret" value="<?php echo $option_value_of['client_secret']; ?>" size="60">
  </p>

  <p>Google Accesss Token:
    <textarea name="access_token" rows="4" cols="60"><?php echo $option_value_of['access_token']; ?></textarea>
  </p>

  <hr />

  <p class="submit">
    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
  </p>

</form>
</div>

<?php
}


