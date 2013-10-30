<?php

namespace Bolt;

Use Silex;

class ChangelogItem implements \ArrayAccess
{
    private $app;
    public $id;
    public $date;
    public $title;
    public $username;
    public $contenttype;
    public $contentid;
    public $mutation_type;
    public $diff;

    public function __construct(Silex\Application $app, $values = array())
    {
        $this->app = $app;
        if (isset($values['id'])) { $this->id = $values['id']; }
        if (isset($values['date'])) { $this->date = $values['date']; }
        if (isset($values['title'])) { $this->title = $values['title']; }
        if (isset($values['username'])) { $this->username = $values['username']; }
        if (isset($values['contenttype'])) { $this->contenttype = $values['contenttype']; }
        if (isset($values['contentid'])) { $this->contentid = $values['contentid']; }
        if (isset($values['mutation_type'])) { $this->mutation_type = $values['mutation_type']; }
        if (isset($values['diff'])) { $this->diff = $values['diff']; }
    }

    public function getParsedDiff()
    {
        $pdiff = json_decode($this->diff, true);
        if (is_array($pdiff)) {
            ksort($pdiff);
        }
        return $pdiff;
    }

    public function __get($key)
    {
        switch ($key) {
            case 'parsedDiff': return $this->getParsedDiff();
        }
    }

    /**
     * ArrayAccess support
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * ArrayAccess support
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * ArrayAccess support
     *
     * @todo we could implement an setDecodedValue() function to do the encoding here
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * ArrayAccess support
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}

