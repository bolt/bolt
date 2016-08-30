<?php

namespace Bolt\Configuration\Validation;

use Bolt\Controller\ExceptionControllerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validation check interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface ValidationInterface
{
    /**
     * Perform the validation check.
     *
     * @param ExceptionControllerInterface $exceptionController
     *
     * @return Response|null
     */
    public function check(ExceptionControllerInterface $exceptionController);

    /**
     * Should a failure be terminal to loading, or should a flash message be
     * added for feedback.
     *
     * @return boolean
     */
    public function isTerminal();
}
