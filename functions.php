<?php

  function enqueue_assets()
  {
    wp_register_script( 'inv_admin_js', plugins_url( 'javascript/wp-admin.js', __FILE__ ) );
    //wp_register_style( 'inv_admin_css', plugins_url( 'css/wp-admin.css', __FILE__ ) );
    //wp_register_script( 'inv_admin_js', '//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js' );
    //wp_register_style( 'inv_admin_css', '//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css' );
    wp_enqueue_script('inv_admin_js');
    wp_enqueue_style('inv_admin_css');
  }

  add_action('admin_menu','enqueue_assets');

  if(function_exists('array_getvalue') === false)
  {
    function array_getvalue($array = array(), $key)
    {
      return (isset($array[$key])) ? $array[$key] : null;
    }
  }

  if(function_exists('unparse_url') === false)
  {
    function unparse_url($parsed_url)
    {
      $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : null;
      $host     = isset($parsed_url['host']) ? $parsed_url['host'] : null;
      $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : null;
      $user     = isset($parsed_url['user']) ? $parsed_url['user'] : null;
      $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : null;
      $pass     = ($user || $pass) ? $pass . '@' : null;
      $path     = isset($parsed_url['path']) ? $parsed_url['path'] : null;
      $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : null;
      $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : null;
      return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
    }
  }

  // Tests if the current page's permalink has been setup as a rule, if not re-process rules
  // this detects a post's permalink updating but the inventory plugins configuration not
  function wp_plugin_dealersolutionsinventorysearch_edit_post($page_id)
  {
    include('global.php');
    $permalink_component = trim(str_replace(get_bloginfo('url'),null,get_permalink($page_id)),'/');
    foreach(get_option('rewrite_rules') as $ruleKey => $rule)
      if(strpos($ruleKey,'^'.$permalink_component.'(?:$|/((?:') === 0)
        return false; // this rule already exists
    if($configuration = $wpdb->get_row('SELECT `id`,`page_id`,`definitioncode`,`content_mode`,`type` FROM `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings` WHERE `type` > 0 AND `page_id` = ' . $page_id . ' LIMIT 1') and empty($configuration) === false and isset($configuration->definitioncode) === true and empty($configuration->definitioncode) === true)
    {
      if(($admin_notices = get_option('wp_plugin_dealersolutionsinventorysearch_admin_notices')) and empty($admin_notices) === false)
        $admin_notices = json_decode($admin_notices,true);
      else
        $admin_notices = array();
      $message = 'The permalink has changed for <a href="' . get_permalink($page_id) . '" target="_blank">' . get_permalink($page_id) . '</a>, please review this page to confirm Inventory Search functionality';
      $admin_notices[$page_id] = array(
        'page_id' => $page_id,
        'message' => $message
      );
      update_option('wp_plugin_dealersolutionsinventorysearch_admin_notices',json_encode($admin_notices));
    }
    flush_rewrite_rules();
  }

  function wp_plugin_dealersolutionsinventorysearch_admin_head()
  {
    require('admin_head.php');
  }

  function wp_plugin_dealersolutionsinventorysearch_generate_rewrite_rules()
  {
    include('global.php');
    // URL Processing - Moved from init.php
    if($configuration = $wpdb->get_results('SELECT `id`,`page_id`,`definitioncode`,`content_mode`,`type` FROM `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings` WHERE `type` > 0 ORDER BY `page_id`') and empty($configuration) === false and is_array($configuration) === true)
    {
      foreach($configuration as $config)
      {
        if(isset($config->page_id) and isset($config->definitioncode) and $page = get_post($config->page_id) and isset($page->post_name) and get_post_status($config->page_id) === 'publish')
        {
          $config->regex_postname = trim(str_replace(get_bloginfo('url'),null,get_permalink($config->page_id)),'/');
          $config->ancestors_count = count($page->ancestors);
        }
        else
          $config->ancestors_count = 0;
      }
      if(function_exists('wp_plugin_dealersolutionsinventorysearch_sort_object_by_ancestors_count') === false)
      {
        function wp_plugin_dealersolutionsinventorysearch_sort_object_by_ancestors_count($a,$b)
        {
          if($a->ancestors_count == $b->ancestors_count){return 0;}
          return ($a->ancestors_count > $b->ancestors_count) ? -1 : 1;
        }
      }
      usort($configuration,'wp_plugin_dealersolutionsinventorysearch_sort_object_by_ancestors_count');
      if(empty($IS->configuration->inventorysearch->regex->files_to_process) === false)
        $regex_files_to_process = implode('|',(array)$IS->configuration->inventorysearch->regex->files_to_process);
      else
        $regex_files_to_process = 'index'; // initial rules get updated with full configuration set
      foreach($configuration as $config)
      {
        // build the regex
        if(isset($config->regex_postname))
        {
          // Is this the front page id?
          if(get_option('show_on_front') === 'page' and get_option('page_on_front') === $config->page_id)
            $rewrite_rule = '^(?:((?:'.$regex_files_to_process.')(/.+|$)))';
          else
            $rewrite_rule = '^'.$config->regex_postname.'(?:$|/((?:'.$regex_files_to_process.')(/.+|$)))';
          $rewrite_redirect = 'index.php?page_id='.$config->page_id.'&path_info_pagename='.$config->regex_postname.'&path_info=$matches[1]';
          add_rewrite_rule($rewrite_rule,$rewrite_redirect,'top');
          unset($rewrite_rule,$rewrite_redirect);

          // [FB:41392] Additional Rewrite Rules...
          if(($pageconfiguration_index = wp_plugin_dealersolutionsinventorysearch_get_pageconfiguration_index($config->page_id)) !== false and isset($IS->configuration->inventorysearch->page,$IS->configuration->inventorysearch->page[$pageconfiguration_index],$IS->configuration->inventorysearch->page[$pageconfiguration_index]->additionalrewriterules) === true and empty($IS->configuration->inventorysearch->page[$pageconfiguration_index]->additionalrewriterules) === false)
          {
            foreach($IS->configuration->inventorysearch->page[$pageconfiguration_index]->additionalrewriterules as $additionalrewriterule)
            {
              $rewrite_rule = '^'.$config->regex_postname.'/' . trim($additionalrewriterule->from,'/') . '$';
              $rewrite_redirect = 'index.php?page_id='.$config->page_id.'&path_info_pagename='.$config->regex_postname.'&path_info=' . trim($additionalrewriterule->to,'/');
              add_rewrite_rule($rewrite_rule,$rewrite_redirect,'top');
              unset($rewrite_rule,$rewrite_redirect);
              // lowercase version eg FJ vs fj
              if(strtolower($additionalrewriterule->from) !== $additionalrewriterule->from)
              {
                $rewrite_rule = '^'.$config->regex_postname.'/' . trim(strtolower($additionalrewriterule->from),'/') . '$';
                $rewrite_redirect = 'index.php?page_id='.$config->page_id.'&path_info_pagename='.$config->regex_postname.'&path_info=' . trim($additionalrewriterule->to,'/');
                add_rewrite_rule($rewrite_rule,$rewrite_redirect,'top');
                unset($rewrite_rule,$rewrite_redirect);
              }
            }
          }
        }
      }
      // Enable Legcay Redirects: /view.php/alias/ redirects to /permalink/view/
      $legacyUrlRedirect = get_option('wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect');
      if(isset($legacyUrlRedirect) === true and $legacyUrlRedirect == true)
      {
        $legacyUrlRedirectConsumed = array();
        foreach($configuration as $config)
        {
          if(isset($config->regex_postname) and $config->regex_postname and isset($config->definitioncode) and $config->definitioncode and array_search($config->definitioncode,$legacyUrlRedirectConsumed) === false)
          {
            $definitioncode = ($config->type == 2) ? 'new/' . $config->definitioncode : $config->definitioncode;
            $definitioncodeArray = array_unique(array(trim(str_replace('-','_',$definitioncode),'_'),trim(str_replace('_','-',$definitioncode),'-')));
            $rewrite_rule = '^('.$regex_files_to_process.').php/(' . implode('|',$definitioncodeArray) . ')(?:\z|/(.*))';
            $rewrite_redirect = 'index.php?pagename='.$config->regex_postname.'&inventory_legacy_page=$matches[1]&inventory_legacy_path=$matches[3]';
            add_rewrite_rule($rewrite_rule,$rewrite_redirect,'top');
            $legacyUrlRedirectConsumed[] = $config->definitioncode;
            unset($rewrite_rule,$rewrite_redirect,$definitioncodeArray);
          }
        }
      }
    }
    unset($configuration,$config,$regex_files_to_process);
  }

  function wp_plugin_dealersolutionsinventorysearch_get_pageconfiguration_index($page_id)
  {
    require('global.php');
    if(isset($IS->configuration,$IS->configuration->inventorysearch,$IS->configuration->inventorysearch->page) === true and empty($IS->configuration->inventorysearch->page) === false and is_array($IS->configuration->inventorysearch->page) === true)
      foreach($IS->configuration->inventorysearch->page as $pageconfigurationKey => $pageconfigurationValue)
        if($pageconfigurationValue->page_id == $page_id)
          return $pageconfigurationKey;
    return false;
  }

  if (!function_exists('http_response_code'))
  {
    function http_response_code($code = NULL)
    {
      if($code !== NULL)
      {
        switch ((int)$code)
        {
          case 100: $text = 'Continue'; break;
          case 101: $text = 'Switching Protocols'; break;
          case 200: $text = 'OK'; break;
          case 201: $text = 'Created'; break;
          case 202: $text = 'Accepted'; break;
          case 203: $text = 'Non-Authoritative Information'; break;
          case 204: $text = 'No Content'; break;
          case 205: $text = 'Reset Content'; break;
          case 206: $text = 'Partial Content'; break;
          case 300: $text = 'Multiple Choices'; break;
          case 301: $text = 'Moved Permanently'; break;
          case 302: $text = 'Moved Temporarily'; break;
          case 303: $text = 'See Other'; break;
          case 304: $text = 'Not Modified'; break;
          case 305: $text = 'Use Proxy'; break;
          case 400: $text = 'Bad Request'; break;
          case 401: $text = 'Unauthorized'; break;
          case 402: $text = 'Payment Required'; break;
          case 403: $text = 'Forbidden'; break;
          case 404: $text = 'Not Found'; break;
          case 405: $text = 'Method Not Allowed'; break;
          case 406: $text = 'Not Acceptable'; break;
          case 407: $text = 'Proxy Authentication Required'; break;
          case 408: $text = 'Request Time-out'; break;
          case 409: $text = 'Conflict'; break;
          case 410: $text = 'Gone'; break;
          case 411: $text = 'Length Required'; break;
          case 412: $text = 'Precondition Failed'; break;
          case 413: $text = 'Request Entity Too Large'; break;
          case 414: $text = 'Request-URI Too Large'; break;
          case 415: $text = 'Unsupported Media Type'; break;
          case 500: $text = 'Internal Server Error'; break;
          case 501: $text = 'Not Implemented'; break;
          case 502: $text = 'Bad Gateway'; break;
          case 503: $text = 'Service Unavailable'; break;
          case 504: $text = 'Gateway Time-out'; break;
          case 505: $text = 'HTTP Version not supported'; break;
          default:
            exit('Unknown http status code "' . htmlentities($code) . '"');
            break;
        }
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol . ' ' . $code . ' ' . $text);
        $GLOBALS['http_response_code'] = $code;
      }
      else
      {
        $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
      }
      return $code;
    }
  }

  function wp_plugin_dealersolutionsinventorysearch_process_the_content(&$the_content = null)
  {
    require('global.php');
    // [FB:22258] Mechanism to display dynamic WordPress post/page content in arbitrary locations on a template
    // if <!-- wordpress:function() --> exists in the content, replace it
    // if [wordpress:function()] exists in the content, replace it
    // <!--\s?wordpress::(?<function>[a-z_-]+)(?:\((?<arguments>.*?)\))?\s?-->
    //if(preg_match_all('/(?:<!--\s?|\[|%5B)wordpress::(?P<function>[a-z_-]+)(?:\((?P<arguments>.*?)\))?(?:\s?-->|\]|%5D)/i',$IS->response->html->body,$wordpressTags,PREG_SET_ORDER))
    if(preg_match_all('/(?:<!--\s?|\[|%5B)wordpress::(?P<function>[a-z_-]+)(?:\((?P<arguments>.*?)\))?(?:\s?-->|\]|%5D)/i',$IS->response->html->body,$wordpressTags,PREG_SET_ORDER))
    {
      foreach($wordpressTags as $tag)
      {
        if(isset($tag['function']) === false or empty($tag['function']) === true or strpos($IS->response->html->body,$tag[0]) === false)
          continue;
        $arguments = explode(',',$tag['arguments']);
        switch($tag['function'])
        {
          // case get the content of this page
          case 'get_the_content':
            if($IS->configuration->content_mode !== '3' and empty($the_content) === false)
              $IS->configuration->content_mode = null; // If "get_the_content" is found, set content mode to "replace"
            if(empty($the_content) === false)
              $IS->response->html->body = str_replace($tag[0],$the_content,$IS->response->html->body);
            else
              $IS->response->html->body = str_replace($tag[0],'<!-- wordpress::get_the_content() content not found -->',$IS->response->html->body);
            break;
          // get a post by its ID
          case 'get_post':
            if(empty($arguments[0]) === false and is_numeric($arguments[0]) === true and ($post = get_post($arguments[0])) !== false and empty($post->post_content) === false)
              $IS->response->html->body = str_replace($tag[0],$post->post_content,$IS->response->html->body);
            else
              $IS->response->html->body = str_replace($tag[0],'<!-- wordpress::get_post() content not found -->',$IS->response->html->body);
            break;
          // get a page by its title
          case 'get_page_by_title':
            if(empty($arguments[0]) === false and ($result = get_page_by_title($arguments[0])) !== false and empty($result) === false)
              $IS->response->html->body = str_replace($tag[0],$result->post_content,$IS->response->html->body);
            else
              $IS->response->html->body = str_replace($tag[0],'<!-- wordpress::get_page_by_title() content not found -->',$IS->response->html->body);
            break;
          // get a post by its title
          case 'get_post_by_title':
            if(empty($arguments[0]) === false and ($result = get_page_by_title($arguments[0],'OBJECT','post')) !== false and empty($result) === false)
              $IS->response->html->body = str_replace($tag[0],$result->post_content,$IS->response->html->body);
            else
              $IS->response->html->body = str_replace($tag[0],'<!-- wordpress::get_page_by_title() content not found -->',$IS->response->html->body);
            break;
          // get the page link by ID
          case 'get_page_link':
            if(empty($arguments[0]) === false and is_numeric($arguments[0]) === true and ($result = get_page_link($arguments[0])) !== false and empty($result) === false)
              $IS->response->html->body = str_replace($tag[0],$result,$IS->response->html->body);
            else
              $IS->response->html->body = str_replace($tag[0],'#404-pagelink-not-found',$IS->response->html->body);
            break;
          // get the permalink by ID
          case 'get_permalink':
            if(empty($arguments[0]) === false and is_numeric($arguments[0]) === true and ($result = get_permalink($arguments[0])) !== false and empty($result) === false)
              $IS->response->html->body = str_replace($tag[0],$result,$IS->response->html->body);
            else
              $IS->response->html->body = str_replace($tag[0],'#404-permalink-not-found',$IS->response->html->body);
            break;
          // get a page by its permalink path
          case 'get_page_by_path':
            if(empty($arguments[0]) === false and ($result = get_page_by_path($arguments[0])) !== false and empty($result) === false)
              $IS->response->html->body = str_replace($tag[0],$result->post_content,$IS->response->html->body);
            else
              $IS->response->html->body = str_replace($tag[0],'<!-- wordpress::get_page_by_path() content not found -->',$IS->response->html->body);
            break;
          // [FB:27028] get a contact detail from plugin http://wordpress.org/plugins/contact/
          case 'contact_detail':
            if(function_exists('contact_detail') === false)
              $IS->response->html->body = str_replace($tag[0],'<!-- wordpress::contact_detail() function not found -->',$IS->response->html->body);
            if(empty($arguments[0]) === false and ($result = contact_detail($arguments[0],false,false,false)) !== false and empty($result) === false)
              $IS->response->html->body = str_replace($tag[0],$result,$IS->response->html->body);
            else
              $IS->response->html->body = str_replace($tag[0],'<!-- wordpress::contact_detail() content not found -->',$IS->response->html->body);
            break;
          // Default
          default:
            $IS->response->html->body = str_replace($tag[0],'<!-- wordpress::function() not handled -->',$IS->response->html->body);
        }
      }
      unset($wordpressTags,$tag,$arguments,$post);
    }

    // Wrap $IS->response->html->body in a DIV
    $IS->response->html->body = '<div id="WPIS_canvas">' . $IS->response->html->body . '</div>';
  }

  function wp_plugin_dealersolutionsinventorysearch_shutdown_function()
  {
    require('global.php');
    if($IS->configuration->content_mode === '3' and headers_sent() === true and (isset($IS->response->rendered) === false or empty($IS->response->rendered) === true))
    {
      echo '<!-- DealerSolutions.com.au Inventory Search has been configured to use "the_inventorysearch()" function, but was not rendered by the time the page finished -->';
    }
  }

  function the_inventorysearch()
  {
    require('global.php');
    if($IS->configuration->content_mode !== '3')
    {
      echo '<!-- the content mode setting is preventing the_inventorysearch() from displaying -->';
      return false;
    }
    elseif(preg_match_all('/(?:<!--\s?|\[|%5B)wordpress::(?P<function>[a-z_-]+)(?:\((?P<arguments>.*?)\))?(?:\s?-->|\]|%5D)/i',$IS->response->html->body,$wordpressTags,PREG_SET_ORDER))
    {
      foreach($wordpressTags as $tag)
      {
        if(isset($tag['function']) === false or empty($tag['function']) === true or strpos($IS->response->html->body,$tag[0]) === false)
        {
          continue;
        }
        switch($tag['function'])
        {
          // case get the content of this page
          case 'get_the_content':
            $IS->response->html->body = str_replace($tag[0],'<!-- the wordpress ' . $tag['function'] . '() tag removed due to being called within the_inventorysearch() -->',$IS->response->html->body);
            break;
        }
      }
    }
    wp_plugin_dealersolutionsinventorysearch_process_the_content();
    $IS->response->rendered = true; // Set when html is being displayed
    echo $IS->response->html->body . '<!-- inventory search rendered by "the_inventorysearch" -->';
  }

  function get_inventorysearch_pagetype()
  {
    require('global.php');
    return (isset($IS->response->information,$IS->response->information->page) === true) ? $IS->response->information->page : false;
  }

  function get_inventorysearch_urlparameter($type = null)
  {
    require('global.php');
    return (empty($type) === false and isset($IS->response->information->urlparameter,$IS->response->information->urlparameter,$IS->response->information->urlparameter->$type) === true) ? $IS->response->information->urlparameter->$type : null;
  }

  function wp_plugin_dealersolutionsinventorysearch_get_the_title()
  {
    require('global.php');
    if(isset($IS->response->html->title) === true and ($title = strip_tags($IS->response->html->title)) and empty($title) === false)
    {
      return $title;
    }
    else
    {
      return null;
    }
  }

  function wp_plugin_dealersolutionsinventorysearch_get_specific_page_list_configuration($source = false)
  {

    $specific_page_list = array(
      array(
        'page_name'=>'Search', // Key
        'page_alias'=>'search',
        'title_mode'=>null
      ),
      array(
        'page_name'=>'List', // Key
        'page_alias'=>'list',
        'title_mode'=>null
      ),
      array(
        'page_name'=>'Detail', // Key
        'page_alias'=>'view',
        'title_mode'=>null
      ),
      array(
        'page_name'=>'Category', // Key
        'url_pattern'=>'%/category=[^/]+/%',
        'title_mode'=>null
      )
    );

    $page_specific_rules = (empty($source) === false and is_array($source) === true) ? $source : json_decode(get_option('wp_plugin_dealersolutionsinventorysearch_page_specific_rules'),true);

    if(empty($page_specific_rules) === false)
    {
      foreach($page_specific_rules as $outerKey => $outerValue)
      {
        foreach($specific_page_list as $innerKey => $innerValue)
        {
          if(strtolower($innerValue['page_name']) === strtolower($outerValue['page_name']))
          {
            // overlay the title mode
            $specific_page_list[$innerKey]['title_mode'] = $outerValue['title_mode'];
          }
        }
      }
    }

    return $specific_page_list;

  }

  function wp_plugin_dealersolutionsinventorysearch_get_page_title_mode()
  {
    include('global.php');
    $specific_page_list = wp_plugin_dealersolutionsinventorysearch_get_specific_page_list_configuration();
    if($IS->response->information->page === 'index')
    {
      $page_alias = (isset($IS->response->information->defaultindexpage) === true) ? $IS->response->information->defaultindexpage : 'search';
    }
    else
    {
      $page_alias = $IS->response->information->page;
    }
    // First Attempt via url_pattern
    $current = parse_url('http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
    foreach($specific_page_list as $page)
    {
      if(isset($current['path'],$page['url_pattern'],$page['title_mode']) === true and preg_match($page['url_pattern'],$current['path']))
      {
        return (strlen($page['title_mode']) === 0) ? $IS->configuration->title_mode : $page['title_mode'];
        break;
      }
    }
    // Second Attempt via page alias
    foreach($specific_page_list as $page)
    {
      if(isset($page['page_alias'],$page['title_mode']) === true and $page['page_alias'] === $page_alias)
      {
        return (strlen($page['title_mode']) === 0) ? $IS->configuration->title_mode : $page['title_mode'];
      }
    }
    return $IS->configuration->title_mode;
  }