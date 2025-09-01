<?php

namespace App\Modifiers;

use Statamic\Modifiers\Modifier;

class Highlight extends Modifier
{
  /**
   * Highlight search keyword in text
   *
   * @param string $value The text to highlight in
   * @param array $params The parameters
   * @param array $context The context
   * @return string
   */
  public function index($value, $params, $context)
{
    if (empty($params) || empty($value))
    {
      return $value;
    }

    $keyword = $params[0];
    $text = strip_tags($value);
    
    // Find the position of the keyword using unicode pattern modifier
    if (preg_match("/(.{0,50})(" . preg_quote($keyword, '/') . ")(.{0,50})/iu", $text, $matches))
    {
      // Ensure we don't cut words at the start
      $prefix = $matches[1];
      if ($prefix !== '' && !preg_match('/^\s/u', $prefix))
      {
        $prefix = preg_replace('/^[^\s]*\s/u', '', $prefix);
      }
      
      // Ensure we don't cut words at the end
      $suffix = $matches[3];
      if ($suffix !== '' && !preg_match('/\s$/u', $suffix))
      {
        $suffix = preg_replace('/\s[^\s]*$/u', '', $suffix);
      }
      
      // Add ellipsis if we're not at the start/end of the text
      $startEllipsis = mb_strlen($matches[1]) > mb_strlen($prefix) ? '...' : '';
      $endEllipsis = mb_strlen($matches[3]) > mb_strlen($suffix) ? '...' : '';
      
      // return 
      //   $startEllipsis . 
      //   $prefix . 
      //     '<mark class="bg-sunella">' . $matches[2] . '</mark>' . 
      //   $suffix .
      //   $endEllipsis;

      return 
        // $startEllipsis . 
        // $prefix . 
        $matches[2] . 
        $suffix .
        $endEllipsis;
    }
      
    // If no match found, return first sentence or up to first word break near 100 chars
    $firstPart = mb_substr($text, 0, 100);
    $cleanPart = preg_replace('/\s[^\s]*$/u', '', $firstPart);
    return $cleanPart . '...';
  }
}
