<?php
namespace Bolt;

use Bolt\Exception\FilesystemException;
use League\Flysystem\File;
use Silex;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Allows (simple) modifications of Bolt .yml files.
 *
 * @author Bob den Otter <bob@twokings.nl>
 **/
class YamlUpdater
{
    /** @var Parser */
    private $parser;
    /** @var integer Number of lines in the file. */
    private $lines = 0;
    /** @var array Contains a line of the file per index. */
    private $yaml = array();
    /** @var File */
    private $file;
    /** @var array the parsed yml file */
    private $parsed;

    /**
     * Creates an updater for the given file.
     *
     * @param \Silex\Application $app
     * @param string             $filename The file to modify
     */
    public function __construct(Silex\Application $app, $filename = '')
    {
        $this->changed = false;
        $this->file = $app['filesystem']->get('config://' . $filename, new File());
        $this->parser = new Parser();

        // Get the contents of the file
        $yaml = $this->file->read();

        // Check that the read-in YAML is valid
        $this->parsed = $this->parser->parse($yaml, true, true);

        // Create a searchable array
        $this->yaml = explode("\n", $yaml);

        // Track the number of lines we have
        $this->lines = count($this->yaml);
    }

    /**
     * Return a value for a key from the yml file.
     *
     * @param string $key
     *
     * @return boolean|array
     */
    public function get($key)
    {
        $yaml = $this->parsed;

        $keyparts = explode('/', $key);
        while ($key = array_shift($keyparts)) {
            $yaml = &$yaml[$key];
        }

        if (is_array($yaml)) {
            return Yaml::dump($yaml, 0, 4);
        }

        return $yaml;
    }

    /**
     * Updates a single value with replacement for given key in yml file.
     *
     * @param string $key
     * @param string $value
     *
     * @return boolean
     */
    public function change($key, $value, $makebackup = true)
    {
        $pattern = str_replace('/', ":.*", $key);
        preg_match_all('/^'.$pattern.'(:\s*)/mis', $this->file->read(), $matches,  PREG_OFFSET_CAPTURE);

        if (count($matches[0]) > 0 && count($matches[1])) {
            $index = $matches[1][0][1] + strlen($matches[1][0][0]);
        } else {
            return false;
        }

        $line = substr_count($this->file->read(), "\n", 0, $index);
        $this->yaml[$line] = preg_replace('/^(.*):(.*)/', "$1: ".$this->prepareValue($value), $this->yaml[$line]);

        return $this->save($makebackup);
    }

    /**
     * Make sure the value is escaped as a yaml value.
     *
     * array('one', 'two', 'three') => [ one, two, three ]
     * "usin' quotes" => 'usin'' quotes
     *
     * @param string $value
     *
     * @return string
     */
    public function prepareValue($value)
    {
        if (is_array($value)) {
            return "[ " . implode(", ", $value) . " ]";
        }

        if (preg_match('/[^a-z0-9]/i', $value)) {
            return "'" . str_replace("'", "''", $value) . "'";
        }

        return $value;
    }

    /**
     * Save our modified .yml file.
     *
     * @param boolean $makebackup Back up the file before commiting changes to it
     *
     * @throws \Bolt\Exception\FilesystemException
     *
     * @return boolean true if save was successful
     */
    protected function save($makebackup)
    {
        if (!$this->verify()) {
            return false;
        }

        // If we're backing up do it, if we can
        if ($makebackup) {
            $this->backup();
        }

        // Update the YAML file if we can, or throw an error
        if (! $this->file->update($this->yaml)) {
            throw new FilesystemException('Unable to write to file: ' . $this->file->getPath(), FilesystemException::FILE_NOT_WRITEABLE);
        }

        return true;
    }

    /**
     * Verify if the modified YAML is still a valid .yml file, and if we
     * are actually allowed to write and update the current file.
     *
     * @return boolean
     */
    protected function verify()
    {
        if (is_array($this->yaml)) {
            $this->yaml = implode("\n", $this->yaml);
        }

        // This will throw a ParseException If the YAML is not valid
        $this->parser->parse($this->yaml, true, true);

        return true;
    }

    /**
     * Backup the YAML file.
     */
    protected function backup()
    {
        $this->file->copy($this->file->getPath() . '.' . date('Ymd-His'));
    }
}
