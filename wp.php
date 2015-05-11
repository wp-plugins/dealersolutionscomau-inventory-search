<?php require('global.php');

  // START CONFIGURATION

  if(get_the_ID() === NULL)
  {
    // Reset the Plugin Object
    $IS = new stdClass();
    return false;
  }

  if($_SERVER['REQUEST_METHOD'] === 'GET' and strlen(get_query_var('inventory_legacy_page')) > 0)
  {
    // No Complex Path, but keep query string stuff
    if(strlen(get_query_var('inventory_legacy_path')) === 0)
    {
      $url = unparse_url(array_merge(parse_url(get_permalink(get_the_ID())),array('query'=>parse_url(add_query_arg(array()),PHP_URL_QUERY))));
      wp_redirect($url,302);
      exit();
    }
    else
    {
      $url = implode('/',array(rtrim(get_permalink(get_the_ID()),'/'),get_query_var('inventory_legacy_page'),substr(add_query_arg(array()),strpos(add_query_arg(array()),get_query_var('inventory_legacy_path')))));
      wp_redirect($url,302);
      exit();
    }
  }

  // START CONFIGURATION
  $IS->configuration = (object) array_merge((array)$IS->configuration,(array)$wpdb->get_row($wpdb->prepare('SELECT `id`,`page_id`,`definitioncode`,`content_mode`,`type` FROM `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings` WHERE `page_id` = \'%d\' ORDER BY `page_id`',get_the_ID())));
  // EXIT POINT
  if(isset($IS->configuration) === false or $IS->configuration === false or empty($IS->configuration) === true or isset($IS->configuration->definitioncode) === false or post_password_required($IS->configuration->page_id) === true)
  {
    // Reset the Plugin Object
    $IS = new stdClass();
    return false;
  }

  $IS->configuration->searchdefinition_domain = get_option('wp_plugin_dealersolutionsinventorysearch_searchdefinition_domain');
  $IS->configuration->development_mode = get_option('wp_plugin_dealersolutionsinventorysearch_development_mode');
  $IS->configuration->development_folder = get_option('wp_plugin_dealersolutionsinventorysearch_development_folder');
  $IS->configuration->css_mode = get_option('wp_plugin_dealersolutionsinventorysearch_css_mode');
  $IS->configuration->title_mode = get_option('wp_plugin_dealersolutionsinventorysearch_title_mode');
  $IS->page = get_post($IS->configuration->page_id);

  // We need to merge the permalink with the actual hostname
  $getBlogInfoParseUrl = (object) parse_url(get_bloginfo('url'));
  $getBlogInfoParseUrl->host = $_SERVER['HTTP_HOST'];

  //$IS->page->permalink = get_permalink($IS->page->ID);
  $IS->page->permalink = str_replace(get_bloginfo('url'),unparse_url((array)$getBlogInfoParseUrl),get_permalink($IS->page->ID));
  $IS->page->permalink_path_info = str_replace(get_bloginfo('url'),'',get_permalink($IS->page->ID));
  unset($getBlogInfoParseUrl);

  if(empty($IS->configuration->definitioncode) === true and isset($IS->page->permalink_path_info) === true)
  {
    $IS->configuration->definitioncode = str_replace('/','_',trim($IS->page->permalink_path_info,'/'));
  }
  if(str_replace('new/','',$IS->configuration->definitioncode) === $IS->page->post_name)
  {
    $IS->configuration->definitioncode_is_prefix = true;
  }
  if($IS->configuration->type === '2') // New Vehicle Library
  {
    $IS->configuration->definitioncode = 'new/' . $IS->configuration->definitioncode;
  }
  $IS->configuration->path_info = '/' . $IS->configuration->definitioncode . ((get_query_var('path_info')) ? '/' . trim(get_query_var('path_info'),'/') . '/' : '/');
  $IS->configuration->permalink = $IS->page->permalink;
  $IS->configuration->definitioncode_full = ($IS->configuration->searchdefinition_domain) ? $IS->configuration->searchdefinition_domain . '_' . $IS->configuration->definitioncode : $IS->configuration->definitioncode;

  if(preg_match('%\A/'.preg_quote($IS->configuration->definitioncode).'/(?P<page>(?:ajax|javascript_makelist_ajax|xml_movie|sitemap))/%i',$IS->configuration->path_info,$regex_result))
  {
    // TODO: fix this to used provided content type
    $IS->configuration->raw_output_mode = strtolower($regex_result['page']);
    switch($IS->configuration->raw_output_mode)
    {
      case 'ajax':
      case 'javascript_makelist_ajax':
        $IS->configuration->raw_output_mode_content_type = 'application/json';
        break;
      case 'xml_movie':
      case 'sitemap':
        $IS->configuration->raw_output_mode_content_type = 'application/xml';
        break;
    }
    unset($regex_result);
  }
  elseif(preg_match('%\A\/'.preg_quote($IS->configuration->definitioncode).'\/.*\/output=ajax\/%i',$IS->configuration->path_info,$regex_result))
  {
    $IS->configuration->raw_output_cache = true; // This output can be cached
    $IS->configuration->raw_output_nocookies = true; // Don't feed user cookies back into the search, the output must be generic
    $IS->configuration->raw_output_mode = true;
    $IS->configuration->raw_output_mode_content_type = 'application/json';
  }
  else
  {
    $IS->configuration->raw_output_mode = false;
  }
  // END CONFIGURATION

  // START ADDITIONAL WORDPRESS HOOKS
  if(function_exists('wp_plugin_dealersolutionsinventorysearch_the_title') === false)
  {
    function wp_plugin_dealersolutionsinventorysearch_the_title($title)
    {
      if(in_the_loop())
      {
        return false;
      }
      else
      {
        return $title;
      }
    }
  }

  // Display an additional menu item when visiting this page
  if(current_user_can('manage_options'))
  {
    add_action('admin_bar_menu','wp_plugin_dealersolutionsinventorysearch_wp_admin_bar_menu');
    function wp_plugin_dealersolutionsinventorysearch_wp_admin_bar_menu($wp_admin_bar)
    {
      global $wp_admin_bar;
      $wp_admin_bar->add_node(array(
        'id' => 'dealersolutionsinventorysearch',
        'title' => 'Edit Inventory Search Settings',
        'parent' => 'edit',
        'href' => site_url() . '/wp-admin/options-general.php?page=wp_plugin_dealersolutionsinventorysearch_admin_page&post=' . get_the_ID()
      ));
    }
  }

  // Remove the title when content mode replaces or displays before
  if(function_exists('wp_plugin_dealersolutionsinventorysearch_the_posts') === false)
  {
    function wp_plugin_dealersolutionsinventorysearch_the_posts()
    {
      require('global.php');
      if(in_the_loop() and isset($IS->configuration) and isset($IS->configuration->content_mode) and ($IS->configuration->content_mode == '0' or $IS->configuration->content_mode == '2'))
      {
        add_filter('the_title','wp_plugin_dealersolutionsinventorysearch_the_title');
      }
    }
  }
  add_action('the_post','wp_plugin_dealersolutionsinventorysearch_the_posts' );

  // HEAD
  if(function_exists('wp_plugin_dealersolutionsinventorysearch_wp_head') === false)
  {
    function wp_plugin_dealersolutionsinventorysearch_wp_head()
    {
      return require('wp_head.php');
    }
  }
  add_action('wp_head','wp_plugin_dealersolutionsinventorysearch_wp_head',($IS->configuration->css_mode === '2') ? 5 : 10 );

  // FOOTER
  if(function_exists('wp_plugin_dealersolutionsinventorysearch_wp_footer') === false)
  {
    function wp_plugin_dealersolutionsinventorysearch_wp_footer()
    {
      return require('wp_footer.php');
    }
  }
  add_action('wp_footer','wp_plugin_dealersolutionsinventorysearch_wp_footer');

  // Grunt Work
  add_filter('the_content','wp_plugin_dealersolutionsinventorysearch_the_content',1000); // high number to ensure no other filters occurs first
  if(function_exists('wp_plugin_dealersolutionsinventorysearch_the_content') === false)
  {
    function wp_plugin_dealersolutionsinventorysearch_the_content($the_content)
    {
      return require('the_content.php');
    }
  }
  if(function_exists('wp_plugin_dealersolutionsinventorysearch_comments_template') === false)
  {
    function wp_plugin_dealersolutionsinventorysearch_comments_template()
    {
      return false;
    }
  }

  // END ADDITIONAL WORDPRESS HOOKS

  // Prevent canonical redirect
  remove_filter('template_redirect','redirect_canonical');

  // convert the current query string into an array
  if(isset($IS->request) === false)
    $IS->request = new stdClass();
  $IS->request->configuration = new stdClass();
  $IS->request->configuration->definitioncode_http_host   = (empty($IS->configuration->searchdefinition_domain) === false) ? $IS->configuration->searchdefinition_domain : $_SERVER['HTTP_HOST'];
  $IS->request->configuration->path_info                  = $IS->configuration->path_info;
  $IS->request->configuration->http_host                  = (isset($_SERVER['HTTP_HOST']) === true) ? $_SERVER['HTTP_HOST'] : null;
  $IS->request->configuration->http_referer               = (isset($_SERVER['HTTP_REFERER']) === true) ? $_SERVER['HTTP_REFERER'] : null;
  $IS->request->configuration->http_user_agent            = (isset($_SERVER['HTTP_USER_AGENT']) === true) ? $_SERVER['HTTP_USER_AGENT'] : null;
  $IS->request->configuration->remote_addr                = (isset($_SERVER['REMOTE_ADDR']) === true) ? $_SERVER['REMOTE_ADDR'] : null;
  $IS->request->configuration->request_method             = (isset($_SERVER['REQUEST_METHOD']) === true) ? $_SERVER['REQUEST_METHOD'] : null;

  // if the permalink doesn't end with a slash, we need to add it in.
  $permalinkParseUrl = (object) parse_url($IS->configuration->permalink);
  if(substr($permalinkParseUrl->path,-1,1) !== '/')
  {
    $permalinkParseUrl->path = $permalinkParseUrl->path . '/';
    $IS->request->configuration->base_url = unparse_url((array) $permalinkParseUrl);
  }
  else
    $IS->request->configuration->base_url = $IS->configuration->permalink;
  unset($permalinkParseUrl);

  // Dealer Solutions specific load balancer IP's, Gomenasai!
  if(is_ssl() === true or $_SERVER['SERVER_ADDR'] === '192.168.100.161' or $_SERVER['SERVER_ADDR'] === '192.168.100.162')
    $IS->request->configuration->base_url = str_replace('http://','https://',$IS->request->configuration->base_url);

  // Todo: watch this function, it may be depreciated in the future
  // [FB:31981] Issues with "Pro_cee'D" input
  if(isset($IS->request->_POST) === false)
    $IS->request->_POST = (isset($_POST) === true) ? stripslashes_deep($_POST) : array();
  if(isset($IS->request->_GET) === false)
    $IS->request->_GET = (isset($_GET) === true) ? stripslashes_deep($_GET) : array();

  $IS->curl = new stdClass();
  $IS->curl->configuration = new stdClass();
  
  // CURL Settings
  switch($IS->configuration->development_mode)
  {
    case '5':
      $IS->curl->configuration->url_domain = 'http://inventory.dealersolutionspreview.com.au/';
      break;
    case '4':
      $IS->curl->configuration->url_domain = 'http://' . $_SERVER['HTTP_HOST'] . '/' . (($IS->configuration->development_folder) ? trim($IS->configuration->development_folder,'/') . '/' : '');
      break;
    case '3':
      $IS->curl->configuration->url_domain = 'http://localhost/' . (($IS->configuration->development_folder) ? trim($IS->configuration->development_folder,'/') . '/' : '');
      break;
    case '2':
      $IS->curl->configuration->url_domain = 'http://dev.inventorysearch.com.au/';
      break;
    case '1':
      $IS->curl->configuration->url_domain = 'http://staging.inventorysearch.com.au/';
      break;
    default:
      $IS->curl->configuration->url_domain = 'http://www.inventorysearch.com.au/';
      break;
  }

  // Dealer Solutions specific load balancer IP's, Gomenasai!
  if(is_ssl() === true or $_SERVER['SERVER_ADDR'] === '192.168.100.161' or $_SERVER['SERVER_ADDR'] === '192.168.100.162')
    $IS->curl->configuration->url_domain = str_replace('http://','https://',$IS->curl->configuration->url_domain);

  $IS->curl->configuration->url = $IS->curl->configuration->url_domain . 'proxy_wordpress.php';// . $IS->configuration->path_info . ((isset($IS->curl->configuration->query) and !empty($IS->curl->configuration->query)) ? '?' . http_build_query($IS->curl->configuration->query) : NULL);

  $IS->curl->handle = curl_init();
  curl_setopt($IS->curl->handle,CURLOPT_REFERER,$IS->configuration->permalink);
  curl_setopt($IS->curl->handle,CURLOPT_USERAGENT,'InventorySearchWordpressClient/1.0');
  curl_setopt($IS->curl->handle,CURLOPT_POST,1);
  curl_setopt($IS->curl->handle,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
  curl_setopt($IS->curl->handle,CURLOPT_POSTFIELDS,json_encode($IS->request));
  curl_setopt($IS->curl->handle,CURLOPT_DNS_CACHE_TIMEOUT,10);
  curl_setopt($IS->curl->handle,CURLOPT_CONNECTTIMEOUT,10);
  curl_setopt($IS->curl->handle,CURLOPT_TIMEOUT,30);
  curl_setopt($IS->curl->handle,CURLOPT_URL,$IS->curl->configuration->url);
  curl_setopt($IS->curl->handle,CURLOPT_BINARYTRANSFER,1);
  curl_setopt($IS->curl->handle,CURLOPT_RETURNTRANSFER,1);
  curl_setopt($IS->curl->handle,CURLOPT_HEADER,1);

  // User Cookies
  if(isset($_COOKIE) and is_array($_COOKIE) and (isset($IS->configuration->raw_output_nocookies) === false or $IS->configuration->raw_output_nocookies !== true))
  {
    $cookies = array();
    foreach($_COOKIE as $cookiename => $cookievalue)
    {
      if(isset($cookiename) === true and empty($cookiename) === false and isset($cookievalue) === true and empty($cookievalue) === false and strpos($cookiename,'WPIS_') === 0)
      {
        $cookies[] = str_replace('WPIS_','',$cookiename) .'='. stripslashes($cookievalue);
      }
    }
    if(isset($cookies) === true and is_array($cookies) === true and empty($cookies) === false)
    {
      curl_setopt($IS->curl->handle, CURLOPT_COOKIE,implode('; ',$cookies));
    }
    unset($cookies);
  }

  // curl process

  // If raw output mode permits caching, prefer that
  if(isset($IS->configuration->raw_output_cache) === true and $IS->configuration->raw_output_cache === true)
  {
    $raw_output_cache_transient_request = array( $IS->request->configuration->definitioncode_http_host, $IS->request->configuration->path_info, $_GET,$_POST );
    $raw_output_cache_transient_curl_exec = substr('ds_cache_curl_exec_' . md5(serialize($raw_output_cache_transient_request)),0,45);
    $raw_output_cache_transient_curl_getinfo = substr('ds_cache_curl_getinfo_' . md5(serialize($raw_output_cache_transient_request)),0,45);
    if(($IS->curl->response = get_transient($raw_output_cache_transient_curl_exec)) == false or ($IS->curl->getinfo = get_transient($raw_output_cache_transient_curl_getinfo)) == false)
    {
      if(($IS->curl->response = curl_exec($IS->curl->handle)) == true and ($IS->curl->getinfo = (object) curl_getinfo($IS->curl->handle)) == true)
      {
        set_transient($raw_output_cache_transient_curl_exec,$IS->curl->response, 3600 );
        set_transient($raw_output_cache_transient_curl_getinfo,$IS->curl->getinfo, 3600 );
      }
    }
  }
  else
  {
    $IS->curl->response = curl_exec($IS->curl->handle);
    $IS->curl->getinfo = (object) curl_getinfo($IS->curl->handle);
  }

  unset($IS->curl->handle);

  // Process any Headers
  // May need to deal with HTTP/1.1 100 Continue
  if(isset($IS->curl->response)
    and empty($IS->curl->response) === false
    and preg_match('%HTTP/\\d\\.\\d(?! 100 Continue).*?(\\r\\n|\\n){2,}%si', $IS->curl->response,$regex_result)
    and isset($regex_result[0]) === true
    and empty($regex_result[0]) === false
    and preg_match_all('/^(((?P<key>\S*):\s(?P<value>.*))|(?P<line>.*))$/m',$regex_result[0],$regex_result,PREG_SET_ORDER)
    and isset($regex_result) === true
    and empty($regex_result) === false
    and is_array($regex_result)
    //and (isset($regex_result['key']) === true or isset($regex_result['value']) === true or isset($regex_result['line']) === true)
  )
  {
    $IS->curl->headers = new stdClass();
    foreach ($regex_result as $regex_item)
    {
      if(isset($regex_item['key'],$regex_item['value']) === true and empty($regex_item['key']) === false and empty($regex_item['value']) === false)
      {
        switch(strtolower($regex_item['key']))
        {
          case 'content-type':
          case 'content-encoding':
            $temp_index = str_replace('-','_',strtolower($regex_item['key']));
            $IS->curl->headers->$temp_index = trim($regex_item['value']);
            unset($temp_index);
            break;
          case 'location':
          case 'expires':
          case 'cache-control':
            header($regex_item['key'] . ': ' . $regex_item['value']);
            break;
          case 'set-cookie':
            if(isset($IS->configuration->raw_output_nocookies) === false)
            {
              header($regex_item['key'] . ': WPIS_' . $regex_item['value'],false);
            }
            break;
        }
      }
      elseif(isset($regex_item['line']) === true and empty($regex_item['line']) === false)
      {
        // MATCH HTTP/1.1 123 TEXT
        if(preg_match('%HTTP/[\\d.]* (?P<code>[\\d]{3}) [\\w ]*\\b%',$regex_item['line'],$regex_result_line))
          if((int) $regex_result_line['code'] >= 300)
          {
            http_response_code($regex_result_line['code']);
          }
      }
    }
    // Strip Headers
    $IS->curl->response = preg_replace('%HTTP/\\d\\.\\d.*?(\\r\\n|\\n){2,}%si','',$IS->curl->response);
  }
  unset($regex_result,$regex_item,$regex_result_line);

  if(isset($IS->curl->headers->content_encoding) === true)
  {
    switch($IS->curl->headers->content_encoding)
    {
      case 'base64':
        if($temp_gzuncompress = @gzuncompress(@base64_decode($IS->curl->response)))
        {
          $IS->curl->response = $temp_gzuncompress;
          unset($temp_gzuncompress);
        }
        break;
    }
  }
  else
  {
    $base64_tag_start = '<!--[BASE64DATA[';
    $base64_tag_end = ']]-->';
    $base64_offset_start = strpos($IS->curl->response,$base64_tag_start);
    if(empty($base64_offset_start) === false)
    {
      $base64_offset_start = $base64_offset_start + strlen($base64_tag_start);
      $base64_offset_length = strpos($IS->curl->response,$base64_tag_end,$base64_offset_start) - $base64_offset_start;
      if($base64_offset_length > 0)
      {
        $IS->curl->response = substr($IS->curl->response,$base64_offset_start,$base64_offset_length);
        if($temp_gzuncompress = gzuncompress(@base64_decode($IS->curl->response)))
        {
          $IS->curl->headers->content_type = 'application/json';
          $IS->curl->response = $temp_gzuncompress;
          unset($temp_gzuncompress);
        }
      }
    }
    unset(
      $base64_tag_start,
      $base64_tag_end,
      $base64_offset_start,
      $base64_offset_length
    );
  }

  // [FB:34464]
  if(is_numeric($IS->curl->getinfo->http_code) === true)
  {
    define('WP_PLUGIN_DEALERSOLUTIONSINVENTORYSEARCH_HTTP_CODE',$IS->curl->getinfo->http_code);
  }

  // AJAX Request
  if($IS->configuration->raw_output_mode !== false)
  {
    // TODO: fix this to used provided content type
    if(isset($IS->configuration->raw_output_mode_content_type) === true and empty($IS->configuration->raw_output_mode_content_type) === false)
      header('content-type: ' . $IS->configuration->raw_output_mode_content_type);
    echo trim($IS->curl->response);
    exit();

  }
  elseif(isset($IS->curl->headers->content_type) === true and $IS->curl->headers->content_type === 'application/json' and isset($IS->curl->response) === true and empty($IS->curl->response) === false and $json_decode = json_decode($IS->curl->response) and $IS->curl->response = $json_decode)
  {

    $IS->response = new stdClass();

    if(isset($IS->curl->response->information) === true)
      $IS->response->information = $IS->curl->response->information;

    $IS->response->html = new stdClass();

    if(isset($IS->curl->response->html->title) === true)
      $IS->response->html->title = $IS->curl->response->html->title;

    if(isset($IS->curl->response->html->meta) === true)
      $IS->response->html->meta = $IS->curl->response->html->meta;

    if(isset($IS->curl->response->html->link) === true)
      $IS->response->html->link = $IS->curl->response->html->link;

    if(isset($IS->curl->response->html->script_external) === true)
      $IS->response->html->script_external = $IS->curl->response->html->script_external;

    if(isset($IS->curl->response->html->script_inline) === true)
      $IS->response->html->script_inline = $IS->curl->response->html->script_inline;

    if(isset($IS->curl->response->html->style) === true)
      $IS->response->html->style = $IS->curl->response->html->style;

    if(isset($IS->curl->response->html->body) === true)
      $IS->response->html->body = $IS->curl->response->html->body;

    if(isset($IS->curl->response->html->body_script_external) === true)
      $IS->response->html->body_script_external = $IS->curl->response->html->body_script_external;

    if(isset($IS->curl->response->html->body_script_inline) === true)
      $IS->response->html->body_script_inline = $IS->curl->response->html->body_script_inline;

    if(isset($IS->curl->response->html->developer_console) === true and current_user_can('administrator') === true)
      $IS->response->html->developer_console = $IS->curl->response->html->developer_console;

    //if(isset($IS->curl->response->html->developer_information) === true and current_user_can('administrator') === true)
    //  $IS->response->html->developer_information = $IS->curl->response->html->developer_information;

    // Global  Configuration Items
    if(isset($IS->curl->response->configuration) === true and is_object($IS->curl->response->configuration) === true)
    {
      if(isset($IS->configuration->inventorysearch) === false)
        $IS->configuration->inventorysearch = new stdClass();
      foreach(get_object_vars ( $IS->curl->response->configuration ) as $objectVarKey => $objectVarValue)
        $IS->configuration->inventorysearch->$objectVarKey = $objectVarValue;
      unset($objectVarKey,$objectVarValue);
    }

    // Per Page configuration
    if(isset($IS->curl->response->pageconfiguration) === true and is_object($IS->curl->response->pageconfiguration) === true)
    {
      if(isset($IS->configuration->inventorysearch->page) === false or is_array($IS->configuration->inventorysearch->page) === false)
        $IS->configuration->inventorysearch->page = array();
      if(($pageconfiguration_index = wp_plugin_dealersolutionsinventorysearch_get_pageconfiguration_index($IS->configuration->page_id)) !== false)
      {
        foreach(get_object_vars ( $IS->curl->response->pageconfiguration ) as $objectVarKey => $objectVarValue)
          $IS->configuration->inventorysearch->page[$pageconfiguration_index]->$objectVarKey = $objectVarValue;
        unset($objectVarKey,$objectVarValue);
      }
      else
      {
        $pageconfigurationObject = new stdClass;
        $pageconfigurationObject->page_id = $IS->configuration->page_id;
        foreach(get_object_vars ( $IS->curl->response->pageconfiguration ) as $objectVarKey => $objectVarValue)
          $pageconfigurationObject->$objectVarKey = $objectVarValue;
        unset($objectVarKey,$objectVarValue);
        $IS->configuration->inventorysearch->page[] = $pageconfigurationObject;
      }
    }

    // Check if we need to update the config
    if(empty($IS->configuration->inventorysearch) === false and $IS->configuration->inventorysearch_hash !== md5(json_encode($IS->configuration->inventorysearch)))
    {
      if(isset($IS->configuration->inventorysearch->page) === true and is_array($IS->configuration->inventorysearch->page) === false)
      {
        foreach($IS->configuration->inventorysearch->page as $pageKey => $pageValue)
          if(get_post_status($pageValue->page_id) !== 'publish')
            unset($IS->configuration->inventorysearch->page[$pageKey]);
        unset($pageKey,$pageValue);
      }
      update_option('wp_plugin_dealersolutionsinventorysearch_configuration',json_encode($IS->configuration->inventorysearch));
      flush_rewrite_rules();
    }

    if(isset($IS->curl->response->configuration_proxy_layout) === true)
      $IS->configuration->proxy_layout = $IS->curl->response->configuration_proxy_layout;

  }
  else // Failsafe Realisticly this should only be for development
  {

    // unexpected JSON object
    if(isset($IS->response) === false)
      $IS->response = new stdClass();
    if(isset($IS->response->html) === false)
      $IS->response->html = new stdClass();
    $IS->response->html->body_failure = $IS->curl->response;
    $IS->response->html->body         = $IS->curl->response; // FINISH THIS
    echo $IS->curl->response;
    exit();

    // Deal with 400~500 errors when dealing with non standard replies (indicating something is very terminal)
    if(is_numeric($IS->curl->getinfo->http_code) === true and $IS->curl->getinfo->http_code >= 400)
    {
      $IS->errors->add('HTTP_' . $IS->curl->getinfo->http_code,'The Wordress plugin was unable to retrieve content from the Dealer Solutions Inventory Search webserver');
    }

  }
  // END JSON Data

  // Destruction of variables we no longer need
  unset($IS->curl);

  // Javascript Loading
    if(is_admin() === false)
  {
        wp_enqueue_script('jquery');
    }

  // Output Buffer - Capture the Entire page in output buffering
  if(function_exists('wp_plugin_dealersolutionsinventorysearch_ob_callback') === false)
  {
    function wp_plugin_dealersolutionsinventorysearch_ob_callback($buffer)
    {
      global $wp_plugin_dealersolutionsinventorysearch; // Had to use Root level global here
      // AJAX Mode (depreciated above with echo/exit combination)
      // if($IS->configuration->raw_output_mode)
      //   return $IS->response->ajax;
      // Developer Console
      if(isset($IS->response->html->developer_console) === true)
        $buffer = preg_replace('/(<BODY.*?>)/i','$1<!-- DEVELOPER CONSOLE -->' . $IS->response->html->developer_console . '<!-- DEVELOPER CONSOLE -->',$buffer);
      return $buffer;
    }
  }
  if(function_exists('wp_plugin_dealersolutionsinventorysearch_ob_start') === false)
  {
    function wp_plugin_dealersolutionsinventorysearch_ob_start()
    {
      ob_start('wp_plugin_dealersolutionsinventorysearch_ob_callback');
    }
  }
  if(function_exists('wp_plugin_dealersolutionsinventorysearch_ob_end') === false)
  {
    function wp_plugin_dealersolutionsinventorysearch_ob_end()
    {
      ob_end_flush();
    }
  }

  // Output Buffer - Copy conditions that may exist above to ensure we're not wasteful
  if(isset($IS->response->html->developer_console) === true)// or $IS->configuration->raw_output_mode)
  {
    add_action('wp_head','wp_plugin_dealersolutionsinventorysearch_ob_start',0);
    add_action('wp_footer','wp_plugin_dealersolutionsinventorysearch_ob_end',0);
  }

  function wp_plugin_dealersolutionsinventorysearch_wp_title($title)
  {
    global $wp_plugin_dealersolutionsinventorysearch;

    $separator = trim(str_replace(array(get_the_title(),get_bloginfo('name','display')),'',$title)); // attempt to figure out the separator

    if(isset($wp_plugin_dealersolutionsinventorysearch->response->html->title) === true and $inventory_title = strip_tags($wp_plugin_dealersolutionsinventorysearch->response->html->title) and empty($inventory_title) === false)
    {
      switch($wp_plugin_dealersolutionsinventorysearch->configuration->title_mode)
      {
        case '1': // replace
          $title = str_replace(get_the_title(),$inventory_title,$title);
          break;
        case '4': // replace (Aggressive)
          $title = $inventory_title;
          break;
        case '2': // append
          $title = $title . ((empty($separator) === false) ? ' ' . $separator . ' ' : ' ') . $inventory_title;
          break;
        case '3': // prepend
          $title = $inventory_title . ((empty($separator) === false) ? ' ' . $separator . ' ' : ' ') . $title;
          break;
      }
    }
    return $title;
  }
  if(empty($IS->configuration->title_mode) === false)
  {
    add_filter( 'wp_title', 'wp_plugin_dealersolutionsinventorysearch_wp_title', 20 );
  }

  // If these two options are set, assume we want to exclude the display of the wordpress site
  if(isset($IS->configuration->proxy_layout->exclude_header) === true and isset($IS->configuration->proxy_layout->exclude_foote) === true and $IS->configuration->proxy_layout->exclude_header and $IS->configuration->proxy_layout->exclude_footer)
  {
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
         wp_plugin_dealersolutionsinventorysearch_wp_head();
    echo '</head>';
    echo '<body>';
    echo '<div id="WPIS_canvas">';
    echo $IS->response->html->body;
         wp_plugin_dealersolutionsinventorysearch_wp_footer();
    echo '</div>';
    echo '</body>';
    echo '</html>';
    exit();
  }

  register_shutdown_function('wp_plugin_dealersolutionsinventorysearch_shutdown_function');

?>