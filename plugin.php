<?php
/*
Plugin Name: DealerSolutions.com.au Inventory Search
Plugin URI: http://www.dealersolutions.com.au/
Description: This plugin embeds Dealer Solution's Inventory Search into your Wordpress Website
Version: 1.0
Author: Dealer Solutions
Author URI: http://www.dealersolutions.com.au/
License: GPL
*/

  // Plugin Activation
  register_activation_hook(__FILE__,'wp_plugin_dealersolutionsinventorysearch_activation'); 
  function wp_plugin_dealersolutionsinventorysearch_activation()
  {
  	global $wpdb;
  	$whatToBuild = array_filter(func_get_args());
  	if(empty($whatToBuild) === true or is_array($whatToBuild) === false)
      $whatToBuild = array('database','option');
    if(is_array($whatToBuild) === true)
    {
      foreach($whatToBuild as $build)
      {
        switch($build)
        {
          case 'option':
            // General Storage Container
            add_option('wp_plugin_dealersolutionsinventorysearch_configuration',NULL);
            // This option contains the domain excluding http://www. and any folders afterwards
            add_option('wp_plugin_dealersolutionsinventorysearch_searchdefinition_domain',NULL);
            // This option selects what target inventory search domain to pull content from
            // 0 = Production  http://www.inventorysearch.com.au/ (default option)
            // 1 = Staging     http://staging.inventorysearch.com.au/
            // 2 = Development http://dev.inventorysearch.com.au/
            // 3 = Localhost   http://localhost/
            add_option('wp_plugin_dealersolutionsinventorysearch_development_mode','0');
            // What folder to use for localhost development
            add_option('wp_plugin_dealersolutionsinventorysearch_development_folder',NULL);
            // How should external CSS be handled?
            // 0 = Include
            // 1 = Exclude
            add_option('wp_plugin_dealersolutionsinventorysearch_css_mode',NULL);
            // How should title tags be handled?
            // 0 = Ignore
            // 1 = Replace (Partial)
            // 2 = Append
            // 3 = Pre-Pend
            // 4 = Replace (Complete)
            add_option('wp_plugin_dealersolutionsinventorysearch_title_mode','4');
            // Place to keep some admin alerts
            add_option('wp_plugin_dealersolutionsinventorysearch_admin_notices',json_encode(false));
            // Legacy URL redirect Mode /view.php/searchdef/ becomes /permalink/view/
            add_option('wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect',false);
            // Place to keep page specific configuration
            add_option('wp_plugin_dealersolutionsinventorysearch_page_specific_rules','[]');
            break;
          case 'database':
            // Table to store linking mechanism between Page ID and Search Alias
            $sql = '
              CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`(
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `page_id` int(11) NOT NULL,
                `definitioncode` varchar(256) NOT NULL,
                `type` tinyint(1) NOT NULL DEFAULT \'0\',
                `content_mode` tinyint(1) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `page_id` (`page_id`)
              );
            ';
            $wpdb->query($sql);
            // Table to store Error Logs
            $sql = '
              CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_error_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `code` varchar(16) DEFAULT NULL,
                `message` text,
                `data` text,
                `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
              );
            ';
            $wpdb->query($sql);
            break;
        }
      }
    }
  }

  // Plugin DeActivation
  register_deactivation_hook( __FILE__, 'wp_plugin_dealersolutionsinventorysearch_deactivation');
  function wp_plugin_dealersolutionsinventorysearch_deactivation()
  {
  	global $wpdb;
    delete_option('wp_plugin_dealersolutionsinventorysearch_configuration');
    delete_option('wp_plugin_dealersolutionsinventorysearch_searchdefinition_domain');
    delete_option('wp_plugin_dealersolutionsinventorysearch_development_mode');
    delete_option('wp_plugin_dealersolutionsinventorysearch_development_folder');
    delete_option('wp_plugin_dealersolutionsinventorysearch_css_mode');
    delete_option('wp_plugin_dealersolutionsinventorysearch_title_mode');
    delete_option('wp_plugin_dealersolutionsinventorysearch_admin_notices');
    delete_option('wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect');
    delete_option('wp_plugin_dealersolutionsinventorysearch_page_specific_rules');
    $sql = 'DROP TABLE `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`';
    $wpdb->query($sql);
    $sql = 'DROP TABLE `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_error_log`';
    $wpdb->query($sql);
    flush_rewrite_rules();
    wp_plugin_dealersolutionsinventorysearch_clear_cache();
  }

  // Initialization
  function wp_plugin_dealersolutionsinventorysearch_init()
  {
    require('init.php');
  }
  add_action('init','wp_plugin_dealersolutionsinventorysearch_init');

  // Cache clearing functionality
  if(isset($_GET['clear_dealersolutions_cache']))
  {
    wp_plugin_dealersolutionsinventorysearch_clear_cache();
  }

  function wp_plugin_dealersolutionsinventorysearch_clear_cache()
  {
    global $wpdb;
    $wpdb->query( 'DELETE FROM `' . $wpdb->prefix . 'options` WHERE `option_name` LIKE "_transient_ds_cache_%" OR "_transient_timeout_ds_cache_%"');
  }