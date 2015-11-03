<?php
namespace Bolt\Configuration\Check;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Checks for writeable directories.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DirectoryAccess extends BaseCheck implements ConfigurationCheckInterface
{
    /** @var array */
    protected $options = [
        'directories' => [
            'cache',
            'config',
            'extensions',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function runCheck()
    {
        $fs = new Filesystem();

        foreach ($this->options['directories'] as $directory) {
            $directory = $this->app['resources']->getPath($directory);

            try {
                $tmpfile = $directory . '/.check';

                if ($fs->exists($directory) && $fs->touch($tmpfile) === null && $fs->remove($tmpfile) === null) {
                    $this->createResult()->pass()->setMessage("Directory $directory is writable.");
                } else {
                    $this->createResult()->fail()->setMessage("Directory $directory NOT writable.");
                }
            } catch (IOException $e) {
                $this->createResult()->fail()->setMessage("Directory $directory NOT writable.");
            } catch (\Exception $e) {
                $this->createResult()->fail()->setMessage('PHP exception')->setException($e);
            }
        }

        return $this->results;
    }
}
