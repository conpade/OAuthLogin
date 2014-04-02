<?php
/**
 * Abstract Oauth Class
 * 
 * @author Francis
 */
abstract class OAuthBase
{
    protected $apiDirs;
    
    protected $config;
    
    protected $data;
    
    protected $user;
    
    protected $errMsg;

    function __construct($appId, $appKey)
    {
        $this->setConfig('appId', $appId);
        $this->setConfig('appKey', $appKey);
    }
    
    function __call($method, $args)
    {
        $area = substr($method, 3);
        switch (substr($method, 0, 3)) {
            case 'get' :
                $data = $this->getProperty($area, isset($args[0]) ? $args[0] : null);
                return $data;

            case 'set' :
                if(1 == count($args))
                {
                    $result = $this->setProperty($area, $args[0]);
                }
                elseif(2 == count($args))
                {
                    $result = $this->setProperty($area, $args[0], $args[1]);
                }
                return $result;

            case 'uns' :
                $result = $this->unsetProperty($area, isset($args[0]) ? $args[0] : null);
                return $result;

            case 'has' :
                if(isset($args[0]))
                {
                    return isset($this->{$area}[$args[0]]);
                }
                return isset($this->$area);
        }
        throw new Exception("Invalid method ".get_class($this)."::".$method."(".print_r($args,1).")");
    }
    
    /**
     * Retrieves data from the object
     */
    public function setProperty()
    {
        $args = func_get_args();
        if(empty($args[0]))
        {
            return $this;
        }
        
        if (2 === func_num_args())
        {
            $this->$args[0] = $args[1];
        }
        elseif (3 === func_num_args())
        {
            if(empty($args[1]))
            {
                return $this;
            }
            $this->{$args[0]}[$args[1]] = $args[2];
        }
        return $this;
    }
    
    /**
     * Retrieves data from the object
     */
    public function getProperty($area, $key, $default = null)
    {
        if(empty($area))
        {
            return get_object_vars($this);
        }
        if(empty($key))
        {
            return isset($this->$area) ? $this->$area : $default;
        }
        return isset($this->{$area}[$key]) ? $this->{$area}[$key] : $default ;
    }
    
    public function unsetProperty($area, $key)
    {
        if(empty($area))
        {
        }
        elseif(empty($key))
        {
            unset($this->$area);
        }
        else
        {
            unset($this->{$area}[$key]);
        }
        return $this;
    }
    
    /**
     * Get a API uri without protocol
     * 
     * @param string $api
     * @return string
     */
    public function getApiUri($api)
    {
        return $this->getConfig('serverName') . $this->getApiDirs($api);
    }
    
    abstract function getRedirectUrl();

    abstract function handlerCallBack($code);
    
    abstract function getUserData();
}