<?php

namespace Bolt\Storage\Query;

use Bolt\Storage\EntityManager;

class Query
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * getContent based on a 'human readable query'.
     *
     * Used directly by {% setcontent %} but also directly.
     * For reference refer to @link https://docs.bolt.cm/content-fetching
     *
     * @param string $textquery
     * @param string $parameters
     *
     * @return array
     */
    public function getContent($textquery, $parameters = null)
    {
        $parser = new ContentQueryParser($this->em, $textquery, $parameters);

        return $parser->fetch();
    }
}
