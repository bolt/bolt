<?php 
namespace Bolt\Storage;

use Silex\Application;

/**
 * 
 */
class ContentLegacyService
{
    use Entity\ContentRelationTrait;
    use Entity\ContentRouteTrait;
    use Entity\ContentSearchTrait;
    use Entity\ContentTaxonomyTrait;
    use Entity\ContentValuesTrait;
    
    protected $app;
    
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    public function initialize($entity)
    {
        if (is_string($entity->contenttype)) {
            $contenttype = $this->app['storage']->getContenttype($contenttype);
        }

        $entity->contenttype = $contenttype;
    }
}
