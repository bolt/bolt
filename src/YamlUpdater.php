<?php

namespace Bolt;

use Bolt\Application;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Yaml\Exception\ParseException;
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
        if (!is_readable($filename)) {
            echo "Can't read $filename\n";

            return false;
        }

        $this->app = $app;
        $this->filename = $filename;
        $this->file = file($filename);
        $this->lines = count($this->file);

        $this->changed = false;

        return true;
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
        $this->filesystem = new FileSystem();

        if (!$this->verify()) {
            return false;
        }

        // If we're backing up do it, if we can
        if ($makebackup && !$this->backup()) {
            return false;
        }

        // Attempt to write out a temporary copy of the new YAML file
        $tmpfile = $this->filename . '.tmp';
        try {
            $this->filesystem->dumpFile($tmpfile, $this->yaml);
        } catch (IOExceptionInterface $e) {
            return false;
        }

        // We know the temporary file is readable, we touched the file in verify(),
        // so attempt a final rename
        try {
            $this->filesystem->rename($tmpfile, $this->filename, true);
        } catch (IOExceptionInterface $e) {
            return false;
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
            $this->yaml = implode('', $this->file);
        }

        // Attempt to change the modification time on the file to test if it is
        // writeable
        try {
            $this->filesystem->touch($this->filename);
        } catch (IOExceptionInterface $e) {
            return false;
        }

        // Test that we can parse the YAML
        // This will throw a ParseException If the YAML is not valid
        try {
            $parser = new Parser();
            $parser->parse($this->yaml, true, true);

        } catch (ParseException $e) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return boolean
     */
    protected function backup()
    {
        try {
            $this->filesystem->copy($this->filename, $this->filename . '.' . date('Ymd-His'), true);
        } catch (IOExceptionInterface $e) {
            return false;
        }

        return true;
    }
}
