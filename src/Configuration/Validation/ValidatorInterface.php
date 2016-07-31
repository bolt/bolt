<?php

namespace Bolt\Configuration\Validation;

use Symfony\Component\HttpFoundation\Response;

/**
 * Validator interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface ValidatorInterface
{
    /**
     * Add a check.
     *
     * @param string                     $checkName Name for the check
     * @param string|ValidationInterface $className Class name, or instance of a ValidationInterface class
     * @param boolean                    $prepend   Prepend to the execution list
     */
    public function add($checkName, $className, $prepend = false);

    /**
     * @param string $checkName
     *
     * @return bool
     */
    public function has($checkName);

    /**
     * Remove a check from the list causing it to be skipped.
     *
     * @param string $checkName Name of the check to remove
     */
    public function remove($checkName);

    /**
     * Perform a named check.
     *
     * @param string $checkName Name of the check to run
     *
     * @return Response|null
     */
    public function check($checkName);

    /**
     * Perform all checks.
     */
    public function checks();
}
