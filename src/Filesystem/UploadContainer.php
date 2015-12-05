<?php

namespace Bolt\Filesystem;

use Sirius\Upload\Container\ContainerInterface;

class UploadContainer implements ContainerInterface
{
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
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has($file)
    {
        return $this->filesystem->has($file);
    }

    /**
     * {@inheritdoc}
     */
    public function save($file, $content)
    {
        $this->filesystem->put($file, $content);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($file)
    {
        $this->filesystem->delete($file);
    }

    /**
     * {@inheritdoc}
     */
    public function moveUploadedFile($localFile, $destination)
    {
        $this->filesystem->putStream($destination, fopen($localFile, 'r+'));
    }
}
