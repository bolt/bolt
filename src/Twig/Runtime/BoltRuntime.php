<?php

namespace Bolt\Twig\Runtime;

use Bolt\Legacy\Content as LegacyContent;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Mapping\MetadataDriver;
use Bolt\Storage\Query\Query;
use Doctrine\DBAL\Schema\Column;

/**
 * Bolt extension runtime for Twig.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BoltRuntime
{
    /** @var Query */
    private $queryEngine;
    private $metadataDriver;

    /**
     * Constructor.
     *
     * @param Query          $queryEngine
     * @param MetadataDriver $metadataDriver
     */
    public function __construct(Query $queryEngine, MetadataDriver $metadataDriver)
    {
        $this->queryEngine = $queryEngine;
        $this->metadataDriver = $metadataDriver;
    }

    /**
     * @return Query
     */
    public function getQueryEngine()
    {
        return $this->queryEngine;
    }

    /**
     * @param Content|LegacyContent|string $content
     * @param Column|string                $column
     * @param string                       $subField
     *
     * @return string
     */
    public function fieldType($content, $column, $subField = null)
    {
        if ($content instanceof Content) {
            $name = (string) $content->getContenttype();
        } elseif ($content instanceof LegacyContent) {
            $name = $content->contenttype['slug'];
        } elseif (is_string($content)) {
            $name = $content;
        } else {
            throw new \BadMethodCallException(sprintf('The first parameter passed to fieldtype() but be a Content object or string, %s given', gettype($content)));
        }

        return $this->metadataDriver->getFieldTypeFor($name, $column, $subField);
    }
}
