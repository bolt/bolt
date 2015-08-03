<?php
namespace Bolt\Session\Handler;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Local filesystem session handler.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FileSessionHandler implements \SessionHandlerInterface
{
    /** @var string */
    protected $savePath;
    /** @var \Symfony\Component\Filesystem\Filesystem */
    protected $fs;

    /**
     * Constructor.
     *
     * @param string     $savePath Path of directory to save session files.
     *                             Default null will leave setting as defined by system temp directory.
     * @param Filesystem $filesystem
     */
    public function __construct($savePath = null, Filesystem $filesystem = null)
    {
        $this->fs = $filesystem ?: new Filesystem();

        $savePath = $savePath ?: sys_get_temp_dir();

        // Handle BC 'N;/path' and 'N;octal-mode;/path` which are not supported here
        if ($count = substr_count($savePath, ';')) {
            if ($count > 2) {
                throw new \InvalidArgumentException(sprintf('Invalid argument $savePath \'%s\'', $savePath));
            }

            // characters after last ';' are the path
            $savePath = ltrim(strrchr($savePath, ';'), ';');
        }

        if (!is_dir($savePath)) {
            $this->fs->mkdir($savePath, 0777);
        }

        $this->savePath = $savePath;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        try {
            $this->fs->touch($this->getSessionFileName($sessionName));

            return true;
        } catch (IOException $e) {
            return false;
        }
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

            return true;
        } catch (IOException $e) {
            return false;
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
            ->date("since $maxlifetime")
        ;

        foreach ($files as $file) {
            try {
                $this->fs->remove($file);
            } catch (IOException $e) {
                return false;
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
