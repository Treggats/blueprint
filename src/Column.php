<?php

namespace Blueprint;

class Column
{
    private $modifiers;
    private $name;
    private $dataType;
    private $attributes;

    public function __construct(string $name, string $dataType = 'string', array $modifiers = [], array $attributes = [])
    {
        $this->name = $name;
        $this->dataType = $dataType;
        $this->modifiers = $modifiers;
        $this->attributes = $attributes;
    }

    public function name()
    {
        return $this->name;
    }

    public function dataType()
    {
        return $this->dataType;
    }

    public function attributes()
    {
        return $this->attributes;
    }

    public function modifiers()
    {
        return $this->modifiers;
    }
}