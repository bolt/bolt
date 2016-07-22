<?php

namespace Bolt\Composer\Action;

/**
 * Shows which packages cause the given package to be installed with
 * detailed information about where a package is referenced.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class DependsPackage extends AbstractDependencyAction
{
    /**
     * @param string $packageName
     * @param string $textConstraint
     * @param bool   $onlyLocal
     *
     * @return array
     */
    public function execute($packageName, $textConstraint = '*', $onlyLocal = true)
    {
        $this->inverted = false;

        return parent::execute($packageName, $textConstraint);
    }
}
