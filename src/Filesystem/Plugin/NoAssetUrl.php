<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\Exception\LogicException;
use Bolt\Filesystem\MountPointAwareInterface;
use Bolt\Filesystem\PluginInterface;

/**
 * Declare that the filesystem cannot use "url" method.
 *
 * Useful when the AssetUrl plugin is used AND when the filesystem mount point matches an asset package,
 * but you don't want to use that one.
 */
class NoAssetUrl implements PluginInterface
{
    use PluginTrait;

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'url';
    }

    public function handle()
    {
        $message = 'This';
        $mp = null;

        if ($this->filesystem instanceof MountPointAwareInterface) {
            $mp = $this->filesystem->getMountPoint();
            $message .= "The \"$mp\"";
        }

        $message .= ' filesystem cannot generate asset urls.';

        if ($mp === 'bolt') {
            $message .= ' Try using the "bolt_asset" filesystem instead.';
        }

        throw new LogicException($message);
    }
}
