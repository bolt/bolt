<?php
namespace Bolt\Session\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Local filesystem session handler.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FileHandler implements \SessionHandlerInterface, LazyWriteHandlerInterface
{
    /** @var integer */
    protected $depth;
    /** @var integer */
    protected $mode;
    /** @var string */
    protected $savePath;
    /** @var \Symfony\Component\Filesystem\Filesystem */
    protected $fs;
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /**
     * Constructor.
     *
     * @param string          $savePath   Path of directory to save session files.
     *                                    Default null will leave setting as defined by system temp directory.
     * @param LoggerInterface $logger
     * @param Filesystem      $filesystem
     */
    public function __construct($savePath = null, LoggerInterface $logger = null, Filesystem $filesystem = null)
    {
        $this->fs = $filesystem ?: new Filesystem();
        $this->logger = $logger ?: new NullLogger();

        // @see http://php.net/manual/en/session.configuration.php#ini.session.save-path
        $depth = 1;
        $mode = 0600;
        $savePath = $savePath ?: sys_get_temp_dir();

        // Handle 'N;/path' and 'N;octal-mode;/path` values. We don't currently support depth handling.
        if ($count = substr_count($savePath, ';')) {
            if ($count > 2) {
                throw new \InvalidArgumentException(sprintf('Invalid argument $savePath \'%s\'', $savePath));
            }

            $path = explode(';', $savePath);
            if ($count === 1) {
                $depth = $path[0];
                $savePath = $path[1];
            } else {
                $depth = $path[0];
                $mode = intval($path[1], 8);
                $savePath = $path[2];
            }
        }

        if (!is_dir($savePath)) {
            $this->fs->mkdir($savePath, 0777);
        }

        $this->depth = $depth;
        $this->mode = $mode;
        $this->savePath = $savePath;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return file_get_contents($this->getSessionFileName($sessionId));
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        try {
            $this->fs->dumpFile($this->getSessionFileName($sessionId), $data);
        } catch (IOException $e) {
            $this->logger->error('Unable to write session file to ' . $this->savePath);

            return false;
        }

        try {
            $this->fs->chmod($this->getSessionFileName($sessionId), $this->mode);
        } catch (IOException $e) {
            $this->logger->error('Unable to set correct permissions on session file in ' . $this->savePath);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTimestamp($sessionId, $data)
    {
        try {
            $this->fs->touch($this->getSessionFileName($sessionId));
        } catch (IOException $e) {
        }
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        try {
            $this->fs->remove($this->getSessionFileName($sessionId));

            return true;
        } catch (IOException $e) {
            $this->logger->error('Unable to remove session file ' . $this->getSessionFileName($sessionId));

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        $finder = new Finder();
        $files = $finder->files()
            ->in($this->savePath)
            ->name('/\.sess$/')
            ->date("< now - $maxlifetime seconds")
        ;

        foreach ($files as $file) {
            try {
                $this->fs->remove($file);
            } catch (IOException $e) {
                $this->logger->error('Unable to remove session file ' . $file);
            }
        }

        return true;
    }

    /**
     * Get the fully qualified file name of a session file based on ID.
     *
     * @param string $sessionId
     *
     * @return string
     */
    private function getSessionFileName($sessionId)
    {
        return $this->savePath . DIRECTORY_SEPARATOR . $sessionId . '.sess';
    }
}
