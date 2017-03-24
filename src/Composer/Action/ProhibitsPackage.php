<?php

namespace Bolt\Composer\Action;

use Bolt\Composer\Package\Dependency;

/**
 * Shows which packages prevent the given package from being installed with
 * detailed information about why a package cannot be installed.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class ProhibitsPackage extends AbstractDependencyAction
{
    /**
     * @param string $packageName
     * @param string $textConstraint
     * @param bool   $onlyLocal
     *
     * @return Dependency[]|null
     */
    public function execute($packageName, $textConstraint = '*', $onlyLocal = true)
    {
        $this->inverted = true;

        return parent::execute($packageName, $textConstraint);
    }
}
