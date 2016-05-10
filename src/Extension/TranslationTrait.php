<?php

namespace Bolt\Extension;

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

    /** @var array $translations */
    private $translations = [];

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
                    $this->loadTranslationsFromDefaultPath();

                    if ($this->translations) {
                        foreach ($this->translations as $translation) {
                            $translator->addResource($translation[0], $translation[1], $translation[2]);
                        }
                    }

                    return $translator;
                }
            )
        );

    }

    /**
     * Load translations from every extensions translations directory.
     * Filenames must follow common naming conventions - en.yml, en_GB.yml, otherwise
     *  function will need to be modified.
     */
    private function loadTranslationsFromDefaultPath()
    {
        $app = $this->getContainer();

        $translationDirectory = $this->getBaseDirectory()->getDir('translations');
        if ($translationDirectory->exists()) {
            foreach ($translationDirectory->getContents(true) as $fileInfo) {
                if ($fileInfo->isFile()) {
                    list($domain, $extension) = explode('.', $fileInfo->getFilename());

                    $path = $app['resources']->getPath('extensions' . DIRECTORY_SEPARATOR . $fileInfo->getPath());

                    $this->translations[] = [$extension, $path, $domain];
                }
            }
        }

    }

    /** @return Container */
    abstract protected function getContainer();

    /** @return DirectoryInterface */
    abstract protected function getBaseDirectory();
}
