<?php

namespace Bolt\Extension;

use Bolt\Config;
use Bolt\Filesystem\Exception\RuntimeException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\YamlFile;
use Bolt\Helpers\Arr;
use Bolt\Storage\Field\FieldInterface;
use Pimple as Container;

/**
 * Config file handling for extensions.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
trait ConfigTrait
{
    /** @var array */
    private $config;

    /**
     * Register a list of Bolt fields.
     *
     * Example:
     * <pre>
     *  return [
     *      new ColourPickField(),
     *  ];
     * </pre>
     *
     * @return FieldInterface[]
     */
    protected function registerFields()
    {
        return [];
    }

    /**
     * Call this in register method.
     */
    protected function extendConfigService()
    {
        $app = $this->getContainer();
        foreach ((array) $this->registerFields() as $fieldClass) {
            if ($fieldClass instanceof FieldInterface) {
                $app['config']->getFields()->addField($fieldClass);
            }
        }
    }

    /**
     * Override this to provide a default configuration,
     * which will be used in the absence of a config file.
     *
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [];
    }

    /**
     * Returns the config for the extension.
     *
     * @return array
     */
    protected function getConfig()
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $this->config = $this->getDefaultConfig();

        $app = $this->getContainer();
        $filesystem = $app['filesystem'];

        $file = new YamlFile();
        $filesystem->getFile(sprintf('config://extensions/%s.%s.yml', strtolower($this->getName()), strtolower($this->getVendor())), $file);

        if (!$file->exists()) {
            try {
                $this->copyDistFile($file);
            } catch (\RuntimeException $e) {
                return $this->config;
            }
        }

        $this->addConfig($file);

        $localFile = new YamlFile();
        $file->getParent()->getFile($file->getFilename('.yml') . '_local.yml', $localFile);
        if ($localFile->exists()) {
            $this->addConfig($localFile);
        }

        return $this->config;
    }

    /**
     * Merge in a yaml file to the config.
     *
     * @param YamlFile $file
     */
    private function addConfig(YamlFile $file)
    {
        $app = $this->getContainer();

        try {
            $newConfig = $file->parse();
        } catch (RuntimeException $e) {
            $app['logger.flash']->danger($e->getMessage());
            $app['logger.system']->error($e->getMessage(), ['event' => 'exception', 'exception' => $e]);
            throw $e;
        }

        if (is_array($newConfig)) {
            $this->config = Arr::mergeRecursiveDistinct($this->config, $newConfig);
        }
    }

    /**
     * Copy config.yml.dist to config/extensions.
     *
     * @param YamlFile $file
     */
    private function copyDistFile(YamlFile $file)
    {
        $app = $this->getContainer();
        $filesystem = $app['filesystem']->getFilesystem('extensions');

        /** @var YamlFile $distFile */
        $distFile = $filesystem->get(sprintf('%s/config/config.yml.dist', $this->getBaseDirectory()->getPath()), new YamlFile());
        if (!$distFile->exists()) {
            throw new \RuntimeException(sprintf('No config.yml.dist file found at extensions://%s', $this->getBaseDirectory()->getPath()));
        }
        $file->write($distFile->read());
        $app['logger.system']->info(
            sprintf('Copied %s to %s', $distFile->getFullPath(), $file->getFullPath()),
            ['event' => 'extensions']
        );
    }

    /** @return string */
    abstract public function getName();

    /** @return string */
    abstract public function getVendor();

    /** @return DirectoryInterface */
    abstract protected function getBaseDirectory();

    /** @return Container */
    abstract protected function getContainer();
}
