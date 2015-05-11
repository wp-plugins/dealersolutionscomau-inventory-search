<?php

  global $post;
  if(basename($_SERVER['SCRIPT_NAME']) === 'post.php' AND isset($_REQUEST['action'],$_REQUEST['post'],$post,$post->ID) === true and $_REQUEST['action'] === 'edit')
  {
    global $wpdb;
    if($configuration = $wpdb->get_row('SELECT `id`,`page_id`,`definitioncode`,`content_mode`,`type` FROM `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings` WHERE `type` > 0 AND `page_id` = ' . $post->ID . ' LIMIT 1') and empty($configuration) === false and isset($configuration->definitioncode) === true and empty($configuration->definitioncode) === true)
    {
      ?>
      <script>
      jQuery(function(){
        jQuery('#editable-post-name').replaceWith(jQuery('#editable-post-name').html());
        jQuery('#sample-permalink').html(jQuery('#sample-permalink').html() + ' <em style="white-space:nowrap;">(Permalink editing locked by <a href="options-general.php?page=wp_plugin_dealersolutionsinventorysearch_admin_page">Inventory Search</a>)</em>');
        jQuery('#edit-slug-buttons').remove();
      });
      </script>
      <?php
    }
  }
  elseif(basename($_SERVER['SCRIPT_NAME']) === 'edit.php' AND $_REQUEST['post_type'] === 'page')
  {
    global $wpdb;
    if($configuration = $wpdb->get_results('SELECT `id`,`page_id`,`definitioncode`,`content_mode`,`type` FROM `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_page_settings` WHERE `type` > 0 AND CHAR_LENGTH(`definitioncode`) = 0') and empty($configuration) === false)
    {
      $deactivate_slug_edit = array();
      foreach($configuration as $value)
        $deactivate_slug_edit[] = (int) $value->page_id;
      ?>
      <script>
        var wp_plugin_dealersolutionsinventorysearch_deactivate_slug_edit = <?php echo json_encode($deactivate_slug_edit); ?>;
        jQuery(function(){
          jQuery('.editinline').on('click',function(){
            window.setTimeout(function(){
              var closest_id = jQuery('input[name="post_name"]').closest('tr').attr('id');
              if(closest_id.substring(0, 5) == 'edit-')
              {
                var post_id = parseInt(closest_id.replace('edit-',''));
                if(wp_plugin_dealersolutionsinventorysearch_deactivate_slug_edit.indexOf(post_id) != -1)
                  jQuery('#' + closest_id + ' input[name="post_name"]').after('<em>Slug editing locked by <a href="options-general.php?page=wp_plugin_dealersolutionsinventorysearch_admin_page">Inventory Search</a></em>').hide();
              }
            },50);
          });
        });
      </script>
      <?php
    }
  }