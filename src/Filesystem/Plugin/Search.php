<?php

namespace Bolt\Filesystem\Plugin;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

class Search implements PluginInterface
{
    /** @var FilesystemInterface */
    protected $filesystem;

    public function getMethod()
    {
        return 'search';
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function handle($term, $extensions = 'jpg,jpeg,gif,png')
    {
        $extensions = explode(",", $extensions);
        $allFiles = $this->filesystem->listContents('', true);
        $files = array();

        foreach ($allFiles as $file) {
            if ($file['type'] == 'file' &&
                ($term == '*' || strpos($file['path'], $term) !== false) &&
                in_array($file['extension'], $extensions)
            ) {
                $files[] = $file['path'];
            }
        }

        return $files;
    }
}
