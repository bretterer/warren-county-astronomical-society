<?php

namespace App\Modifiers;

use Statamic\Modifiers\Modifier;

class HumanReadableAmount extends Modifier
{
    /**
     * Modify a value.
     *
     * @param mixed  $value    The value to be modified
     * @param array  $params   Any parameters used in the modifier
     * @param array  $context  Contextual values
     * @return mixed
     */
    public function index($value, $params, $context)
    {
        return number_format($value/100, 2);
        // return $value;
    }
}
