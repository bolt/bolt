<?php

namespace Bolt;

use Silex;

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
        if (isset($values['id'])) {
            $this->id = $values['id'];
        }
        if (isset($values['date'])) {
            $this->date = $values['date'];
        }
        if (isset($values['title'])) {
            $this->title = $values['title'];
        }
        if (isset($values['username'])) {
            $this->username = $values['username'];
        }
        if (isset($values['ownerid'])) {
            $this->ownerid = $values['ownerid'];
            $user = $this->app['users']->getUser($values['ownerid']);
            if (isset($user['username'])) {
                $this->username = $user['username'];
            } else {
                $this->username = "(deleted user #" . $values['ownerid'] . ")";
            }
        }
        if (isset($values['contenttype'])) {
            $this->contenttype = $values['contenttype'];
        }
        if (isset($values['contentid'])) {
            $this->contentid = $values['contentid'];
        }
        if (isset($values['mutation_type'])) {
            $this->mutation_type = $values['mutation_type'];
        }
        if (isset($values['diff'])) {
            $this->diff = $values['diff'];
        }
    }

    public function getParsedDiff()
    {
        $pdiff = json_decode($this->diff, true);
        if (is_array($pdiff)) {
            ksort($pdiff);
        }
        return $pdiff;
    }

    public function getEffectiveMutationType()
    {
        switch ($this->mutation_type) {
            case 'INSERT':
            case 'DELETE':
            default:
                return $this->mutation_type;

            case 'UPDATE':
                $diff = $this->getParsedDiff();
                if (isset($diff['status'])) {
                    switch ($diff['status'][1]) {
                        case 'published':
                            return 'PUBLISH';

                        case 'draft':
                            return 'DRAFT';

                        case 'held':
                            return 'HOLD';

                        default:
                            return 'UPDATE';
                    }
                } else {
                    return 'UPDATE';
                }
        }
    }

    public function __get($key)
    {
        switch ($key) {
            case 'parsedDiff':
                return $this->getParsedDiff();

            case 'effectiveMutationType':
                return $this->getEffectiveMutationType();
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
