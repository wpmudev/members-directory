<?php
/*
Plugin Name: Members Directory
Plugin URI: http://premium.wpmudev.org/project/members-directory
Description: Provides an automatic list of all the users on your site, with avatars, pagination, a built in search facility and extended customizable user profiles
Author: Andrew Billits & Ulrich Sossou (Incsub)
Version: 1.0.6
Author URI:
*/

/*
Copyright 2007-2009 Incsub (http://incsub.com)

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

if ($current_blog->domain . $current_blog->path == $current_site->domain . $current_site->path){
	add_filter('generate_rewrite_rules','members_directory_rewrite');
	$members_directory_wp_rewrite = new WP_Rewrite;
	$members_directory_wp_rewrite->flush_rules();
	add_filter('the_content', 'members_directory_output', 20);
	add_filter('the_title', 'members_directory_title_output', 99, 2);
	add_action('admin_footer', 'members_directory_page_setup');
}

add_action('wpmu_options', 'members_directory_site_admin_options');
add_action('update_wpmu_options', 'members_directory_site_admin_options_process');

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function members_directory_page_setup() {
	global $wpdb, $user_ID, $members_directory_base;
	if ( get_site_option('members_directory_page_setup') != 'complete' && is_site_admin() ) {
		$page_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_name = '" . $members_directory_base . "' AND post_type = 'page'");
		if ( $page_count < 1 ) {
			$wpdb->query( "INSERT INTO " . $wpdb->posts . " ( post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count ) VALUES ( '" . $user_ID . "', '" . current_time( 'mysql' ) . "', '" . current_time( 'mysql' ) . "', '', '" . __('Members') . "', '', 'publish', 'closed', 'closed', '', '" . $members_directory_base . "', '', '', '" . current_time( 'mysql' ) . "', '" . current_time( 'mysql' ) . "', '', 0, '', 0, 'page', '', 0 )" );
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
		<h3><?php _e('Members Directory') ?></h3>
		<table class="form-table">
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Sort By') ?></th>
                <td>
                    <select name="members_directory_sort_by" id="members_directory_sort_by">
                       <option value="alphabetically" <?php if ( $members_directory_sort_by == 'alphabetically' ) { echo 'selected="selected"'; } ?> ><?php _e('Username (A-Z)'); ?></option>
                       <option value="latest" <?php if ( $members_directory_sort_by == 'latest' ) { echo 'selected="selected"'; } ?> ><?php _e('Newest'); ?></option>
                    </select>
                <br /><?php //_e('') ?></td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Listing Per Page') ?></th>
                <td>
				<select name="members_directory_per_page" id="members_directory_per_page">
				   <option value="5" <?php if ( $members_directory_per_page == '5' ) { echo 'selected="selected"'; } ?> ><?php _e('5'); ?></option>
				   <option value="10" <?php if ( $members_directory_per_page == '10' ) { echo 'selected="selected"'; } ?> ><?php _e('10'); ?></option>
				   <option value="15" <?php if ( $members_directory_per_page == '15' ) { echo 'selected="selected"'; } ?> ><?php _e('15'); ?></option>
				   <option value="20" <?php if ( $members_directory_per_page == '20' ) { echo 'selected="selected"'; } ?> ><?php _e('20'); ?></option>
				   <option value="25" <?php if ( $members_directory_per_page == '25' ) { echo 'selected="selected"'; } ?> ><?php _e('25'); ?></option>
				   <option value="30" <?php if ( $members_directory_per_page == '30' ) { echo 'selected="selected"'; } ?> ><?php _e('30'); ?></option>
				   <option value="35" <?php if ( $members_directory_per_page == '35' ) { echo 'selected="selected"'; } ?> ><?php _e('35'); ?></option>
				   <option value="40" <?php if ( $members_directory_per_page == '40' ) { echo 'selected="selected"'; } ?> ><?php _e('40'); ?></option>
				   <option value="45" <?php if ( $members_directory_per_page == '45' ) { echo 'selected="selected"'; } ?> ><?php _e('45'); ?></option>
				   <option value="50" <?php if ( $members_directory_per_page == '50' ) { echo 'selected="selected"'; } ?> ><?php _e('50'); ?></option>
				</select>
                <br /><?php //_e('') ?></td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Background Color') ?></th>
                <td><input name="members_directory_background_color" type="text" id="members_directory_background_color" value="<?php echo $members_directory_background_color; ?>" size="20" />
                <br /><?php _e('Default') ?>: #F2F2EA</td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Alternate Background Color') ?></th>
                <td><input name="members_directory_alternate_background_color" type="text" id="members_directory_alternate_background_color" value="<?php echo $members_directory_alternate_background_color; ?>" size="20" />
                <br /><?php _e('Default') ?>: #FFFFFF</td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Border Color') ?></th>
                <td><input name="members_directory_border_color" type="text" id="members_directory_border_color" value="<?php echo $members_directory_border_color; ?>" size="20" />
                <br /><?php _e('Default') ?>: #CFD0CB</td>
            </tr>
            <?php
            if ( function_exists('post_indexer_make_current') ) {
			?>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Profile: Show Posts') ?></th>
                <td>
				<select name="members_directory_profile_show_posts" id="members_directory_profile_show_posts">
				   <option value="yes" <?php if ( $members_directory_profile_show_posts == 'yes' ) { echo 'selected="selected"'; } ?> ><?php _e('Yes'); ?></option>
				   <option value="no" <?php if ( $members_directory_profile_show_posts == 'no' ) { echo 'selected="selected"'; } ?> ><?php _e('No'); ?></option>
				</select>
                <br /><?php _e('Show posts on profile pages.') ?></td>
            </tr>
            <?php
			}
            if ( function_exists('comment_indexer_make_current') ) {
			?>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Profile: Show Comments') ?></th>
                <td>
				<select name="members_directory_profile_show_comments" id="members_directory_profile_show_comments">
				   <option value="yes" <?php if ( $members_directory_profile_show_comments == 'yes' ) { echo 'selected="selected"'; } ?> ><?php _e('Yes'); ?></option>
				   <option value="no" <?php if ( $members_directory_profile_show_comments == 'no' ) { echo 'selected="selected"'; } ?> ><?php _e('No'); ?></option>
				</select>
                <br /><?php _e('Show comments on profile pages.') ?></td>
            </tr>
            <?php
			}
			if ( function_exists('friends_make_current') ) {
			?>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Profile: Show Friends') ?></th>
                <td>
				<select name="members_directory_profile_show_friends" id="members_directory_profile_show_friends">
				   <option value="yes" <?php if ( $members_directory_profile_show_friends == 'yes' ) { echo 'selected="selected"'; } ?> ><?php _e('Yes'); ?></option>
				   <option value="no" <?php if ( $members_directory_profile_show_friends == 'no' ) { echo 'selected="selected"'; } ?> ><?php _e('No'); ?></option>
				</select>
                <br /><?php _e('Show friends on profile pages.') ?></td>
            </tr>
            <?php
			}
			?>
		</table>
	<?php
}

function members_directory_site_admin_options_process() {

	update_site_option( 'members_directory_sort_by' , $_POST['members_directory_sort_by']);
	update_site_option( 'members_directory_per_page' , $_POST['members_directory_per_page']);
	update_site_option( 'members_directory_background_color' , trim( $_POST['members_directory_background_color'] ));
	update_site_option( 'members_directory_alternate_background_color' , trim( $_POST['members_directory_alternate_background_color'] ));
	update_site_option( 'members_directory_border_color' , trim( $_POST['members_directory_border_color'] ));
	if ( function_exists('post_indexer_make_current') ) {
	update_site_option( 'members_directory_profile_show_posts' , $_POST['members_directory_profile_show_posts']);
	}
	if ( function_exists('comment_indexer_make_current') ) {
	update_site_option( 'members_directory_profile_show_comments' , $_POST['members_directory_profile_show_comments']);
	}
	if ( function_exists('friends_make_current') ) {
	update_site_option( 'members_directory_profile_show_friends' , $_POST['members_directory_profile_show_friends']);
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
	global $wpdb, $current_site, $post, $members_directory_base;
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
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/">' . __('Search') . '</a> &raquo; ' . '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/' . urlencode($members_directory['phrase']) .  '/' . $members_directory['page'] . '/">' . $members_directory['page'] . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/">' . __('Search') . '</a>';
			}
		} else if ( $members_directory['page_type'] == 'user' ) {
			$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "' AND spam != 1 AND deleted != 1");
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'");
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/">' . ucfirst($user_details->display_name) . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; ' . __('Invalid User');
			}
		} else if ( $members_directory['page_type'] == 'user_posts' ) {
			$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "' AND spam != 1 AND deleted != 1");
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'");
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/">' . ucfirst($user_details->display_name) . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/posts/">' . __('Posts') . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; ' . __('Invalid User');
			}
		} else if ( $members_directory['page_type'] == 'user_comments' ) {
			$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "' AND spam != 1 AND deleted != 1");
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'");
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/">' . ucfirst($user_details->display_name) . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/comments/">' . __('Comments') . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; ' . __('Invalid User');
			}
		} else if ( $members_directory['page_type'] == 'user_friends' ) {
			$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "' AND spam != 1 AND deleted != 1");
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'");
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/">' . ucfirst($user_details->display_name) . '</a> &raquo; <a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/friends/">' . __('Friends') . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/">' . $post->post_title . '</a> &raquo; ' . __('Invalid User');
			}
		}
	}
	return $title;
}

function members_directory_output($content) {
	global $wpdb, $current_site, $post, $members_directory_base, $friends_enable_approval, $user_ID;
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

				$query = "SELECT * FROM " . $wpdb->base_prefix . "users WHERE spam != 1 AND deleted != 1";
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
			$query = "SELECT * FROM " . $wpdb->base_prefix . "users WHERE ( user_login LIKE '%" . $members_directory['phrase'] . "%' OR display_name LIKE '%" . $members_directory['phrase'] . "%' ) AND spam != 1 AND deleted != 1";
			$query .= " LIMIT " . intval( $start ) . ", " . intval( $members_directory_per_page );
			if ( !empty( $members_directory['phrase'] ) ) {
				$users = $wpdb->get_results( $query, ARRAY_A );
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
						$content .= '<td style="background-color:' . $bg_color . ';" width="90%">' . __('No results...') . '</td>';
					$content .= '</tr>';
				}
				//=================================//
			$content .= '</table>';
			$content .= '</div>';
			if ( count( $users ) > 0 ) {
				$content .= $navigation_content;
			}
		} else if ( $members_directory['page_type'] == 'user' ) {
			$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "' AND spam != 1 AND deleted != 1");
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'");
				$blog_details = get_active_blog_for_user( $user_details->ID );
				$user_bio = get_usermeta( $user_details->ID, "description" );
				$user_name = get_usermeta( $user_details->ID, "first_name" ) . ' ' . get_usermeta( $user_details->ID, "last_name" );
				$user_website = $user_details->user_url;
				$content .= '<div style="float:left; width:100%; margin-bottom:20px;">';
				$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
					$content .= '<tr>';
						$content .= '<td valign="top" width="20%">';
						$content .= '<div style="width:100%">';
						$content .= '<center>';
						$content .= '<div style="padding:3px; background-color:' . $members_directory_background_color . '; border-style:solid; border-color:' . $members_directory_border_color . '; border-width:1px;">';
						$content .= '<a style="text-decoration:none;" href="' . $blog_details->siteurl . '/">' . get_avatar($user_details->ID, 96, get_option('avatar_default')) . '</a>';
						$content .= '</div>';
						$content .= '</center>';
						if ( $members_directory_profile_show_friends == 'yes' && function_exists('friends_make_current') ) {
							if ( $_POST['action'] == 'members_directory_process_friend' ) {
								if ($friends_enable_approval == 1) {
									friends_add($_POST['user_id'], $_POST['friend_user_id'], '0');
									friends_add_notification($_POST['friend_user_id'],$_POST['user_id']);
								} else {
									friends_add($_POST['user_id'], $_POST['friend_user_id'], '1');
									friends_add_notification($_POST['friend_user_id'],$_POST['user_id']);
								}
								$friend_added = 'yes';
							}
							if ( $friends_enable_approval == '1' ) {
								$friend_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "friends WHERE user_ID = '" . $user_details->ID . "' AND friend_approved = '1'");
							} else {
								$friend_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "friends WHERE user_ID = '" . $user_details->ID . "'");
							}
							if ( $friends_enable_approval == '1' ) {
								$query = "SELECT * FROM " . $wpdb->base_prefix . "friends WHERE user_ID = '" . $user_details->ID . "' AND friend_approved = '1' ORDER BY RAND() LIMIT 6";
							} else {
								$query = "SELECT * FROM " . $wpdb->base_prefix . "friends WHERE user_ID = '" . $user_details->ID . "' ORDER BY RAND() LIMIT 6";
							}
							$friends = $wpdb->get_results( $query, ARRAY_A );
							if ( $friend_count > 0 ) {
								$content .= '<h3>' . __('Friends') . '</h3>';
							}
							$content .= '<div style="width:100%">';
							if ( count( $friends  ) > 0 ) {
								$content .= '<center>';
								$content .= '<div style="width:96px;padding:3px; background-color:' . $members_directory_background_color . '; border-style:solid; border-color:' . $members_directory_border_color . '; border-width:1px;">';
								foreach ($friends as $friend){
									//$friend_blog_details = get_active_blog_for_user( $friend['friend_user_ID'] );
									$friend_display_name = $wpdb->get_var("SELECT display_name FROM " . $wpdb->users . " WHERE ID = '" . $friend['friend_user_ID'] . "'");
									$friend_nicename = $wpdb->get_var("SELECT display_name FROM " . $wpdb->users . " WHERE ID = '" . $friend['friend_user_ID'] . "'");
									$content .= '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $friend_nicename . '/" style="padding:0px;margin:0px;text-decoration:none;border:0px;">' .  get_avatar($friend['friend_user_ID'], 48, get_option('avatar_default')) . '</a>';
								}
								if ( $friend_count > 6 ) {
									$content .= '<div style="margin-top:2px;">(<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/friends/">' . __('All Friends') . '</a>)</div>';
								}
								$content .= '</div>';
								$content .= '</center>';
								$content .= '<br />';
							}
							if ( !empty( $user_ID ) ) {
								$friend_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "friends WHERE friend_user_ID = '" . $user_details->ID . "' AND user_ID = '" . $user_ID . "'");
								if ( count( $friends  ) < 1 ) {
									$content .= '<br />';
								}
								$content .= '<form name="add_friend" method="POST">';
								$content .= '<input type="hidden" name="action" value="members_directory_process_friend" />';
								$content .= '<input type="hidden" name="user_id" value="' . $user_ID . '" />';
								$content .= '<input type="hidden" name="friend_user_id" value="' . $user_details->ID . '" />';
								$content .= '<center>';
								if ( $friend_count > 0 || $user_ID == $user_details->ID || $friend_added == 'yes' ) {
									$content .= '<input disabled="disabled" type="submit" name="Submit" value="' . __('Add Friend') . '" />';
								} else {
									$content .= '<input type="submit" name="Submit" value="' . __('Add Friend') . '" />';
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
							$content .= '<strong>' . __('Name') . '</strong>: ' . $user_name . '<br />';
						}
						if ( !empty($user_website) ) {
							$content .= '<strong>' . __('Website') . '</strong>: ' . $user_website . '<br />';
						}
						if ( !empty($user_bio) ) {
							$content .= '<strong>' . __('Bio') . '</strong>: ' . $user_bio . '<br />';
						}
						if ( empty($user_name) && empty($user_website) && empty($user_bio) ) {
							$content .= __('This user has not entered any information.');
						}
						$content .= '<div style="width:100%">';
						$content .= '</div>';
						$content .= '</td>';
					$content .= '</tr>';
				$content .= '</table>';
				$content .= '</div>';
				if ( $members_directory_profile_show_posts == 'yes' && function_exists('post_indexer_make_current') ) {
					$content .= '<h3 style="border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px;">' . __('Recent Posts') . ' (<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/posts/">' . __('All Posts') . '</a>)</h3>';
					//===============================================//
					$query = "SELECT * FROM " . $wpdb->base_prefix . "site_posts WHERE post_author = '" . $user_details->ID . "' ORDER BY site_post_id DESC LIMIT 5";
					$posts = $wpdb->get_results( $query, ARRAY_A );
					//=====================================//
					if ( count( $posts ) > 0 ) {
						$content .= '<div style="float:left; width:100%">';
						$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
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
									$content .= '<td style="background-color:' . $bg_color . '; padding-bottom:10px" width="100%">';
									$content .= '<strong><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . $post['post_title'] . '</a></strong><br />';
									$content .= substr(strip_tags($post['post_content'],'<a>'),0, 250) . ' (<a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . __('More') . '</a>)';
									$content .= '</td>';
								$content .= '</tr>';
							}
						} else {
							$content .= __('No recent posts.');
						}
						//=================================//
					if ( count( $posts ) > 0 ) {
						$content .= '</table>';
						$content .= '</div>';
					}
					//===============================================//
					$content .= '<br />';
					$content .= '<br />';
				}
				if ( $members_directory_profile_show_comments == 'yes' && function_exists('comment_indexer_make_current') ) {
					$content .= '<h3 style="border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px;">' . __('Recent Comments') . ' (<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $members_directory['user'] . '/comments/">' . __('All Comments') . '</a>)</h3>';
					//===============================================//
					$query = "SELECT * FROM " . $wpdb->base_prefix . "site_comments WHERE comment_author_user_id = '" . $user_details->ID . "' ORDER BY site_comment_id DESC LIMIT 5";
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
									$content .= substr(strip_tags($comment['comment_content'],'<a>'),0, 350) . ' (<a style="text-decoration:none;" href="' . $comment['comment_post_permalink'] . '">' . __('More') . '</a>)';
									$content .= '</td>';
								$content .= '</tr>';
							}
						} else {
							$content .= __('No recent comments.');
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
				$content .= __('Member not found.');
			}
		} else if ( $members_directory['page_type'] == 'user_posts' ) {
			$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "' AND spam != 1 AND deleted != 1");
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'");
				//=====================================//
				if ($members_directory['page'] == 1){
					$start = 0;
				} else {
					$math = $members_directory['page'] - 1;
					$math = $members_directory_per_page * $math;
					$start = $math;
				}
				$query = "SELECT * FROM " . $wpdb->base_prefix . "site_posts WHERE post_author = '" . $user_details->ID . "' ORDER BY site_post_id DESC";
				$query .= " LIMIT " . intval( $start ) . ", " . intval( $members_directory_per_page );
				$posts = $wpdb->get_results( $query, ARRAY_A );
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
							$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="90%"><center><strong>' .  __('Posts By') . ' ' . ucfirst($user_details->display_name) .  '</strong></center></td>';
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
								$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px;" valign="top" width="10%"><center><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . get_avatar($user_details->ID, 32, $avatar_default) . '</a></center></td>';
								$content .= '<td style="background-color:' . $bg_color . ';" width="90%">';
								$content .= '<strong><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . $post['post_title'] . '</a></strong><br />';
								$content .= substr(strip_tags($post['post_content'],'<a>'),0, 250) . ' (<a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . __('More') . '</a>)';
								$content .= '</td>';
							$content .= '</tr>';
						}
					} else {
						$content = __('No posts available.');
					}
					//=================================//
				if ( count( $posts ) > 0 ) {
					$content .= '</table>';
					$content .= '</div>';
					$content .= $navigation_content;
				}
			} else {
				$content = __('Member not found.');
			}
		} else if ( $members_directory['page_type'] == 'user_comments' ) {
			$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "' AND spam != 1 AND deleted != 1");
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'");
				//=====================================//
				if ($members_directory['page'] == 1){
					$start = 0;
				} else {
					$math = $members_directory['page'] - 1;
					$math = $members_directory_per_page * $math;
					$start = $math;
				}
				$query = "SELECT * FROM " . $wpdb->base_prefix . "site_comments WHERE comment_author_user_id = '" . $user_details->ID . "' ORDER BY site_comment_id DESC";
				$query .= " LIMIT " . intval( $start ) . ", " . intval( $members_directory_per_page );
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
							$content .= '<td style="background-color:' . $members_directory_background_color . '; border-bottom-style:solid; border-bottom-color:' . $members_directory_border_color . '; border-bottom-width:1px; font-size:12px;" width="90%"><center><strong>' .  __('Comments By') . ' ' . ucfirst($user_details->display_name) .  '</strong></center></td>';
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
								$content .= substr(strip_tags($comment['comment_content'],'<a>'),0, 350) . ' (<a style="text-decoration:none;" href="' . $comment['comment_post_permalink'] . '">' . __('More') . '</a>)';
								$content .= '</td>';
							$content .= '</tr>';
						}
					} else {
						$content = __('No comments available.');
					}
					//=================================//
				if ( count( $comments ) > 0 ) {
					$content .= '</table>';
					$content .= '</div>';
					$content .= $navigation_content;
				}
			} else {
				$content = __('Member not found.');
			}
		} else if ( $members_directory['page_type'] == 'user_friends' ) {
			$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "' AND spam != 1 AND deleted != 1");
			if ( $user_count > 0 ) {
				$user_details = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix . "users WHERE user_nicename = '" . $members_directory['user'] . "'");
				//=====================================//
				if ( $members_directory_profile_show_friends == 'yes' && function_exists('friends_make_current') ) {
					if ( $friends_enable_approval == '1' ) {
						$query = "SELECT * FROM " . $wpdb->base_prefix . "friends WHERE user_ID = '" . $user_details->ID . "' AND friend_approved = '1' ORDER BY friend_ID DESC";
					} else {
						$query = "SELECT * FROM " . $wpdb->base_prefix . "friends WHERE user_ID = '" . $user_details->ID . "' ORDER BY friend_ID DESC";
					}
					$friends = $wpdb->get_results( $query, ARRAY_A );
					if ( $friend_count > 0 ) {
						$content .= '<h3>' . __('Friends') . '</h3>';
					}
					$content .= '<div style="width:100%;padding:5px">';
					if ( count( $friends  ) > 0 ) {
						$content .= '<center>';
						foreach ($friends as $friend){
							//$friend_blog_details = get_active_blog_for_user( $friend['friend_user_ID'] );
							$friend_display_name = $wpdb->get_var("SELECT display_name FROM " . $wpdb->users . " WHERE ID = '" . $friend['friend_user_ID'] . "'");
							$friend_nicename = $wpdb->get_var("SELECT display_name FROM " . $wpdb->users . " WHERE ID = '" . $friend['friend_user_ID'] . "'");
							$content .= '<a href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $friend_nicename . '/" style="padding:0px;margin:1px;text-decoration:none;border:0px;">' .  get_avatar($friend['friend_user_ID'], 48, get_option('avatar_default')) . '</a>';
						}
						$content .= '</center>';
					}
						$content .= '</div>';
				}
				//=====================================//
			} else {
				$content = __('Member not found.');
			}
		} else {
			$content = __('Invalid page.');
		}
	}
	return $content;
}

function members_directory_user_posts_navigation_output($content, $per_page, $page, $user_nicename, $user_id, $next){
	global $wpdb, $current_site, $members_directory_base;
	$post_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "site_posts WHERE post_author = '" . $user_id . "' ORDER BY site_post_id DESC");
	$post_count = $post_count - 1;

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
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user_nicename . '/posts/' . $previous_page . '/">&laquo; ' . __('Previous') . '</a>';
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
				$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user_nicename . '/posts/' . $next_page . '/">' . __('Next') . ' &raquo;</a>';
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
	$comment_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "site_comments WHERE comment_author_user_id = '" . $user_id . "' ORDER BY site_comment_id DESC");
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
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user_nicename . '/comments/' . $previous_page . '/">&laquo; ' . __('Previous') . '</a>';
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
				$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $user_nicename . '/comments/' . $next_page . '/">' . __('Next') . ' &raquo;</a>';
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
				$content .= '<input name="phrase" style="width: 100%;" type="text" value="' . $phrase . '">';
			$content .= '</td>';
			$content .= '<td style="font-size:12px; text-align:right;" width="20%">';
				$content .= '<input name="Submit" value="' . __('Search') . '" type="submit">';
			$content .= '</td>';
		$content .= '</tr>';
		$content .= '</table>';
	$content .= '</form>';
	return $content;
}

function members_directory_search_navigation_output($content, $per_page, $page, $phrase, $next){
	global $wpdb, $current_site, $members_directory_base;
	$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE ( user_login LIKE '%" . $phrase . "%' OR display_name LIKE '%" . $phrase . "%' ) AND spam != 1 AND deleted != 1");
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
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/' . urlencode( $phrase ) . '/' . $previous_page . '/">&laquo; ' . __('Previous') . '</a>';
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
				$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/search/' . urlencode( $phrase ) . '/' . $next_page . '/">' . __('Next') . ' &raquo;</a>';
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
	global $wpdb, $current_site, $members_directory_base;
	$user_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "users WHERE spam != 1 AND deleted != 1");

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
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $previous_page . '/">&laquo; ' . __('Previous') . '</a>';
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
			$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $next_page . '/">' . __('Next') . ' &raquo;</a>';
			}
		}
	//============================================================================//
	}
    $content .= '</td>';
	$content .= '</tr>';
    $content .= '</table>';
	return $content;
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
