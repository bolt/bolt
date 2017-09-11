<?php

namespace Bolt\Configuration;

use Bolt\Collection\MutableBag;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\FileInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Inline;
use Symfony\Component\Yaml\Yaml;

/**
 * Allows (simple) modifications of Bolt .yml files.
 *
 * @author Bob den Otter <bob@twokings.nl>
 **/
class YamlUpdater
{
    /** @var FileInterface */
    private $file;
    /** @var MutableBag|string[] Contains a line of the file per index. */
    private $lines;
    /** @var MutableBag the parsed yml file */
    private $parsed;

    /**
     * Creates an updater for the given file.
     *
     * @param FileInterface $file
     */
    public function __construct(FileInterface $file)
    {
        $this->file = $file;
    }

    /**
     * Return a value for a key from the yml file.
     *
     * @param string $key
     * @param bool   $throwEx Throw exception if key is not found.
     *
     * @return mixed|string
     */
    public function get($key, $throwEx = true)
    {
        if ($this->parsed === null) {
            $this->parse();
        }

        if ($throwEx && !$this->parsed->hasPath($key)) {
            throw new \InvalidArgumentException(sprintf("The key '%s' was not found in %s.", $key, $this->file->getFullPath()));
        }

        $value = $this->parsed->getPath($key);
        if (is_array($value)) {
            return Yaml::dump($value, 0, 4);
        }

        return $value;
    }

    /**
     * Updates a single value with replacement for given key in yml file.
     *
     * @param string       $key
     * @param string|array $value
     * @param bool         $backup
     *
     * @return bool
     */
    public function change($key, $value, $backup = true)
    {
        if ($this->parsed === null) {
            $this->parse();
        }

        $yaml = $this->lines->join("\n");

        $pattern = '/^' . str_replace('/', ':.*?', $key) . '(:\s*)/mis';
        preg_match($pattern, $yaml, $matches, PREG_OFFSET_CAPTURE);
        if (!$matches) {
            throw new \InvalidArgumentException(sprintf("The key '%s' was not found in %s.", $key, $this->file->getFullPath()));
        }

        $column = $matches[1][1] + strlen($matches[1][0]);
        $line = substr_count($yaml, "\n", 0, $column);
        $this->lines[$line] = preg_replace('/^(.*?):(.*(?= #)|.*$)/', '$1: ' . Inline::dump($value), $this->lines[$line]);

        $this->save($backup);

        return true;
    }

    /**
     * Parse the YAML file.
     */
    protected function parse()
    {
        $yaml = $this->file->read();
        $this->parsed = MutableBag::from(Yaml::parse($yaml, true, true));
        // Create a searchable array based on original text file
        $this->lines = MutableBag::from(explode("\n", $yaml));
    }

    /**
     * Save the data to the YAML file.
     *
     * @param boolean $backup Back up the file before committing changes to it
     *
     * @throws IOException
     * @throws ParseException
     */
    protected function save($backup)
    {
        $yaml = $this->lines->join("\n");

        Yaml::parse($yaml, true, true);

        if ($backup) {
            $this->file->copy($this->file->getPath() . '.' . date('Ymd-His'));
        }

        $this->file->update($yaml);
    }
}
