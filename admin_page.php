<?php require('global.php');

// count how many update/insert/delete queries are successful
$update_count = 0;

// Check that the Database tables exist
if($_SERVER['REQUEST_METHOD'] !== 'POST' and $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}plugin_dealersolutionsinventorysearch_page_settings'") == false)
{
  wp_plugin_dealersolutionsinventorysearch_activation('database');
  add_settings_error('dealersolutionsinventorysearch','wp_plugin_dealersolutionsinventorysearch_admin_page_error','Inventory Search database tables restored','updated');
}

// if we have a post request
if($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['wp_plugin_dealersolutionsinventorysearch']) and is_array($_POST['wp_plugin_dealersolutionsinventorysearch']))
{

  // update the global config
  if(isset($_POST['wp_plugin_dealersolutionsinventorysearch']['searchdefinition_domain']) and update_option('wp_plugin_dealersolutionsinventorysearch_searchdefinition_domain',preg_replace('%(https?://|www\.|/.*)%','',$_POST['wp_plugin_dealersolutionsinventorysearch']['searchdefinition_domain'])))
  {
    $update_count++;
  }
  if(isset($_POST['wp_plugin_dealersolutionsinventorysearch']['development_mode']) and update_option('wp_plugin_dealersolutionsinventorysearch_development_mode',$_POST['wp_plugin_dealersolutionsinventorysearch']['development_mode']))
  {
    $update_count++;
  }
  if(isset($_POST['wp_plugin_dealersolutionsinventorysearch']['development_folder']) and update_option('wp_plugin_dealersolutionsinventorysearch_development_folder',trim($_POST['wp_plugin_dealersolutionsinventorysearch']['development_folder'],'/')))
  {
    $update_count++;
  }
  if(isset($_POST['wp_plugin_dealersolutionsinventorysearch']['css_mode']) and update_option('wp_plugin_dealersolutionsinventorysearch_css_mode',trim($_POST['wp_plugin_dealersolutionsinventorysearch']['css_mode'],'/')))
  {
    $update_count++;
  }
  if(isset($_POST['wp_plugin_dealersolutionsinventorysearch']['title_mode']) and update_option('wp_plugin_dealersolutionsinventorysearch_title_mode',trim($_POST['wp_plugin_dealersolutionsinventorysearch']['title_mode'],'/')))
  {
    $update_count++;
  }
  if(isset($_POST['wp_plugin_dealersolutionsinventorysearch']['page_specific_rules']) and update_option('wp_plugin_dealersolutionsinventorysearch_page_specific_rules',json_encode(wp_plugin_dealersolutionsinventorysearch_get_specific_page_list_configuration($_POST['wp_plugin_dealersolutionsinventorysearch']['page_specific_rules']))))
  {
    $update_count++;
  }
  if(isset($_POST['wp_plugin_dealersolutionsinventorysearch']['legacy_url_redirect']) and update_option('wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect',trim($_POST['wp_plugin_dealersolutionsinventorysearch']['legacy_url_redirect'],'/')))
  {
    $update_count++;
  }

}

if(($admin_notices = get_option('wp_plugin_dealersolutionsinventorysearch_admin_notices')) != false and empty($admin_notices) === false and ($admin_notices = json_decode($admin_notices,true)) !== false and empty($admin_notices) === false and is_array($admin_notices) === true)
{
  foreach($admin_notices as $admin_notice)
  {
    add_settings_error('dealersolutionsinventorysearch','wp_plugin_dealersolutionsinventorysearch_admin_page_error','<div style="float:right;" class="button-secondary wp_plugin_dealersolutionsinventorysearch_admin_notice_remove" data-page-id="'.$admin_notice['page_id'].'">Remove this notice</div>' . $admin_notice['message'] . '<div class="clear:both;"></div>','error');
  }
}
unset($admin_notices,$admin_notice);

