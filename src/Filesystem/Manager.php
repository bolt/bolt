<?php
namespace Bolt\Filesystem;

use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Silex\Application;

class Manager extends MountManager
{
    const DEFAULT_PREFIX = 'files';

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct(
            [
                'root'       => $app['resources']->getPath('root'),
                'app'        => $app['resources']->getPath('app'),
                'default'    => $app['resources']->getPath('files'),
                'files'      => $app['resources']->getPath('files'),
                'config'     => $app['resources']->getPath('config'),
                'theme'      => $app['resources']->getPath('themebase'),
                'extensions' => $app['resources']->getPath('extensionspath'),
            ]
        );
    }

    public function getFilesystem($prefix = null)
    {
        $prefix = isset($this->filesystems[$prefix]) ? $prefix : static::DEFAULT_PREFIX;

        return parent::getFilesystem($prefix);
    }

    public function mountFilesystems(array $filesystems)
    {
        foreach ($filesystems as $prefix => $filesystem) {
            if (!$filesystem instanceof FilesystemInterface) {
                $filesystem = $this->createFilesystem($filesystem);
            }
            $this->mountFilesystem($prefix, $filesystem);
        }

        return $this;
    }

    public function mountFilesystem($prefix, FilesystemInterface $filesystem)
    {
        parent::mountFilesystem($prefix, $filesystem);

        $filesystem->addPlugin(new Plugin\Search());
        $filesystem->addPlugin(new Plugin\Browse());
        $filesystem->addPlugin(new Plugin\PublicUrl($this->app, $prefix));
        $filesystem->addPlugin(new Plugin\ThumbnailUrl($this->app, $prefix));
        $filesystem->addPlugin(new Plugin\Authorized($this->app, $prefix));

        return $this;
    }

    /**
     * Mounts a local filesystem if the directory exists.
     *
     * @param string $prefix
     * @param string $location
     *
     * @return $this
     */
    public function mount($prefix, $location)
    {
        return parent::mountFilesystem($prefix, $this->createFilesystem($location));
    }

    protected function createFilesystem($location)
    {
        return new Filesystem(is_dir($location) ? new Local($location) : new NullAdapter());
    }

    public function filterPrefix(array $arguments)
    {
        try {
            return parent::filterPrefix($arguments);
        } catch (\InvalidArgumentException $e) {
            return [static::DEFAULT_PREFIX, $arguments];
        }
    }
}
