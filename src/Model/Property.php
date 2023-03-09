<?php

namespace Hub\Client\Model;

class Property
{
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    protected $value;

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