// if we have a post request
if($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['wp_plugin_dealersolutionsinventorysearch']['page']) and is_array($_POST['wp_plugin_dealersolutionsinventorysearch']['page']))
{

  // update the per page config
  foreach($_POST['wp_plugin_dealersolutionsinventorysearch']['page'] as $page)
  {

    // if we have a [id] but no [searchdefinition] consider it a deletion
    if(isset($page['id']) and !empty($page['id']) and (isset($page['type']) == false or empty($page['type']) or $page['type'] === '0'))
    {
      if(
        $wpdb->query(
          $wpdb->prepare(
            'DELETE FROM `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings` WHERE id = \'%d\''
            ,$page['id']
          )
        )
      )
      {
        $update_count++;
      }
    } // end if

    // if we have a [id] and we have [searchdefinition] consider it an update
    elseif(isset($page['id']) and !empty($page['id']))
    {
      if(
        $wpdb->update(
          // TABLE
          $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings',
          // DATA
          array(
            'id' => $page['id'],
            'definitioncode' => $page['definitioncode'],
            'content_mode' => $page['content_mode'],
            'type' => $page['type']
          ),
          // WHERE
          array(
            'id' => $page['id']
          ),
          // FORMAT
          array(
            '%d',
            '%s'
          ),
          // WHERE FORMAT
          array( '%d' ) 
        )
      )
      {
        $update_count++;
      }
    } // end elseif

    // if we have [searchdefinition] but no [id] consider it an insert
    elseif(isset($page['page_id']) and !empty($page['page_id']) and (isset($page['type']) and !empty($page['type'])))
    {
      if(
        $wpdb->insert(
          // TABLE
          $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings',
          // DATA
          array(
            'page_id' => $page['page_id'],
            'definitioncode' => $page['definitioncode'],
            'content_mode' => $page['content_mode'],
            'type' => $page['type']
          ),
          // FORMAT
          array(
            '%d',
            '%s'
          )
        )
      )
      {
        $update_count++;
      }
    } // end elseif

  } // end foreach loop
} // end if post

// Used to force a rule flush and display the message
if(isset($_REQUEST['wp_plugin_dealersolutionsinventorysearch']['generate_rewrite_rules']) === true)
{
  $update_count++;
  add_settings_error('dealersolutionsinventorysearch','wp_plugin_dealersolutionsinventorysearch_admin_page_error','Wordpress Rewrite rules have been flushed.','updated');
}

// Update the rewrite rules
if($update_count > 0)
{
  flush_rewrite_rules();
}

// Error Handling - Test if curl is loaded
if(in_array('curl',get_loaded_extensions()) === false)
{
  add_settings_error('dealersolutionsinventorysearch','wp_plugin_dealersolutionsinventorysearch_admin_page_error','PHP extension "cURL" is not avilable. <a href="http://www.php.net/manual/en/curl.installation.php" target="_blank">http://www.php.net/manual/en/curl.installation.php</a>','error');
}

// Error Handling - Test if zlib is loaded
if(in_array('zlib',get_loaded_extensions()) === false)
{
  add_settings_error('dealersolutionsinventorysearch','wp_plugin_dealersolutionsinventorysearch_admin_page_error','PHP extension "zlib" is not avilable. <a href="http://www.php.net/manual/en/zlib.installation.php" target="_blank">http://www.php.net/manual/en/zlib.installation.php</a>','error');
}

// Error Handling - Test permalink configuration
if(empty($IS->get_option->permalink_structure) === true)
{
  add_settings_error('dealersolutionsinventorysearch','wp_plugin_dealersolutionsinventorysearch_admin_page_error','Inventory Search does not work with the default <em>Permalink</em> Setting. <a href="'.admin_url('options-permalink.php').'">Permalink Settings</a>','error');
}

// Error Handling - Test if any updates occured
if($update_count > 0)
{
  add_settings_error('dealersolutionsinventorysearch','wp_plugin_dealersolutionsinventorysearch_admin_page_error','Inventory Search configuration has been updated.','updated');
}

// Jump to post
if(isset($_GET['post']) === true and empty($_GET['post']) === false and is_numeric($_GET['post']) === true)
{
  $jumpToPost = get_post($_GET['post']);
}

$configuration_pagelist = new stdClass;

// Duplicate Definitioncode Check
/*
if($duplicate_check = $wpdb->get_results('
  SELECT `duplicate`.`definitioncode` AS secondary_definitioncode,
         `duplicate`.`id` AS secondary_id,
         `duplicate`.`page_id` AS secondary_page_id,
         `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`definitioncode` AS primary_definitioncode,
         `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`id` AS primary_id,
         `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`page_id` AS primary_page_id
  FROM `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`
    RIGHT JOIN `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings` AS duplicate
      ON ( `duplicate`.`definitioncode` = `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`definitioncode`
        AND `duplicate`.`id` != `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`id`
        AND `duplicate`.`page_id` != `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`page_id`
        AND `duplicate`.`type` = `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`type`
      )
  WHERE `duplicate`.`type` != 0 AND `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`type` != 0
  GROUP BY CONCAT_WS(\'@\',`duplicate`.`definitioncode`,`duplicate`.`type`)
') AND isset($duplicate_check) === true and empty($duplicate_check) === false and is_array($duplicate_check) === true and empty($duplicate_check) === false)
{
  foreach($duplicate_check as $duplicate)
  {
    $primary_page = get_post($duplicate->primary_page_id);
    $secondary_page = get_post($duplicate->secondary_page_id);
    add_settings_error('dealersolutionsinventorysearch','wp_plugin_dealersolutionsinventorysearch_admin_page_error','Search Code "<em>'.((empty($duplicate->definitioncode) === FALSE) ? $duplicate->definitioncode : 'empty').'</em>" for the "<a href="'.get_permalink($primary_page->ID).'" target="_blank">'.$primary_page->post_title.'</a>" page, appears as a duplicate configuration for the "<a href="'.get_permalink($secondary_page->ID).'" target="_blank">'.$secondary_page->post_title.'</a>" page','error');
  }
  unset($duplicate_check,$duplicate);
}
*/

