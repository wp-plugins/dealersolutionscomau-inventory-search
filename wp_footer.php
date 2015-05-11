<?php require('global.php');

  echo "\n<!-- IS Footer Start -->\n";

  // Javascript Tags - SRC
  if(isset($IS->response->html->body_script_external) === true AND empty($IS->response->html->body_script_external) === false)
    if(is_array($IS->response->html->body_script_external))
      foreach($IS->response->html->body_script_external as $echo)
        echo $echo;
    else
      echo $IS->response->html->body_script_external;

  // Javascript Tags - Inline
  if(isset($IS->response->html->body_script_inline) === true AND empty($IS->response->html->body_script_inline) === false)
    if(is_array($IS->response->html->body_script_inline))
      foreach($IS->response->html->body_script_inline as $echo)
        echo $echo;
    else
      echo $IS->response->html->body_script_inline;

  if(isset($IS->response->html->debug) === true and empty($IS->response->html->debug) === false)
  {
    echo $IS->response->html->debug;
  }

  echo "\n<!-- IS Footer End -->\n";

?>