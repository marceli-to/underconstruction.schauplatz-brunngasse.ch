<?php

namespace App\Modifiers;

use Statamic\Modifiers\Modifier;
use Illuminate\Support\Str;

class Slugifier extends Modifier
{
    public function index($value, $params, $context)
    {
        return Str::slug($value);
    }
}