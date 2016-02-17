<?php

namespace Hub\Client\Model;

class Share
{
    
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
    
    private $identifier;
    
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }
    
    private $identifierType;
    
    public function getIdentifierType()
    {
        return $this->identifierType;
    }
    
    public function setIdentifierType($identifierType)
    {
        $this->identifierType = $identifierType;
        return $this;
    }
}
