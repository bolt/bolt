<?php

namespace Bolt\Storage\Query;

use Bolt\Storage\Entity\Content;

class Query
{
    /** @var ContentQueryParser */
    protected $parser;

    /**
     * Constructor.
     *
     * @param ContentQueryParser $parser
     */
    public function __construct(ContentQueryParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * getContent based on a 'human readable query'.
     *
     * Used by the twig command {% setcontent %} but also directly.
     * For reference refer to @link https://docs.bolt.cm/templating/content-fetching
     *
     * @param string $textquery
     * @param array $parameters
     *
     * @return QueryResultset|Content|null
     */
    public function getContent($textquery, array $parameters = [])
    {
        $this->parser->setQuery($textquery);
        $this->parser->setParameters($parameters);

        return $this->parser->fetch();
    }
}
