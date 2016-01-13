<?php

namespace Bolt\Composer;

use Composer\Package\CompletePackageInterface;
use JsonSerializable;

/**
 * Class describing a single package, either composer installed, locally installed, or pending installation.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class Package implements JsonSerializable
{
    protected $status;
    protected $type;
    protected $name;
    protected $title;
    protected $description;
    protected $version;
    protected $authors = [];
    protected $keywords = [];
    protected $readmeLink;
    protected $configLink;
    protected $constraint;
    protected $valid = false;
    protected $enabled = false;

    /**
     * @param CompletePackageInterface $package
     *
     * @return Package
     */
    public static function createFromComposerPackage(CompletePackageInterface $package)
    {
        $class = new self();
        $class->type = $package->getType();
        $class->name = $package->getPrettyName();
        $class->description = $package->getDescription();
        $class->authors = $package->getAuthors();
        $class->keywords = $package->getKeywords();
        if ($package->getVersion() === '9999999-dev') {
            $class->version = sprintf('%s (%s)', $package->getPrettyVersion(), substr($package->getSourceReference(), 0, 6));
        } else {
            $class->version = $package->getPrettyVersion();
        }

        return $class;
    }

    /**
     * @param array $composerJson
     *
     * @return Package
     */
    public static function createFromComposerJson(array $composerJson)
    {
        $class = new self();
        $class->type = $composerJson['type'];
        $class->name = $composerJson['name'];
        $class->description = isset($composerJson['description']) ? $composerJson['description'] : '';
        $class->version = isset($composerJson['version']) ? $composerJson['version'] : 'local';
        $class->authors = isset($composerJson['authors']) ? $composerJson['authors'] : [];
        $class->keywords = isset($composerJson['keywords']) ? $composerJson['keywords'] : [];
        $class->constraint = isset($composerJson['require']['bolt/bolt']) ? $composerJson['require']['bolt/bolt'] : '0.0.0';

        return $class;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * Set the status, either 'installed', 'pending', or 'local'.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Set the type, e.g. 'bolt-extension', 'bolt-theme', 'composer-plugin', etc.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Set the Composer name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Return a package Composer name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the user-friendly title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Set the description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Set the version.
     *
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Set the authors.
     *
     * @param array $authors
     */
    public function setAuthors(array $authors)
    {
        $this->authors = $authors;
    }

    /**
     * Set the keywords.
     *
     * @param array $keywords
     */
    public function setKeywords(array $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Set the relative URI to the README file.
     *
     * @param string $readmeLink
     */
    public function setReadmeLink($readmeLink)
    {
        $this->readmeLink = $readmeLink;
    }

    /**
     * Set the relative URI to the YAML config file.
     *
     * @param string $configLink
     */
    public function setConfigLink($configLink)
    {
        $this->configLink = $configLink;
    }

    /**
     * Set the version constraint this package uses.
     *
     * @param string $constraint
     */
    public function setConstraint($constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * Set if this extension is valid.
     *
     * @param bool $valid
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    /**
     * Set if this extension is enabled.
     *
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }
}
