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
     * @param string  $checkName
     * @param string  $className
     * @param boolean $prepend
     */
    public function add($checkName, $className, $prepend = false);

    /**
     * Remove a check from the list causing it to be skipped.
     *
     * @param string $checkName
     */
    public function remove($checkName);

    /**
     * Perform a named check.
     *
     * @param string $checkName
     *
     * @return Response|null
     */
    public function check($checkName);

    /**
     * Perform all checks.
     */
    public function checks();
}