$internal_pagelist = $wpdb->get_results('
  SELECT `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`id`,
         `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`page_id`,
         `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`definitioncode`,
         `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`content_mode`,
         `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`type`
  FROM `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`
  LEFT JOIN `' . $wpdb->prefix . 'posts` ON (`' . $wpdb->prefix . 'posts`.`ID` = `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`page_id`)
  ORDER BY IF(`' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings`.`type`,1,0) DESC, `' . $wpdb->prefix . 'posts`.`post_title` ASC
');
if(isset($internal_pagelist) and is_array($internal_pagelist))
{
  foreach($internal_pagelist as $item)
  {
    $configuration_pagelist->{$item->page_id} = new stdClass();
    $configuration_pagelist->{$item->page_id}->definitioncode = $item->definitioncode;
    $configuration_pagelist->{$item->page_id}->id = $item->id;
    $configuration_pagelist->{$item->page_id}->page_id = $item->page_id;
    $configuration_pagelist->{$item->page_id}->content_mode = $item->content_mode;
    $configuration_pagelist->{$item->page_id}->type = $item->type;
    $configuration_pagelist->{$item->page_id}->post_status = get_post_status($item->page_id);
  }
}

$external_pagelist = get_pages(0,'ASC','id');
if(isset($external_pagelist) and is_array($external_pagelist))
{
  foreach($external_pagelist as $item)
  {
    $page = get_post($item->ID);
    if(isset($configuration_pagelist->{$item->ID}) === false)
      $configuration_pagelist->{$item->ID} = new stdClass();
    $configuration_pagelist->{$item->ID}->post_name = $page->post_name;
    $configuration_pagelist->{$item->ID}->post_title = $item->post_title;
    $configuration_pagelist->{$item->ID}->guid = $item->guid;
    $configuration_pagelist->{$item->ID}->page_id = $item->ID;
    $configuration_pagelist->{$item->ID}->page_exists = true;
    $configuration_pagelist->{$item->ID}->page_uri = get_page_uri($item->ID);
    if(isset($configuration_pagelist->{$item->ID}->definitioncode) === false)
      $configuration_pagelist->{$item->ID}->definitioncode = null;
    if(isset($configuration_pagelist->{$item->ID}->type) === false)
      $configuration_pagelist->{$item->ID}->type = 0;
    if(isset($configuration_pagelist->{$item->ID}->content_mode) === false)
      $configuration_pagelist->{$item->ID}->content_mode = false;
    if(isset($configuration_pagelist->{$item->ID}->id) === false)
      $configuration_pagelist->{$item->ID}->id = null;
    if(isset($configuration_pagelist->{$item->ID}->post_status) === false or empty($configuration_pagelist->{$item->ID}->post_status) === true)
      $configuration_pagelist->{$item->ID}->post_status = get_post_status($item->page_id);
  }
}

