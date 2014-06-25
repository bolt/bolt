<?php
namespace RelatedContentByTags;

class Extension extends \Bolt\BaseExtension
{

    function info()
    {

        $data = array(
            'name' => "Related Content By Tags",
            'description' => "Retrieves a list of similar content based on tags.",
            'author' => "Xiao-Hu Tai",
            'link' => "http://bolt.cm",
            'version' => "0.1",
            'required_bolt_version' => "1.2",
            'highest_bolt_version' => "1.2",
            'type' => "General",
            'first_releasedate' => "2013-10-25",
            'latest_releasedate' => "2013-10-25",
            'dependencies' => "",
            'priority' => 10,
        );

        return $data;

    }

    function initialize()
    {

        $this->addTwigFunction('relatedcontentbytags', 'relatedContentByTags');

        // Always make sure a default config.yml is available.
        if( !isset($this->config) ) {
            $this->config = array();
        }

        if ( !isset($this->config['limit']) ) {
            $this->config['limit'] = 10;
        }

        if ( !isset($this->config['points']) ) {
            $this->config['points'] = array();
        }

        if ( !isset($this->config['points']['tag']) ) {
            $this->config['points']['tag'] = 10;
        }

        if ( !isset($this->config['points']['type']) ) {
            $this->config['points']['type'] = 10;
        }

    }

    /**
     * @author Xiao-Hu Tai
     * @param \Bolt\Content $record   The record to search similar content for.
     * @param array         $options  Options for custom queries.
     *
     * @return array Returns an array with the elements sorted by similarity.
     */
    function relatedContentByTags($record, $options = array())
    {

        $startTime     = microtime(true);

        $app           = $this->app;
        $limit         = isset($options['limit'])  ? $options['limit']  : $this->config['limit'];
        $tablePrefix   = $app['config']->get('general/database/prefix', 'bolt_');
        $taxonomyTable = sprintf('%staxonomy', $tablePrefix);
        $contenttypes  = $app['config']->get('contenttypes');
        $filter        = isset($options['contenttypes']) ? $options['contenttypes'] : false;

        // if set, filter contenttypes
        if ($filter) {
            //\util::var_dump($filter);
            $filterContenttypes = array();
            foreach ($filter as $contenttypeName) {
                if (isset($contenttypes[$contenttypeName])) {
                    $filterContenttypes[$contenttypeName] = $contenttypes[$contenttypeName];
                }
            }
            if ($filterContenttypes) {
                $contenttypes = $filterContenttypes;
            }
        }

        // Get all taxonomies that behave like tags and their values from $record.
        $tagsValues     = array();
        $tagsTaxonomies = array();

        // If no taxonomies exist, then no matching items exist
        if (!isset( $record->contenttype['taxonomy'])) {
            return array();
        }
        foreach ( $record->contenttype['taxonomy'] as $key ) {
            if ($app['config']->get('taxonomy/'.$key.'/behaves_like') == 'tags') {
                // only useful if values exist, otherwise just skip this taxonomy
                if ($record->taxonomy[$key]) {
                    $tagsValues[$key] = array_values( $record->taxonomy[$key] );
                    $tagsTaxonomies[] = $key;
                }
            }
        }

        // Make the basic WHERE query for all behaves-like-tags values in $record.
        $queryWhere = array();
        foreach ( $tagsValues as $tagName => $values ) {
            $subqueryWhere = array();

            foreach( $values as $word ) {
                $subqueryWhere[] = sprintf('%s.slug = "%s"', $taxonomyTable, $word);
            }
            $temp  = sprintf( '%s.taxonomytype = "%s"', $taxonomyTable, $tagName );
            $temp .= sprintf( ' AND (%s)', implode(' OR ', $subqueryWhere) );
            $queryWhere[] = $temp;

        }
        $queryWhere = implode(' OR ', $queryWhere);

        // Get all contenttypes (database tables) that have a similar behaves-like-tags taxonomies like $record
        $tables = array();
        foreach ( $contenttypes as $key => $contenttype ) {
            foreach( $contenttype['taxonomy'] as $taxonomyKey ) {
                if (in_array( $taxonomyKey, $tagsTaxonomies )) {
                    $tables[] = $contenttype['slug'];
                    break;
                }
            }
        }

        // Fetch results for every proper contenttype
        $results = array();
        foreach ($tables as $name) {

            $table        = sprintf('%s%s', $tablePrefix, $name);
            $querySelect  = '';
            $querySelect .= sprintf('SELECT %s.id FROM %s', $table, $table);
            $querySelect .= sprintf(' LEFT JOIN %s', $taxonomyTable);
            $querySelect .= sprintf(' ON %s.id = %s.content_id', $table, $taxonomyTable);
            $querySelect .= sprintf(' WHERE %s.status = "published"', $table);
            if ($name == $record->contenttype['slug']) {
                $querySelect .= sprintf('AND %s.id != '. $record->id, $table);
            }
            $querySelect .= sprintf(' AND %s.contenttype = "%s"', $taxonomyTable, $name);
            $querySelect .= sprintf(' AND (%s)', $queryWhere);

            $queryResults = $app['db']->fetchAll( $querySelect );

            //\util::var_dump($querySelect); // print the query
            //\util::var_dump($queryResults);

            if (!empty($queryResults)) {
                $ids      = implode(' || ', \util::array_pluck($queryResults, 'id'));
                $contents = $app['storage']->getContent($name, array('id' => $ids, 'returnsingle' => false));
                $results  = array_merge( $results,  $contents );
            }
        }

        // Add similarities by tags and difference in publication dates.
        foreach ($results as $result) {
            $similarity = $this->calculateTaxonomySimilarity($record, $result, $tagsTaxonomies, $tagsValues, $options);
            $diff       = $this->calculatePublicationDiff($record, $result);

            $result->similarity = $similarity;
            $result->diff       = $diff;
        }

        // Sort results
        usort($results, array($this, 'compareSimilarity'));

        // Limit results
        $results = array_slice($results, 0, $limit);

        $totalTime = microtime(true) - $startTime;
        // \util::var_dump( sprintf('%.03f seconds', $totalTime) );

        return $results;

    }

