<?php
namespace Bolt\Storage;

use Bolt\Storage\Mapping\ContentType;
use Silex\Application;

/**
 * Legacy bridge for Content object backward compatibility.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ContentLegacyService
{
    use Entity\ContentRelationTrait;
    use Entity\ContentRouteTrait;
    use Entity\ContentSearchTrait;
    use Entity\ContentTaxonomyTrait;
    use Entity\ContentValuesTrait;

    protected $app;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Initialise.
     *
     * @param Entity\Entity $entity
     */
    public function initialize(Entity\Entity $entity)
    {
        $this->setupContenttype($entity);
    }

    /**
     * Set the legacy ContentType object on the Entity.
     *
     * @param Entity\Entity $entity
     */
    public function setupContenttype(Entity\Entity $entity)
    {
        $contentType = $entity->getContenttype();
        if (is_string($contentType)) {
            $entity->contenttype = new ContentType($contentType, $this->app['storage']->getContenttype($contentType));
        }
    }
}
