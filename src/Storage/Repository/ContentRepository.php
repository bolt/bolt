<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Repository;

/**
 * A Repository class that handles dynamically created content tables.
 */
class ContentRepository extends Repository
{
    
    /**
     * getContent based on a 'human readable query'.
     *
     * Used directly by {% setcontent %} but also in other parts.
     * For reference refer to @link https://docs.bolt.cm/content-fetching
     *
     * @param string $textquery
     * @param string $parameters
     *
     * @return array
     */
    public function getContent($query, $parameters = null)
    {
        $parser = new ContentQueryParser($this->getEntityManager(), $query, $parameters);
        $query = $parser->getQuery();
        foreach($query as $q) {
            var_dump($q->getSQL());
        }
        
    }

}
