<?php

namespace Bolt\Filesystem;

use League\Flysystem\PluginInterface;
use League\Flysystem\FilesystemInterface;

class SearchPlugin implements PluginInterface
{

    public $filesystem;

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

            if ($term == "*" || strpos($file['path'], $term) !== false) {

                if (in_array($file['extension'], $extensions)) {
                    $files[] = $file['path'];
                }

            }

        }

        return $files;
    }
}
