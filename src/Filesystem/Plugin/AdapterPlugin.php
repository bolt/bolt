<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\MountPointAwareInterface;
use Bolt\Filesystem\PluginInterface;
use Silex\Application;

abstract class AdapterPlugin implements PluginInterface
{
    /** @var FilesystemInterface */
    protected $filesystem;
    /** @var string */
    protected $namespace;
    /** @var Application */
    protected $app;

    public function __construct(Application $app, $namespace = 'files')
    {
        $this->app = $app;
        $this->namespace = $namespace === 'default' ? 'files' : $namespace;
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
        
        if ($this->filesystem instanceof MountPointAwareInterface) {
            $this->namespace = $this->filesystem->getMountPoint();
        }
    }

    public function getDefault()
    {
        return false;
    }

    public function handle()
    {
        $args = func_get_args();
        $method = 'get' . $this->adapterType() . ucfirst($this->getMethod());

        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $args);
        }

        return $this->getDefault();
    }

    protected function adapterType()
    {
        if ($this->filesystem instanceof Filesystem) {
            $reflect = new \ReflectionClass($this->filesystem->getAdapter());

            return $reflect->getShortName();
        }

        return 'Unknown';
    }
}
