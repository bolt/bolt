<?php

namespace Bolt;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Silex;
use Bolt;
use util;
use Doctrine\DBAL\Connection as DoctrineConn;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class Storage
{
    /**
     * @var Application
     */
    private $app;
    /**
     * @var string
     */
    private $prefix;
    /**
     * @var array
     */
    private $checkedfortimed = array();

    public function __construct(Bolt\Application $app)
    {
        $this->app = $app;

        $this->prefix = isset($this->app['config']['general']['database']['prefix']) ? $this->app['config']['general']['database']['prefix'] : "bolt_";

        // Make sure prefix ends in '_'. Prefixes without '_' are lame..
        if ($this->prefix[ strlen($this->prefix)-1 ] != "_") {
            $this->prefix .= "_";
        }

    }

    /**
     * @return Database\IntegrityChecker
     */
    public function getIntegrityChecker() {

        return new \Bolt\Database\IntegrityChecker( $this->app );

    }

    /**
     * Check if just the users table is present.
     *
     * @return boolean
     * @deprecated see \Bolt\Database\IntegrityChecker::checkUserTableIntegrity()
     */
    public function checkUserTableIntegrity()
    {
        return $this->getIntegrityChecker()->checkUserTableIntegrity();
    }

    /**
     * Check if all required tables and columns are present in the DB
     *
     * @return boolean
     * @deprecated see \Bolt\Database\IntegrityChecker::checkTablesIntegrity()
     */
    public function checkTablesIntegrity()
    {
        $messages = $this->getIntegrityChecker()->checkTablesIntegrity();

        if (empty($messages)) {
            return true;
        } else {
            return $messages;
        }
    }

    /**
     * @return array
     * @deprecated see \Bolt\Database\IntegrityChecker::repairTables()
     */
    public function repairTables()
    {
        return $this->getIntegrityChecker()->repairTables();
    }

    /**
     * Get an object for the content of a specific contenttype. This will be
     * \Bolt\Content, unless the contenttype defined another class to be used.
     *
     * @param array|string $contenttype
     * @param array $values
     * @throws \Exception
     * @return \Bolt\Content
     */
    public function getContentObject($contenttype, $values = array()) {

        // Make sure $contenttype is an array, and not just the slug.
        if (!is_array($contenttype)) {
            $contenttype = $this->getContentType($contenttype);
        }

        // If the contenttype has a 'class' specified, and the class exists,
        // Initialize the content as an object of that class.
        if (!empty($contenttype['class']) && class_exists($contenttype['class'])) {
            $content = new $contenttype['class']($this->app, $contenttype, $values);

            // Check if the class actually extends \Bolt\Content..
            if (!($content instanceof \Bolt\Content)) {
                throw new \Exception($contenttype['class'] . ' does not extend \\Bolt\\Content.');
            }

        } else {

            $content = new \Bolt\Content($this->app, $contenttype, $values);

        }

        return $content;

    }


    /**
     * Add some records with dummy content..
     *
     * Only fill the contenttypes passed as parameters
     * If the parameters is empty, only fill empty tables
     *
     * @see preFillSingle
     * @return string
     */
    public function preFill($contenttypes=array())
    {

        $this->guzzleclient = new \Guzzle\Service\Client('http://loripsum.net/api/');

        $output = "";

        // get a list of images..
        $this->images = findFiles('', 'jpg,jpeg,png');

        $empty_only = empty($contenttypes);

        foreach ($this->app['config']['contenttypes'] as $key => $contenttype) {

            $tablename = $this->getTablename($key);
            if ($empty_only && $this->hasRecords($tablename)) {
                $output .= __("Skipped <tt>%key%</tt> (already has records)",array('%key%' =>$key)) . "<br>\n";
                continue;
            } else if (!in_array($key,$contenttypes) && !$empty_only) {
                $output .= __("Skipped <tt>%key%</tt> (not checked)",array('%key%' =>$key)) . "<br>\n";
                continue;
            }

            $amount = isset($contenttype['prefill']) ? $contenttype['prefill'] : 5;

            for ($i=1; $i<= $amount; $i++) {
                $output .= $this->preFillSingle($key, $contenttype);
            }


        }


        $output .= "<br>\n\n" .__('Done!');

        return $output;

    }

    /**
     * Add a record with dummy content..
     *
     * @see preFill
     * @param $key
     * @param $contenttype
     * @return string
     */
    private function preFillSingle($key, $contenttype)
    {

        $content = array();
        $title = "";

        $content['contenttype'] = $key;
        $content['datecreated'] = date('Y-m-d H:i:s', time() - rand(0, 365*24*60*60));
        $content['datepublish'] = date('Y-m-d H:i:s', time() - rand(0, 365*24*60*60));

        $content['username'] = array_rand($this->app['users']->getUsers());

        switch (rand(1, 20)) {
            case 1:
                $content['status'] = "timed";
                break;
            case 2:
                $content['status'] = "draft";
                break;
            case 3:
                $content['status'] = "held";
                break;
            default:
                $content['status'] = "published";
                break;
        }

        foreach ($contenttype['fields'] as $field => $values) {

            switch ($values['type']) {
                case 'text':
                    $content[$field] = trim(strip_tags($this->guzzleclient->get('1/veryshort')->send()->getBody(true)));
                    if (empty($title)) {
                        $title = $content[$field];
                    }
                    break;
                case 'image':
                    // Get a random image..
                    if (!empty($this->images)) {
                        $content[$field] = $this->images[array_rand($this->images)];
                    }
                    break;
                case 'html':
                case 'textarea':
                case 'markdown':
                    if (in_array($field, array('teaser', 'introduction', 'excerpt', 'intro'))) {
                        $params = 'medium/decorate/link/1';
                    } else {
                        $params = 'medium/decorate/link/ol/ul/3';
                        //$params = 'long/1';
                    }
                    $content[$field] = trim($this->guzzleclient->get($params)->send()->getBody(true));

                    if ($values['type'] == "markdown") {
                        $content[$field] = strip_tags($content[$field]);
                    }
                    break;
                case 'datetime':
                    $content[$field] = date('Y-m-d H:i:s', time() - rand(-365*24*60*60, 365*24*60*60));
                    break;
                case 'date':
                    $content[$field] = date('Y-m-d', time() - rand(-365*24*60*60, 365*24*60*60));
                    break;
                case 'float':
                case 'number': // number is deprecated..
                case 'integer':
                    $content[$field] = rand(-1000,1000) + (rand(0,1000)/1000);
                    break;
            }

        }

        $contentobject = $this->getContentObject($contenttype);
        $contentobject->setValues($content);

        if (!empty($contenttype['taxonomy'])) {
            foreach ($contenttype['taxonomy'] as $taxonomy) {
                if (isset($this->app['config']['taxonomy'][$taxonomy]['options'])) {
                    $options = $this->app['config']['taxonomy'][$taxonomy]['options'];
                    $contentobject->setTaxonomy($taxonomy, $options[array_rand($options)]);
                }
                if ( isset($this->app['config']['taxonomy'][$taxonomy]['behaves_like']) &&
                    ($this->app['config']['taxonomy'][$taxonomy]['behaves_like'] == "tags") ) {
                    $contentobject->setTaxonomy($taxonomy, $this->getSomeRandomTags(5));
                }
            }
        }


        $this->saveContent($contentobject);

        $output = __("Added to <tt>%key%</tt> '%title%'",
            array('%key%'=>$key, '%title%'=>$contentobject->getTitle())) . "<br>\n";

        return $output;

    }

    private function getSomeRandomTags($num = 5)
    {

        $tags = array("action", "adult", "adventure", "alpha", "animals", "animation", "anime", "architecture", "art",
            "astronomy", "baby", "batshitinsane", "biography", "biology", "book", "books", "business", "business",
            "camera", "cars", "cats", "cinema", "classic", "comedy", "comics", "computers", "cookbook", "cooking",
            "crime", "culture", "dark", "design", "digital", "documentary", "dogs", "drama", "drugs", "education",
            "environment", "evolution", "family", "fantasy", "fashion", "fiction", "film", "fitness", "food",
            "football", "fun", "gaming", "gift", "health", "hip", "historical", "history", "horror", "humor",
            "illustration", "inspirational", "internet", "journalism", "kids", "language", "law", "literature", "love",
            "magic", "math", "media", "medicine", "military", "money", "movies", "mp3", "murder", "music", "mystery",
            "news", "nonfiction", "nsfw", "paranormal", "parody", "philosophy", "photography", "photos", "physics",
            "poetry", "politics", "post-apocalyptic", "privacy", "psychology", "radio", "relationships", "research",
            "rock", "romance", "rpg", "satire", "science", "sciencefiction", "scifi", "security", "self-help",
            "series", "software", "space", "spirituality", "sports", "story", "suspense", "technology", "teen",
            "television", "terrorism", "thriller", "travel", "tv", "uk", "urban", "us", "usa", "vampire", "video",
            "videogames", "war", "web", "women", "world", "writing", "wtf", "zombies");

        shuffle($tags);

        $picked = array_slice($tags, 0, $num);

        return $picked;
    }


    public function saveContent($content, $contenttype = "")
    {

        $contenttype = $content->contenttype;

        $fieldvalues = $content->values;

        if (empty($contenttype)) {
            echo "Contenttype is required.";

            return false;
        }

        if ($this->app['dispatcher']->hasListeners(StorageEvents::preSave)) {
            $event = new StorageEvent($content);
            $this->app['dispatcher']->dispatch(StorageEvents::preSave, $event);
        }

        if (!isset($fieldvalues['slug'])) {
            $fieldvalues['slug'] = ''; // Prevent 'slug may not be NULL'
        }

        // add the fields for this contenttype,
        foreach ($contenttype['fields'] as $key => $values) {

            // Set the slug, while we're at it..
            if ($values['type'] == "slug") {
                if (!empty($values['uses']) && empty($fieldvalues['slug'])) {
                    $uses = '';
                    foreach ($values['uses'] as $usesField) {
                        $uses .= $fieldvalues[$usesField] . ' ';
                    }
                    $fieldvalues['slug'] = makeSlug($uses);
                } else if (!empty($fieldvalues['slug'])) {
                    $fieldvalues['slug'] = makeSlug($fieldvalues['slug']);
                } else if (empty($fieldvalues['slug']) && $fieldvalues['id']) {
                    $fieldvalues['slug'] = $fieldvalues['id'];
                }
            }

            if ($values['type'] == "video") {
                if (!empty($fieldvalues[$key]['url'])) {
                    $fieldvalues[$key] = serialize($fieldvalues[$key]);
                } else {
                    $fieldvalues[$key] = "";
                }
            }

            if ($values['type'] == "geolocation") {
                if (!empty($fieldvalues[$key]['address'])) {
                    $fieldvalues[$key] = serialize($fieldvalues[$key]);
                } else {
                    $fieldvalues[$key] = "";
                }
            }

            if ($values['type'] == "imagelist") {

                if (!empty($fieldvalues[$key]) && strlen($fieldvalues[$key])<3) {
                    // Don't store '[]'
                    $fieldvalues[$key] = "";
                }
            }

            if ($values['type'] == "integer") {
                $fieldvalues[$key] = round($fieldvalues[$key]);
            }

            if ($values['type'] == "select" && is_array($fieldvalues[$key])) {
                $fieldvalues[$key] = serialize($fieldvalues[$key]);
            }

        }

        // Make sure a username is set.
        if (empty($fieldvalues['username'])) {
            $user = $this->app['session']->get('user');
            $fieldvalues['username'] = $user['username'];
        }

        // Clean up fields, check unneeded columns.
        foreach ($fieldvalues as $key => $value) {

            if ($this->isValidColumn($key, $contenttype)) {
                // Trim strings..
                if (is_string($fieldvalues[$key])) {
                    $fieldvalues[$key] = trim($fieldvalues[$key]);
                }
            } else {
                // unset columns we don't need to store..
                unset($fieldvalues[$key]);
            }

        }

        // Decide whether to insert a new record, or update an existing one.
        if (empty($fieldvalues['id'])) {
            $id = $this->insertContent($fieldvalues, $contenttype);
            $fieldvalues['id'] = $id;
            $content->setValue('id', $id);
        } else {
            $id = $fieldvalues['id'];
            $this->updateContent($fieldvalues, $contenttype);
        }

        if (empty($fieldvalues['slug'])) {
            $fieldvalues['slug'] = $id;
            $this->updateContent($fieldvalues, $contenttype);
        }

        $this->updateTaxonomy($contenttype, $id, $content->taxonomy);
        $this->updateRelation($contenttype, $id, $content->relation);

        if ($this->app['dispatcher']->hasListeners(StorageEvents::postSave)) {
            $event = new StorageEvent($content);
            $this->app['dispatcher']->dispatch(StorageEvents::postSave, $event);
        }

        return $id;

    }


    public function deleteContent($contenttype, $id)
    {

        if (empty($contenttype)) {
            echo "Contenttype is required.";

            return false;
        }

        if ($this->app['dispatcher']->hasListeners(StorageEvents::preDelete)) {
            $event = new StorageEvent(array( $contenttype, $id ));
            $this->app['dispatcher']->dispatch(StorageEvents::preDelete, $event);
        }

        // Make sure $contenttype is a 'slug'
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        $tablename = $this->getTablename($contenttype);

        $res = $this->app['db']->delete($tablename, array('id' => $id));

        // Make sure relations and taxonomies are deleted as well.
        if ($res) {
            $this->app['db']->delete($this->prefix."relations", array('from_contenttype' => $contenttype, 'from_id' => $id));
            $this->app['db']->delete($this->prefix."relations", array('to_contenttype' => $contenttype, 'to_id' => $id));
            $this->app['db']->delete($this->prefix."taxonomy", array('contenttype' => $contenttype, 'content_id' => $id));
        }

        if ($this->app['dispatcher']->hasListeners(StorageEvents::postDelete)) {
            $event = new StorageEvent(array( $contenttype, $id ));
            $this->app['dispatcher']->dispatch(StorageEvents::postDelete, $event);
        }

        return $res;

    }


    protected function insertContent($content, $contenttype, $taxonomy = "")
    {

        // Make sure $contenttype is a 'slug'
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        $tablename = $this->getTablename($contenttype);

        $content['datecreated'] = date('Y-m-d H:i:s');
        $content['datechanged'] = date('Y-m-d H:i:s');

        // id is set to autoincrement, so let the DB handle it
        unset($content['id']);

        $res = $this->app['db']->insert($tablename, $content);

        $seq = null;
        if ($this->app['db']->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $seq = $tablename.'_id_seq';
        }
        $id = $this->app['db']->lastInsertId($seq);

        return $id;

    }


    private function updateContent($content, $contenttype)
    {

        // Make sure $contenttype is a 'slug'
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        $tablename = $this->getTablename($contenttype);

        unset($content['datecreated']);
        $content['datechanged'] = date('Y-m-d H:i:s');

        $res = $this->app['db']->update($tablename, $content, array('id' => $content['id']));

        if ($res == true) {
            return true;
        } else {
            // Attempt to _insert_ it, instead of updating..
            return $this->app['db']->insert($tablename, $content);
        }

    }


    public function updateSingleValue($contenttype, $id, $field, $value)
    {

        $tablename = $this->getTablename($contenttype);

        $id = intval($id);

        if (!$this->isValidColumn($field, $contenttype)) {
            $error = __("Can't set %field% in %contenttype%: Not a valid field.", array('%field%' => $field, '%contenttype%' => $contenttype));
            $this->app['session']->getFlashBag()->set('error', $error);
            return false;
        }

        // @todo make sure we don't set datecreated
        // @todo update datechanged

        $query = sprintf("UPDATE %s SET $field = ? WHERE id = ?", $tablename);
        $stmt = $this->app['db']->prepare($query);
        $stmt->bindValue(1, $value);
        $stmt->bindValue(2, $id);
        $res = $stmt->execute();

        return $res;

    }

    public function getEmptyContent($contenttypeslug)
    {

        $content = $this->getContentObject($contenttypeslug);

        // don't use 'undefined contenttype' as title/name
        $content->setValues(array('name' => '', 'title' => ''));

        return $content;

    }

    public function searchAllContentTypes(array $parameters = array(), &$pager = array())
    {
        //return $this->searchContentTypes($this->getContentTypes(), $parameters, $pager);
        // Note: we can only apply this kind of results aggregating when we don't
        // use LIMIT and OFFSET! If we'd want to use it, this should be rewritten.
        // Results aggregator
        $result = array();
        foreach($this->getContentTypes() as $contenttype){
            $contentTypeSearchResults = $this->searchContentType($contenttype, $parameters, $pager);
            foreach($contentTypeSearchResults as $searchresult){
                $result []= $searchresult;
            }
        }
        return $result;
    }

    public function searchContentType($contenttypename, array $parameters = array(), &$pager = array())
    {
        $tablename = $this->getTablename($contenttypename);

        $contenttype = $this->app['config']['contenttypes'][$contenttypename];

        // for all the non-reserved parameters that are fields, we assume people want to do a 'where'
        foreach ($parameters as $key => $value) {
            if (in_array($key, array('order', 'where', 'limit', 'offset'))) {
                continue; // Skip this one..
            }
            if (!$this->isValidColumn($key, $contenttype)) {
                continue; // Also skip if 'key' isn't a field in the contenttype.
            }

            $where[] = $this->parseWhereParameter($key, $value);

        }

        // @todo update with nice search string
        // If we need to filter, add the WHERE for that.
        // Meh, InnoDB doesn't support full text search.
        if (!empty($parameters['filter'])) {

            $filter = safeString($parameters['filter']);

            $filter_where = array();

            foreach ($contenttype['fields'] as $key => $value) {
                if (in_array($value['type'], array('text', 'textarea', 'html'))) {
                    $filter_where[] = sprintf("%s LIKE '%%%s%%'", $key, $filter);
                }
            }

            if (!empty($filter_where)) {
                $where[] = "(" . implode(" OR ", $filter_where) . ")";
            }



        }

        // @todo This is preparation for stage 2..
        $limit = !empty($parameters['limit']) ? $parameters['limit'] : 100;
        $page = !empty($parameters['page']) ? $parameters['page'] : 1;

        // If we're allowed to use pagination, use the 'page' parameter.
        if (!empty($parameters['paging']) && $this->app->raw('request') instanceof Request) {
            $page = $this->app['request']->get('page', $page);
        }

        $queryparams = "";

        // implode 'where'
        if (!empty($where)) {
            $queryparams .= " WHERE (" . implode(" AND ", $where) . ")";
        }

        // Order, with a special case for 'RANDOM'.
        if (!empty($parameters['order'])) {
            if ($parameters['order'] == "RANDOM") {
                $dboptions = getDBOptions($this->app['config']);
                $queryparams .= " ORDER BY " . $dboptions['randomfunction'];
            } else {
                $order = safeString($parameters['order']);
                if ($order[0] == "-") {
                    $order = substr($order, 1) . " DESC";
                }
                $queryparams .= " ORDER BY " . $order;
            }
        }

        // Make the query for the pager..
        $pagerquery = "SELECT COUNT(*) AS count FROM $tablename" . $queryparams;

        // Add the limit
        $queryparams = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($queryparams, $limit, ($page-1)*$limit);

        // Make the query to get the results..
        $query = "SELECT * FROM $tablename" . $queryparams;

        $rows = $this->app['db']->fetchAll($query);

        // Make sure content is set, and all content has information about its contenttype
        $content = array();
        foreach ($rows as $row) {
            $content[ $row['id'] ] = $this->getContentObject($contenttype, $row);
        }

        // Make sure all content has their taxonomies and relations
        $this->getTaxonomy($content);
        $this->getRelation($content);

        // Set up the $pager array with relevant values..
        $rowcount = $this->app['db']->executeQuery($pagerquery)->fetch();
        $pager = array(
            'for' => 'search',
            'count' => $rowcount['count'],
            'totalpages' => ceil($rowcount['count'] / $limit),
            'current' => $page,
            'showing_from' => ($page-1)*$limit + 1,
            'showing_to' => ($page-1)*$limit + count($content)
        );

        //$GLOBALS['pager'][$contenttypeslug] = $pager;
        $GLOBALS['pager']['search'] = $pager;

        return $content;
    }

    public function searchContentTypes(array $contenttypenames, array $parameters = array(), &$pager = array())
    {
        // Set $parameters['filter'] with $terms.
        // Perhaps do something smart with $terms as well

        // @todo Parse $terms to an acceptable search string for the database.


        $tables = array();
        foreach ($contenttypenames as $contenttypename) {
            $contenttypetable = $this->getTablename($contenttypename);
            $tables [] = $contenttypetable;


            $contenttype = $this->app['config']['contenttypes'][$contenttypename];

            // for all the non-reserved parameters that are fields, we assume people want to do a 'where'
            foreach ($parameters as $key => $value) {
                if (in_array($key, array('order', 'where', 'limit', 'offset'))) {
                    continue; // Skip this one..
                }
                if (!in_array($key, $this->getContentTypeFields($contenttype['slug'])) &&
                    !in_array($key, array("id", "slug", "datecreated", "datechanged", "datepublish", "username", "status"))
                ) {
                    continue; // Also skip if 'key' isn't a field in the contenttype.
                }

                $where[] = $this->parseWhereParameter($key, $value);

            }

            // @todo update with nice search string
            // If we need to filter, add the WHERE for that.
            // Meh, InnoDB doesn't support full text search.
            if (!empty($parameters['filter'])) {

                $filter = safeString($parameters['filter']);

                $filter_where = array();

                foreach ($contenttype['fields'] as $key => $value) {
                    if (in_array($value['type'], array('text', 'textarea', 'html'))) {
                        $filter_where[] = sprintf("%s.%s LIKE '%%%s%%'", $contenttypetable, $key, $filter);
                    }
                }

                if (!empty($filter_where)) {
                    $where[] = "(" . implode(" OR ", $filter_where) . ")";
                }

            }
        }

        // @todo This is preparation for stage 2..
        $limit = !empty($parameters['limit']) ? $parameters['limit'] : 100;
        $page = !empty($parameters['page']) ? $parameters['page'] : 1;

        // If we're allowed to use pagination, use the 'page' parameter.
        if (!empty($parameters['paging']) && $this->app->raw('request') instanceof Request) {
            $page = $this->app['request']->get('page', $page);
        }

        $tablename = implode(", ", $tables);

        $queryparams = "";

        // implode 'where'
        if (!empty($where)) {
            $queryparams .= " WHERE (" . implode(" AND ", $where) . ")";
        }

        // Order, with a special case for 'RANDOM'.
        if (!empty($parameters['order'])) {
            if ($parameters['order'] == "RANDOM") {
                $dboptions = getDBOptions($this->app['config']);
                $queryparams .= " ORDER BY " . $dboptions['randomfunction'];
            } else {
                $order = safeString($parameters['order']);
                if ($order[0] == "-") {
                    $order = substr($order, 1) . " DESC";
                }
                $queryparams .= " ORDER BY " . $order;
            }
        }

        // Make the query for the pager..
        $pagerquery = "SELECT COUNT(*) AS count FROM $tablename" . $queryparams;

        // Add the limit
        $queryparams = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($queryparams, $limit, ($page-1)*$limit);

        // Make the query to get the results..
        $query = "SELECT * FROM $tablename" . $queryparams;

        $rows = $this->app['db']->fetchAll($query);

        // Make sure content is set, and all content has information about its contenttype
        $content = array();
        foreach ($rows as $row) {
            // @todo Make sure contenttype is set properly..
            $content[ $row['id'] ] = $this->getContentObject('', $row);
        }

        // Make sure all content has their taxonomies and relations
        $this->getTaxonomy($content);
        $this->getRelation($content);

        // Set up the $pager array with relevant values..
        $rowcount = $this->app['db']->executeQuery($pagerquery)->fetch();
        $pager = array(
            'for' => 'search',
            'count' => $rowcount['count'],
            'totalpages' => ceil($rowcount['count'] / $limit),
            'current' => $page,
            'showing_from' => ($page-1)*$limit + 1,
            'showing_to' => ($page-1)*$limit + count($content)
        );

        //$GLOBALS['pager'][$contenttypeslug] = $pager;
        $GLOBALS['pager']['search'] = $pager;

        return $content;

    }

    /**
     * Retrieve content from the database, filtered on taxonomy.
     */
    public function getContentByTaxonomy($taxonomyslug, $slug, $parameters = "")
    {

        $tablename = $this->getTablename("taxonomy");

        $limit = $parameters['limit'] ?: 100;
        $page = $parameters['page'] ?: 1;

        $taxonomytype = $this->getTaxonomyType($taxonomyslug);

        // No taxonomytype, no possible content..
        if (empty($taxonomytype)) {
            return false;
        }

        $where = " WHERE (taxonomytype=". $this->app['db']->quote($taxonomytype['slug']) . " AND slug=". $this->app['db']->quote($slug) .")";

        // Make the query for the pager..
        $pagerquery = "SELECT COUNT(*) AS count FROM $tablename" . $where;

        // Add the limit
        $query = "SELECT * FROM $tablename" . $where . " ORDER BY id DESC";
        $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, $limit, ($page-1)*$limit);

        $taxorows = $this->app['db']->fetchAll($query);

        $content = array();

        if (is_array($taxorows)) {
            foreach($taxorows as $row) {
                $record = $this->getContent($row['contenttype']."/".$row['content_id']);
                if ($record instanceof \Bolt\Content && !empty($record->id)) {
                    $content[] = $record;
                }
            }
        }

        // Set up the $pager array with relevant values..
        $rowcount = $this->app['db']->executeQuery($pagerquery)->fetch();
        $pager = array(
            'for' => $taxonomytype['slug'] . "/" . $slug,
            'count' => $rowcount['count'],
            'totalpages' => ceil($rowcount['count'] / $limit),
            'current' => $page,
            'showing_from' => ($page-1)*$limit + 1,
            'showing_to' => ($page-1)*$limit + count($taxorows)
        );
        $GLOBALS['pager'][ $taxonomytype['slug'] . "/" . $slug ] = $pager;

        return $content;

    }

    /**
     * Check (and update) any records that need to be updated from "timed" to "published".
     *
     * @param array $contenttype
     */
    public function publishTimedRecords($contenttype)
    {
        // We need to do this only once per contenttype, max.
        if (isset($this->checkedfortimed[$contenttype['slug']])) {
            return;
        }

        $this->checkedfortimed[$contenttype['slug']] = true;
        $tablename = $this->getTablename($contenttype['slug']);
        $now = date('Y-m-d H:i:s', time());

        try {

            // Check if there are any records that need publishing..
            $query = "SELECT id FROM $tablename WHERE status = 'timed' and datepublish < :now";
            $stmt = $this->app['db']->prepare($query);
            $stmt->bindValue("now", $now);
            $stmt->execute();

            // If there's a result, we need to set these to 'publish'..
            if ($stmt->fetch() != false) {
                $query = "UPDATE $tablename SET status = 'published', datechanged = :now, datepublish = :now  WHERE status = 'timed' and datepublish < :now";
                $stmt = $this->app['db']->prepare($query);
                $stmt->bindValue("now", $now);
                $stmt->execute();
            }

        } catch (\Doctrine\DBAL\DBALException $e) {

            // Oops. Couldn't execute the queries.

        }

    }


    /**
     * Retrieve content from the database.
     *
     * @param string $contenttypeslug
     * @param string $parameters
     * @param array $pager
     * @param array $whereparameters
     * @return array|Content|bool|mixed
     */
    public function getContent($contenttypeslug, $parameters = "", &$pager = array(), $whereparameters = array())
    {
        // $whereparameters is passed if called from a compiled template. If present, merge it with $parameters.
        if (!empty($whereparameters)) {
            $parameters = array_merge((array)$parameters, (array)$whereparameters);
        }

        $returnsingle = false;

        // Some special cases, like 'entry/1' or 'page/about' need to be caught before further processing.
        if (preg_match('#^/?([a-z0-9_-]+)/([0-9]+)$#i', $contenttypeslug, $match)) {
            // like 'entry/12' or '/page/12345'
            $contenttypeslug = $match[1];
            $parameters['id'] = $match[2];
            $returnsingle = true;
        } elseif (preg_match('#^/?([a-z0-9_-]+)/([a-z0-9_-]+)$#i', $contenttypeslug, $match)) {
            // like 'page/lorem-ipsum-dolor' or '/page/home'
            $contenttypeslug = $match[1];
            $parameters['slug'] = $match[2];
            $returnsingle = true;
        } elseif (preg_match('#^/?([a-z0-9_-]+)/(latest|first)/([0-9]+)$#i', $contenttypeslug, $match)) {
            // like 'page/latest/lorem-ipsum-dolor'
            $contenttypeslug = $match[1];
            $parameters['order'] = 'datepublish ' . ($match[2]=="latest" ? "DESC" : "ASC");
            $parameters['limit'] = $match[3];
        } elseif (preg_match('#^/?([a-z0-9_-]+)/random/([0-9]+)$#i', $contenttypeslug, $match)) {
            // like 'page/random/lorem-ipsum-dolor'
            $contenttypeslug = $match[1];
            $parameters['order'] = 'RANDOM';
            $parameters['limit'] = $match[2];
        }

        // When using from the frontend, we assume (by default) that we only want published items,
        // unless something else is specified explicitly
        if (isset($this->app['end']) && $this->app['end']=="frontend" && empty($parameters['status'])) {
            $parameters['status'] = "published";
        }


        $limit = !empty($parameters['limit']) ? $parameters['limit'] : 100;
        $page = !empty($parameters['page']) ? $parameters['page'] : 1;

        // If we're allowed to use pagination, use the 'page' parameter.
        if (!empty($parameters['paging']) && $this->app->raw('request') instanceof Request) {
            $page = $this->app['request']->get('page', $page);
        }

        $contenttype = $this->getContentType($contenttypeslug);

        // If we can't match to a valid contenttype, return (undefined) content;
        if (!$contenttype) {
            $emptycontent = $this->getContentObject($contenttypeslug);
            $this->app['log']->add("Storage: No valid contenttype '$contenttypeslug'");

            return $emptycontent;
        }

        // Check if we need to 'publish' any 'timed' records.
        $this->publishTimedRecords($contenttype);

        // If requesting something with a content-type slug in singular, return only the first item.
        // If requesting a record with a specific 'id', return only the first item.
        if ( ($contenttype['singular_slug'] == $contenttypeslug)
            || isset($parameters['returnsingle'])
            || (!empty($parameters['id']) && is_numeric($parameters['id']) ) ) {
            $returnsingle = true;
        }

        $tablename = $this->getTablename($contenttype['slug']);

        // If the table doesn't exist (yet), return false..
        if (!$this->tableExists($tablename)) {
            return false;
        }

        // Set the 'FROM' part of the query, without the LEFT JOIN (i.e. no taxonomies..)
        $from = sprintf("FROM %s AS r", $tablename);

        // for all the non-reserved parameters that are fields or taxonomies, we assume people want to do a 'where'
        foreach ($parameters as $key => $value) {

            // Skip these..
            if (in_array($key, array('order', 'where', 'limit', 'offset'))) {
                continue;
            }

            // for all the parameters that are fields
            if (in_array($key, $this->getContentTypeFields($contenttype['slug'])) ||
                in_array($key, array("id", "slug", "datecreated", "datechanged", "datepublish", "username", "status")) ) {
                $rkey = "r." . $key;
                $where[] = $this->parseWhereParameter($rkey, $value);
            }


            // for all the  parameters that are taxonomies
            if (array_key_exists($key, $this->getContentTypeTaxonomy($contenttype['slug'])) ) {
                // Set the new 'from', with LEFT JOIN for taxonomies..
                $from = sprintf("FROM %s AS r LEFT JOIN %s AS t ON %s.%s = %s.%s",
                    $this->getTablename($contenttype['slug']),
                    $this->getTablename('taxonomy'),
                    $this->app['db']->quoteIdentifier('r'),
                    $this->app['db']->quoteIdentifier('id'),
                    $this->app['db']->quoteIdentifier('t'),
                    $this->app['db']->quoteIdentifier('content_id'));
                $where[] = $this->parseWhereParameter("t.taxonomytype", $key);
                $where[] = $this->parseWhereParameter("t.slug", $value);
                $where[] = $this->parseWhereParameter("t.contenttype", $contenttype['slug']);
            }

        }

        // If we need to filter, add the WHERE for that. InnoDB doesn't support full text search. WTF is up
        // with that shit? This feature is currently only used when filtering items in the backend.
        if (!empty($parameters['filter'])) {

            $filter = safeString($parameters['filter']);

            $filter_where = array();

            foreach ($contenttype['fields'] as $key => $value) {
                if (in_array($value['type'], array('text', 'textarea', 'html'))) {
                    $filter_where[] = sprintf("%s LIKE '%%%s%%'", $key, $filter);
                }
            }

            if (!empty($filter_where)) {
                $where[] = "(" . implode(" OR ", $filter_where) . ")";
            }

        }

        $queryparams = "";

        // implode 'where'
        if (!empty($where)) {
            $queryparams .= " WHERE (" . implode(" AND ", $where) . ")";
        }

        // Make the query for the pager..
        $pagerquery = "SELECT COUNT(*) AS count $from $queryparams";

        // Order, with a special case for 'RANDOM'.
        $queryparams .= $this->queryParamOrder($parameters, $contenttype);

        // Add the limit
        $queryparams = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($queryparams, $limit, ($page-1)*$limit);

        // Make the query to get the results..
        $query = sprintf("SELECT %s.* %s %s", $this->app['db']->quoteIdentifier('r'), $from, $queryparams);

        // Print the query, if the parameter is present.
        if (!empty($parameters['printquery'])) {
            echo nl2br(htmlentities($query));
        }

        // Fetch the results.
        // TODO: Convert this to a loop, to fetch the rows.
        $rows = $this->app['db']->fetchAll($query);

        // Make sure content is set, and all content has information about its contenttype
        $content = array();
        foreach ($rows as $row) {
            $content[ $row['id'] ] = $this->getContentObject($contenttype, $row);
        }

        // Make sure all content has their taxonomies and relations
        $this->getTaxonomy($content);
        $this->getRelation($content);

        // Iterate over the contenttype's taxonomy, check if there's one we can use for grouping.
        // But only if we're not sorting manually (i.e. have a ?order=.. parameter or $parameter['order'] )
        $order = null;
        if ($this->app->raw('request') instanceof Request) {
            $order = $this->app['request']->query->get('page', isset($parameters['order'])?$parameters['order']:null);
        }
        if (empty($order)) {
            if ($this->getContentTypeGrouping($contenttypeslug)) {
                uasort($content, array($this, 'groupingSort'));
            }
        }

        if (!$returnsingle) {
            // Set up the $pager array with relevant values..
            $rowcount = $this->app['db']->executeQuery($pagerquery)->fetch();
            $pager = array(
                'for' => $contenttypeslug,
                'count' => $rowcount['count'],
                'totalpages' => ceil($rowcount['count'] / $limit),
                'current' => $page,
                'showing_from' => ($page-1)*$limit + 1,
                'showing_to' => ($page-1)*$limit + count($content)
            );
            $GLOBALS['pager'][$contenttypeslug] = $pager;
            $this->app['twig']->addGlobal('pager', $pager);
        }

        // If we requested a singular item..
        if ($returnsingle) {
            if (util::array_first_key($content)) {
                return util::array_first($content);
            } else {
                $msg = sprintf(
                    "Storage: requested specific single content '%s%s%s', not found.",
                    $contenttypeslug,
                    isset($match[2]) ? "/".$match[2] : "",
                    isset($match[3]) ? "/".$match[3] : ""
                );
                $this->app['log']->add($msg);

                return false;
            }
        } else {
            return $content;
        }
    }

    /**
     * Check if a given name is a valid column, and if it can be used in queries.
     *
     * @param string $name
     * @param array $contenttype
     * @param bool $allowVariants
     * @return bool
     */
    private function isValidColumn($name, $contenttype, $allowVariants = false) {

        // Strip the minus in '-title' if allowed..
        if ($allowVariants) {
            if ($name[0] == "-") {
                $name = substr($name, 1);
            }
            $name = $this->getFieldName($name);
        }

        // Check if the $name is in the contenttype's fields.
        if(isset($contenttype['fields'][$name])) {
            return true;
        }

        if (in_array($name, array("id", "slug", "datecreated", "datechanged", "datepublish", "username", "status"))) {
            return true;
        }


        return false;

    }

    /**
     * Get field name, stripping possible " DESC" " ASC" etc.
     *
     * @param string $name
     * @return string
     */
    private function getFieldName($name) {
        return preg_replace("/ (desc|asc)$/i", "", $name);
    }

    /**
     * Get an escaped sortorder for use in SQL, from a fieldname like 'title' or '-id'.
     *
     * for example, -id returns `r`.`id` DESC
     *
     * @param string $name
     * @return string
     */
    private function getEscapedSortorder($name, $prefix='r') {

        list ($name, $asc) = $this->getSortOrder($name);

        $order = $this->app['db']->quoteIdentifier($prefix . '.' . $name);

        if (!$asc) {
            $order .= " DESC";
        }

        return $order;

    }

    /**
     * Get sorting order of name, stripping possible " DESC" " ASC" etc., and
     * also return the sorting order
     *
     * @param string $name
     * @return string
     */
    private function getSortOrder($name) {

        $parts = explode(' ', $name);
        $fieldname = $parts[0];
        $sort = 'ASC';
        if (isset($parts[1])) {
            $sort = $parts[1];
        }

        if ($fieldname[0] == "-") {
            $fieldname = substr($fieldname, 1);
            $sort = 'DESC';
        }

        return array($fieldname, (strtoupper($sort) == 'ASC'));
    }



    /**
     * Get the parameter for the 'order by' part of a query.
     *
     * @param array $parameters
     * @param array $contenttype
     * @return string
     */
    private function queryParamOrder($parameters, $contenttype) {

        if (empty($parameters['order'])) {
            if ($this->isValidColumn($contenttype['sort'], $contenttype, true)) {
                $order = $this->getEscapedSortorder($contenttype['sort']);
            }
        } else {
            $parameters['order'] = safeString($parameters['order']);
            if ($parameters['order'] == "RANDOM") {
                $dboptions = getDBOptions($this->app['config']);
                $order = $dboptions['randomfunction'];
            } elseif ($this->isValidColumn($parameters['order'], $contenttype, true)) {
                $order = $this->getEscapedSortorder($parameters['order']);
            }
        }

        if (!empty($order)) {
            $param = " ORDER BY " . $order;
        } else {
            $param = sprintf(" ORDER BY %s.datepublish DESC", $this->app['db']->quoteIdentifier('r'));
        }

        return $param;

    }

    /**
     * Helper function for sorting Records of content that have a Grouping.
     *
     * @param object $a
     * @param object $b
     * @return int
     */
    private function groupingSort($a, $b)
    {
        // Same group, sort within group..
        if ($a->group == $b->group) {

            if (!empty($a->sortorder) || !empty($b->sortorder)) {
                if (empty($a->sortorder) ) {
                    return -1;
                } else if (empty($b->sortorder)) {
                    return 1;
                } else {
                    return ($a->sortorder < $b->sortorder) ? -1 : 1;
                }
            }

            // Same group, so we sort on contenttype['sort']
            list($second_sort, $order) = $this->getSortOrder( $a->contenttype['sort'] );

            $vala = strtolower($a->values[$second_sort]);
            $valb = strtolower($b->values[$second_sort]);

            if ($vala == $valb) {
                return 0;
            } else {
                $result = ($vala < $valb) ? -1 : 1;
                // if $order is false, the 'getSortOrder' indicated that we used something like '-id'.
                // So, perhaps we need to inverse the result.
                return $order ? $result : -$result;
            }
        }
        // Otherwise, sort based on the group. Or, more specifically, on the index of
        // the item in the group's taxonomy definition.
        return ($a->group['index'] < $b->group['index']) ? -1 : 1;
    }

    /**
     * Helper function to set the proper 'where' parameter,
     * when getting values like '<2012' or '!bob'
     */
    private function parseWhereParameter($key, $value)
    {

        $value = trim($value);

        // check if we need to split..
        if (strpos($value, " || ") !== false) {
            list($value1, $value2) = explode(" || ", $value);
            $param1 = $this->parseWhereParameter($key, $value1);
            $param2 = $this->parseWhereParameter($key, $value2);

            return sprintf("( %s OR %s )", $param1, $param2);
        } elseif (strpos($value, " && ") !== false) {
            list($value1, $value2) = explode(" && ", $value);
            $param1 = $this->parseWhereParameter($key, $value1);
            $param2 = $this->parseWhereParameter($key, $value2);

            return sprintf("( %s AND %s )", $param1, $param2);
        }

        // Set the correct operator for the where clause
        $operator = "=";

        if ($value[0] == "!") {
            $operator = "!=";
            $value = substr($value, 1);
        } elseif (substr($value, 0, 2) == "<=") {
            $operator = "<=";
            $value = substr($value, 2);
        } elseif (substr($value, 0, 2) == ">=") {
            $operator = ">=";
            $value = substr($value, 2);
        } elseif ($value[0] == "<") {
            $operator = "<";
            $value = substr($value, 1);
        } elseif ($value[0] == ">") {
            $operator = ">";
            $value = substr($value, 1);
        } elseif ($value[0] == "%" || $value[strlen($value)-1] == "%" ) {
            $operator = "LIKE";
        }

        // special cases, for 'NOW', 'TODAY', 'YESTERDAY', 'TOMORROW'
        if ($value == "NOW") {
            $value = date('Y-m-d H:i:s');
        }
        if ($value == "TODAY") {
            $value = date('Y-m-d 00:00:00');
        }
        if ($value == "YESTERDAY") {
            $value = date('Y-m-d 00:00:00', strtotime('yesterday'));
        }
        if ($value == "TOMORROW") {
            $value = date('Y-m-d 00:00:00', strtotime('tomorrow'));
        }

        $parameter = sprintf("%s %s %s", $this->app['db']->quoteIdentifier($key), $operator, $this->app['db']->quote($value));

        return $parameter;

    }

    /**
     * Deprecated: use getContent.
     */
    public function getSingleContent($contenttypeslug, $parameters = array())
    {

        return $this->getContent($contenttypeslug, $parameters);

    }



    public function getContentType($contenttypeslug)
    {

        $contenttypeslug = makeSlug($contenttypeslug);

        // Return false if empty, can't find it..
        if (empty($contenttypeslug)) {
            return false;
        }

        // See if we've either given the correct contenttype, or try to find it by name or singular_name.
        if (isset($this->app['config']['contenttypes'][$contenttypeslug])) {
            $contenttype = $this->app['config']['contenttypes'][$contenttypeslug];
        } else {
            foreach ($this->app['config']['contenttypes'] as $key => $ct) {
                if (isset($ct['singular_slug']) && ($contenttypeslug == $ct['singular_slug'])) {
                    $contenttype = $this->app['config']['contenttypes'][$key];
                    break;
                }
                if ($contenttypeslug == makeSlug($ct['singular_name']) || $contenttypeslug == makeSlug($ct['name'])) {
                    $contenttype = $this->app['config']['contenttypes'][$key];
                    break;
                }
            }
        }

        if (!empty($contenttype)) {
            return $contenttype;
        } else {
            return false;
        }

    }



    public function getTaxonomyType($taxonomyslug)
    {

        $taxonomyslug = makeSlug($taxonomyslug);

        // Return false if empty, can't find it..
        if (empty($taxonomyslug)) {
            return false;
        }

        // See if we've either given the correct contenttype, or try to find it by name or singular_name.
        if (isset($this->app['config']['taxonomy'][$taxonomyslug])) {
            $taxonomytype = $this->app['config']['taxonomy'][$taxonomyslug];
        } else {
            foreach ($this->app['config']['taxonomy'] as $key => $tt) {
                if (isset($tt['singular_slug']) && ($taxonomyslug == $tt['singular_slug'])) {
                    $taxonomytype = $this->app['config']['taxonomy'][$key];
                    break;
                }
            }
        }

        if (!empty($taxonomytype)) {
            return $taxonomytype;
        } else {
            return false;
        }

    }



    /**
     * Get an array of the available contenttypes
     *
     * @return array $contenttypes
     */
    public function getContentTypes()
    {
        return array_keys($this->app['config']['contenttypes']);

    }



    /**
     * Get a value to use in 'assert() with the available contenttypes
     *
     * @return string $contenttypes
     */
    public function getContentTypeAssert($includesingular = false)
    {

        $slugs = array();
        foreach ($this->app['config']['contenttypes'] as $type) {
            $slugs[] = $type['slug'];
            if ($includesingular) {
                $slugs[] = $type['singular_slug'];
            }
        }

        return implode("|", $slugs);

    }


    /**
     * Get a value to use in 'assert() with the available taxonomytypes
     *
     * @return string $taxonomytypes
     */
    public function getTaxonomyTypeAssert($includesingular = false)
    {

        $slugs = array();
        foreach ($this->app['config']['taxonomy'] as $type) {
            $slugs[] = $type['slug'];
            if ($includesingular) {
                $slugs[] = $type['singular_slug'];
            }
        }

        return implode("|", $slugs);

    }

    /**
     * Get an array of the available fields for a given contenttype
     *
     * @param  string $contenttypeslug
     * @return array  $fields
     */
    public function getContentTypeFields($contenttypeslug)
    {

        $contenttype = $this->getContentType($contenttypeslug);

        if (empty($contenttype['fields'])) {
            return array();
        } else {
            return array_keys($contenttype['fields']);
        }

    }


    /**
     * Check if a given contenttype has a grouping, and if it does, return it.
     *
     * @param  string $contenttypeslug
     * @return mixed  $grouping
     */
    public function getContentTypeGrouping($contenttypeslug)
    {

        $grouping = false;
        $taxonomy = $this->getContentTypeTaxonomy($contenttypeslug);
        foreach ($taxonomy as $taxokey => $taxo) {
            if ($taxo['behaves_like']=="grouping") {
                $grouping = $taxo['slug'];
                break;
            }
        }

        return $grouping;

    }

    /**
     * Get an array of the available taxonomytypes for a given contenttype
     *
     * @param  string $contenttypeslug
     * @return array  $taxonomy
     */
    public function getContentTypeTaxonomy($contenttypeslug)
    {

        $contenttype = $this->getContentType($contenttypeslug);

        if (empty($contenttype['taxonomy'])) {
            return array();
        } else {
            $taxokeys = $contenttype['taxonomy'];

            $taxonomy = array();

            foreach ($taxokeys as $key) {
                if (isset($this->app['config']['taxonomy'][$key])) {
                    $taxonomy[$key] = $this->app['config']['taxonomy'][$key];
                }
            }

            return $taxonomy;
        }

    }

    /**
     * Get the taxonomy for one or more units of content, return the array with the taxonomy attached.
     *
     * @param array $content
     *
     * @return array $content
     */
    protected function getTaxonomy($content)
    {

        $tablename = $this->getTablename("taxonomy");

        $ids = util::array_pluck($content, 'id');

        if (empty($ids)) {
            return;
        }

        // Get the contenttype from first $content
        $contenttype = $content[ util::array_first_key($content) ]->contenttype['slug'];

        $taxonomytypes = array_keys($this->app['config']['taxonomy']);

        $query = sprintf(
            "SELECT * FROM %s WHERE content_id IN (?) AND contenttype=? AND taxonomytype IN (?)",
            $tablename
        );
        $rows = $this->app['db']->executeQuery(
            $query,
            array($ids, $contenttype, $taxonomytypes),
            array(DoctrineConn::PARAM_INT_ARRAY, \PDO::PARAM_STR, DoctrineConn::PARAM_STR_ARRAY)
        )->fetchAll();

        foreach ($rows as $key => $row) {
            $content[ $row['content_id'] ]->setTaxonomy($row['taxonomytype'], $row['slug'], $row['sortorder']);
        }

        foreach($content as $key => $value) {
            $content[$key]->sortTaxonomy();
        }

    }

    /**
     * Update / insert taxonomy for a given content-unit.
     *
     * @param string  $contenttype
     * @param integer $content_id
     * @param array   $taxonomy
     */
    protected function updateTaxonomy($contenttype, $content_id, $taxonomy)
    {

        $tablename = $this->getTablename("taxonomy");

        // Make sure $contenttype is a 'slug'
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        if (empty($taxonomy)) {
            // nothing to do here..
            return;
        }

        foreach ($taxonomy as $taxonomytype => $newvalues) {

            // Get the current values from the DB..
            $query = sprintf(
                "SELECT id, slug, sortorder FROM %s WHERE content_id=? AND contenttype=? AND taxonomytype=?",
                $tablename
            );
            $currentvalues = $this->app['db']->executeQuery(
                $query,
                array($content_id, $contenttype, $taxonomytype),
                array(\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR)
            )->fetchAll();

            if (!empty($currentvalues)) {
                $currentsortorder = $currentvalues[0]['sortorder'];
                $currentvalues = makeValuePairs($currentvalues, 'id', 'slug');
            } else {
                $currentsortorder = 'id';
                $currentvalues = array();
            }

            // Add the ones not yet present..
            foreach ($newvalues as $value) {

                // If it's like 'desktop#10', split it into value and sortorder..
                list($value, $sortorder) = explode('#', $value."#");

                if (empty($sortorder)) {
                    $sortorder = 0;
                }

                if ( (!in_array($value, $currentvalues) || ($currentsortorder != $sortorder) ) && (!empty($value))) {
                    // Insert it!
                    $row = array(
                        'content_id' => $content_id,
                        'contenttype' => $contenttype,
                        'taxonomytype' => $taxonomytype,
                        'slug' => $value,
                        'sortorder' => $sortorder
                    );
                    $this->app['db']->insert($tablename, $row);
                }

            }

            // Delete the ones that have been removed.
            foreach ($currentvalues as $id => $value) {

                // Make it look like 'desktop#10'
                $valuewithorder = $value . "#" . $currentsortorder;

                if (!in_array($value, $newvalues) && !in_array($valuewithorder, $newvalues)) {
                    $this->app['db']->delete($tablename, array('id' => $id));
                }
            }

        }

    }


    /**
     * Get the relations for one or more units of content, return the array with the taxonomy attached.
     *
     * @param array $content
     *
     * @return array $content
     */
    protected function getRelation($content)
    {

        $tablename = $this->getTablename("relations");

        $ids = util::array_pluck($content, 'id');

        if (empty($ids)) {
            return;
        }

        // Get the contenttype from first $content
        $contenttype = $content[ util::array_first_key($content) ]->contenttype['slug'];

        $query = sprintf(
            "SELECT * FROM %s WHERE from_contenttype=? AND from_id IN (?) ORDER BY id",
            $tablename
        );
        $params = array($contenttype, $ids);
        $paramTypes = array(\PDO::PARAM_STR, DoctrineConn::PARAM_INT_ARRAY);
        $rows = $this->app['db']->executeQuery($query, $params, $paramTypes)->fetchAll();

        foreach ($rows as $row) {
            $content[ $row['from_id'] ]->setRelation($row['to_contenttype'], $row['to_id']);
        }

        // switch it, flip it and reverse it. wop wop wop.
        $query = sprintf(
            "SELECT * FROM %s WHERE to_contenttype=? AND to_id IN (?) ORDER BY id",
            $tablename
        );
        $params = array($contenttype, $ids);
        $paramTypes = array(\PDO::PARAM_STR, DoctrineConn::PARAM_INT_ARRAY);
        $rows = $this->app['db']->executeQuery($query, $params, $paramTypes)->fetchAll();

        foreach ($rows as $row) {
            $content[ $row['to_id'] ]->setRelation($row['from_contenttype'], $row['from_id']);
        }

    }

    /**
     * Update / insert relation for a given content-unit.
     *
     * $relation looks like:
     * arr(2)
     * [
     *   "pages"        => arr(1)
     *   [
     *      0 => str(2) "22"
     *   ]
     *   "kitchensinks" => arr(3)
     *   [
     *      0 => str(2) "15"
     *      1 => str(1) "9"
     *      2 => str(2) "13"
     *   ]
     * ]
     *
     * $currentvalues looks like
     * arr(2)
     * [
     *   0 => arr(3)
     *   [
     *     "id"             => str(1) "5"
     *     "to_contenttype" => str(12) "kitchensinks"
     *     "to_id"          => str(2) "15"
     *   ]
     *   1 => arr(3)
     *   [
     *     "id"             => str(1) "6"
     *     "to_contenttype" => str(12) "kitchensinks"
     *     "to_id"          => str(1) "9"
     *   ]
     * ]
     *
     *
     * @param string  $contenttype
     * @param integer $content_id
     * @param array   $relation
     */
    protected function updateRelation($contenttype, $content_id, $relation)
    {

        $tablename = $this->getTablename("relations");

        // Make sure $contenttype is a 'slug'
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        // Get the current values from the DB..
        $query = sprintf(
            "SELECT id, to_contenttype, to_id FROM %s WHERE from_id=? AND from_contenttype=?",
            $tablename
        );
        $currentvalues = $this->app['db']->executeQuery(
            $query,
            array($content_id, $contenttype),
            array(\PDO::PARAM_INT, \PDO::PARAM_STR)
        )->fetchAll();

        // Delete the ones that have been removed.
        foreach ($currentvalues as $currentvalue) {

            if (!isset($relation[ $currentvalue['to_contenttype'] ]) ||
                !in_array($currentvalue['to_id'], $relation[ $currentvalue['to_contenttype'] ])) {
                $this->app['db']->delete($tablename, array('id' => $currentvalue['id']));
            }
        }

        // Make an easier array out of $currentvalues.
        $tempvalues = $currentvalues;
        $currentvalues = array();
        foreach($tempvalues as $tempvalue) {
            $currentvalues[] = $tempvalue['to_contenttype'] ."/" . $tempvalue['to_id'];
        }

        // Add the ones not yet present..
        if (!empty($relation)) {
            foreach ($relation as $to_contenttype => $newvalues) {

                foreach ($newvalues as $value) {

                    if (!in_array($to_contenttype."/".$value, $currentvalues) && (!empty($value))) {
                        // Insert it!
                        $row = array(
                            'from_contenttype' => $contenttype,
                            'from_id' => $content_id,
                            'to_contenttype' => $to_contenttype,
                            'to_id' => $value
                        );
                        $this->app['db']->insert($tablename, $row);
                    }

                }

            }
        }

    }


    public function getUri($title, $id = 0, $contenttypeslug = "", $fulluri = true)
    {

        $contenttype = $this->getContentType($contenttypeslug);
        $tablename = $this->getTablename($contenttype['slug']);

        $id = intval($id);
        $fulluri = util::str_to_bool($fulluri);

        $slug = makeSlug($title);

        // don't allow strictly numeric slugs.
        if (is_numeric($slug)) {
            $slug = $contenttype['singular_slug'] . "-" . $slug;
        }

        // Only add 'entry/' if $full is requested.
        if ($fulluri) {
            $prefix = "/" . $contenttype['singular_slug'] . "/";
        } else {
            $prefix = "";
        }

        $query = sprintf(
            "SELECT id from %s WHERE slug=? and id!=?",
            $tablename
        );
        $res = $this->app['db']->executeQuery(
            $query,
            array($slug, $id),
            array(\PDO::PARAM_STR, \PDO::PARAM_INT)
        )->fetch();

        if (!$res) {
            $uri = $prefix . $slug;
        } else {
            for ($i = 1; $i <= 10; $i++) {
                $newslug = $slug.'-'.$i;
                $res = $this->app['db']->executeQuery(
                    $query,
                    array($newslug, $id),
                    array(\PDO::PARAM_STR, \PDO::PARAM_INT)
                )->fetch();
                if (!$res) {
                    $uri = $prefix . $newslug;
                    break;
                }
            }

            // otherwise, just get a random slug.
            if (empty($uri)) {
                $slug = trimText($slug, 32, false, false) . "-" . makeKey(6);
                $uri = $prefix . $slug;
            }
        }

        return $uri;

    }

    /**
     * Get an associative array with the bolt_tables tables and columns in the DB.
     *
     * @return array
     */
    protected function getTables()
    {
        // Only do this once..
        if (!empty($this->tables)) {
            return $this->tables;
        }

        $sm = $this->app['db']->getSchemaManager();

        $this->tables = array();

        foreach ($sm->listTables() as $table) {
            if ( strpos($table->getName(), $this->prefix) === 0 ) {
                foreach ($table->getColumns() as $column) {
                    $this->tables[ $table->getName() ][ $column->getName() ] = $column->getType();
                }
                // $output[] = "Found table <tt>" . $table->getName() . "</tt>.";
            }
        }

        return $this->tables;

    }

    /**
     * Check if the table $name exists.
     *
     * @param $name
     * @return bool
     */
    protected function tableExists($name)
    {

        $tables = $this->getTables();

        return (!empty($tables[$name]));

    }

    /**
     * Get the tablename with prefix from a given $name
     *
     * @param $name
     * @return mixed
     */
    protected function getTablename($name)
    {

        $name = str_replace("-", "_", makeSlug($name));
        $tablename = sprintf("%s%s", $this->prefix, $name);
        return $tablename;

    }


    /**
     * Get an associative array with the bolt_tables tables as Doctrine\DBAL\Schema\Table objects
     *
     * @return array
     */
    protected function getTableObjects()
    {

        $sm = $this->app['db']->getSchemaManager();

        $tables = array();

        foreach ($sm->listTables() as $table) {
            if ( strpos($table->getName(), $this->prefix) === 0 ) {
                $tables[ $table->getName() ] = $table;
                // $output[] = "Found table <tt>" . $table->getName() . "</tt>.";
            }
        }

        return $tables;

    }

    protected function hasRecords($tablename)
    {

        $count = $this->app['db']->fetchColumn('SELECT COUNT(id) FROM ' . $tablename);
        return intval($count) > 0;

    }

}
