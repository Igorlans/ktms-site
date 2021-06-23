<?php
/**
* Plugin Name: Wp Maximum Upload File Size
* Description: Wp Maximum Upload File Size will increase upload limit with one click. you can easily increase upload file size according to your need.
* Author: CodePopular
* Author URI: https://profiles.wordpress.org/codepopular/
* Plugin URI: https://wordpress.org/plugins/wp-maximum-upload-file-size/
* Version: 1.0.4
* License: GPL2
* Text Domain: wp-maximum-upload-file-size
* Requires at least: 4.0
* Tested up to: 5.7
* Requires PHP: 5.6
* @coypright: -2021 CodePopular (support: support@codepopular.com)
*/

define('WMUFS_PLUGIN_FILE', __FILE__);
define('WMUFS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WMUFS_PLUGIN_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('WMUFS_PLUGIN_URL', trailingslashit(plugins_url('/', __FILE__)));
define('WMUFS_PLUGIN_VERSION', '1.0.4');

/**
 * Class Codepopular_WMUFS
 */
class Codepopular_WMUFS
{
  static function init() {
    if ( is_admin() ) {
      add_action('admin_enqueue_scripts', array( __CLASS__, 'wmufs_style_and_script' ));
      add_action('admin_menu', array( __CLASS__, 'upload_max_file_size_add_pages' ));
      add_filter('install_plugins_table_api_args_featured', array( __CLASS__, 'featured_plugins_tab' ));
      add_filter('plugin_action_links_' . plugin_basename(__FILE__), array( __CLASS__, 'plugin_action_links' ));
      add_filter('plugin_row_meta', array( __CLASS__, 'plugin_meta_links' ), 10, 2);
      add_filter('admin_footer_text', array( __CLASS__, 'admin_footer_text' ));

      if ( isset($_POST['upload_max_file_size_field'])
          && wp_verify_nonce($_POST['upload_max_file_size_nonce'], 'upload_max_file_size_action')
          && is_numeric($_POST['upload_max_file_size_field']) ) {
          $max_size = (int) $_POST['upload_max_file_size_field'] * 1024 * 1024;
          update_option('max_file_size', $max_size);
          wp_safe_redirect(admin_url('?page=upload_max_file_size&max-size-updated=true'));
      }
    }

    add_filter('upload_size_limit', array( __CLASS__, 'upload_max_increase_upload' ));
  }

    /**
     * Load Plugin Style and Scripts.
     * @return string
     */

   static function wmufs_style_and_script(){
       wp_enqueue_style('wmufs-admin-style', WMUFS_PLUGIN_URL . 'assets/css/wmufs.css');
   }


  // get plugin version from header
  static function get_plugin_version() {
    $plugin_data = get_file_data(__FILE__, array( 'version' => 'Version' ), 'plugin');

    return $plugin_data['version'];
  } // get_plugin_version


  // test if we're on plugin's page
  static function is_plugin_page() {
    $current_screen = get_current_screen();

    if ( $current_screen->id == 'toplevel_page_upload_max_file_size' ) {
      return true;
    } else {
      return false;
    }
  } // is_plugin_page


  // add settings link to plugins page
  static function plugin_action_links( $links ) {
    $settings_link = '<a href="' . admin_url('admin.php?page=upload_max_file_size') . '" title="Adjust Max File Upload Size Settings">Settings</a>';

    array_unshift($links, $settings_link);

    return $links;
  } // plugin_action_links


  // add links to plugin's description in plugins table
  static function plugin_meta_links( $links, $file ) {
    $support_link = '<a target="_blank" href="https://wordpress.org/support/plugin/wp-maximum-upload-file-size/" title="Get help">Support</a>';


    if ( $file == plugin_basename(__FILE__) ) {
      $links[] = $support_link;
    }

    return $links;
  } // plugin_meta_links


  // additional powered by text in admin footer; only on plugin's page
  static function admin_footer_text( $text ) {
    if ( ! self::is_plugin_page() ) {
      return $text;
    }

    $text = '<i>Wp Maximum Upload File Size v' . self::get_plugin_version() . ' by <a href="https://www.codepopular.com/" title="Visit our site to get more great plugins" target="_blank">CodePopular</a>.</i> ' . $text;

    return $text;
  } // admin_footer_text


  /**
   * Add menu pages
   *
   * @since 1.0
   *
   * @return null
   *
   */
  static function upload_max_file_size_add_pages() {
      // Add a new menu on main menu
      add_menu_page('Increase Max Upload File Size', 'Wp upload limit', 'manage_options', 'upload_max_file_size', array( __CLASS__, 'upload_max_file_size_dash' ), 'dashicons-upload');
  } // upload_max_file_size_add_pages


    /**
     * Get closest value from array
     * @param $search
     * @param $arr
     * @return mixed|null
     */
  static function get_closest( $search, $arr ) {
      $closest = null;
      foreach ( $arr as $item ) {
          if ( $closest === null || abs($search - $closest) > abs($item - $search) ) {
              $closest = $item;
          }
      }
      return $closest;
  } // get_closest


    /**
     * Dashboard Page
     */
  static function upload_max_file_size_dash() {
      include_once WMUFS_PLUGIN_PATH. 'includes/class-wmufs-template.php';
  }


  /**
   * Filter to increase max_file_size
   *
   * @since 1.4
   *
   * @return int max_size in bytes
   *
   */
  static function upload_max_increase_upload() {
      $max_size = (int) get_option('max_file_size');
      if ( ! $max_size ) {
          $max_size = 64 * 1024 * 1024;
      }

      return $max_size;
  } // upload_max_increase_upload


    /**
     * helper function for adding plugins to fav list.
     * @param $args
     * @return mixed
     */
  static function featured_plugins_tab( $args ) {
    add_filter('plugins_api_result', array( __CLASS__, 'plugins_api_result' ), 10, 3);

    return $args;
  }


    /**
     * add our plugins to recommended list
     * @param $res
     * @param $action
     * @param $args
     * @return mixed
     */
    static function plugins_api_result( $res, $action, $args ) {
        remove_filter('plugins_api_result', array( __CLASS__, 'plugins_api_result' ), 10, 3);
        $res = self::add_plugin_favs('unlimited-theme-addons', $res);
        return $res;
    } // plugins_api_result


    /**
     * add single plugin to list of favs
     * @param $plugin_slug
     * @param $res
     * @return mixed
     */
  static function add_plugin_favs( $plugin_slug, $res ) {
    if ( ! empty( $res->plugins ) && is_array( $res->plugins ) ) {
      foreach ( $res->plugins as $plugin ) {
        if ( is_object($plugin) && ! empty($plugin->slug) && $plugin->slug == $plugin_slug ) {
          return $res;
        }
      }
    }

    if ( $plugin_info = get_transient('wf-plugin-info-' . $plugin_slug) ) {
      array_unshift($res->plugins, $plugin_info);
    } else {
      $plugin_info = plugins_api('plugin_information', array(
		  'slug'   => $plugin_slug,
		  'is_ssl' => is_ssl(),
		  'fields' => array(
			  'banners'           => true,
			  'reviews'           => true,
			  'downloaded'        => true,
			  'active_installs'   => true,
			  'icons'             => true,
			  'short_description' => true,
		  ),
      ));
      if ( ! is_wp_error($plugin_info) ) {
        $res->plugins[] = $plugin_info;
        set_transient('wf-plugin-info-' . $plugin_slug, $plugin_info, DAY_IN_SECONDS * 7);
      }
    }

    return $res;
  }


}

/**
 * Instance of the class  // Codepopular_WMUFS
 */
add_action('init', array( 'Codepopular_WMUFS', 'init' ));
