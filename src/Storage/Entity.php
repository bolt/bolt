<?php
namespace Bolt\Storage;


/**
 * An abstract class that other entities can inherit. Provides automatic getters and setters along
 * with serialization.
 */
abstract class Entity
{
        
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $method = "set".$key;
            if(property_exists($this, $key)) $this->$method($value);
        }
        
    }

    public function __get($key)
    {
        $method = "get".ucfirst($key);
        if(property_exists($this, $key)) {return $this->$method();}
    }

    public function __set($key, $value)
    {
        $method = "set".ucfirst($key);
        if(property_exists($this, $key)) $this->$method($value);
    }

    public function __isset($key)
    {
        if(property_exists($this, $key)) return true;
        return false;
    }

    public function __unset($key)
    {
        if(property_exists($this, $key)) unset($this->$key);
        return false;
    }

    public function __call($method, $arguments)
    {
        $var = lcfirst(substr($method, 3));

        if (strncasecmp($method, "get", 3) ==0) {
            return $this->$var;
        }
        
        if (strncasecmp($method, "serialize", 9) ==0) {
            $method = 'get'.substr($method,9);
            return $this->$method();
        }

        if (strncasecmp($method, "set", 3)==0) {
            $this->$var = $arguments[0];
        }
    }

    public function __toString()
    {
      return strval( $this->getId() );
    }

    public function serialize() {
        $data = [];
        foreach($this as $k=>$v) {
            $method = "serialize".$k;
            $data[$k] = $this->$method();
        }
        return $data;
    }
    
    public function jsonSerialize()
    {
        return $this->serialize();
    }
    
    
    public function getName()
    {
        return get_class($this);
    }




}
