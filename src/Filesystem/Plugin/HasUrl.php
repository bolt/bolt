<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\PluginInterface;

/**
 * Determines if the path has a url by try-catching the url plugin.
 */
class HasUrl implements PluginInterface
{
    use PluginTrait;

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'hasUrl';
    }

    /**
     * Determine if the path has a url.
     *
     * @param string $path
     *
     * @return bool
     */
    public function handle($path)
    {
        try {
            $this->filesystem->url($path);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
