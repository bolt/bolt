<?php
namespace Bolt\Filesystem;

use League\Flysystem\Filesystem;
use Sirius\Upload\Container\ContainerInterface;

class FlysystemContainer implements ContainerInterface
{
    public $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function isWritable()
    {
        return true;
    }

    public function has($file)
    {
        return $this->filesystem->has($file);
    }

    public function save($file, $content)
    {
        return $this->filesystem->put($file, $content);
    }

    public function delete($file)
    {
        return $this->filesystem->delete($file);
    }

    public function moveUploadedFile($localFile, $destination)
    {
        $stream = fopen($localFile, "r+");
        if ($this->filesystem->putStream($destination, $stream) === true) {
            return $destination;
        }

        return false;
    }
}
