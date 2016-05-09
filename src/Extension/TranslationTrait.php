<?php

namespace Bolt\Extension;

use Bolt\Filesystem\Handler\DirectoryInterface;
use Pimple as Container;

/**
 * Twig function/filter addition and interface functions for an extension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait TranslationTrait
{

    /**
     * Call this in register method.
     *
     * @internal
     */
    final protected function addTranslations()
    {
        $app = $this->getContainer();

        $translationDirectory = $this->getBaseDirectory()->getDir('translations');
        if ($translationDirectory->exists()) {
            foreach ($translationDirectory->getContents(true) as $fileInfo) {
                $filename = explode('.', $fileInfo->getFilename());
                if ($fileInfo->isFile()) {
                    $translation = isset($filename[0]) ? $filename[0] : '';

                    $app['translator']->addResource('yml', $app['paths']['extensionspath'] . DIRECTORY_SEPARATOR . $fileInfo->getPath(), $translation);
                }
            }
        }

    }

    /** @return Container */
    abstract protected function getContainer();

    /** @return string */
    abstract public function getName();

    /** @return DirectoryInterface */
    abstract protected function getBaseDirectory();
}
