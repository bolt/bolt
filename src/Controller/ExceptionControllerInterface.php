<?php

namespace Bolt\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception controller interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface ExceptionControllerInterface
{
    /**
     * System check exceptions.
     *
     * @param string $type
     * @param array  $messages
     * @param array  $context
     *
     * @return Response|null
     */
    public function systemCheck($type, $messages = [], $context = []);
}
