<?php require('global.php');

  echo "\n<!-- IS Head Start -->\n";

  // Meta Tags
  if(isset($IS->response->html->meta) === true AND empty($IS->response->html->meta) === false)
    echo $IS->response->html->meta;

  // Link Tags
  if(isset($IS->response->html->link) === true AND empty($IS->response->html->link) === false)
    echo $IS->response->html->link;

  // CSS Tags
  if(isset($IS->response->html->style) === true AND empty($IS->response->html->style) === false AND $IS->configuration->css_mode !== '1')
    echo $IS->response->html->style;

  // Javascript Tags - SRC
  if(isset($IS->response->html->script_external) === true AND empty($IS->response->html->script_external) === false)
    if(is_array($IS->response->html->script_external))
      foreach($IS->response->html->script_external as $echo)
        echo $echo;
    else
      echo $IS->response->html->script_external;

  // Javascript Tags - Inline
  if(isset($IS->response->html->script_inline) === true AND empty($IS->response->html->script_inline) === false)
    if(is_array($IS->response->html->script_inline))
      foreach($IS->response->html->script_inline as $echo)
        echo $echo;
    else
      echo $IS->response->html->script_inline;

  echo "\n<!-- IS Head END -->\n";

?>