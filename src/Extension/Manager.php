<?php

namespace Bolt\Extension;

use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\JsonFile;
use Silex\Application;

/**
 * Management class for extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
    /** @var \Bolt\Composer\ExtensionLoader */
    protected $loader;

    /** @var FilesystemInterface */
    private $filesystem;
    /** @var Application */
    private $app;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->loader = $app['extensions.loader'];
        $this->filesystem = $app['filesystem']->getFilesystem('extensions');
    }

    /**
     * Get an extension's composer.json data.
     *
     * @param $name
     *
     * @return array
     */
    public function getComposerJson($name)
    {
        if ($extension = $this->loader->get($name)) {
            $jsonFile = file_get_contents($extension->getPath() . '/composer.json');
            return json_decode($jsonFile, true);

            /** @var JsonFile $jsonFile */
            //$jsonFile = $this->filesystem->get($extension->getPath() . '/composer.json');
            //
            //return $jsonFile->parse();
        }
    }
}
