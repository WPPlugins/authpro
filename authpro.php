<?php
/**
 * @package AuthPro
 */
/*
Plugin Name: AuthPro
Plugin URI: http://www.authpro.com/wordpress.shtml
Description: Adds AuthPro.com remotely hosted service support to your WordPress website. You'll need to <a href="http://www.authpro.com/signup.shtml">signup for AuthPro account</a> if you do not have one yet.
Version: 1.0
Author: yuryk
Author URI: 
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


add_action( 'admin_menu', 'authpro_plugin_menu' );

add_action( 'add_meta_boxes', 'authpro_meta_box' );
add_action( 'save_post', 'authpro_set_post_protection' );
add_action( 'save_page', 'authpro_set_page_protection' );

add_action( 'wp_enqueue_scripts', 'authpro_enqueue_script' );

add_filter( 'plugin_action_links', 'authpro_plugin_action_links', 10, 2 );

// add authpro configuration link
function authpro_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename(__FILE__) ) {
		array_unshift($links, '<a href="' . admin_url( 'options-general.php?page=authpro' ) . '">'.__( 'Settings' ).'</a>');
	}
	return $links;
}

// output Authpro protection script code if needed
function authpro_enqueue_script() {

	if (is_admin()) { return; }
	$authpro_protect='';
	$authpro_usage = get_option('authpro_usage');
	$authpro_username = get_option('authpro_username');
	if ($authpro_usage=='D') { return; }
	if ($authpro_usage=='A') { 
		$authpro_protect='1';
	} else {
		$page_obj = get_queried_object();
		if ( isset($page_obj) && (array_key_exists('post_type', $page_obj)) ) {
			if ( ($page_obj->post_type == 'post') || ($page_obj->post_type == 'page') ) { 
				$authpro_protect = get_post_meta( $page_obj->ID, '_authpro_protect', true );
			}
		}
	}

	if ($authpro_protect=='1') {
		wp_enqueue_script( 'ap-js', 'http://www.authpro.com/auth/' . $authpro_username . '/?action=pp', false );
	}
}

// register metabox
function authpro_meta_box() {
	add_meta_box('authpro_meta_box_id', 'AuthPro page protection', 'authpro_meta_box_content', 'page', 'normal', 'default');
	add_meta_box('authpro_meta_box_id', 'AuthPro post protection', 'authpro_meta_box_content', 'post', 'normal', 'default');
}

// display the metabox
function authpro_meta_box_content( $post ) {
	// nonce field for security check, you can have the same
	// nonce field for all your meta boxes of same plugin
	wp_nonce_field( plugin_basename( __FILE__ ), 'authpro_nonce' );
	$value = get_post_meta( $post->ID, '_authpro_protect', true );
	if ( $value==1 ) { $check='checked'; } else { $check=''; }
	echo '<input type="checkbox" name="authpro_protect" value="1" ' . $check . '/> Protect with AuthPro <br />';
}

function authpro_set_post_protection( $post_id ) {

    // check if this isn't an auto save
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    // security check
    if ( !wp_verify_nonce( $_POST['authpro_nonce'], plugin_basename( __FILE__ ) ) )
        return;

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
    }

    // now store data in custom fields based on checkbox selected
    if ( isset( $_POST['authpro_protect'] ) )
        update_post_meta( $post_id, '_authpro_protect', 1 );
    else
        update_post_meta( $post_id, '_authpro_protect', 0 );
}



function authpro_plugin_menu() {
	add_options_page( 'AuthPro Options', 'AuthPro', 'manage_options', 'authpro', 'authpro_plugin_options' );
}


/*** OPTIONS ***/
function authpro_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	/* Make sure post was from this page */
	if (count($_POST) > 0) {
		check_admin_referer('authpro-options');
	}
		
	if (isset($_POST['update_options'])) {

	        $authpro_usage = $_POST['authpro_usage'];
	        $authpro_username = $_POST['authpro_username'];

        	update_option('authpro_usage', $authpro_usage);
        	update_option('authpro_username', $authpro_username);

		echo '<div class="updated">AuthPro settings updated.</div>';
	}

	$authpro_usage = get_option('authpro_usage');
	$authpro_username = get_option('authpro_username');
	$authpro_usage_set = array_fill_keys(array('D', 'P', 'A'), '');
	$authpro_usage_set[$authpro_usage]='selected';
	?>
	<div class="wrap">
	  <h2><?php echo __('AuthPro Settings','authpro'); ?></h2>
  	  <form action="options-general.php?page=authpro" method="post">
	  <?php wp_nonce_field('authpro-options'); ?>
		<table class="form-table">
		  <tr>
			<th scope="row" valign="top">AuthPro protection:</th>
			<td>
			  <select name="authpro_usage"> <option value='D' <?php echo $authpro_usage_set['D']; ?>>Disabled</option><option value='P' <?php echo $authpro_usage_set['P']; ?>>Selected pages only</option><option value='A' <?php echo $authpro_usage_set['A']; ?>>Entire website/blog</option></select>
			</td>
		  </tr>
		  <tr>
			<th scope="row" valign="top">AuthPro account username:</th>
			<td>
			  <input name="authpro_username" value="<?php echo $authpro_username ?>" type="text" size="20" />
			</td>
		  </tr>
		</table>
		<p class="submit">
			<input name="update_options" type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	  </form>
	  </div>

	<?php
}
?>