if(isset($configuration_pagelist))
{
  foreach($configuration_pagelist as $key => $item)
  {
    if(isset($item->post_status) === true and in_array($item->post_status,array('draft','pending','trash','private')) === true and ($get_post = get_post($item->page_id)) !== null)
    {
      $item->guid = $get_post->guid;
      $item->post_name = $get_post->post_name;
      $item->post_title = $get_post->post_title;
    }
    elseif(isset($item->page_exists) === false)
    {
      unset($configuration_pagelist->$key);
      continue;
    }
    if(isset($item->page_id) AND $item->ancestors = get_ancestors($item->page_id,'page') AND isset($item->ancestors) and !empty($item->ancestors) AND is_array($item->ancestors))
    {
      $item->permalink = get_permalink($item->page_id);
      $item->permalink_path_info = str_replace(get_bloginfo('url'),'',get_permalink($item->page_id));
      $item->definitioncode_default = str_replace('/','_',trim($item->permalink_path_info,'/'));
      foreach($item->ancestors as $key => $ancestor)
      {
        $item->ancestors_post_title[$key] = get_the_title($ancestor);
      }
      if(isset($item->ancestors_post_title) and !empty($item->ancestors_post_title))
      {
        $item->post_title = implode(' / ',array_merge($item->ancestors_post_title,array($item->post_title)));
      }
    }
    if(isset($configuration_pagelist->type) == false OR empty($configuration_pagelist->type) OR $configuration_pagelist->type === '0')
    {
      $configuration_hidden_pages = true;
    }
    if(isset($IS->configuration->inventorysearch->regex->files_to_process) === true and ($permalink_collision_key = array_search(basename(get_permalink($item->page_id)),(array)$IS->configuration->inventorysearch->regex->files_to_process)) !== false)
    {
      $item->permalink_collision = $IS->configuration->inventorysearch->regex->files_to_process->$permalink_collision_key;
    }
    unset($permalink_collision_key);
  }
}

?>

