<?php require('global.php');

  if($IS->configuration->content_mode === '3')
  {
    return $the_content . '<!-- the content mode setting requires the use of the_inventorysearch() for display -->';
  }

  // final error handling
  if($error_codes = $IS->errors->get_error_codes() and is_array($error_codes) === true and empty($error_codes) === false)
  {
    unset($wpdb->insert_id);
    foreach($error_codes as $code)
    {
      // Log the error into the DB
      $wpdb->insert( // Table
      	$wpdb->prefix . 'plugin_dealersolutionsinventorysearch_error_log',
      	array( // Data
          'code' => $code,
          'message' => $IS->errors->get_error_message($error_code),
          'data' => $IS->errors->get_error_data($error_code)
        ),
      	array( // Format
      		'%s',
      		'%s',
      		'%s'
        )
      );
    }
    // ensure the log file doesn't reach more than 100 entries
    if(isset($wpdb->insert_id) === TRUE)
    {
      $sql = 'DELETE FROM `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_error_log` WHERE `' . $wpdb->prefix . 'plugin_dealersolutionsinventorysearch_error_log`.`id` <= %d - 100';
      $wpdb->query($wpdb->prepare($sql,$wpdb->insert_id));
    }
    unset($error_codes,$code);
    // Display Error Page
    $html = print_r($IS->errors->get_error_messages(),true);
    $html .= '<hr><textarea style="width:100%;height:1000px;font-size:10px;line-height:12px;font-family:system;">' . $IS->response->html->body . '</textarea>';
    return $html;
  }

  if(isset($IS->configuration) == false or empty($IS->configuration) === true or isset($IS->response->html->body) == false or empty($IS->response->html->body) == true)
  {
    return $the_content . '<!-- inventory search not included during "the_content" -->';
  }

  wp_plugin_dealersolutionsinventorysearch_process_the_content($the_content);

  $IS->response->rendered = true; // Set when html is being displayed

  switch($IS->configuration->content_mode)
  {
    case '2': // Before Content
      return $IS->response->html->body . '<!-- inventory search rendered before "the_content" -->' . $the_content;
      break;
    case '1': // After Content
      return $the_content . $IS->response->html->body . '<!-- inventory search rendered after "the_content" -->';
      break;
    default: // Replace Content
      return $IS->response->html->body . '<!-- inventory search replaced "the_content" -->';
      break;
  }

?>