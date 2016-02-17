<?php

namespace Hub\Client\Model;

class Resource
{
    
    protected $type;
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
    }
    
    private $properties = array();
    
    public function addProperty(Property $property)
    {
        $this->properties[$property->getName()] = $property;
    }
    
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }
    
    public function getProperty($name)
    {
        return $this->properties[$name];
    }
    
    public function getPropertyValue($name)
    {
        return $this->getProperty($name)->getValue();
    }
    
    public function addPropertyValue($name, $value)
    {
        $value = (string)$value;
        $property = new Property($name, $value);
        $this->addProperty($property);
        return $this;
    }
}
