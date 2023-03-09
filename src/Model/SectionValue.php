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

    protected function isStamp()
    {
        if (!is_numeric($this->value)) {
            return false;
        }
        if (strpos(strtolower($this->label), 'datum')!== false) {
            return true;
        }

        return false;
    }

    public function presentValue()
    {
        $value = $this->getValue();
        $value = nl2br($value);
        if ($this->isStamp()) {
            $value = date('d-m-Y', $value);
        }

        return $value;
    }
}
