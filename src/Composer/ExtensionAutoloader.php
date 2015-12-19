<?php

namespace Bolt\Composer;

use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\File;
use Bolt\Filesystem\Handler\JsonFile;

/**
 * Class to manage autoloading functionality for extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionAutoloader
{
    /** @var array */
    protected $extensions;

    /** @var FilesystemInterface */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Build the autoload data for all extensions.
     */
    public function build()
    {
        /** @var File $file */
        foreach ($this->getComposerJson() as $file) {
            /** @var JsonFile $jsonFile */
            $jsonFile = $this->filesystem->get($file->getPath());
            $this->parseComposerJson($jsonFile);
        }

        $this->filesystem
            ->get('autoload.json', new JsonFile())
            ->dump($this->extensions)
        ;
    }

    /**
     * Load the extensions meta data from the composer.json file.
     *
     * @param JsonFile $jsonFile
     */
    protected function parseComposerJson(JsonFile $jsonFile)
    {
        $jsonData = $jsonFile->parse();
        $key = $jsonData['name'];
        $this->extensions[$key] = [
            'name'  => $jsonData['name'],
            'class' => $jsonData['extra']['bolt-class'],
            'path'  => $jsonFile->getDirname(),
        ];
    }

    /**
     * Return the collective composer.json files for all installed extensions.
     *
     * @return \Bolt\Filesystem\Finder
     */
    protected function getComposerJson()
    {
        return $this->filesystem
            ->find()
            ->files()
            ->in(['vendor', 'local'])
            ->notPath('vendor/composer')
            ->depth(2)
            ->name('composer.json')
            ->contains('"bolt-class"')
        ;
    }
}
