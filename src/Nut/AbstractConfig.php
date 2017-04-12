<?php

namespace Bolt\Nut;

use Bolt\Exception\FilesystemException;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\YamlFile;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Config command case class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractConfig extends BaseCommand
{
    /**
     * @param Exception $e
     * @param YamlFile  $file
     *
     * @throws Exception
     */
    protected function handleException(Exception $e, YamlFile $file)
    {
        if ($e instanceof FileNotFoundException) {
            $this->io->error(sprintf("Can't read file: %s.", $file->getFilename()));
        } elseif ($e instanceof ParseException) {
            $this->io->error(sprintf('Invalid YAML in file: %s.', $file->getFilename()));
        } elseif ($e instanceof FilesystemException) {
            $this->io->error(sprintf('' . $e->getMessage() . ''));
        } else {
            throw $e;
        }
    }

    /**
     * @param InputInterface $input
     *
     * @return YamlFile
     */
    protected function getFile(InputInterface $input)
    {
        $fs = $this->app['filesystem'];
        $fileName = $input->getOption('file');
        if (strpos($fileName, '://') == false) {
            $fileName = 'config://' . $fileName;
        }

        return $fs->get($fileName, new YamlFile());
    }
}
