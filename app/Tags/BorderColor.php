<?php
namespace App\Tags;

use Statamic\Tags\Tags;

class BorderColor extends Tags
{
  /**
   * The {{ border_color }} tag.
   *
   * @return string|array
   */
  public function index()
  {
    $colors = [
      'border-mystiris',
      'border-verdique', 
      'border-flareon',
      'border-sunella',
      'border-blushra'
    ];
    
    $index = $this->params->get('index', 0);
    return $colors[$index % 5];
  }
}
