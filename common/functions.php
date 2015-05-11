<?php

  if(function_exists('pre_print_r') === false)
  {
    function pre_print_r($input, $return = false)
    {
      if($return === true)
        return '<pre>' . htmlentities(print_r($input,true)) . '</pre>';
      else
        echo '<pre>' . htmlentities(print_r($input,true)) . '</pre>';
    }
  }

  if(function_exists('wp_plugin_dealersolutionscommon_shortcode_ifstatement') === false)
  {
    function wp_plugin_dealersolutionscommon_shortcode_ifstatement($the_content)
    {
      // Tag Count Validation
      preg_match_all('/\[if[^]]*\]/i',$the_content,$starttag_matches,PREG_SET_ORDER);
      preg_match_all('%\[/if\]%i',$the_content,$endtag_matches,PREG_SET_ORDER);
      if(count($starttag_matches) > 0 and count($endtag_matches) > 0 and count($starttag_matches) === count($endtag_matches) and preg_match_all('%(?P<starttag>\[if\s(?P<shortcode>[^\]]*)\])(?P<content>.*?)(?:\[else\](?P<else>.*?))?(?P<endtag>\[/if\])%si',$the_content,$matches,PREG_SET_ORDER))
      {
        foreach($matches as $match)
        {
          $shortcode = '[' . $match['shortcode'] . ']';
          $do_shortcode = do_shortcode($shortcode);
          if($shortcode !== $do_shortcode and empty($do_shortcode) === false and preg_match('/^<!--.*?-->$/',$do_shortcode) !== 1 and isset($match['content']) === true and ($empty = trim($match['content'])) and empty($empty) === false)
          {
            $the_content = str_replace($match[0],$match['content'],$the_content);
          }
          elseif(isset($match['else']) === true and ($empty = trim($match['else'])) and empty($empty) === false)
          {
            $the_content = str_replace($match[0],$match['else'],$the_content);
          }
          else
          {
            $the_content = str_replace($match[0],null,$the_content);
          }
        }
      }
      return $the_content;
    }

    add_filter('the_content','wp_plugin_dealersolutionscommon_shortcode_ifstatement');

  }