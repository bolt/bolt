<?php

namespace Bolt\Search;

use Silex\Application;
use Silex\ServiceProviderInterface;

use ZendSearch\Lucene\Analysis\Analyzer\Common\Utf8\CaseInsensitive as AnalyzerUtf8CaseInsensitive;
use ZendSearch\Lucene\Analysis\Analyzer\Analyzer as Analyzer;
use ZendSearch\Lucene\Lucene as Search;
use ZendSearch\Lucene\Search\QueryParser as QueryParser;
use ZendSearch\Lucene\Search\Query\MultiTerm as MultiTerm;
use ZendSearch\Lucene\Index\Term as Term;
use ZendSearch\Lucene\Document as Document;
use ZendSearch\Lucene\Document\Field as Field;

/**
 * Description of SearchServiceProvider
 *
 * @author leon
 */
class SearchServiceProvider implements ServiceProviderInterface 
{
    public static $indexPath;
    
    public static $indexFieldTypes = array("text", "html");

    public function register(Application $app) 
    {
        // Content indexing on save
        $app["dispatcher"]->addListener(\Bolt\StorageEvents::postSave, function($event) use($app) 
        {
            $content = $event->getContent();
            $index = self::getIndex();
            // Remove content from index
            $query = new MultiTerm();
            $query->addTerm(new Term($content->id, 'pk'), true);
            $query->addTerm(new Term($content->contenttype["name"], 'type'), true);
            $hits = $index->find($query);
            foreach ($hits as $hit) {
                $index->delete($hit->id);
            }
            // Add content to index
            $doc = new Document();
            $doc->addField(Field::keyword("pk", $content->id));
            $doc->addField(Field::keyword("type", $content->contenttype["name"]));
            foreach (self::getSearchFields($content->contenttype["fields"]) as $key) {
                $doc->addField(Field::unStored($key, $content->values[$key]));
            }
            $index->addDocument($doc);
            $index->commit();
        });
        // Modify search query to use lucene
        $app["dispatcher"]->addListener(\Bolt\StorageEvents::preSearch, function ($event) use($app) 
        {
            $contenttype = $event->getContenttype();
            $queryBuilder = $event->getQueryBuilder();
            $filter = $event->getFilter();
            if(!$filter)
                return;
            $index = self::getIndex();
            $searchFields = self::getSearchFields($contenttype["fields"]);
            foreach ($searchFields as $key => $val) {
                $searchFields[$key] .= ":'{$filter}'";
            }
            $queryStr = sprintf("type:{$contenttype["name"]} AND (%s)", implode(" OR ", $searchFields));
            $query = QueryParser::parse($queryStr, "utf-8");
            $hits = $index->find($query);
            $ids = array();
            foreach ($hits as $hit) {
                $ids[] = $hit->getDocument()->pk;
            }
            if(count($ids)) {
                $queryBuilder->modifyQuery($ids);
            }
        });
        // Register search index resource
        $app["search"] = $app->share(function() use ($app) 
        {
            return self::getIndex();
        });
    }
    
    /**
     * Get content fields for indexing
     * 
     * @param array $fields
     * @return array
     */
    public static function getSearchFields($fields)
    {
        $result = array();
        foreach ($fields as $key => $val) {
            if(in_array($val["type"], self::$indexFieldTypes)) {
                $result[] = $key;
            }
        }
        return $result;
    }

    /**
     * 
     * @return \Zend_Search_Lucene_Interface search index
     */
    public static function getIndex()
    {
        $analyzer = new AnalyzerUtf8CaseInsensitive();
        Analyzer::setDefault($analyzer);
        try {
            $index = Search::open(self::$indexPath);
        } catch(\ZendSearch\Lucene\Exception\RuntimeException $e) {
            $index = Search::create(self::$indexPath);
        }
        return $index;
    }

    /**
     * Bootstraps the application. Required by interface
     */
    public function boot(Application $app) 
    {
        // Path to the search index files
        self::$indexPath = $app['search.root_dir'] . $app["config"]["general"]["search"]["index_path"];
    }
}

