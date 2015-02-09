<?php
namespace Bolt\Filesystem;

use Bolt\Application;
use Bolt\Filesystem\Plugin;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;

class Manager extends MountManager
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct(array(
            'root'       => $app['resources']->getPath('root'),
            'default'    => $app['resources']->getPath('files'),
            'files'      => $app['resources']->getPath('files'),
            'config'     => $app['resources']->getPath('config'),
            'theme'      => $app['resources']->getPath('themebase'),
            'extensions' => $app['resources']->getPath('extensionspath'),
        ));
    }

    public function getManager($namespace = null)
    {
        return $this->getFilesystem($namespace);
    }

    public function getFilesystem($prefix = null)
    {
        if (isset($this->filesystems[$prefix])) {
            return parent::getFilesystem($prefix);
        } else {
            return parent::getFilesystem('files');
        }
    }

    public function setManager($namespace, FilesystemInterface $filesystem)
    {
        $this->mountFilesystem($namespace, $filesystem);
    }

    public function mountFilesystem($prefix, FilesystemInterface $filesystem)
    {
        parent::mountFilesystem($prefix, $filesystem);

        $filesystem->addPlugin(new Plugin\SearchPlugin());
        $filesystem->addPlugin(new Plugin\BrowsePlugin());
        $filesystem->addPlugin(new Plugin\PublicUrlPlugin($this->app, $prefix));
        $filesystem->addPlugin(new Plugin\ThumbnailUrlPlugin($this->app, $prefix));

        return $this;
    }

    /**
     * Mounts a local filesystem if the directory exists.
     *
     * @param string $prefix
     * @param string $location
     *
     * @return $this|false
     */
    public function mount($prefix, $location)
    {
        if (!is_dir($location)) {
            return false;
        }
        return parent::mountFilesystem($prefix, new Filesystem(new Local($location)));
    }

    /**
     * By default we forward anything called on this class to the default manager
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $callback = array($this->getManager(), $method);

        return call_user_func_array($callback, $arguments);
    }
}
