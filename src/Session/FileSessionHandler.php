<?php
namespace Bolt\Session;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileSessionHandler implements \SessionHandlerInterface
{
    /** @var string */
    protected $savePath;

    /** @var \Symfony\Component\Filesystem\Filesystem */
    private $fs;
    /** @var bool */
    private $gcCalled = false;

    /**
     * Constructor.
     *
     * @param string $savePath Path of directory to save session files.
     *                         Default null will leave setting as defined by PHP.
     *                         '/path', 'N;/path', or 'N;octal-mode;/path
     *
     * @see http://php.net/session.configuration.php#ini.session.save-path for
     * further details.
     *
     * @throws \InvalidArgumentException On invalid $savePath
     */
    public function __construct($savePath = null)
    {
        $savePath = $savePath ?: ini_get('session.save_path') ?: sys_get_temp_dir();

        $this->fs = new Filesystem();
        $baseDir = $savePath;

        if ($count = substr_count($savePath, ';')) {
            if ($count > 2) {
                throw new \InvalidArgumentException(sprintf('Invalid argument $savePath \'%s\'', $savePath));
            }

            // characters after last ';' are the path
            $baseDir = ltrim(strrchr($savePath, ';'), ';');
        }

        if ($baseDir && !is_dir($baseDir)) {
            $this->fs->mkdir($baseDir, 0777);
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
        try {
            return file_get_contents($this->getSessionFileName($sessionId));
        } catch (IOException $e) {
            return '';
        }
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
            ->name('/\.bolt_sess$/')
            ->date("since $maxlifetime")
        ;

        foreach ($files as $file) {
            try {
                $this->fs->remove($file);
            } catch (IOException $e) {
                return false;
            }
        }
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
        return $this->savePath . DIRECTORY_SEPARATOR . $sessionId . '.bolt_sess';
    }
}