    private function calculateTaxonomySimilarity($a, $b, $tagsTaxonomies, $tagsValues, $options = array())
    {

        $similarity = 0;
        $pointsTag  = isset($options['pointsTag'])  ? $options['pointsTag']  : $this->config['points']['tag'];
        $pointsType = isset($options['pointsType']) ? $options['pointsType'] : $this->config['points']['type'];

        // 1. more similar tags => higher score
        $taxonomies = $b->taxonomy;
        foreach ($taxonomies as $taxonomyKey => $values) {
            if( in_array($taxonomyKey, $tagsTaxonomies) ) {
                foreach ($values as $value) {
                    if (in_array($value, $tagsValues[$taxonomyKey])) {
                        $similarity += $pointsTag;
                    }
                }
            }
        }

        // 2. same contenttype => higher score
        //    e.g. a book and a book is more similar, than a book and a kitchensink
        if ($a->contenttype['slug'] == $b->contenttype['slug']) {
            $similarity += $pointsType;
        }

        return $similarity;

    }

    // smaller difference datepublish  => higher score
    // e.g. news article in the same period is more similar than other articles
    private function calculatePublicationDiff($a, $b)
    {

        $t1   = \DateTime::createFromFormat( 'Y-m-d H:i:s', $a->values['datepublish'] );
        $t2   = \DateTime::createFromFormat( 'Y-m-d H:i:s', $b->values['datepublish'] );
        $diff = abs( $t1->getTimestamp() - $t2->getTimestamp() ); // diff in seconds
        // $diff = $diff / (60 * 60 *24 ); // diff in days

        return $diff;

    }

    private function compareSimilarity($a, $b)
    {

        if ($a->similarity > $b->similarity) {
            return -1;
        }
        if ($a->similarity < $b->similarity) {
            return +1;
        }

        if ($a->diff < $b->diff) {
            // less difference is more important
            return -1;
        }
        if ($a->diff > $b->diff) { 
            // more difference is less important
            return +1;
        }

        return strcasecmp($a->values['title'], $b->values['title']);

    }

}
