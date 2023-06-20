<?php

namespace App\Modifiers;

use Statamic\Modifiers\Modifier;

class AddProperty extends Modifier
{
    public function index($value, $parameters, $context)
    {
        $startTag = $parameters[0] ?? '';
        $propertyPairs = $parameters[1] ?? [];
        $propertyString = $this->buildPropertyString($propertyPairs);

        $modifiedValue = preg_replace(
            $this->getRegex($startTag),
            '$1 ' . $propertyString . '$2$3>',
            $value,
            1 // Only replace the first occurrence
        );

        return $modifiedValue;
    }

    protected function getRegex($startTag)
    {
        $escapedStartTag = preg_quote($startTag, '/');
        return '/(' . $escapedStartTag . ')([^>]*)()>/';
    }

    protected function buildPropertyString($propertyPairs)
    {
        $propertyStrings = [];
        foreach ($propertyPairs as $key => $value) {
            $propertyStrings[] = $key . '="' . $value . '"';
        }
        return implode(' ', $propertyStrings);
    }
}
