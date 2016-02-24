<?php

namespace Hub\Client\Model;

class Resource
{
    
    protected $type;
    protected $shares = array();
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    
    private $properties = array();
    
    public function addProperty(Property $property)
    {
        $this->properties[$property->getName()] = $property;
        return $this;
    }
    
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }
    
    public function getProperty($name)
    {
        if (!isset($this->properties[$name])) {
            return null;
        }
        return $this->properties[$name];
    }
    
    public function getPropertyValue($name)
    {
        return $this->getProperty($name)->getValue();
    }
    
    public function addPropertyValue($name, $value)
    {
        $name = (string)$name;
        $value = (string)$value;
        $property = new Property($name, $value);
        $this->addProperty($property);
        return $this;
    }
    
    public function getProperties()
    {
        return $this->properties;
    }
    
    public function addShare(Share $share)
    {
        $this->shares[] = $share;
        return $this;
    }
    
    public function getShares()
    {
        return $this->shares;
    }
    
    private $sourceUrl;
    
    public function getSourceUrl()
    {
        return $this->sourceUrl;
    }
    
    public function setSourceUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;
        return $this;
    }
    
    private $sourceApi;
    
    public function getSourceApi()
    {
        return $this->sourceApi;
    }
    
    public function setSourceApi($sourceApi)
    {
        $this->sourceApi = $sourceApi;
        return $this;
    }
    
    
    private $sourceJwt;
    
    public function getSourceJwt()
    {
        return $this->sourceJwt;
    }
    
    public function setSourceJwt($sourceJwt)
    {
        $this->sourceJwt = $sourceJwt;
        return $this;
    }
}