<div class="wrap">

  <div id="icon-options-general" class="icon32"><br /></div>

  <h2>DealerSolutions.com.au Inventory Search Settings</h2>

  <?php settings_errors(); ?>
  
  <h2 class="nav-tab-wrapper">
    <a id="globalTab-nav" href="javascript:setActiveTab('globalTab');" class="nav-tab">Global Settings</a>
    <a id="pagesTab-nav" href="javascript:setActiveTab('pagesTab');" class="nav-tab">Page Settings</a>
  </h2>

  <form method="post" action="<?php echo get_admin_url(null,'options-general.php?page=wp_plugin_dealersolutionsinventorysearch_admin_page');?>" id="wp_plugin_dealersolutionsinventorysearch_form_show_hidden_pages"><input type="hidden" name="wp_plugin_dealersolutionsinventorysearch[show_hidden_pages]" value="1"></form>

  <form method="post" action="<?php echo get_admin_url(null,'options-general.php?page=wp_plugin_dealersolutionsinventorysearch_admin_page');?>" id="wp_plugin_dealersolutionsinventorysearch_form_admin_page">
    <!--
    <input type="hidden" name="wp_plugin_dealersolutionsinventorysearch[show_hidden_pages]" value="<?php if(isset($_REQUEST['wp_plugin_dealersolutionsinventorysearch']['show_hidden_pages']) === true AND $_REQUEST['wp_plugin_dealersolutionsinventorysearch']['show_hidden_pages'] == true){?>1<?php } ?>">
    -->
    
    <div id="globalTab" class="tab-pane" style="display: none;">
    
    <h3>Global Settings</h3>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">Search Domain</th>
        <td>
          <input type="text" class="regular-text code" name="wp_plugin_dealersolutionsinventorysearch[searchdefinition_domain]" value="<?php echo get_option('wp_plugin_dealersolutionsinventorysearch_searchdefinition_domain');?>" />
          <span class="description">Default <code><?php echo $_SERVER['HTTP_HOST'];?></code></span>
        </td>
      </tr>
      <tr valign="top" id="wp_plugin_dealersolutionsinventorysearch_advanced.container">
        <th scope="row"></th>
        <td><small><a href="javascript:void(0);" onclick="javascript:jQuery('.wp_plugin_dealersolutionsinventorysearch_advanced').fadeIn();jQuery('#wp_plugin_dealersolutionsinventorysearch_advanced\\.container').hide();">Advanced Options</a></small></td>
      </tr>
    </table>
    <table class="form-table wp_plugin_dealersolutionsinventorysearch_advanced" <?php if(get_option('wp_plugin_dealersolutionsinventorysearch_css_mode') == false and get_option('wp_plugin_dealersolutionsinventorysearch_css_mode') == false){?>style="display:none;"<?php } ?>>
      <tr valign="top">
        <th scope="row">CSS Options</th>
        <td>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[css_mode]" id="wp_plugin_dealersolutionsinventorysearch_css_mode_0" value="0" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_css_mode') == false) ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_css_mode_0">Enable External Inventory CSS <span class="description">(default option)</span></label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[css_mode]" id="wp_plugin_dealersolutionsinventorysearch_css_mode_2" value="2" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_css_mode') === '2')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_css_mode_2">Enable External Inventory CSS and Prioritise it</label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[css_mode]" id="wp_plugin_dealersolutionsinventorysearch_css_mode_1" value="1" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_css_mode') === '1')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_css_mode_1">Disable External Inventory CSS</label></div>
        </td>
      </tr>
    </table>

    <table class="form-table">
      <tr valign="top">
        <th scope="row">Default Page Title Mode</th>
        <td>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[title_mode]" id="wp_plugin_dealersolutionsinventorysearch_title_mode_4" value="4" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_title_mode') === '4')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_title_mode_4">Full Title Replacement <span class="description">(default option)</span></label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[title_mode]" id="wp_plugin_dealersolutionsinventorysearch_title_mode_1" value="1" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_title_mode') === '1')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_title_mode_1">Partial Title Replacement</label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[title_mode]" id="wp_plugin_dealersolutionsinventorysearch_title_mode_2" value="2" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_title_mode') === '2')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_title_mode_2">Append Title</label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[title_mode]" id="wp_plugin_dealersolutionsinventorysearch_title_mode_3" value="3" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_title_mode') === '3')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_title_mode_3">Prepend Title</label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[title_mode]" id="wp_plugin_dealersolutionsinventorysearch_title_mode_0" value="0" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_title_mode') == false) ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_title_mode_0">Ignore Title</label></div>
        </td>
      </tr>
    </table>

    <?php foreach(wp_plugin_dealersolutionsinventorysearch_get_specific_page_list_configuration() as $key => $item){ ?>

      <table class="form-table wp_plugin_dealersolutionsinventorysearch_advanced" <?php if(isset($item['title_mode']) === false or strlen($item['title_mode']) === 0){?>style="display:none;"<?php } ?>>
        <tr valign="top">
          <th scope="row"><?php echo $item['page_name'];?> Page Title Mode</th>
          <td>
            <input type="hidden" name="wp_plugin_dealersolutionsinventorysearch[page_specific_rules][<?php echo $key;?>][page_name]" value="<?php echo strtolower($item['page_name']);?>">
            <select name="wp_plugin_dealersolutionsinventorysearch[page_specific_rules][<?php echo $key;?>][title_mode]"">
              <option <?php echo ($item['title_mode'] === '')   ? 'selected="true" data-was-original-value="true"':null;?>value="">Default Behavor</option>
              <option <?php echo ($item['title_mode'] === '4')  ? 'selected="true" data-was-original-value="true"':null;?> value="4">Full Title Replacement</option>
              <option <?php echo ($item['title_mode'] === '1')  ? 'selected="true" data-was-original-value="true"':null;?> value="1">Partial Title Replacement</option>
              <option <?php echo ($item['title_mode'] === '2')  ? 'selected="true" data-was-original-value="true"':null;?> value="2">Append Title</option>
              <option <?php echo ($item['title_mode'] === '3')  ? 'selected="true" data-was-original-value="true"':null;?> value="3">Prepend Title</option>
              <option <?php echo ($item['title_mode'] === '0')  ? 'selected="true" data-was-original-value="true"':null;?> value="0">Ignore Title</option>
            </select>
          </td>
        </tr>
      </table>

    <?php } ?>

    <table class="form-table wp_plugin_dealersolutionsinventorysearch_advanced" <?php if(get_option('wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect') == false and get_option('wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect') == false){?>style="display:none;"<?php } ?>>
      <tr valign="top">
        <th scope="row">Legacy URL Redirects</th>
        <td>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[legacy_url_redirect]" id="wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect_0" value="0" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect') == false) ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect_0">Disable Legacy Redirects <span class="description">(default option)</span></label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[legacy_url_redirect]" id="wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect_1" value="1" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect') === '1')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_legacy_url_redirect_1">Enable Legacy Redirects <span class="description"><code>/view.php/alias/</code> redirects to <code>/permalink/view/</code></span></label></div>
        </td>
      </tr>
    </table>
    <table class="form-table wp_plugin_dealersolutionsinventorysearch_advanced" <?php if(get_option('wp_plugin_dealersolutionsinventorysearch_development_mode') != true){?>style="display:none;"<?php } ?>>
      <tr valign="top">
        <th scope="row">Development Mode</th>
        <td>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[development_mode]" id="wp_plugin_dealersolutionsinventorysearch_development_mode_0" value="0" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_development_mode') == false) ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_development_mode_0">Production <span class="description">http://www.inventorysearch.com.au/ (default option)</span></label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[development_mode]" id="wp_plugin_dealersolutionsinventorysearch_development_mode_5" value="5" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_development_mode') === '5')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_development_mode_5">Preview <span class="description">http://inventory.dealersolutionspreview.com.au/</span></label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[development_mode]" id="wp_plugin_dealersolutionsinventorysearch_development_mode_1" value="1" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_development_mode') === '1')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_development_mode_1">Staging <span class="description">http://staging.inventorysearch.com.au/</span></label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[development_mode]" id="wp_plugin_dealersolutionsinventorysearch_development_mode_2" value="2" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_development_mode') === '2')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_development_mode_2">Development <span class="description">http://dev.inventorysearch.com.au/</span></label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[development_mode]" id="wp_plugin_dealersolutionsinventorysearch_development_mode_3" value="3" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_development_mode') === '3')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_development_mode_3">Localhost <span class="description">http://localhost/</span></label></div>
          <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[development_mode]" id="wp_plugin_dealersolutionsinventorysearch_development_mode_4" value="4" <?php echo (get_option('wp_plugin_dealersolutionsinventorysearch_development_mode') === '4')  ? 'checked="true" data-was-original-value="true"':null;?> /> <label for="wp_plugin_dealersolutionsinventorysearch_development_mode_4">Current Host <span class="description">http://<?php echo $_SERVER['HTTP_HOST'];?>/</span></label></div>
        </td>
      </tr>
      <tr valign="top" id="wp_plugin_dealersolutionsinventorysearch_development_folder.container" <?php echo (in_array(get_option('wp_plugin_dealersolutionsinventorysearch_development_mode'),array('3','4')) == false)  ? 'style="display:none;"':null;?>>
        <th scope="row">Development Folder</th>
        <td>
          <input type="text" class="regular-text code" name="wp_plugin_dealersolutionsinventorysearch[development_folder]" id="wp_plugin_dealersolutionsinventorysearch_development_folder" value="<?php echo get_option('wp_plugin_dealersolutionsinventorysearch_development_folder');?>" />
          <span class="description">Example <code>inventory</code></span>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row">Flush Rewrite Rules</th>
        <td>
          <label for="wp_plugin_dealersolutionsinventorysearch_generate_rewrite_rules">
            <input type="checkbox" name="wp_plugin_dealersolutionsinventorysearch[generate_rewrite_rules]" id="wp_plugin_dealersolutionsinventorysearch_generate_rewrite_rules" value="true" />
            Yes
          </label>
        </td>
      </tr>
    </table>
    <hr/>
    
    </div>
    <script type="text/javascript">
    //<![CDATA[
      jQuery(document).ready(function()
      {
        jQuery('[name="wp_plugin_dealersolutionsinventorysearch\\[development_mode\\]"]').change(function()
        {
          if(jQuery(this).val() == '3' || jQuery(this).val() == '4')
          {
            jQuery('#wp_plugin_dealersolutionsinventorysearch_development_folder\\.container').fadeIn();
          }
          else
          {
            jQuery('#wp_plugin_dealersolutionsinventorysearch_development_folder\\.container').fadeOut();
            jQuery('[name="wp_plugin_dealersolutionsinventorysearch\\[development_folder\\]"]').val('');
          }
        });
      });
    //]]>
    </script>
    
    <div id="pagesTab" class="tab-pane" style="display: none;">
    
    <?php $options = array(); $index = 0; foreach( $configuration_pagelist as $page ){
      $display = ( $page->type == 0 )? 'none' : 'block' ;
    ?>
      <div id="<?php echo $page->post_name; ?>" class="page-div" style="display: <?php echo $display; ?>;">
        <h3>
          <?php if(isset($page->guid)){?>
            Page: <?php echo (empty($page->post_title)) ? $page->post_name : $page->post_title;?>
            <?php if(get_option('show_on_front') === 'page' and $page->page_id == get_option('page_on_front')){ ?>
              (Wordpress front page)
            <?php } ?>
            <?php if(isset($page->post_status) === true and $page->post_status != 'publish'){ ?>
              (<?php echo ucfirst($page->post_status); ?>)
            <?php } ?>
          <?php }else{ ?>
            <?php echo (isset($page->post_title)) ? $page->post_title : 'Unknown or deleted page <small>(Page #'.$page->page_id.')</small>';?>
          <?php } ?>
        </h3>
        <!-- 
        <table class="form-table" id="section_<?php echo $index;?>_button">
          <tr valign="top">
            <th scope="row"></th>
            <td><input type="button" class="button-secondary" onclick="javascript:jQuery('#section_<?php echo $index;?>_button').hide();jQuery('#section_<?php echo $index;?>').fadeIn();" value="Show Configuration" /></td>
          </tr>
        </table>
        -->
        <?php if (isset($page->permalink_collision)){ ?>
          <div class="" title="Reserved: <?php echo implode(', ',(array)$IS->configuration->inventorysearch->regex->files_to_process);?>"><strong style="color:#FF0000;">Warning:</strong> "<strong><em><?php echo $page->permalink_collision;?></em></strong>" is a reserved permalink word for this plugin</div>
        <?php } ?>
        <div style="display:block;" id="section_<?php echo $index;?>">
          <table class="form-table">
            <?php if(get_permalink($page->page_id)){?>
              <tr valign="top">
                <th scope="row">Permalink</th>
                <td><a href="<?php echo get_permalink($page->page_id);?>" target="_blank"><em><?php echo get_permalink($page->page_id);?></em></a></td>
              </tr>
            <?php } ?>
            <tr valign="top" id="row_<?php echo $index;?>_type">
              <th scope="row">Inventory Type</th>
              <td>
                <div><input type="radio" class="wp_plugin_dealersolutionsinventorysearch_field_page_type" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][type]" data-index="<?php echo $index;?>" id="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_type_0" value="0" <?php echo ($page->type == false) ? 'checked="true" data-was-original-value="true"':'';?> /> <label for="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_type_0">Disabled</label></div>
                <div><input type="radio" class="wp_plugin_dealersolutionsinventorysearch_field_page_type" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][type]" data-index="<?php echo $index;?>" id="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_type_1" value="1" <?php echo ($page->type === '1') ? 'checked="true" data-was-original-value="true"':'';?> /> <label for="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_type_1">Dealership Stock</label></div>
                <div><input type="radio" class="wp_plugin_dealersolutionsinventorysearch_field_page_type" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][type]" data-index="<?php echo $index;?>" id="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_type_2" value="2" <?php echo ($page->type === '2') ? 'checked="true" data-was-original-value="true"':'';?> /> <label for="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_type_2">New Car Database</label></div>
              </td>
            </tr>
          </table>
          <div class="row_<?php echo $index;?>_advanced.container" <?php if(empty($page->definitioncode) === true){?>style="display:none;"<?php } ?>>
            <table class="form-table">
              <tr valign="top" id="row_<?php echo $index;?>_definitioncode" >
                <th scope="row">Search Definition Alias</th>
                <td>
                  <!-- page: <?php echo $page->page_id;?> / conf: <?php echo $page->id;?> -->
                  <input type="hidden" class="regular-text code" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][page_id]" value="<?php echo $page->page_id;?>" />
                  <input type="hidden" class="regular-text code" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][id]" value="<?php echo $page->id;?>" />
                  <input type="text" class="regular-text code" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][definitioncode]" value="<?php echo $page->definitioncode;?>" data-index="<?php echo $index;?>" />
                  <?php if(empty($page->post_name) === false){?><span class="description">Default <code><?php echo ((empty($page->definitioncode_default) === false) ? $page->definitioncode_default : $page->post_name);?></code></span><?php } ?>
                </td>
              </tr>
            </table>
          </div>
          <div class="row_<?php echo $index;?>_advanced.container" <?php if(empty($page->content_mode) === true){?>style="display:none;"<?php } ?>>
            <table class="form-table">
              <tr valign="top" id="row_<?php echo $index;?>_content_mode">
                <th scope="row">Content Mode</th>
                <td>
                  <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][content_mode]" id="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_content_mode_0" value="0" <?php echo ($page->content_mode == false) ? 'checked="true" data-was-original-value="true"':'';?> /> <label for="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_content_mode_0">Replace page content <span class="description">(default option)</span></label></div>
                  <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][content_mode]" id="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_content_mode_1" value="1" <?php echo ($page->content_mode === '1') ? 'checked="true" data-was-original-value="true"':'';?> /> <label for="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_content_mode_1">Display after page content</label></div>
                  <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][content_mode]" id="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_content_mode_2" value="2" <?php echo ($page->content_mode === '2') ? 'checked="true" data-was-original-value="true"':'';?> /> <label for="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_content_mode_2">Display before page content</label></div>
                  <div><input type="radio" name="wp_plugin_dealersolutionsinventorysearch[page][<?php echo $index;?>][content_mode]" id="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_content_mode_3" value="3" <?php echo ($page->content_mode === '3') ? 'checked="true" data-was-original-value="true"':'';?> /> <label for="wp_plugin_dealersolutionsinventorysearch_page_<?php echo $index;?>_content_mode_3">Suppress Output <span class="description">(display using the_inventorysearch() function)</span></label></div>
                </td>
              </tr>
            </table>
          </div>
          <?php if(empty($page->definitioncode) === true or empty($page->content_mode) === true){?>
            <div id="row_<?php echo $index;?>_advanced">
              <table class="form-table">
                <tr valign="top">
                  <th scope="row"></th>
                  <td><small><a href="javascript:void(0);" onclick="javascript:jQuery('.row_<?php echo $index;?>_advanced\\.container').fadeIn();jQuery('#row_<?php echo $index;?>_advanced').hide();">Advanced Options</a></small></td>
                </tr>
              </table>
            </div>
          <?php } ?>
        </div>
        <hr/>
      </div>
    <?php
        if( $page->type == 0 )
        {
          // Add to Select List Array
          $title = ( empty( $page->post_title ) )? $page->post_name : $page->post_title ;
          if(get_option('show_on_front') === 'page' and $page->page_id == get_option('page_on_front'))
            $options[] = '<option value="'.$page->post_name.'">'.$title.' (Front page)</option>';
          else
            $options[] = '<option value="'.$page->post_name.'">'.$title.'</option>';
        }
        $index++;
    }
      // Print Select List
    ?>
    <?php if( empty( $options ) == FALSE ): ?>
    <div class="alignleft actions bulkactions submit">
      <select name="hiddenPages" id="hiddenPages">
        <option value="">-- Select a Page to Configure --</option>
        <?php foreach( $options as $option ){ echo $option.PHP_EOL; } ?>
      </select>
      <input type="button" name="showHidden" id="showHidden" value="Configure Selected Page" class="button action" onClick="showPage();" />
    </div>
    <br class="clear" />
    <hr />
    <?php endif; ?>

    </div>

    <p class="submit">
      <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

  </form>

  <script type="text/javascript">
  //<![CDATA[
    jQuery(function()
    {
      if(jQuery('.wp_plugin_dealersolutionsinventorysearch_advanced').filter(":hidden").size() === 0)
      {
        jQuery('#wp_plugin_dealersolutionsinventorysearch_advanced\\.container').hide();
      }
      jQuery('[data-was-original-value="true"]:not(option)').next('label').prepend('&laquo; ');
      jQuery('option[data-was-original-value="true"]').prepend('&raquo; ');
      jQuery('.wp_plugin_dealersolutionsinventorysearch_field_page_type').click(function()
      {
        if(jQuery(this).val() == '0')
        {
          jQuery('#row_'+jQuery(this).data('index')+'_content_mode').fadeOut();
          jQuery('#row_'+jQuery(this).data('index')+'_definitioncode').fadeOut();
        }
        else
        {
          jQuery('#section_'+jQuery(this).data('index')+'_button').hide();
          jQuery('#section_'+jQuery(this).data('index')).fadeIn();
          jQuery('#row_'+jQuery(this).data('index')+'_content_mode').fadeIn();
          jQuery('#row_'+jQuery(this).data('index')+'_definitioncode').fadeIn();
        }
      });
      jQuery('.wp_plugin_dealersolutionsinventorysearch_field_page_type:checked').click();
      <?php if(get_settings_errors()){?>
        //jQuery('#wp_plugin_dealersolutionsinventorysearch_form_admin_page').find('input,select,textarea').each(function(){
        //  jQuery(this).attr('disabled','disabled');
        //});
        //alert('Please address outstanding errors before making changes to plugin configuration');
      <?php } ?>
    });

    jQuery(function(){
      jQuery('.wp_plugin_dealersolutionsinventorysearch_admin_notice_remove').on('click',function(){
        jQuery.ajax({
          type: 'POST',
          url: 'options-general.php?page=wp_plugin_dealersolutionsinventorysearch_admin_page',
          data:{wp_plugin_dealersolutionsinventorysearch_admin_notice_remove: jQuery(this).data('page-id')},
          dataType: "json"
        }).done(function(response){
          if(response !== false)
            jQuery('.wp_plugin_dealersolutionsinventorysearch_admin_notice_remove[data-page-id="' + response + '"]').parent().fadeOut();
        });
      });
      setTimeout(function(){
        jQuery('.updated.settings-error').slideUp();
      },5000);

      // Jump to post
      <?php if(isset($jumpToPost) === true){ ?>
      if(jQuery('#<?php echo $jumpToPost->post_name;?>').length > 0)
      {
        setActiveTab('pagesTab');
        jQuery('html, body').animate({scrollTop: jQuery('#<?php echo $jumpToPost->post_name;?>').offset().top - jQuery('#wpadminbar').height() }, 'slow');
      }
      <?php } ?>

    });

  //]]>
  </script>
</div>