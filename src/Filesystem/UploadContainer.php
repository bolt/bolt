<?php

namespace Bolt\Filesystem;

use Bolt\Filesystem\Exception\IOException;
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
     *
     * @throws IOException
     *
     * This is called from \Sirius\Upload\Handler::processSingleFile() which expects a boolean return value,
     * and as \Bolt\FilesystemInterface::putStream only returns void or throws an error, we catch
     * IOExceptions here and return a false on exception.
     */
    public function moveUploadedFile($localFile, $destination)
    {
        $this->filesystem->putStream($destination, fopen($localFile, 'r+'));

        return true;
    }
}
