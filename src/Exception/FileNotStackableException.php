<?php

namespace Bolt\Exception;

use Bolt\Filesystem\Handler\FileInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FileNotStackableException extends Exception implements HttpExceptionInterface
{
    /** @var FileInterface */
    private $unstackableFile;

    /**
     * {@inheritdoc}
     */
    public function __construct(FileInterface $file, $message = '')
    {
        $this->unstackableFile = $file;
        parent::__construct($message ?: 'File is not stackable: ' . $file->getFullPath(), 0);
    }

    /**
     * @return FileInterface
     */
    public function getUnstackableFile()
    {
        return $this->unstackableFile;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return Response::HTTP_FORBIDDEN;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return [];
    }
}
