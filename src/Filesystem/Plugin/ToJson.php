<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\ImageInterface;
use Bolt\Filesystem\PluginInterface;

/**
 * Returns intrinsic data about file as well as some pre-generated links for JS to use.
 *
 * Goal of this plugin is to always give JS consistent data about a file in a DRY way.
 */
class ToJson implements PluginInterface
{
    /** @var FilesystemInterface */
    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'toJson';
    }

    /**
     * {@inheritdoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function handle($path)
    {
        $file = $this->filesystem->getFile($path);

        $result = [
            'path'     => $file->getPath(),
            'fullPath' => $file->getFullPath(),
        ];
        if ($file instanceof ImageInterface) {
            $result['previewUrl'] = $file->thumb(200, 150, 'c');
        }
        try {
            $result['url'] = $file->url();
        } catch (\Exception $e) {
        }

        return $result;
    }
}
