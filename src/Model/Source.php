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

    private $providerAccount;
    
    public function getProviderAccount()
    {
        return $this->providerAccount;
    }
    
    public function setProviderAccount($providerAccount)
    {
        $this->providerAccount = $providerAccount;
        return $this;
    }
    
    private $providerDisplayName;
    
    public function getProviderDisplayName()
    {
        return $this->providerDisplayName;
    }
    
    public function setProviderDisplayName($providerDisplayName)
    {
        $this->providerDisplayName = $providerDisplayName;
        return $this;
    }

    private $providerXillionDomain;
    
    public function getProviderXillionDomain()
    {
        return $this->providerXillionDomain;
    }
    
    public function setProviderXillionDomain($providerXillionDomain)
    {
        $this->providerXillionDomain = $providerXillionDomain;
        return $this;
    }
    
    
}
