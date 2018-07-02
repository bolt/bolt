<?php

namespace Bolt\Events;

/**
 * Definitions for all possible QueryEvents.
 *
 *  * @codeCoverageIgnore
 */
final class QueryEvents
{

    const PARSE = 'query.parse';
    const EXECUTE = 'query.execute';


    private function __construct()
    {
    }
}
