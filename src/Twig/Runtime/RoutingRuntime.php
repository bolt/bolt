<?php

namespace Bolt\Twig\Runtime;

use Bolt\Canonical;

/**
 * Bolt specific Twig functions and filters that provide routing functionality
 *
 * @internal
 */
class RoutingRuntime
{
    /** @var Canonical */
    private $canonical;

    /**
     * Constructor.
     *
     * @param Canonical $canonical
     */
    public function __construct(Canonical $canonical)
    {
        $this->canonical = $canonical;
    }

    /**
     * Get canonical url for current request.
     *
     * @return string|null
     */
    public function canonical()
    {
        return $this->canonical->getUrl();
    }
}
