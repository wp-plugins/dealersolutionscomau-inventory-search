<?php include('global.php');require_once('common/functions.php');

  require_once('functions.php');

  add_action('delete_option_rewrite_rules','wp_plugin_dealersolutionsinventorysearch_generate_rewrite_rules');

  // setup plugin global
  $wp_plugin_dealersolutionsinventorysearch = new stdClass();
  $wp_plugin_dealersolutionsinventorysearch->plugin_dir_path = plugin_dir_path(__FILE__);
  $wp_plugin_dealersolutionsinventorysearch->plugin_dir_url = plugin_dir_url(__FILE__);
  $wp_plugin_dealersolutionsinventorysearch->configuration = new stdClass();
  $wp_plugin_dealersolutionsinventorysearch->errors = new WP_Error();
  $wp_plugin_dealersolutionsinventorysearch->get_option = new stdClass();
  $wp_plugin_dealersolutionsinventorysearch->get_option->permalink_structure = get_option('permalink_structure');

  // http://codex.wordpress.org/Function_Reference/register_taxonomy#Reserved_Terms
  // targets forms with a specific form value that may cause conflict
  if(isset($_GET['dealersolutionsinventorysearch']) === true or isset($_POST['dealersolutionsinventorysearch']) === true)
  {
    $wp_plugin_dealersolutionsinventorysearch->request = new stdClass();
    $wp_plugin_dealersolutionsinventorysearch->request->_POST = stripslashes_deep($_POST);
    $wp_plugin_dealersolutionsinventorysearch->request->_GET = stripslashes_deep($_GET);
    $wp_plugin_dealersolutionsinventorysearch->request->_REQUEST = stripslashes_deep($_REQUEST);
    $_POST = array();
    $_GET = array();
    $_REQUEST = array();
  }

  if(isset($_POST['wp_plugin_dealersolutionsinventorysearch_admin_notice_remove']) === true and empty($_POST['wp_plugin_dealersolutionsinventorysearch_admin_notice_remove']) === false)
  {
    if(($admin_notices = get_option('wp_plugin_dealersolutionsinventorysearch_admin_notices')) != false and empty($admin_notices) === false and ($admin_notices = json_decode($admin_notices,true)) !== false and empty($admin_notices) === false and is_array($admin_notices) === true and isset($admin_notices[$_POST['wp_plugin_dealersolutionsinventorysearch_admin_notice_remove']]) === true)
    {
      unset($admin_notices[$_POST['wp_plugin_dealersolutionsinventorysearch_admin_notice_remove']]);
      update_option('wp_plugin_dealersolutionsinventorysearch_admin_notices',json_encode($admin_notices));
      exit(json_encode($_POST['wp_plugin_dealersolutionsinventorysearch_admin_notice_remove']));
    }
    else
      exit(json_encode(false));
  }

  $IS->configuration->inventorysearch = json_decode(get_option('wp_plugin_dealersolutionsinventorysearch_configuration'));
  $IS->configuration->inventorysearch_hash = md5(get_option('wp_plugin_dealersolutionsinventorysearch_configuration'));

  // Admin Interface
  if(is_admin())
  {
    add_action('edit_post','wp_plugin_dealersolutionsinventorysearch_edit_post');
    add_action('admin_head', 'wp_plugin_dealersolutionsinventorysearch_admin_head');
    add_action('admin_menu','wp_plugin_dealersolutionsinventorysearch_admin_menu');
    function wp_plugin_dealersolutionsinventorysearch_admin_menu()
    {
      $admin_notices = json_decode(get_option('wp_plugin_dealersolutionsinventorysearch_admin_notices'));
      add_options_page(
        'Dealer Solutions Inventory Search',
        'Inventory Search' . ((empty($admin_notices) === false) ? ' (!!!)' : null),
        'administrator',
        'wp_plugin_dealersolutionsinventorysearch_admin_page',
        'wp_plugin_dealersolutionsinventorysearch_admin_page'
        );
      add_menu_page(
        'Dealer Solutions Inventory Search',
        'Inventory Search' . ((empty($admin_notices) === false) ? ' (!!!)' : null),
        'administrator',
        'wp_plugin_dealersolutionsinventorysearch_admin_page',
        'wp_plugin_dealersolutionsinventorysearch_admin_page',
        plugin_dir_url( __FILE__ ) . 'icon.png'
        );
      unset($admin_notices);
    }
    // Admin Page
    add_action('admin_page','wp_plugin_dealersolutionsinventorysearch_admin_page');
    function wp_plugin_dealersolutionsinventorysearch_admin_page()
    {
      require('admin_page.php');
    }
  }

  // once the admin hook has been created, check if permalinks are disabled and exit
  if(empty($wp_plugin_dealersolutionsinventorysearch->get_option->permalink_structure) === true)
    return false;

  if(is_admin() === false)
  {

    function wp_plugin_dealersolutionsinventorysearch_wp()
    {
      return require('wp.php');
    }
    add_action('wp','wp_plugin_dealersolutionsinventorysearch_wp');

    function wp_insertMyRewriteQueryVars($vars)
    {
      array_push($vars,'path_info');
      array_push($vars,'path_info_pagename');
      array_push($vars,'inventory_legacy_page');
      array_push($vars,'inventory_legacy_path');
      return $vars;
    }
    add_filter('query_vars','wp_insertMyRewriteQueryVars');

  }

  /*
  // Alternative method of including stock list in page (may prove rubbish)
  function wp_plugin_dealersolutionsinventorysearch_add_shortcode_inventorysearch()
  {
    require('global.php');
    extract(shortcode_atts(array(
      'page' => null,
      'searchdefinition' => null
    ),func_get_arg(0)));
    $content = "(here will go the content that should replace the tags" . json_encode($searchdefinition) . ")";
    return $content;
  }
  add_shortcode('inventorysearch','wp_plugin_dealersolutionsinventorysearch_add_shortcode_inventorysearch');
  // Insert one stock item into the page
  function wp_plugin_dealersolutionsinventorysearch_add_shortcode_stockitem()
  {
    require('global.php');
    extract(shortcode_atts(array(
      'searchdefinition' => null,
      'id' => null,
      'stockno' => null,
      'rego' => null
    ),func_get_arg(0)));
    $content = "(Please show stock item: " . json_encode($rego) . ")";
    return $content;
  }
  add_shortcode('stockitem','wp_plugin_dealersolutionsinventorysearch_add_shortcode_stockitem');
  */

  /*
  // future usage
  function wp_plugin_dealersolutionsinventorysearch_save_post($id)
  {
    $post = get_post($id);
  }
  add_action('save_post','wp_plugin_dealersolutionsinventorysearch_save_post');
  */

?>