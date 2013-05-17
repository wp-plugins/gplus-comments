<?php
/*
Plugin Name: Google+ Comments
Plugin URI: http://www.cloudhero.net/gplus-comments
Description: Google+ Comments for WordPress plugin adds Google Plus comments along side your native WordPress comment system in a responsive tab interface.
Author: Brandon Holtsclaw <me@brandonholtsclaw.com>
Author URI: http://www.brandonholtsclaw.com/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: http://www.wepay.com/donations/brandonholtsclaw
Version: 1.4.8
*/

/* *    
 *       DEVELOPERS AND THEMERS : DONT EDIT THIS FILE DIRECTLY
 *       THERE ARE INSTRUCTIONS ON THE PLUGINS WEBPAGE TO CUSTOMIZE IT
 *       SO THAT IT WONT BE LOST ON PLUGIN UPATES.
 */

// No direct access
defined('ABSPATH') or exit;

define('GPLUS_COMMENTS_VERSION', '1.4.8');
define('GPLUS_COMMENTS_DEFAULT_TAB_ORDER', 'gplus,facebook,wordpress');
defined('GPLUS_COMMENTS_DEBUG') or define('GPLUS_COMMENTS_DEBUG', false);
defined('GPLUS_COMMENTS_DIR') or define('GPLUS_COMMENTS_DIR', dirname(__FILE__));
defined('GPLUS_COMMENTS_URL') or define('GPLUS_COMMENTS_URL', rtrim(plugin_dir_url(__FILE__),"/"));
defined('GPLUS_COMMENTS_LIB') or define('GPLUS_COMMENTS_LIB', GPLUS_COMMENTS_DIR . "/lib");
defined('GPLUS_COMMENTS_TEMPLATES') or define('GPLUS_COMMENTS_TEMPLATES', GPLUS_COMMENTS_DIR . "/templates");

if (version_compare(phpversion(), '5.3', '<'))
{
  function gplus_comments_php_too_low()
  {
    echo "<div class='error'><p>";
    echo "Google+ Comments for WordPress requires PHP 5.3+ and will not activate, your current server configuration is running PHP version '" . phpversion() . "' . Any PHP version less than 5.3.0 has reached 'End of Life' from PHP.net and no longer receives bugfixes or security updates. The official information on how to update and why at <a href='http://php.net/eol.php' target='_blank'><strong>php.net/eol.php</strong></a>";
    echo "</p></div>";
  }
  add_action('admin_notices', 'gplus_comments_php_too_low');
  return;
}

function gplus_comments_init()
{
  wp_register_style('gplus_comments_tabs_css', GPLUS_COMMENTS_URL . '/styles/tabs.css', null, GPLUS_COMMENTS_VERSION, "all");
  wp_enqueue_script('jquery');
  wp_enqueue_script('jquery-ui-core');
  wp_enqueue_script('jquery-ui-tabs');
}
add_action('init', 'gplus_comments_init');

add_action('admin_init', function() {} ({
  register_setting( 'gplus-comments-options', 'gplus-comments' );
});
//add_action( 'admin_init', 'gplus_comments_admin_init' );

function gplus_comments_activate()
{
  $options = array();
  $options = get_option('gplus-comments');
  $options["tab_order"] = GPLUS_COMMENTS_DEFAULT_TAB_ORDER;
  update_option('gplus-comments', $options);
}
register_activation_hook( __FILE__, 'gplus_comments_activate');

/**
 * Replace the theme's loaded comments.php with our own souped up version.
 */
function gplus_comments_template($file)
{
    global $post, $comments;

    /**
     * Do we even need to load ?
     */
    if (!(is_singular() && (have_comments() || 'open' == $post->comment_status))) { return; }

    /**
     * This will allow theme authors to override the comments template files easy.
     */
    if (file_exists(TEMPLATEPATH . '/comments-container.php'))
    {
      return TEMPLATEPATH . '/comments-container.php';
    }
    else
    {
      return GPLUS_COMMENTS_TEMPLATES . '/comments-container.php';
    }
}
add_filter('comments_template', 'gplus_comments_template', 4269);

function gplus_comments_get_comments_number()
{  
    global $post;  
    $url = get_permalink($post->ID);  
  
    $filecontent = file_get_contents('https://graph.facebook.com/?ids=' . $url);  
    $json = json_decode($filecontent);  
    $count = $json->$url->comments;  
    $wpCount = get_comments_number();  
    $realCount = $count + $wpCount;  
    if ($realCount == 0 || !isset($realCount)) {  
        $realCount = 0;  
    }  
    return $realCount;  
}  
//add_filter('get_comments_number', 'gplus_comments_get_comments_number');

/**
 * Load up our assets (last) for frontend to make us pretty and functional.
 */
function gplus_comments_enqueue_styles()
{
  wp_enqueue_style('gplus_comments_tabs_css');
}
add_action('wp_head', 'gplus_comments_enqueue_styles', 4269);

function gplus_comments_enqueue_scripts()
{
  print "\n<script>jQuery('#comment-tabs').tabs();</script>\n";
}
add_action('wp_footer', 'gplus_comments_enqueue_scripts', 4269);

/**
 * Set the link for settings under the plugin name on the wp-admin plugins page
 */
function gplus_comments_plugin_action_links($links, $file) {
  $plugin_file = basename(__FILE__);
  if (basename($file) == $plugin_file) {
    $settings_link = '<a href="edit-comments.php?page=gplus-comments">Settings</a>';
    array_unshift($links, $settings_link);
  }
  return $links;
}
add_filter('plugin_action_links', 'gplus_comments_plugin_action_links', 10, 2);

/**
 * Load the G+ options page when called by the admin_menu
 */
function gplus_comments_render_admin_page()
{
  require GPLUS_COMMENTS_LIB . '/gplus-comments-admin.php';
}

/**
 * Add ourself to the admin menu under the Comments section
 */
function gplus_comments_admin_menu()
{
     add_submenu_page
     (
         'edit-comments.php',
         'Google+ Comments',
         'G+ Comments',
         'manage_options',
         'gplus-comments',
         'gplus_comments_render_admin_page'
     );
}
add_action('admin_menu', 'gplus_comments_admin_menu', 10);

/**
 * Load a bit of jQuery to adjust some of the minor admin menu items
 */
function gplus_comments_admin_head()
{
  print "<script type='text/javascript' charset='utf-8'>jQuery(document).ready(function($) { jQuery('ul.wp-submenu a[href=\"edit-comments.php\"]').text('WP Comments');jQuery('#menu-comments').find('a.wp-has-submenu').attr('href', 'edit-comments.php?page=gplus-comments').end().find('.wp-submenu  li:has(a[href=\"edit-comments.php?page=gplus-comments\"])').prependTo($('#menu-comments').find('.wp-submenu ul')); jQuery('#wp-admin-bar-comments a.ab-item').attr('href', 'edit-comments.php?page=gplus-comments'); });</script>";
}
add_action('admin_head', 'gplus_comments_admin_head');

?>
<!--
<a class="twitter-timeline"  href="https://twitter.com/imbrandon"  data-widget-id="330505805105336320">Tweets by @imbrandon</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
-->
<?php


