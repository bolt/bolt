<?php

namespace Bolt\Composer\EventListener;

use Composer\Composer;
use Composer\Package\Link;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Semver;
use JsonSerializable;

/**
 * Package reference descriptor.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class PackageDescriptor implements JsonSerializable
{
    /** @var string */
    protected $name;
    /** @var string */
    protected $type;
    /** @var string */
    protected $class;
    /** @var string */
    protected $path;
    /** @var string */
    protected $webPath;
    /** @var string */
    protected $constraint;
    /** @var bool */
    protected $valid;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $type
     * @param string $class
     * @param string $path
     * @param string $webPath
     * @param string $constraint
     * @param bool   $valid
     */
    public function __construct($name, $type, $class, $path, $webPath, $constraint, $valid)
    {
        $this->name = $name;
        $this->type = $type;
        $this->class = $class;
        $this->path = $path;
        $this->webPath = $webPath;
        $this->constraint = $constraint;
        $this->valid = $valid;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getWebPath()
    {
        return $this->webPath;
    }

    /**
     * @return string
     */
    public function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Create class from uncertain JSON data.
     *
     * @param Composer $composer
     * @param string   $webPath
     * @param string   $path
     * @param array    $jsonData
     *
     * @return PackageDescriptor
     */
    public static function parse(Composer $composer, $webPath, $path, array $jsonData)
    {
        $name = $jsonData['name'];
        $type = strpos($path, 'vendor') === 0 ? 'composer' : 'local';
        $class = self::parseClass($jsonData);
        $constraint = self::parseConstraint($jsonData);
        $valid = self::parseValid($composer, $class, $constraint);

        return new self($name, $type, $class, $path, $webPath, $constraint, $valid);
    }

    /**
     * Re-instantiate class from array element.
     *
     * @param array $data
     *
     * @return PackageDescriptor
     */
    public static function create(array $data)
    {
        return new self(
            $data['name'],
            $data['type'],
            $data['class'],
            $data['path'],
            $data['webPath'],
            $data['constraint'],
            $data['valid']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'name'       => $this->name,
            'type'       => $this->type,
            'path'       => $this->path,
            'webPath'    => $this->webPath,
            'class'      => $this->class,
            'constraint' => $this->constraint,
            'valid'      => $this->valid,
        ];
    }

    /**
     * Record the package's loading class.
     *
     * @param array $jsonData
     *
     * @return string|null
     */
    private static function parseClass(array $jsonData)
    {
        if (isset($jsonData['extra']['bolt-class'])) {
            return $jsonData['extra']['bolt-class'];
        }

        return null;
    }

    /**
     * Record the package's version constraints.
     *
     * @param array $jsonData
     *
     * @return string|null
     */
    private static function parseConstraint(array $jsonData)
    {
        if (isset($jsonData['require']['bolt/bolt'])) {
            return $jsonData['require']['bolt/bolt'];
        }

        return null;
    }

    /**
     * Check if the extension is valid for loading, i.e has a class and is withing version constraints.
     *
     * @param Composer    $composer
     * @param string|null $class
     * @param string|null $constraint
     *
     * @return bool
     */
    private static function parseValid(Composer $composer, $class, $constraint)
    {
        if ($constraint === null) {
            return false;
        }

        $provides = $composer->getPackage()->getProvides();
        $boltVersion = isset($provides['bolt/bolt']) ? $provides['bolt/bolt'] : new Link('__root__', 'bolt/bolt', new Constraint('=', '0.0.0'));

        return $class && Semver::satisfies($boltVersion->getPrettyConstraint(), $constraint);
    }
}
