<?php

namespace Bolt\Storage\Query;

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
     * For reference refer to @link https://docs.bolt.cm/templates/content-fetching
     *
     * @param string $textquery
     * @param string $parameters
     *
     * @return QueryResultset
     */
    public function getContent($textquery, $parameters = null)
    {
        $this->parser->setQuery($textquery);
        $this->parser->setParameters($parameters);

        return $this->parser->fetch();
    }
}
