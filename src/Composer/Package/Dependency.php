<?php

namespace Bolt\Composer\Package;

use Composer\Package\Link;
use Composer\Package\PackageInterface;
use JsonSerializable;

/**
 * A single composer package dependency.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Dependency implements JsonSerializable
{
    const DEPENDS = 'depends';
    const PROHIBITS = 'prohibits';

    /** @var string */
    protected $name;
    /** @var string */
    private $type;
    /** @var PackageInterface */
    protected $package;
    /** @var Link */
    protected $link;
    /** @var Dependency[]|false */
    protected $children;
    /** @var DependencyRelationship */
    protected $relationship;

    /**
     * Constructor.
     *
     * @param string             $name
     * @param string             $type
     * @param PackageInterface   $package
     * @param Link               $link
     * @param Dependency[]|false $children
     */
    public function __construct($name, $type, PackageInterface $package, Link $link, $children)
    {
        $this->name = $name;
        $this->type = $type;
        $this->package = $package;
        $this->link = $link;
        $this->setChildren($children);
        $this->relationship = (new DependencyRelationship())
            ->setSourceName($package->getPrettyName())
            ->setSourceVersion($package->getPrettyVersion())
            ->setTargetName($link->getTarget())
            ->setTargetVersion($link->getPrettyConstraint())
            ->setReason($link->getDescription())
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->serialize();
    }

    /**
     * Return object as array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->serialize();
    }

    /**
     * @param string $name
     * @param string $type
     * @param array  $result
     *
     * @return Dependency
     */
    public static function create($name, $type, array $result)
    {
        list($package, $link, $children) = $result;

        return new self($name, $type, $package, $link, $children);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Dependency
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Dependency
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return PackageInterface
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param PackageInterface $package
     *
     * @return Dependency
     */
    public function setPackage(PackageInterface $package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * @return Link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param Link $link
     *
     * @return Dependency
     */
    public function setLink(Link $link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @return Dependency[]|false
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Dependency[]|false $children
     *
     * @return Dependency
     */
    public function setChildren($children)
    {
        if (is_array($children)) {
            $c = [];
            foreach ($children as $name => $child) {
                list($package, $link, $children) = $child;
                $c[] = new self($name, $this->type, $package, $link, $children);
            }

            $this->children = $c;
        }

        return $this;
    }

    /**
     * @return DependencyRelationship
     */
    public function getRelationship()
    {
        return $this->relationship;
    }

    /**
     * @param DependencyRelationship $relationship
     *
     * @return Dependency
     */
    public function setRelationship(DependencyRelationship $relationship)
    {
        $this->relationship = $relationship;

        return $this;
    }

    /**
     * @return array
     */
    private function serialize()
    {
        return [
            'name'         => $this->name,
            'type'         => $this->type,
            'package'      => (string) $this->package,
            'link'         => (string) $this->link,
            'relationship' => $this->relationship,
        ];
    }
}
