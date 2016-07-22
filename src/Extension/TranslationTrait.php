<?php

namespace Bolt\Extension;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Pimple as Container;

/**
 * Automatic translation inclusion for an extension based upon three factors.
 *  - All translations are in the translations dub-directory of the extension
 *  - Translations are named as en.yml, en_GB.yml, or etc... based upon the locale
 *
 * @author Aaron Valandra <amvalandra@gmail.com>
 */
trait TranslationTrait
{
    /**
     * Call this in register method.
     *
     * @internal
     */
    final protected function extendTranslatorService()
    {
        $app = $this->getContainer();

        $app['translator'] = $app->share(
            $app->extend(
                'translator',
                function ($translator) {
                    $translations = $this->loadTranslationsFromDefaultPath();
                    if ($translations === null) {
                        return $translator;
                    }
                    foreach ($translations as $translation) {
                        $translator->addResource($translation[0], $translation[1], $translation[2]);
                    }

                    return $translator;
                }
            )
        );
    }

    /**
     * Load translations from every extensions translations directory.
     *
     * File names must follow common naming conventions, e.g.:
     *   - en.yml
     *   - en_GB.yml
     */
    private function loadTranslationsFromDefaultPath()
    {
        /** @var DirectoryInterface $baseDir */
        $baseDir = $this->getBaseDirectory();
        /** @var Filesystem $filesystem */
        $filesystem = $this->getBaseDirectory()->getFilesystem();
        if ($filesystem->has($baseDir->getFullPath() . '/translations') === false) {
            return null;
        }
        $translations = [];
        /** @var Local $local */
        $local = $filesystem->getAdapter();
        $basePath = $local->getPathPrefix();

        /** @var DirectoryInterface $translationDirectory */
        $translationDirectory = $filesystem->get($baseDir->getFullPath() . '/translations');
        foreach ($translationDirectory->getContents(true) as $fileInfo) {
            if ($fileInfo->isFile() === false) {
                continue;
            }

            $format = $fileInfo->getExtension();
            $resource = $basePath . $fileInfo->getPath();
            $locale = $fileInfo->getFilename('.' . $format);

            $translations[] = [$format, $resource, $locale];
        }

        return $translations;
    }

    /** @return Container */
    abstract protected function getContainer();

    /** @return DirectoryInterface */
    abstract protected function getBaseDirectory();
}
