<?php
/*
Plugin Name: Members Directory
Plugin URI: http://premium.wpmudev.org/project/members-directory
Description: Provides an automatic list of all the users on your site, with avatars, pagination, a built in search facility and extended customizable user profiles
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
Version: 1.0.9
Network: true
WDP ID: 100
*/

/*
Copyright 2007-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//

$members_directory_base = 'members'; //domain.tld/BASE/ Ex: domain.tld/user/

//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//

register_activation_hook( __FILE__, 'flush_rewrite_rules' );

global $current_blog, $current_site, $members_directory_multisite_query;

$members_directory_multisite_query = ' AND spam != 1 AND deleted != 1';
if($current_site === NULL) {
	class current_site {
	    var $domain;
	    var $path;
	}
	$current_site = new current_site();
	$url_parts = parse_url(get_bloginfo('url'));
	$current_site->domain = $url_parts['host'];
	$current_site->path = $url_parts['path'].'/';
	$current_blog = $current_site;
	$members_directory_multisite_query = '';
}

if ( isset($current_blog) && isset($current_site) && $current_blog->domain . $current_blog->path == $current_site->domain . $current_site->path ){
	add_filter('generate_rewrite_rules','members_directory_rewrite');
	add_filter('the_content', 'members_directory_output', 20);
	add_filter('the_title', 'members_directory_title_output', 99, 2);
	add_action('admin_init', 'members_directory_page_setup');
}

add_action('wpmu_options', 'members_directory_site_admin_options');
add_action('update_wpmu_options', 'members_directory_site_admin_options_process');
add_action( 'network_admin_notices', 'members_directory_site_admin_options_validate_save_notices');

add_action( 'edit_user_profile', 'members_directory_wp_admins_profile' );
add_action( 'show_user_profile', 'members_directory_wp_admins_profile' );
add_action( 'edit_user_profile_update', 'members_directory_wp_admins_profile_save' );
add_action( 'personal_options_update', 'members_directory_wp_admins_profile_save' );

function members_directory_init() {
	load_plugin_textdomain( 'members-directory', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action('plugins_loaded', 'members_directory_init');

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function members_directory_page_setup() {
	global $wpdb, $user_ID, $members_directory_base;

	global $wpmudev_notices;
	$wpmudev_notices[] = array( 'id'=> 100, 'name'=> 'Members Directory', 'screens' => array( 'settings-network' ) );
	include_once( dirname(__FILE__) . '/lib/dash-notices/wpmudev-dash-notification.php' );

	if ( get_site_option('members_directory_page_setup') != 'complete' && is_super_admin() ) {
		$page_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_name = %s AND post_type = 'page'", $members_directory_base));
		if ( $page_count < 1 ) {
			$wpdb->query( $wpdb->prepare("INSERT INTO " . $wpdb->posts . " ( post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count ) VALUES ( %d, '" . current_time( 'mysql' ) . "', '" . current_time( 'mysql' ) . "', '', %s, '', 'publish', 'closed', 'closed', '', %s, '', '', '" . current_time( 'mysql' ) . "', '" . current_time( 'mysql' ) . "', '', 0, '', 0, 'page', '', 0 )", $user_ID, __('Members', 'members-directory'), $members_directory_base ));
		}
		update_site_option('members_directory_page_setup', 'complete');
	}
}

function members_directory_site_admin_options() {
	$members_directory_sort_by = get_site_option('members_directory_sort_by', 'alphabetically');
	$members_directory_per_page = get_site_option('members_directory_per_page', '10');
	$members_directory_background_color = get_site_option('members_directory_background_color', '#F2F2EA');
	$members_directory_alternate_background_color = get_site_option('members_directory_alternate_background_color', '#FFFFFF');
	$members_directory_border_color = get_site_option('members_directory_border_color', '#CFD0CB');
	$members_directory_profile_show_posts = get_site_option('members_directory_profile_show_posts', 'yes');
	$members_directory_profile_show_comments = get_site_option('members_directory_profile_show_comments', 'yes');
	$members_directory_profile_show_friends = get_site_option('members_directory_profile_show_friends', 'yes');
	?>
	<div class="wrap">
		<h3><?php _e('Members Directory', 'members-directory') ?></h3>

			<table class="form-table">
	            <tr valign="top">
	                <th scope="row"><label for="members_directory_sort_by"><?php _e('Sort By', 'members-directory') ?></label></th>
	                <td>
	                    <select name="members_directory_sort_by" id="members_directory_sort_by">
	                       <option value="alphabetically" <?php if ( $members_directory_sort_by == 'alphabetically' ) { echo 'selected="selected"'; } ?> ><?php _e('Username (A-Z)', 'members-directory'); ?></option>
	                       <option value="latest" <?php if ( $members_directory_sort_by == 'latest' ) { echo 'selected="selected"'; } ?> ><?php _e('Newest', 'members-directory'); ?></option>
	                    </select>
	                <br /></td>
	            </tr>
	            <tr valign="top">
	                <th scope="row"><label for="members_directory_sort_by"><?php _e('Listing Per Page', 'members-directory') ?></label></th>
	                <td>
					<select name="members_directory_per_page" id="members_directory_per_page">
					   <option value="5" <?php if ( $members_directory_per_page == '5' ) { echo 'selected="selected"'; } ?> >5</option>
					   <option value="10" <?php if ( $members_directory_per_page == '10' ) { echo 'selected="selected"'; } ?> >10</option>
					   <option value="15" <?php if ( $members_directory_per_page == '15' ) { echo 'selected="selected"'; } ?> >15</option>
					   <option value="20" <?php if ( $members_directory_per_page == '20' ) { echo 'selected="selected"'; } ?> >20</option>
					   <option value="25" <?php if ( $members_directory_per_page == '25' ) { echo 'selected="selected"'; } ?> >25</option>
					   <option value="30" <?php if ( $members_directory_per_page == '30' ) { echo 'selected="selected"'; } ?> >30</option>
					   <option value="35" <?php if ( $members_directory_per_page == '35' ) { echo 'selected="selected"'; } ?> >35</option>
					   <option value="40" <?php if ( $members_directory_per_page == '40' ) { echo 'selected="selected"'; } ?> >40</option>
					   <option value="45" <?php if ( $members_directory_per_page == '45' ) { echo 'selected="selected"'; } ?> >45</option>
					   <option value="50" <?php if ( $members_directory_per_page == '50' ) { echo 'selected="selected"'; } ?> >50</option>
					</select>
	                <br /></td>
	            </tr>
	            <tr valign="top">
	                <th scope="row"><label for="members_directory_background_color"><?php _e('Background Color', 'members-directory') ?></label></th>
	                <td><input name="members_directory_background_color" type="text" id="members_directory_background_color" value="<?php echo esc_attr($members_directory_background_color); ?>" size="20" />
	                <br /><?php _e('Default', 'members-directory') ?>: #F2F2EA</td>
	            </tr>
	            <tr valign="top">
	                <th scope="row"><label for="members_directory_alternate_background_color"><?php _e('Alternate Background Color', 'members-directory') ?></label></th>
	                <td><input name="members_directory_alternate_background_color" type="text" id="members_directory_alternate_background_color" value="<?php echo esc_attr($members_directory_alternate_background_color); ?>" size="20" />
	                <br /><?php _e('Default', 'members-directory') ?>: #FFFFFF</td>
	            </tr>
	            <tr valign="top">
	                <th scope="row"><label for="members_directory_border_color"><?php _e('Border Color', 'members-directory') ?></label></th>
	                <td><input name="members_directory_border_color" type="text" id="members_directory_border_color" value="<?php echo esc_attr($members_directory_border_color); ?>" size="20" />
	                <br /><?php _e('Default', 'members-directory') ?>: #CFD0CB</td>
	            </tr>
	            <?php
	            if (( function_exists('post_indexer_make_current')) || ( class_exists('postindexermodel'))) {
				?>
	            <tr valign="top">
	                <th scope="row"><label for="members_directory_profile_show_posts"><?php _e('Profile: Show Posts', 'members-directory') ?></label></th>
	                <td>
					<select name="members_directory_profile_show_posts" id="members_directory_profile_show_posts">
					   <option value="yes" <?php if ( $members_directory_profile_show_posts == 'yes' ) { echo 'selected="selected"'; } ?> ><?php _e('Yes', 'members-directory'); ?></option>
					   <option value="no" <?php if ( $members_directory_profile_show_posts == 'no' ) { echo 'selected="selected"'; } ?> ><?php _e('No', 'members-directory'); ?></option>
					</select>
	                <br /><?php _e('Show posts on profile pages.', 'members-directory') ?></td>
	            </tr>
	            <?php
				}
	            if ( function_exists('comment_indexer_make_current') ) {
				?>
	            <tr valign="top">
	                <th scope="row"><label for="members_directory_profile_show_comments"><?php _e('Profile: Show Comments', 'members-directory') ?></label></th>
	                <td>
					<select name="members_directory_profile_show_comments" id="members_directory_profile_show_comments">
					   <option value="yes" <?php if ( $members_directory_profile_show_comments == 'yes' ) { echo 'selected="selected"'; } ?> ><?php _e('Yes', 'members-directory'); ?></option>
					   <option value="no" <?php if ( $members_directory_profile_show_comments == 'no' ) { echo 'selected="selected"'; } ?> ><?php _e('No', 'members-directory'); ?></option>
					</select>
	                <br /><?php _e('Show comments on profile pages.', 'members-directory') ?></td>
	            </tr>
	            <?php
				}
				if ( function_exists('friends_add') ) {
				?>
	            <tr valign="top">
	                <th scope="row"><label for="members_directory_profile_show_friends"><?php _e('Profile: Show Friends', 'members-directory') ?></label></th>
	                <td>
					<select name="members_directory_profile_show_friends" id="members_directory_profile_show_friends">
					   <option value="yes" <?php if ( $members_directory_profile_show_friends == 'yes' ) { echo 'selected="selected"'; } ?> ><?php _e('Yes', 'members-directory'); ?></option>
					   <option value="no" <?php if ( $members_directory_profile_show_friends == 'no' ) { echo 'selected="selected"'; } ?> ><?php _e('No', 'members-directory'); ?></option>
					</select>
	                <br /><?php _e('Show friends on profile pages.', 'members-directory') ?></td>
	            </tr>
	            <?php
				}
				?>
			</table>
			<p>
				<a class="button" href="<?php echo add_query_arg(array('members_directory_action' => 'recreate_page', '_wpnonce' => wp_create_nonce('members_directory_action'))); ?>"><?php _e('Recreate Members Page', 'members-directory') ?></a>
			</p>
	</div>
	<?php
}

function members_directory_site_admin_options_process() {
	update_site_option( 'members_directory_sort_by' , $_POST['members_directory_sort_by']);
	update_site_option( 'members_directory_per_page' , $_POST['members_directory_per_page']);
	update_site_option( 'members_directory_background_color' , trim( $_POST['members_directory_background_color'] ));
	update_site_option( 'members_directory_alternate_background_color' , trim( $_POST['members_directory_alternate_background_color'] ));
	update_site_option( 'members_directory_border_color' , trim( $_POST['members_directory_border_color'] ));
	if (( function_exists('post_indexer_make_current') ) || (class_exists('postindexermodel')))
		update_site_option( 'members_directory_profile_show_posts' , $_POST['members_directory_profile_show_posts']);
	if ( function_exists('comment_indexer_make_current') )
		update_site_option( 'members_directory_profile_show_comments' , $_POST['members_directory_profile_show_comments']);
	if ( function_exists('friends_add') )
		update_site_option( 'members_directory_profile_show_friends' , $_POST['members_directory_profile_show_friends']);
}
function members_directory_site_admin_options_validate_save_notices() {
	 {
	}
	if(isset($_REQUEST['members_directory_action']) && isset($_REQUEST['_wpnonce']) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'members_directory_action' )) {
		if($_REQUEST['members_directory_action'] == 'recreate_page') {
			global $wpdb, $members_directory_base;

			$page_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_name = %s AND post_type = 'page'", $members_directory_base));
			if ( $page_count > 0 ) {
				echo '<div id="message" class="updated"><p>'.__( 'Page already exists. Check if its in trash.', 'members-directory' ).'</p></div>';
			}
			else {
				echo '<div id="message" class="updated"><p>'.__( 'Page successfully created.', 'members-directory' ).'</p></div>';
				update_site_option('members_directory_page_setup', '');
			}
		}
	}
}

function members_directory_rewrite($wp_rewrite){
	global $members_directory_base;
    $members_directory_rules = array(
        $members_directory_base . '/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?pagename=' . $members_directory_base,
        $members_directory_base . '/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?pagename=' . $members_directory_base,
        $members_directory_base . '/([^/]+)/([^/]+)/?$' => 'index.php?pagename=' . $members_directory_base,
        $members_directory_base . '/([^/]+)/?$' => 'index.php?pagename=' . $members_directory_base
    );
    $wp_rewrite->rules = $members_directory_rules + $wp_rewrite->rules;
	return $wp_rewrite;
}

function members_directory_url_parse(){
	global $wpdb, $current_site, $members_directory_base;
	$members_directory_url = $_SERVER['REQUEST_URI'];
	if ( $current_site->path != '/' ) {
		$members_directory_url = str_replace('/' . $current_site->path . '/', '', $members_directory_url);
		$members_directory_url = str_replace($current_site->path . '/', '', $members_directory_url);
		$members_directory_url = str_replace($current_site->path, '', $members_directory_url);
	}
	$members_directory_url = ltrim($members_directory_url, "/");
	$members_directory_url = rtrim($members_directory_url, "/");
	$members_directory_url = ltrim($members_directory_url, $members_directory_base);
	$members_directory_url = ltrim($members_directory_url, "/");

	$members_directory_1 = $members_directory_2 = $members_directory_3 = $members_directory_4 = '';
	if( !empty( $members_directory_url ) ) {
		$members_directory_array = explode("/", $members_directory_url);
		for( $i = 1, $j = count( $members_directory_array ); $i <= $j ; $i++ ) {
			$members_directory_var = "members_directory_$i";
			${$members_directory_var} = $members_directory_array[$i-1];
		}
	}

	$page_type = '';
	$page_subtype = '';
	$page = '';
	$user = '';
	$phrase = '';

	if ( empty( $members_directory_1 ) || is_numeric( $members_directory_1 ) ) {
		//landing
		$page_type = 'landing';
		$page = $members_directory_1;
		if ( empty( $page ) ) {
			$page = 1;
		}
	} else if ( $members_directory_1 == 'search' ) {
		//search
		$page_type = 'search';
		$phrase = $_POST['phrase'];
		if ( empty( $phrase ) ) {
			$phrase = $members_directory_2;
			$page = $members_directory_3;
			if ( empty( $page ) ) {
				$page = 1;
			}
		} else {
			$page = $members_directory_3;
			if ( empty( $page ) ) {
				$page = 1;
			}
		}
		$phrase = urldecode( $phrase );
	} else {
		//user
		if ( $members_directory_2 == 'posts' ) {
			$page_type = 'user_posts';
			$user = $members_directory_1;
			$page = $members_directory_3;
			if ( empty( $page ) ) {
				$page = 1;
			}
		} else if ( $members_directory_2 == 'comments' ) {
			$page_type = 'user_comments';
			$user = $members_directory_1;
			$page = $members_directory_3;
			if ( empty( $page ) ) {
				$page = 1;
			}
		} else if ( $members_directory_2 == 'friends' ) {
			$page_type = 'user_friends';
			$user = $members_directory_1;
		} else {
			$page_type = 'user';
			$user = $members_directory_1;
		}
	}

	$members_directory['page_type'] = $page_type;
	$members_directory['page'] = $page;
	$members_directory['user'] = $user;
	$members_directory['phrase'] = $phrase;

	return $members_directory;
}

//------------------------------------------------------------------------//
//---Output Functions-----------------------------------------------------//
//------------------------------------------------------------------------//

function members_directory_title_output($title, $post_ID = '') {
	global $wpdb, $current_site, $post, $members_directory_base, $members_directory_multisite_query;
	if ( in_the_loop() && $post->post_name == $members_directory_base && $post_ID == $post->ID) {
		$members_directory = members_directory_url_parse();
		if ( $members_directory['page_type'] == 'landing' ) {
			if ( $members_directory['page'] > 1 ) {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; ' . '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['page'] . '/">' . $members_directory['page'] . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a>';
			}
		} else if ( $members_directory['page_type'] == 'search' ) {
			if ( $members_directory['page'] > 1 ) {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/">' . __('Search', 'members-directory') . '</a> &raquo; ' . '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/' . urlencode($members_directory['phrase']) .  '/' . $members_directory['page'] . '/">' . $members_directory['page'] . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/">' . __('Search', 'members-directory') . '</a>';
			}
		} else if ( $members_directory['page_type'] == 'user' ) {
			$user_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s".$members_directory_multisite_query, $members_directory['user'] ));
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s", $members_directory['user']));
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/">' . ucfirst($user_details->display_name) . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; ' . __('Invalid User', 'members-directory');
			}
		} else if ( $members_directory['page_type'] == 'user_posts' ) {
			$user_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s".$members_directory_multisite_query, $members_directory['user']));
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s", $members_directory['user']));
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/">' . ucfirst($user_details->display_name) . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/posts/">' . __('Posts', 'members-directory') . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; ' . __('Invalid User', 'members-directory');
			}
		} else if ( $members_directory['page_type'] == 'user_comments' ) {
			$user_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s".$members_directory_multisite_query, $members_directory['user']));
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s", $members_directory['user'] ));
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/">' . ucfirst($user_details->display_name) . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/comments/">' . __('Comments', 'members-directory') . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; ' . __('Invalid User', 'members-directory');
			}
		} else if ( $members_directory['page_type'] == 'user_friends' ) {
			$user_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s".$members_directory_multisite_query, $members_directory['user'] ));
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s", $members_directory['user'] ));
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/">' . ucfirst($user_details->display_name) . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/friends/">' . __('Friends', 'members-directory') . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; ' . __('Invalid User', 'members-directory');
			}
		}
	}
	return $title;
}

function members_directory_output($content) {
	global $wpdb, $current_site, $post, $members_directory_base, $friends_enable_approval, $user_ID, $members_directory_multisite_query;
	if(!isset($friends_enable_approval))
		$friends_enable_approval = 1;

//	echo "members_directory<pre>"; print_r($members_directory); echo "</pre>";
//	die();

	if ( $post->post_name == $members_directory_base ) {
		$members_directory_sort_by = get_site_option('members_directory_sort_by', 'alphabetically');
		$members_directory_per_page = get_site_option('members_directory_per_page', '10');
		$members_directory_background_color = get_site_option('members_directory_background_color', '#F2F2EA');
		$members_directory_alternate_background_color = get_site_option('members_directory_alternate_background_color', '#FFFFFF');
		$members_directory_border_color = get_site_option('members_directory_border_color', '#CFD0CB');
		$members_directory_profile_show_posts = get_site_option('members_directory_profile_show_posts', 'yes');
		$members_directory_profile_show_comments = get_site_option('members_directory_profile_show_comments', 'yes');
		$members_directory_profile_show_friends = get_site_option('members_directory_profile_show_friends', 'yes');
		$members_directory = members_directory_url_parse();
		if ( $members_directory['page_type'] == 'landing' ) {
			$search_form_content = members_directory_search_form_output('', $members_directory['phrase']);
			$navigation_content = members_directory_landing_navigation_output('', $members_directory_per_page, $members_directory['page']);
			$content .= $search_form_content;
			$content .= '<br />';
			$content .= $navigation_content;
			$content .= '<div style="float:left; width:100%">';
			$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
				$content .= '<tr>';
					$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="10%"> </td>';
					$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="90%"><center><strong>' .  $post->post_title . '</strong></center></td>';
				$content .= '</tr>';
				//=================================//
				$avatar_default = get_option('avatar_default');
				$tic_toc = 'toc';
				//=================================//
				if ($members_directory['page'] == 1){
					$start = 0;
				} else {
					$math = $members_directory['page'] - 1;
					$math = $members_directory_per_page * $math;
					$start = $math;
				}

				$query = "SELECT * FROM " . $wpdb->base_prefix . "users WHERE NOT EXISTS (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE user_id = ID AND meta_key = 'members_directory_show_user' AND meta_value = 'no')".$members_directory_multisite_query;
				if ( $members_directory_sort_by == 'alphabetically' ) {
					$query .= " ORDER BY user_login ASC";
				} else {
					$query .= " ORDER BY ID DESC";
				}
				$query .= " LIMIT " . intval( $start ) . ", " . intval( $members_directory_per_page );
				$users = $wpdb->get_results( $query, ARRAY_A );
				//=================================//
				foreach ($users as $user){
					//=============================//
					if ($tic_toc == 'toc'){
						$tic_toc = 'tic';
					} else {
						$tic_toc = 'toc';
					}
					if ($tic_toc == 'tic'){
						$bg_color = $members_directory_alternate_background_color;
					} else {
						$bg_color = $members_directory_background_color;
					}
					//=============================//
					$content .= '<tr>';
						$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px;" valign="top" width="10%"><center><a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user['user_nicename'] . '/">' . get_avatar($user['ID'], 32, $avatar_default) . '</a></center></td>';
						$content .= '<td style="background-color:' . $bg_color . ';" width="90%">';
						$content .= '<a style="text-decoration:none; font-size:1.5em; margin-left:20px;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user['user_nicename'] . '/">' . $user['display_name'] . '</a><br />';
						$content .= '</td>';
					$content .= '</tr>';
                }
				//=================================//
			$content .= '</table>';
			$content .= '</div>';
			$content .= $navigation_content;
		} else if ( $members_directory['page_type'] == 'search' ) {
			//=====================================//
			if ($members_directory['page'] == 1){
				$start = 0;
			} else {
				$math = $members_directory['page'] - 1;
				$math = $members_directory_per_page * $math;
				$start = $math;
			}
			//TODO MAKE SURE IT WORKS
			if ( !empty( $members_directory['phrase'] ) ) {
				$users = $wpdb->get_results( $wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "users WHERE ( user_login LIKE %s OR display_name LIKE %s ) AND NOT EXISTS (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE user_id = ID AND meta_key = 'members_directory_show_user' AND meta_value = 'no')".$members_directory_multisite_query." LIMIT %d, %d", '%'.$members_directory['phrase'].'%', '%'.$members_directory['phrase'].'%', intval( $start ), intval( $members_directory_per_page ) ), ARRAY_A );
			}
			//=====================================//
			$search_form_content = members_directory_search_form_output('', $members_directory['phrase']);
			if ( count( $users ) > 0 ) {
				if ( count( $users ) < $members_directory_per_page ) {
					$next = 'no';
				} else {
					$next = 'yes';
				}
				$navigation_content = members_directory_search_navigation_output('', $members_directory_per_page, $members_directory['page'], $members_directory['phrase'], $next);
			}
			$content .= $search_form_content;
			$content .= '<br />';
			if ( count( $users ) > 0 ) {
				$content .= $navigation_content;
			}
			$content .= '<div style="float:left; width:100%">';
			$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
				$content .= '<tr>';
					$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="10%"> </td>';
					$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="90%"><center><strong>' .  $post->post_title . '</strong></center></td>';
				$content .= '</tr>';
				//=================================//
				$avatar_default = get_option('avatar_default');
				$tic_toc = 'toc';
				$bg_color = $members_directory_alternate_background_color;
				//=================================//
				if ( count( $users ) > 0 ) {
					foreach ($users as $user){
						//=============================//
						if ($tic_toc == 'toc'){
							$tic_toc = 'tic';
						} else {
							$tic_toc = 'toc';
						}

						if ($tic_toc == 'tic'){
							$bg_color = $members_directory_alternate_background_color;
						} else {
							$bg_color = $members_directory_background_color;
						}
						//=============================//
						$content .= '<tr>';
							$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px;" valign="top" width="10%"><center><a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user['user_nicename'] . '/">' . get_avatar($user['ID'], 32, $avatar_default) . '</a></center></td>';
							$content .= '<td style="background-color:' . $bg_color . ';" width="90%">';
							$content .= '<a style="text-decoration:none; font-size:1.5em; margin-left:20px;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user['user_nicename'] . '/">' . $user['display_name'] . '</a><br />';
							$content .= '</td>';
						$content .= '</tr>';
					}
				} else {
					$content .= '<tr>';
						$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px;" valign="top" width="10%"></td>';
						$content .= '<td style="background-color:' . $bg_color . ';" width="90%">' . __('No results...', 'members-directory') . '</td>';
					$content .= '</tr>';
				}
				//=================================//
			$content .= '</table>';
			$content .= '</div>';
			if ( count( $users ) > 0 ) {
				$content .= $navigation_content;
			}
		} else if ( $members_directory['page_type'] == 'user' ) {
			$sql_str = $wpdb->prepare( "SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s AND NOT EXISTS (SELECT meta_value FROM " . $wpdb->base_prefix . "usermeta WHERE user_id = ID AND meta_key = 'members_directory_show_user' AND meta_value = 'no')".$members_directory_multisite_query, $members_directory['user']);
			//echo "sql_str[". $sql_str ."]<br />";
			$user_count = $wpdb->get_var( $sql_str );
			if ( $user_count > 0 ) {
				$message = '';
				if ( $members_directory_profile_show_friends == 'yes' && function_exists('friends_add') ) {
					if ( $_POST['action'] == 'members_directory_process_friend' ) {
						$current_user_id = get_current_user_id();
						if (!empty($current_user_id)) {
						
							if ($friends_enable_approval == 1) {
								friends_add($current_user_id, intval($_POST['friend_user_id']), '0');
								friends_add_notification(intval($_POST['friend_user_id']), $current_user_id);
								$message .= '<p id="friends-request-notification" class="members-dir-notification">' . __('Friend request has been sent', 'members-directory') . '</p>';
							} else {
								friends_add($current_user_id, intval($_POST['friend_user_id']), '1');
								friends_add_notification(intval($_POST['friend_user_id']), $current_user_id);
								$message .= '<p id="friends-request-notification" class="members-dir-notification">' . __('User has been successfully added', 'members-directory') . '</p>';
							}
							$friend_added = 'yes';
						}
					}
				}
				$user_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s", $members_directory['user']));
				if(function_exists('get_active_blog_for_user'))
					$blog_details = get_active_blog_for_user( $user_details->ID );
				$user_bio = get_user_meta( $user_details->ID, "description", true );
				$user_name = get_user_meta( $user_details->ID, "first_name", true ) . ' ' . get_user_meta( $user_details->ID, "last_name", true );
				$user_website = $user_details->user_url;
				$content .= '<div style="float:left; width:100%; margin-bottom:20px;">';
				$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
				if(!empty($message)){
					$content .= '<tr>';
						$content .= '<td valign="top" colspan="2">';
							$content .= $message;
						$content .= '</td>';
					$content .= '</tr>';
				}

					$content .= '<tr>';
						$content .= '<td valign="top" width="20%">';
						$content .= '<div style="width:100%">';
						$content .= '<center>';
						$content .= '<div style="padding:3px; background-color:' . $members_directory_background_color . '; border-style:solid; border-color:' . $members_directory_border_color . '; border-width:1px;">';
						if(isset($blog_details))
							$content .= '<a style="text-decoration:none;" href="' . $blog_details->siteurl . '/">' . get_avatar($user_details->ID, 96, get_option('avatar_default')) . '</a>';
						else
							$content .= get_avatar($user_details->ID, 96, get_option('avatar_default'));
						$content .= '</div>';
						$content .= '</center>';
						if ( $members_directory_profile_show_friends == 'yes' && function_exists('friends_add') ) {
							if ( $friends_enable_approval == '1' ) {
								$friend_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "friends WHERE user_ID = %d AND friend_approved = '1'", $user_details->ID));
							} else {
								$friend_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "friends WHERE user_ID = %d", $user_details->ID));
							}
							if ( $friends_enable_approval == '1' ) {
								$query = $wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "friends WHERE user_ID = %d AND friend_approved = '1' ORDER BY RAND() LIMIT 6", $user_details->ID);
							} else {
								$query = $wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "friends WHERE user_ID = %d ORDER BY RAND() LIMIT 6", $user_details->ID);
							}
							//echo "query[". $query ."]<br />";
							//echo "user_ID[". $user_ID ."] current_user_id[". get_current_user_id() ."]<br />";
							
							$friends = $wpdb->get_results( $query, ARRAY_A );
							if ( $friend_count > 0 ) {
								$content .= '<h3>' . __('Friends', 'members-directory') . '</h3>';
							}
							$content .= '<div style="width:100%">';
							if ( count( $friends  ) > 0 ) {
								$content .= '<center>';
								$content .= '<div style="padding:3px; background-color:' . $members_directory_background_color . '; border-style:solid; border-color:' . $members_directory_border_color . '; border-width:1px;">';
								foreach ($friends as $friend) {
									$friends_user = get_user_by('id', $friend['friend_user_ID']);
									if (!empty($friends_user)) {
									
										$content .= '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $friends_user->user_nicename . '/" title="'. __('View profile for', 'members-directory') .' '. $friends_user->display_name .'" style="padding:0px;margin:0px;text-decoration:none;border:0px;">' .  get_avatar($friend['friend_user_ID'], 48, get_option('avatar_default')) . '</a>';
									}
								}
								if ( $friend_count > 6 ) {
									$content .= '<div style="margin-top:2px;">(<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/friends/">' . __('All Friends', 'members-directory') . '</a>)</div>';
								}
								$content .= '</div>';
								$content .= '</center>';
								$content .= '<br />';
							}
							if ( !empty( $user_ID ) ) {
								$friend_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "friends WHERE friend_user_ID = %d AND user_ID = %d", $user_details->ID, $user_ID));
								if ( count( $friends  ) < 1 ) {
									$content .= '<br />';
								}
								$content .= '<form name="add_friend" method="POST">';
								$content .= '<input type="hidden" name="action" value="members_directory_process_friend" />';
								$content .= '<input type="hidden" name="user_id" value="' . esc_attr($user_ID) . '" />';
								$content .= '<input type="hidden" name="friend_user_id" value="' . esc_attr($user_details->ID) . '" />';
								$content .= '<center>';
								if (($user_ID == $user_details->ID) || ($friend_added == 'yes')) {
									
									$content .= '<input disabled="disabled" type="submit" name="Submit" value="' . __('Add Friend', 'members-directory') . '" />';
								} else {
									$content .= '<input type="submit" name="Submit" value="' . __('Add Friend', 'members-directory') . '" />';
								}
								$content .= '</center>';
								$content .= '</form>';
							}
							$content .= '</div>';
						}
						$content .= '</div>';
						$content .= '</td>';
						$content .= '<td valign="top" width="80%">';
						if ( !empty( $user_name ) && $user_name != ' ' ) {
							$content .= '<strong>' . __('Name', 'members-directory') . '</strong>: ' . $user_name . '<br />';
						}
						if ( !empty($user_website) ) {
							$content .= '<strong>' . __('Website', 'members-directory') . '</strong>: ' . $user_website . '<br />';
						}
						if ( !empty($user_bio) ) {
							$content .= '<strong>' . __('Bio', 'members-directory') . '</strong>: ' . $user_bio . '<br />';
						}
						$user_name_ws = str_replace(' ', '', $user_name);
						$user_bio_ws = str_replace(' ', '', $user_bio);
						$user_website_ws = str_replace(' ', '', $user_website);
						if ( empty($user_name_ws) && empty($user_bio_ws) && empty($user_website_ws) ) {
							$content .= __('This user has not entered any information.', 'members-directory');
						}
						$content .= '<div style="width:100%">';
						$content .= '</div>';
						$content .= '</td>';
					$content .= '</tr>';
				$content .= '</table>';
				$content .= '</div>';
				
				//echo "members_directory_profile_show_posts[". $members_directory_profile_show_posts ."]<br />";
				if (( $members_directory_profile_show_posts == 'yes' ) 
				 && ( (function_exists('post_indexer_make_current') ) || (	class_exists('postindexermodel') ) )) {
					
					//===============================================//
					if (function_exists('post_indexer_make_current') ) { //Post Indexer 2.x
						$query = $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "site_posts WHERE post_author = %d ORDER BY site_post_id DESC LIMIT 5", $user_details->ID);
					} else if (	class_exists('postindexermodel') ) { 
						$query = $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "network_posts WHERE post_author = %d ORDER BY ID DESC LIMIT 5", $user_details->ID);
					}
					if ((isset($query)) && (!empty($query))) {
						//echo "query[". $query ."]<br />";
						$posts = $wpdb->get_results( $query, ARRAY_A );
					
						
						$content .= '<h3 style="border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px;">' . __('Recent Posts', 'members-directory') . ' (<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/posts/">' . __('All Posts', 'members-directory') . '</a>)</h3>';

						if ( count( $posts ) > 0 ) {
							$content .= '<div style="float:left; width:100%">';
							$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';

							$avatar_default = get_option('avatar_default');
							$tic_toc = 'toc';
							//echo "posts<pre>"; print_r($posts); echo "</pre>";
							foreach ($posts as $post){
								if ($tic_toc == 'toc'){
									$tic_toc = 'tic';
								} else {
									$tic_toc = 'toc';
								}
								if ($tic_toc == 'tic'){
									$bg_color = $members_directory_alternate_background_color;
								} else {
									$bg_color = $members_directory_background_color;
								}
								
								if ((!isset($post['post_permalink'])) || (empty($post['post_permalink']))) {
									if ((class_exists('postindexermodel') ) && (function_exists('network_get_permalink'))) { 
										$post['post_permalink'] = network_get_permalink( $post['BLOG_ID'], $post['ID']);
									}
								}
								
								$content .= '<tr>';
									$content .= '<td style="background-color:' . $bg_color . '; padding-bottom:10px" width="100%">';
									$content .= '<strong><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . $post['post_title'] . '</a></strong><br />';
									$content .= substr(strip_tags($post['post_content'],'<a>'),0, 250) . ' (<a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . __('More', 'members-directory') . '</a>)';
									$content .= '</td>';
								$content .= '</tr>';
							}
							$content .= '</table>';
							$content .= '</div>';
							
						} else {
							$content .= __('No recent posts.', 'members-directory');
						}
						$content .= '<br />';
						$content .= '<br />';
					}
				}
				if ( $members_directory_profile_show_comments == 'yes' && function_exists('comment_indexer_make_current') ) {
					$content .= '<h3 style="border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px;">' . __('Recent Comments', 'members-directory') . ' (<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/comments/">' . __('All Comments', 'members-directory') . '</a>)</h3>';
					//===============================================//
					$query = $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "site_comments WHERE comment_author_user_id = %d ORDER BY site_comment_id DESC LIMIT 5", $user_details->ID);
					$comments = $wpdb->get_results( $query, ARRAY_A );
					//=====================================//
					if ( count( $comments ) > 0 ) {
						$content .= '<div style="float:left; width:100%">';
						$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
					}
						//=================================//
						$avatar_default = get_option('avatar_default');
						$tic_toc = 'toc';
						//=================================//
						if ( count( $comments ) > 0 ) {
							foreach ($comments as $comment){
								//=============================//
								if ($tic_toc == 'toc'){
									$tic_toc = 'tic';
								} else {
									$tic_toc = 'toc';
								}
								if ($tic_toc == 'tic'){
									$bg_color = $members_directory_alternate_background_color;
								} else {
									$bg_color = $members_directory_background_color;
								}
								//=============================//
								$content .= '<tr>';
									$content .= '<td style="background-color:' . $bg_color . '; padding-bottom:10px" width="100%">';
									$content .= substr(strip_tags($comment['comment_content'],'<a>'),0, 350) . ' (<a style="text-decoration:none;" href="' . $comment['comment_post_permalink'] . '">' . __('More', 'members-directory') . '</a>)';
									$content .= '</td>';
								$content .= '</tr>';
							}
						} else {
							$content .= __('No recent comments.', 'members-directory');
						}
						//=================================//
					if ( count( $comments ) > 0 ) {
						$content .= '</table>';
						$content .= '</div>';
					}
					//===============================================//
					$content .= '<br />';
					$content .= '<br />';
				}
			} else {
				$content .= __('Member not found.', 'members-directory');
			}
		} else if ( $members_directory['page_type'] == 'user_posts' ) {
			$user_count = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s".$members_directory_multisite_query, $members_directory['user'] ));
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s", $members_directory['user'] ));
				//=====================================//
				if ($members_directory['page'] == 1){
					$start = 0;
				} else {
					$math = $members_directory['page'] - 1;
					$math = $members_directory_per_page * $math;
					$start = $math;
				}
				if (function_exists('post_indexer_make_current') ) { //Post Indexer 2.x
					$query = $wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "site_posts WHERE post_author = %d ORDER BY site_post_id DESC LIMIT %d, %d", $user_details->ID, intval( $start ), intval( $members_directory_per_page ) );
					$posts = $wpdb->get_results( $query, ARRAY_A );
				} else if (	class_exists('postindexermodel') ) { 
					$query = $wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "network_posts WHERE post_author = %d ORDER BY ID DESC LIMIT %d, %d", $user_details->ID, intval( $start ), intval( $members_directory_per_page ) );
					$posts = $wpdb->get_results( $query, ARRAY_A );
				}
				
				//=====================================//
				if ( count( $posts ) > 0 ) {
					if ( count( $posts ) < $members_directory_per_page ) {
						$next = 'no';
					} else {
						$next = 'yes';
					}
					$navigation_content = members_directory_user_posts_navigation_output('', $members_directory_per_page, $members_directory['page'], $members_directory['user'], $user_details->ID, $next);
				}
				if ( count( $posts ) > 0 ) {
					$content .= $navigation_content;
				}
				if ( count( $posts ) > 0 ) {
					$content .= '<div style="float:left; width:100%">';
					$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
						$content .= '<tr>';
							$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="10%"> </td>';
							$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="90%"><center><strong>' .  __('Posts By', 'members-directory') . ' ' . ucfirst($user_details->display_name) .  '</strong></center></td>';
						$content .= '</tr>';
				}
					//=================================//
					$avatar_default = get_option('avatar_default');
					$tic_toc = 'toc';
					//=================================//
					if ( count( $posts ) > 0 ) {
						foreach ($posts as $post){
							//=============================//
							if ($tic_toc == 'toc'){
								$tic_toc = 'tic';
							} else {
								$tic_toc = 'toc';
							}
							if ($tic_toc == 'tic'){
								$bg_color = $members_directory_alternate_background_color;
							} else {
								$bg_color = $members_directory_background_color;
							}
							//=============================//
							$content .= '<tr>';
							
								if ((!isset($post['post_permalink'])) || (empty($post['post_permalink']))) {
									if ((class_exists('postindexermodel') ) && (function_exists('network_get_permalink'))) { 
										$post['post_permalink'] = network_get_permalink( $post['BLOG_ID'], $post['ID']);
									}
								}
							
								$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px;" valign="top" width="10%"><center><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . get_avatar($user_details->ID, 32, $avatar_default) . '</a></center></td>';
								$content .= '<td style="background-color:' . $bg_color . ';" width="90%">';
								$content .= '<strong><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . $post['post_title'] . '</a></strong><br />';
								$content .= substr(strip_tags($post['post_content'],'<a>'),0, 250) . ' (<a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . __('More', 'members-directory') . '</a>)';
								$content .= '</td>';
							$content .= '</tr>';
						}
					} else {
						$content = __('No posts available.', 'members-directory');
					}
					//=================================//
				if ( count( $posts ) > 0 ) {
					$content .= '</table>';
					$content .= '</div>';
					$content .= $navigation_content;
				}
			} else {
				$content = __('Member not found.', 'members-directory');
			}
		} else if ( $members_directory['page_type'] == 'user_comments' ) {
			$user_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s".$members_directory_multisite_query, $members_directory['user']));
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = %s", $members_directory['user']));
				//=====================================//
				if ($members_directory['page'] == 1){
					$start = 0;
				} else {
					$math = $members_directory['page'] - 1;
					$math = $members_directory_per_page * $math;
					$start = $math;
				}
				$query = $wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "site_comments WHERE comment_author_user_id = %s ORDER BY site_comment_id DESC LIMIT %d, %d", $user_details->ID, intval( $start ), intval( $members_directory_per_page ) );
				$comments = $wpdb->get_results( $query, ARRAY_A );
				//=====================================//
				if ( count( $comments ) > 0 ) {
					if ( count( $comments ) < $members_directory_per_page ) {
						$next = 'no';
					} else {
						$next = 'yes';
					}
					$navigation_content = members_directory_user_comments_navigation_output('', $members_directory_per_page, $members_directory['page'], $members_directory['user'], $user_details->ID, $next);
				}
				if ( count( $comments ) > 0 ) {
					$content .= $navigation_content;
				}
				if ( count( $comments ) > 0 ) {
					$content .= '<div style="float:left; width:100%">';
					$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
						$content .= '<tr>';
							$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="10%"> </td>';
							$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="90%"><center><strong>' .  __('Comments By', 'members-directory') . ' ' . ucfirst($user_details->display_name) .  '</strong></center></td>';
						$content .= '</tr>';
				}
					//=================================//
					$avatar_default = get_option('avatar_default');
					$tic_toc = 'toc';
					//=================================//
					if ( count( $comments ) > 0 ) {
						foreach ($comments as $comment){
							//=============================//
							if ($tic_toc == 'toc'){
								$tic_toc = 'tic';
							} else {
								$tic_toc = 'toc';
							}
							if ($tic_toc == 'tic'){
								$bg_color = $members_directory_alternate_background_color;
							} else {
								$bg_color = $members_directory_background_color;
							}
							//=============================//
							$content .= '<tr>';
								$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px;" valign="top" width="10%"><center><a style="text-decoration:none;" href="' . $comment['comment_post_permalink'] . '">' . get_avatar($user_details->ID, 32, $avatar_default) . '</a></center></td>';
								$content .= '<td style="background-color:' . $bg_color . ';" width="90%">';
								$content .= substr(strip_tags($comment['comment_content'],'<a>'),0, 350) . ' (<a style="text-decoration:none;" href="' . $comment['comment_post_permalink'] . '">' . __('More', 'members-directory') . '</a>)';
								$content .= '</td>';
							$content .= '</tr>';
						}
					} else {
						$content = __('No comments available.', 'members-directory');
					}
					//=================================//
				if ( count( $comments ) > 0 ) {
					$content .= '</table>';
					$content .= '</div>';
					$content .= $navigation_content;
				}
			} else {
				$content = __('Member not found.', 'members-directory');
			}
		} else if ( $members_directory['page_type'] == 'user_friends' ) {
			$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'".$members_directory_multisite_query);
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'");
				//=====================================//
				if ( $members_directory_profile_show_friends == 'yes' && function_exists('friends_add') ) {
					if ( $friends_enable_approval == '1' ) {
						$query = $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "friends WHERE user_ID = %d AND friend_approved = '1' ORDER BY friend_ID DESC", $user_details->ID);
					} else {
						$query = $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "friends WHERE user_ID = %d ORDER BY friend_ID DESC", $user_details->ID );
					}
					$friends = $wpdb->get_results( $query, ARRAY_A );
					if ( $friend_count > 0 ) {
						$content .= '<h3>' . __('Friends', 'members-directory') . '</h3>';
					}
					$content .= '<div style="width:100%;padding:5px">';
					if ( count( $friends  ) > 0 ) {
						$content .= '<center>';
						foreach ($friends as $friend){
							$friend_display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM " . $wpdb->users . " WHERE ID = %d", $friend['friend_user_ID']));
							$friend_nicename = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM " . $wpdb->users . " WHERE ID = %d", $friend['friend_user_ID']));
							$content .= '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $friend_nicename . '/" style="padding:0px;margin:1px;text-decoration:none;border:0px;">' .  get_avatar($friend['friend_user_ID'], 48, get_option('avatar_default')) . '</a>';
						}
						$content .= '</center>';
					}
						$content .= '</div>';
				}
				//=====================================//
			} else {
				$content = __('Member not found.', 'members-directory');
			}
		} else {
			$content = __('Invalid page.', 'members-directory');
		}
	}
	return $content;
}

function members_directory_user_posts_navigation_output($content, $per_page, $page, $user_nicename, $user_id, $next){
	global $wpdb, $current_site, $members_directory_base;
	
	if (function_exists('post_indexer_make_current') ) { //Post Indexer 2.x
		$post_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "site_posts WHERE post_author = %d ORDER BY site_post_id DESC", $user_id));
		$post_count = $post_count - 1;
	} else if (	class_exists('postindexermodel') ) { 
		$post_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "network_posts WHERE post_author = %d ORDER BY ID DESC", $user_id));
		$post_count = $post_count - 1;
	}
	
	

	//generate page div
	//============================================================================//
	$total_pages = members_directory_roundup($post_count / $per_page, 0);
	$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
	$content .= '<tr>';
	$showing_low = ($page * $per_page) - ($per_page - 1);
	if ($total_pages == $page){
		//last page...
		//$showing_high = $post_count - (($total_pages - 1) * $per_page);
		$showing_high = $post_count;
	} else {
		$showing_high = $page * $per_page;
	}

    $content .= '<td style="font-size:12px; text-align:left;" width="50%">';
	if ($post_count > $per_page){
	//============================================================================//
		if ($page == '' || $page == '1'){
			//$content .= __('Previous');
		} else {
		$previous_page = $page - 1;
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user_nicename . '/posts/' . $previous_page . '/">&laquo; ' . __('Previous', 'members-directory') . '</a>';
		}
	//============================================================================//
	}
	$content .= '</td>';
    $content .= '<td style="font-size:12px; text-align:right;" width="50%">';
	if ($post_count > $per_page){
	//============================================================================//
		if ( $next != 'no' ) {
			if ($page == $total_pages){
				//$content .= __('Next');
			} else {
				if ($total_pages == 1){
					//$content .= __('Next');
				} else {
					$next_page = $page + 1;
				$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user_nicename . '/posts/' . $next_page . '/">' . __('Next', 'members-directory') . ' &raquo;</a>';
				}
			}
		}
	//============================================================================//
	}
    $content .= '</td>';
	$content .= '</tr>';
    $content .= '</table>';
	return $content;
}

function members_directory_user_comments_navigation_output($content, $per_page, $page, $user_nicename, $user_id, $next){
	global $wpdb, $current_site, $members_directory_base;
	$comment_count = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM " . $wpdb->base_prefix . "site_comments WHERE comment_author_user_id = %d ORDER BY site_comment_id DESC", $user_id));
	$comment_count = $comment_count - 1;

	//generate page div
	//============================================================================//
	$total_pages = members_directory_roundup($comment_count / $per_page, 0);
	$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
	$content .= '<tr>';
	$showing_low = ($page * $per_page) - ($per_page - 1);
	if ($total_pages == $page){
		//last page...
		//$showing_high = $comment_count - (($total_pages - 1) * $per_page);
		$showing_high = $comment_count;
	} else {
		$showing_high = $page * $per_page;
	}

    $content .= '<td style="font-size:12px; text-align:left;" width="50%">';
	if ($comment_count > $per_page){
	//============================================================================//
		if ($page == '' || $page == '1'){
			//$content .= __('Previous');
		} else {
		$previous_page = $page - 1;
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user_nicename . '/comments/' . $previous_page . '/">&laquo; ' . __('Previous', 'members-directory') . '</a>';
		}
	//============================================================================//
	}
	$content .= '</td>';
    $content .= '<td style="font-size:12px; text-align:right;" width="50%">';
	if ($comment_count > $per_page){
	//============================================================================//
		if ( $next != 'no' ) {
			if ($page == $total_pages){
				//$content .= __('Next');
			} else {
				if ($total_pages == 1){
					//$content .= __('Next');
				} else {
					$next_page = $page + 1;
				$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user_nicename . '/comments/' . $next_page . '/">' . __('Next', 'members-directory') . ' &raquo;</a>';
				}
			}
		}
	//============================================================================//
	}
    $content .= '</td>';
	$content .= '</tr>';
    $content .= '</table>';
	return $content;
}

function members_directory_search_form_output($content, $phrase) {
	global $wpdb, $current_site, $members_directory_base;
	if ( !empty( $phrase ) ) {
		$content .= '<form action="' . $current_site->path . $members_directory_base . '/search/' . urlencode( $phrase ) . '/" method="post">';
	} else {
		$content .= '<form action="' . $current_site->path . $members_directory_base . '/search/" method="post">';
	}
		$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
		$content .= '<tr>';
		    $content .= '<td style="font-size:12px; text-align:left;" width="80%">';
				$content .= '<input name="phrase" style="width: 100%;" type="text" value="' . esc_attr($phrase) . '">';
			$content .= '</td>';
			$content .= '<td style="font-size:12px; text-align:right;" width="20%">';
				$content .= '<input name="Submit" value="' . __('Search', 'members-directory') . '" type="submit">';
			$content .= '</td>';
		$content .= '</tr>';
		$content .= '</table>';
	$content .= '</form>';
	return $content;
}

function members_directory_search_navigation_output($content, $per_page, $page, $phrase, $next){
	global $wpdb, $current_site, $members_directory_base, $members_directory_multisite_query;
	$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE ( user_login LIKE '%" . $phrase . "%' OR display_name LIKE '%" . $phrase . "%' )".$members_directory_multisite_query);
	$user_count = $user_count - 1;

	//generate page div
	//============================================================================//
	$total_pages = members_directory_roundup($user_count / $per_page, 0);
	$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
	$content .= '<tr>';
	$showing_low = ($page * $per_page) - ($per_page - 1);
	if ($total_pages == $page){
		//last page...
		//$showing_high = $user_count - (($total_pages - 1) * $per_page);
		$showing_high = $user_count;
	} else {
		$showing_high = $page * $per_page;
	}

    $content .= '<td style="font-size:12px; text-align:left;" width="50%">';
	if ($user_count > $per_page){
	//============================================================================//
		if ($page == '' || $page == '1'){
			//$content .= __('Previous');
		} else {
		$previous_page = $page - 1;
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/' . urlencode( $phrase ) . '/' . $previous_page . '/">&laquo; ' . __('Previous', 'members-directory') . '</a>';
		}
	//============================================================================//
	}
	$content .= '</td>';
    $content .= '<td style="font-size:12px; text-align:right;" width="50%">';
	if ($user_count > $per_page){
	//============================================================================//
		if ( $next != 'no' ) {
			if ($page == $total_pages){
				//$content .= __('Next');
			} else {
				if ($total_pages == 1){
					//$content .= __('Next');
				} else {
					$next_page = $page + 1;
				$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/' . urlencode( $phrase ) . '/' . $next_page . '/">' . __('Next', 'members-directory') . ' &raquo;</a>';
				}
			}
		}
	//============================================================================//
	}
    $content .= '</td>';
	$content .= '</tr>';
    $content .= '</table>';
	return $content;
}

function members_directory_landing_navigation_output($content, $per_page, $page){
	global $wpdb, $current_site, $members_directory_base, $members_directory_multisite_query;
	$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE 1=1".$members_directory_multisite_query);

	//generate page div
	//============================================================================//
	$total_pages = members_directory_roundup($user_count / $per_page, 0);
	$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
	$content .= '<tr>';
	$showing_low = ($page * $per_page) - ($per_page - 1);
	if ($total_pages == $page){
		//last page...
		//$showing_high = $user_count - (($total_pages - 1) * $per_page);
		$showing_high = $user_count;
	} else {
		$showing_high = $page * $per_page;
	}

    $content .= '<td style="font-size:12px; text-align:left;" width="50%">';
	if ($user_count > $per_page){
	//============================================================================//
		if ($page == '' || $page == '1'){
			//$content .= __('Previous');
		} else {
		$previous_page = $page - 1;
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $previous_page . '/">&laquo; ' . __('Previous', 'members-directory') . '</a>';
		}
	//============================================================================//
	}
	$content .= '</td>';
    $content .= '<td style="font-size:12px; text-align:right;" width="50%">';
	if ($user_count > $per_page){
	//============================================================================//
		if ($page == $total_pages){
			//$content .= __('Next');
		} else {
			if ($total_pages == 1){
				//$content .= __('Next');
			} else {
				$next_page = $page + 1;
			$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $next_page . '/">' . __('Next', 'members-directory') . ' &raquo;</a>';
			}
		}
	//============================================================================//
	}
    $content .= '</td>';
	$content .= '</tr>';
    $content .= '</table>';
	return $content;
}

function members_directory_wp_admins_profile_save( $user_id ) {
    if(isset($_POST['members_directory_show_user']))
    	update_user_meta( $user_id, 'members_directory_show_user', $_POST['members_directory_show_user'] );
}

function members_directory_wp_admins_profile() {
    global $user_ID;

    if ( !empty( $_GET['user_id'] ) ) {
        $user_id = $_GET['user_id'];
    } else {
        $user_id = $user_ID;
    }

    $unsubscribe_code = get_user_meta( $user_id, 'members_directory_show_user', 'yes' );
    ?>

    <h3><?php _e('Members Directory', 'members-directory'); ?></h3>

    <table class="form-table">
    <tr>
        <th><label for="members_directory_show_user"><?php _e('Show profile in sites members directory.', 'members-directory'); ?></label></th>
        <td>
            <select name="members_directory_show_user" id="members_directory_show_user">
                    <option value="yes"<?php if ( $unsubscribe_code != 'no' ) { echo ' selected="selected" '; } ?>><?php _e('Yes', 'members-directory'); ?></option>
                    <option value="no"<?php if ( $unsubscribe_code == 'no' ) { echo ' selected="selected" '; } ?>><?php _e('No', 'members-directory'); ?></option>
            </select>
        </td>

    </tr>
    </table>

    <?php
}

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//

//------------------------------------------------------------------------//
//---Support Functions----------------------------------------------------//
//------------------------------------------------------------------------//

function members_directory_roundup($value, $dp){
    return ceil($value*pow(10, $dp))/pow(10, $dp);
}
?>