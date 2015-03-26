<?php

namespace Bolt\Database\Migration;

use Bolt\Application;
use Symfony\Component\Yaml\Parser;

/**
 * Database records iport class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Import extends AbstractMigration
{
    /** @var Bolt\Application */
    private $app;

    /**
     * Constructor.
     *
     * @param \Bolt\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Read a migration file.
     *
     * This function will determine what type based on extension.
     *
     * @param string $file
     *
     * @return array
     */
    protected function readMigrationFile($file)
    {
        $fileObj = new \SplFileInfo($file);
        $ext = $fileObj->getExtension();

        if (is_readable($file)) {
            if ($ext === 'yml' || $ext === 'yaml') {
                return $this->readYamlFile($file);
            } else {
                return $this->readJsonFile($file);
            }
        } else {
            $this->setError(true)->setErrorMessage("File '$file' not readable!");

            return false;
        }
    }

    /**
     * Read a YAML migration file.
     *
     * @param string $file
     *
     * @return array
     */
    private function readYamlFile($file, $output)
    {
        $parser = new Parser();

        try {
            return $parser->parse(file_get_contents($file) . "\n");
        } catch (ParseException $e) {
            $this->setError(true)->setErrorMessage("File '$file' has invalid YAML!");

            return false;
        }
    }

    /**
     * Read a JSON migration file.
     *
     * @param string $file
     *
     * @return array
     */
    private function readJsonFile($file)
    {
        $json = json_decode(file_get_contents($file), true);
        if ($json === false) {
            $this->setError(true)->setErrorMessage("File '$file' has invalid JSON!");
        }

        return $json;
    }
}
