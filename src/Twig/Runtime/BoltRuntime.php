<?php

namespace Bolt\Twig\Runtime;

use Bolt\Storage\Query\Query;

/**
 * Bolt extension runtime for Twig.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BoltRuntime
{
    /** @var Query */
    private $queryEngine;

    /**
     * Constructor.
     *
     * @param Query $queryEngine
     */
    public function __construct(Query $queryEngine)
    {
        $this->queryEngine = $queryEngine;
    }

    /**
     * @return Query
     */
    public function getQueryEngine()
    {
        return $this->queryEngine;
    }
}
