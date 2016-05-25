<?php

namespace Hub\Client\Model;

class SectionValue
{
    private $key;
    private $label;
    private $value;
    private $repeat;
    

    public function getKey()
    {
        return $this->key;
    }
    
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }
    
    public function getLabel()
    {
        return $this->label;
    }
    
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    
    public function getRepeat()
    {
        return $this->repeat;
    }
    
    public function setRepeat($repeat)
    {
        $this->repeat = $repeat;
        return $this;
    }
}
