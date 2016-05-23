<?php

namespace Hub\Client\Model;

class Source
{
    private $url;
    
    public function getUrl()
    {
        return $this->url;
    }
    
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    
    private $api;
    
    public function getApi()
    {
        return $this->api;
    }
    
    public function setApi($api)
    {
        $this->api = $api;
        return $this;
    }
    
    private $jwt;
    
    public function getJwt()
    {
        return $this->jwt;
    }
    
    public function setJwt($jwt)
    {
        $this->jwt = $jwt;
        return $this;
    }
    
}
