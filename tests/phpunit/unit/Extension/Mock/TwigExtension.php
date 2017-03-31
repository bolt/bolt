<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\SimpleExtension;

/**
 * Mock extension that extends SimpleExtension for testing the TwigTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TwigExtension extends SimpleExtension
{
    public function isSafe()
    {
        return true;
    }

    public function getTestTemplateOutput($template, array $context = [])
    {
        return $this->renderTemplate($template, $context);
    }

    public function twigFunctionCallback($input)
    {
        return strtolower($input);
    }

    public function twigFilterCallback($input)
    {
        return strtoupper($input);
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
            'koala'    => 'twigFunctionCallback',
            'dropbear' => ['twigFunctionCallback', ['safe' => true]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFilters()
    {
        return [
            'koala'    => 'twigFilterCallback',
            'dropbear' => ['twigFilterCallback', ['safe' => true]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'koala',
            'dropbear' => ['position' => 'prepend', 'namespace' => 'Marsupial'],
        ];
    }
}
