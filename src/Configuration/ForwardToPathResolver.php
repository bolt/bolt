<?php

namespace Bolt\Configuration;

/**
 * Forwards all relevant paths to PathResolver.
 *
 * @author Carson Full <carsonfull@gmail.com>
 *
 * @deprecated since 3.3, to be removed in 4.0.
 */
class ForwardToPathResolver extends ResourceManager
{
    /**
     * {@inheritdoc}
     */
    public function __construct(\ArrayAccess $container)
    {
        parent::__construct($container);
        $paths = [
            'app',
            'cache',
            'config',
            'database',
            'extensions',
            'extensions_config',
            'web',
            'files',
            'themes',
            'bolt_assets',
            'themebase',
            'extensionsconfig',
            'view',
        ];
        foreach ($paths as $path) {
            $this->setPath($path, "%$path%", false);
        }
    }
}
