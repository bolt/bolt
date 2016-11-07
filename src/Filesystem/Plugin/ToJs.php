<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\Handler\ImageInterface;
use Bolt\Filesystem\PluginInterface;

/**
 * Returns intrinsic data about file as well as some pre-generated links for JS to use.
 *
 * Goal of this plugin is to always give JS consistent data about a file in a DRY way.
 */
class ToJs implements PluginInterface
{
    use PluginTrait;

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'toJs';
    }

    public function handle($path)
    {
        $file = $this->filesystem->getFile($path);

        $result = [
            'filename'  => $file->getFilename(),
            'path'      => $file->getPath(),
            'fullPath'  => $file->getFullPath(),
            'extension' => $file->getExtension(),
        ];
        if ($file instanceof ImageInterface) {
            $result['previewUrl'] = $file->thumb(200, 150, 'c');
            $result['previewListUrl'] = $file->thumb(60, 40, 'c');
        }
        try {
            $result['url'] = $file->url();
        } catch (\Exception $e) {
        }

        return $result;
    }
}
