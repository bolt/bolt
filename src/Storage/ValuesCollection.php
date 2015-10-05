<?php

namespace Bolt\Storage;

/**
 *  This class is used by lazily loaded field values. It stores a reference to an array of rows and
 *  fetches from the database on demand.
 */
class ValuesCollection implements \SeekableIterator, \Countable
{
    public $references = [];
    protected $loaded = false;
    protected $proxy;
    protected $em;
    protected $position = 0;
    protected $instances = [];

    public function __construct(array $references, EntityManager $em)
    {
        $this->references = $references;
        $this->em = $em;
    }

    public function load()
    {
        if ($this->loaded) {
            return true;
        }
        $repo = $this->em->getRepository('Bolt\Storage\Entity\FieldValue');
        $this->instances = $repo->findBy(['id'=>$this->references]);
        $this->loaded = true;
        $this->em = null;
    }


    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return array_key_exists($this->position, $this->references);
    }

    public function seek($position)
    {
        $this->position = (int) $position;
    }

    public function current()
    {
        $this->load();

        return $this->instances[$this->position];
    }

    public function count()
    {
        return count($this->references);
    }

}
