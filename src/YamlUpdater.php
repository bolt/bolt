<?php

namespace Bolt;

use Bolt\Application;
use Bolt\Exception\FilesystemException;
use Symfony\Component\Yaml\Parser;

/**
 * Allows (simple) modifications of Bolt .yml files.
 *
 * @author Bob den Otter <bob@twokings.nl>
 *
 **/
class YamlUpdater
{
    /**
     * @var $app Silex\Application
     */
    private $app;

    /**
     * @var Symfony\Component\Yaml\Parser
     */
    private $parser;

    /**
     * "File pointer". Basically used as offset for searching.
     * @var int
     */
    private $pointer = 0;

    /**
     * Number of lines in the file.
     * @var int
     */
    private $lines = 0;

    /**
     * Contains a line of the file per index.
     * @var array
     */
    private $file = array();

    /**
     * @var string
     */
    private $filename;

    /**
     * Creates an updater for the given file.
     *
     * @param Silex\Application $app
     * @param string            $filename   The file to modify
     */
    public function __construct(Application $app, $filename = '')
    {
        $this->app = $app;
        $this->changed = false;
        $this->filename = $filename;
        $this->parser = new Parser();

        $this->file = $app['filesystem']->getManager('config')->read($filename);

        // Check that the read-in YAML is valid
        $this->parser->parse($this->file, true, true);

        // Create a searchable array
        $this->file = explode("\n", $this->file);

        // Track the number of lines we have
        $this->lines = count($this->file);
    }

    /**
     * Get a value from the yml. return an array with info
     *
     * @param  string     $key
     * @return bool|array
     */
    public function get($key)
    {
        // resets pointer
        $this->pointer = 0;
        $result = false;
        $keyparts = explode("/", $key);

        foreach ($keyparts as $count => $keypart) {
            $result = $this->find($keypart, $count);
        }

        if ($result !== false) {
            return $this->parseline($result);
        } else {
            return false;
        }
    }

    /**
     * Find a specific part of the key, starting from $this->pointer
     *
     * @param  string   $keypart
     * @param  int      $indent
     * @return bool|int
     */
    private function find($keypart, $indent = 0)
    {
        while ($this->pointer <= $this->lines) {
            $needle = substr('                                      ', 0, 2 * $indent) . $keypart . ':';
            if (isset($this->file[$this->pointer]) && strpos($this->file[$this->pointer], $needle) === 0) {
                return $this->pointer;
            }
            $this->pointer++;
        }

        // Pointer is past end of file..
        return false;
    }

    /**
     * Parse a specific line-number into its key, value parts, with the used indentation.
     * @param $line
     * @return array
     */
    private function parseline($line)
    {
        preg_match_all('/(\s*)([a-z0-9_-]+):(\s)?(.*)/', $this->file[$line], $match);

        return array(
            'line' => $line,
            'indentation' => $match[1][0],
            'key' => $match[2][0],
            'value' => $match[4][0]
        );
    }

    /**
     * Change a key into a new value. Save .yml afterwards.
     *
     * @param  string  $key        YAML key to modify
     * @param  mixed   $value      New value
     * @param  boolean $makebackup Back up the file before commiting changes to it
     * @return bool
     */
    public function change($key, $value, $makebackup = true)
    {
        $this->makebackup = $makebackup;
        $match = $this->get($key);

        // Not found.
        if (!$match) {
            return false;
        }

        $value = $this->prepareValue($value);

        $this->file[$match['line']] = sprintf("%s%s: %s\n", $match['indentation'], $match['key'], $value);

        return $this->save($makebackup);
    }

    /**
     * Make sure the value is escaped as a yaml value..
     *
     * array('one', 'two', 'three') => [ one, two, three ]
     * "usin' quotes" => 'usin'' quotes
     *
     * @param  string $value
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
     * @param  boolean $makebackup Back up the file before commiting changes to it
     * @return bool true if save was successful
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

        // Attempt to write out a temporary copy of the new YAML file
        $tmpfile = $this->filename . '.tmp';
        if (! $this->app['filesystem']->getManager('config')->put($tmpfile, $this->yaml)) {
            throw new FilesystemException("Unable to write to temporary file: $tmpfile", FilesystemException::FILE_NOT_WRITEABLE);
        }

        // Delete original file
        if (! $this->app['filesystem']->getManager('config')->delete($this->filename)) {
            throw new FilesystemException("Unable to remove to YAML file: $this->filename", FilesystemException::FILE_NOT_REMOVEABLE);
        }

        // Copy temporary file over original
        if (! $this->app['filesystem']->getManager('config')->copy($tmpfile, $this->filename)) {
            throw new FilesystemException("Unable to write to file: $this->filename", FilesystemException::FILE_NOT_WRITEABLE);
        }

        // Delete temproary file
        if (! $this->app['filesystem']->getManager('config')->delete($tmpfile)) {
            throw new FilesystemException("Unable to remove to temporary file: $tmpfile", FilesystemException::FILE_NOT_REMOVEABLE);
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
        if (empty($this->yaml)) {
            $this->yaml = implode("\n", $this->file);
        }

        // This will throw a ParseException If the YAML is not valid
        $this->parser->parse($this->yaml, true, true);

        return true;
    }

    /**
     * Backup the YAML file
     *
     * @return boolean
     */
    protected function backup()
    {
        $this->app['filesystem']->getManager('config')->copy($this->filename, $this->filename . '.' . date('Ymd-His'));
    }
}
