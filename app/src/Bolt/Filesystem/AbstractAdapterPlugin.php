<?php

namespace Bolt\Filesystem;

use League\Flysystem\PluginInterface;
use League\Flysystem\FilesystemInterface;
use Bolt\Application;

abstract class AbstractAdapterPlugin implements PluginInterface
{

    public $filesystem;
    public $namespace;
    public $handlers = array();

    public function __construct(Application $app, $namespace = 'files')
    {
        $this->app = $app;
        if ($namespace == 'default') {
            $this->namespace = 'files';
        } else {
            $this->namespace = $namespace;
        }
    }

    public function getMethod()
    {
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function handle($path)
    {
        $method = "get".$this->adapterType().ucfirst($this->getMethod());

        if (method_exists($this, $method)) {
            return $this->$method($path);
        }

        if (property_exists($this, 'default')) {
            return $this->default;
        }

        return false;
    }

    protected function adapterType()
    {
        $reflect = new \ReflectionClass($this->filesystem->getAdapter());

        return $reflect->getShortName();
    }
}
