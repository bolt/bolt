<?php

namespace Bolt;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Bolt;
use Bolt\Content;
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
    private $prefix = "bolt_";

    /**
     * @var array
     */
    private $checkedfortimed = array();

    public function __construct(Bolt\Application $app)
    {
        $this->app = $app;

        $this->prefix = $app['config']->get('general/database/prefix', "bolt_");

        // Make sure prefix ends in '_'. Prefixes without '_' are lame..
        if ($this->prefix[strlen($this->prefix) - 1] != "_") {
            $this->prefix .= "_";
        }

    }

    /**
     * Get an object for the content of a specific contenttype. This will be
     * \Bolt\Content, unless the contenttype defined another class to be used.
     *
     * @param  array|string $contenttype
     * @param  array $values
     * @throws \Exception
     * @return \Bolt\Content
     */
    public function getContentObject($contenttype, $values = array())
    {
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
     * @param array $contenttypes
     * @return string
     */
    public function preFill($contenttypes = array())
    {

        $this->guzzleclient = new \Guzzle\Service\Client('http://loripsum.net/api/');

        $output = "";

        // get a list of images..
        $this->images = findFiles('', 'jpg,jpeg,png');

        $empty_only = empty($contenttypes);

        foreach ($this->app['config']->get('contenttypes') as $key => $contenttype) {

            $tablename = $this->getTablename($key);
            if ($empty_only && $this->hasRecords($tablename)) {
                $output .= __("Skipped <tt>%key%</tt> (already has records)", array('%key%' => $key)) . "<br>\n";
                continue;
            } elseif (!in_array($key, $contenttypes) && !$empty_only) {
                $output .= __("Skipped <tt>%key%</tt> (not checked)", array('%key%' => $key)) . "<br>\n";
                continue;
            }

            $amount = isset($contenttype['prefill']) ? $contenttype['prefill'] : 5;

            for ($i = 1; $i <= $amount; $i++) {
                $output .= $this->preFillSingle($key, $contenttype);
            }

        }

        $output .= "<br>\n\n" . __('Done!');

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
        $content['datecreated'] = date('Y-m-d H:i:s', time() - rand(0, 365 * 24 * 60 * 60));
        $content['datepublish'] = date('Y-m-d H:i:s', time() - rand(0, 365 * 24 * 60 * 60));
        $content['datedepublish'] = "1900-01-01 00:00:00";

        $username = array_rand($this->app['users']->getUsers());
        $user = $this->app['users']->getUser($username);

        $content['ownerid'] = $user['id'];

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
                        $content[$field]['file'] = $this->images[array_rand($this->images)];
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
                    $content[$field] = date('Y-m-d H:i:s', time() - rand(-365 * 24 * 60 * 60, 365 * 24 * 60 * 60));
                    break;
                case 'date':
                    $content[$field] = date('Y-m-d', time() - rand(-365 * 24 * 60 * 60, 365 * 24 * 60 * 60));
                    break;
                case 'checkbox':
                    $content[$field] = rand(0,1);
                    break;
                case 'float':
                case 'number': // number is deprecated..
                case 'integer':
                    $content[$field] = rand(-1000, 1000) + (rand(0, 1000) / 1000);
                    break;
            }

        }

        $contentobject = $this->getContentObject($contenttype);
        $contentobject->setValues($content);

        if (!empty($contenttype['taxonomy'])) {
            foreach ($contenttype['taxonomy'] as $taxonomy) {
                if ($this->app['config']->get('taxonomy/' . $taxonomy . '/options')) {
                    $options = $this->app['config']->get('taxonomy/' . $taxonomy . '/options');
                    $contentobject->setTaxonomy($taxonomy, array_rand($options), rand(1, 1000));
                }
                if ($this->app['config']->get('taxonomy/' . $taxonomy . '/behaves_like') == "tags") {
                    $contentobject->setTaxonomy($taxonomy, $this->getSomeRandomTags(5));
                }
            }
        }

        $this->saveContent($contentobject);

        $output = __("Added to <tt>%key%</tt> '%title%'",
                array('%key%' => $key, '%title%' => $contentobject->getTitle())) . "<br>\n";

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

    /**
     * Writes a content-changelog entry for a newly-created entry.
     */
    private function logInsert($contenttype, $contentid, $content) {
        $this->writeChangelog('INSERT', $contenttype, $contentid, $content);
    }

    /**
     * Writes a content-changelog entry for an updated entry.
     * This function must be called *before* the actual update, because it
     * fetches the old content from the database.
     */
    private function logUpdate($contenttype, $contentid, $newContent) {
        $this->writeChangelog('UPDATE', $contenttype, $contentid, $newContent);
    }

    /**
     * Writes a content-changelog entry for a deleted entry.
     * This function must be called *before* the actual update, because it
     */
    private function logDelete($contenttype, $contentid) {
        $this->writeChangelog('DELETE', $contenttype, $contentid);
    }

    /**
     * Writes a content-changelog entry.
     *
     * @param string $action Must be one of 'INSERT', 'UPDATE', or 'DELETE'.
     * @param string $contenttype The contenttype setting to log.
     * @param int $contentid ID of the content item to log.
     * @param array $newContent For 'INSERT' and 'UPDATE', the new content;
     *                          null for 'DELETE'.
     *
     * For the 'UPDATE' and 'DELETE' actions, this function fetches the
     * previous data from the database; this means that you must call it
     * _before_ running the actual update/delete query; for the 'INSERT'
     * action, this is not necessary, and since you really want to provide
     * an ID, you can only really call the logging function _after_ the update.
     * @throws \Exception
     */
    private function writeChangelog($action, $contenttype, $contentid, $newContent = null) {
        $allowed = array('INSERT', 'UPDATE', 'DELETE');
        if (!in_array($action, $allowed)) {
            throw new \Exception("Invalid action '$action' specified for changelog (must be one of [ " . implode(', ', $allowed) . " ])");
        }

        if ($this->app['config']->get('general/changelog/enabled')) {
            $tablename = $this->getTablename($contenttype);
            if ($action === 'INSERT') {
                $oldContent = null;
            }
            else {
                $oldContent = $this->app['db']->fetchAssoc("SELECT * FROM $tablename WHERE id = ?", array($contentid));
            }
            if (empty($oldContent) && empty($newContent)) {
                throw new \Exception("Tried to log something that cannot be: both old and new content are empty");
            }
            if (empty($oldContent) && in_array($action, array('UPDATE', 'DELETE'))) {
                throw new \Exception("Cannot log action $action when old content doesn't exist");
            }
            if (empty($newContent) && in_array($action, array('INSERT', 'UPDATE'))) {
                throw new \Exception("Cannot log action $action when new content is empty");
            }
            switch ($action) {
                case 'UPDATE':
                    $diff = DeepDiff::deep_diff($oldContent, $newContent);
                    foreach ($diff as $item) {
                        list($k, $old, $new) = $item;
                        if (isset($newContent[$k]))
                            $data[$k] = array($old, $new);
                    }
                    break;
                case 'INSERT':
                    foreach ($newContent as $k => $val) {
                        $data[$k] = array(null, $val);
                    }
                    break;
                case 'DELETE':
                    foreach ($oldContent as $k => $val) {
                        $data[$k] = array($val, null);
                    }
                    break;
            }
            if ($newContent) {
                $content = new Content($this->app, $contenttype, $newContent);
            }
            else {
                $content = new Content($this->app, $contenttype, $oldContent);
            }
            $title = $content->getTitle();
            if (empty($title)) {
                $content = $this->getContent("$contenttype/$contentid");
                $title = $content->getTitle();
            }
            $str = json_encode($data);
            $user = $this->app['users']->getCurrentUser();
            $entry['title'] = $title;
            $entry['date'] = date('Y-m-d H:i:s');
            $entry['ownerid'] = $user['id'];
            $entry['contenttype'] = $contenttype;
            $entry['contentid'] = $contentid;
            $entry['mutation_type'] = $action;
            $entry['diff'] = $str;
            $this->app['db']->insert($this->getTablename('content_changelog'), $entry);
        }
    }

    private function makeOrderLimitSql($options) {
        $sql = '';
        if (isset($options['order'])) {
            $sql .= " ORDER BY " . $options['order'];
        }
        if (isset($options['limit'])) {
            if (isset($options['offset'])) {
                $sql .= sprintf(" LIMIT %s, %s ",
                            intval($options['offset']),
                            intval($options['limit']));
            }
            else {
                $sql .= " LIMIT " . intval($options['limit']);
            }
        }
        return $sql;
    }

    /**
     * Get content changelog entries for all content types
     * @param array $options An array with additional options. Currently, the
     *                       following options are supported:
     *                       - 'limit' (int)
     *                       - 'offset' (int)
     *                       - 'order' (string)
     * @return array
     */
    public function getChangelog($options) {
        $tablename = $this->getTablename('content_changelog');
        $sql = "SELECT log.*, log.title " .
               "    FROM $tablename as log ";
        $sql .= $this->makeOrderLimitSql($options);

        $rows = $this->app['db']->fetchAll($sql, array());
        $objs = array();
        foreach ($rows as $row) {
            $objs[] = new ChangelogItem($this->app, $row);
        }
        return $objs;
    }

    public function countChangelog($options) {
        $tablename = $this->getTablename('content_changelog');
        $sql = "SELECT COUNT(1) " .
               "    FROM $tablename as log ";
        return $this->app['db']->fetchColumn($sql, array());
    }

    /**
     * Get content changelog entries by content type.
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param array $options An array with additional options. Currently, the
     *                       following options are supported:
     *                       - 'limit' (int)
     *                       - 'order' (string)
     *                       - 'contentid' (int), to filter further by content ID
     *                       - 'id' (int), to filter by a specific changelog entry ID
     * @return array
     */
    public function getChangelogByContentType($contenttype, $options) {
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }
        $tablename = $this->getTablename('content_changelog');
        $content_tablename = $this->getTablename($contenttype);
        $sql = "SELECT log.*, log.title " .
               "    FROM $tablename as log " .
               "    LEFT JOIN $content_tablename as content " .
               "    ON content.id = log.contentid " .
               "    WHERE contenttype = ? ";
        $params = array($contenttype);
        if (isset($options['contentid'])) {
            $sql .= "    AND contentid = ? ";
            $params[] = intval($options['contentid']);
        }
        if (isset($options['id'])) {
            $sql .= "    AND log.id = ? ";
            $params[] = intval($options['contentid']);
        }
        $sql .= $this->makeOrderLimitSql($options);

        $rows = $this->app['db']->fetchAll($sql, $params);
        $objs = array();
        foreach ($rows as $row) {
            $objs[] = new ChangelogItem($this->app, $row);
        }
        return $objs;
    }

    public function countChangelogByContentType($contenttype, $options) {
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }
        $tablename = $this->getTablename('content_changelog');
        $sql = "SELECT COUNT(1) " .
               "    FROM $tablename as log " .
               "    WHERE contenttype = ? ";
        $params = array($contenttype);
        if (isset($options['contentid'])) {
            $sql .= "    AND contentid = ? ";
            $params[] = intval($options['contentid']);
        }
        if (isset($options['id'])) {
            $sql .= "    AND log.id = ? ";
            $params[] = intval($options['contentid']);
        }

        return $this->app['db']->fetchColumn($sql, $params);
    }

    /**
     * Get a content changelog entry by ID
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param $contentid
     * @param int $id The content-changelog ID
     * @return \Bolt\ChangelogItem|null
     */
    public function getChangelogEntry($contenttype, $contentid, $id) {
        return $this->_getChangelogEntry($contenttype, $contentid, $id, '=');
    }

    /**
     * Get the content changelog entry that follows the given ID.
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param $contentid
     * @param int $id The content-changelog ID
     * @return \Bolt\ChangelogItem|null
     */
    public function getNextChangelogEntry($contenttype, $contentid, $id) {
        return $this->_getChangelogEntry($contenttype, $contentid, $id, '>');
    }

    /**
     * Get the content changelog entry that precedes the given ID.
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param $contentid
     * @param int $id The content-changelog ID
     * @return \Bolt\ChangelogItem|null
     */
    public function getPrevChangelogEntry($contenttype, $contentid, $id) {
        return $this->_getChangelogEntry($contenttype, $contentid, $id, '<');
    }

    /**
     * Get one changelog entry from the database.
     *
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param $contentid
     * @param int $id The content-changelog ID
     * @param string $cmp_op One of '=', '<', '>'; this parameter is used
     *                       to select either the ID itself, or the subsequent
     *                       or preceding entry.
     * @throws \Exception
     * @return \Bolt\ChangelogItem|null
     */
    private function _getChangelogEntry($contenttype, $contentid, $id, $cmp_op) {
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }
        switch ($cmp_op) {
            case '=':
                $ordering = ''; // no need to order
                break;
            case '<':
                $ordering = " ORDER BY date DESC";
                break;
            case '>':
                $ordering = " ORDER BY date ";
                break;
            default:
                throw new \Exception("Invalid value for argument 'cmp_op'; must be one of '=', '<', '>' (got '$cmd_op')");
        }
        $tablename = $this->getTablename('content_changelog');
        $content_tablename = $this->getTablename($contenttype);
        $sql = "SELECT log.* " .
               "    FROM $tablename as log " .
               "    LEFT JOIN $content_tablename as content " .
               "    ON content.id = log.contentid " .
               "    WHERE log.id $cmp_op ? " .
               "    AND log.contentid = ? " .
               "    AND contenttype = ? " .
               $ordering .
               "    LIMIT 1";
        $params = array($id, $contentid, $contenttype);

        $row = $this->app['db']->fetchAssoc($sql, $params);
        if (is_array($row)) {
            return new ChangelogItem($this->app, $row);
        }
        else {
            return null;
        }
    }

    public function saveContent($content, $contenttype = "")
    {

        $contenttype = $content->contenttype;

        $fieldvalues = $content->values;

        if (empty($contenttype)) {
            echo "Contenttype is required.";

            return false;
        }

        if ($this->app['dispatcher']->hasListeners(StorageEvents::PRE_SAVE)) {
            $event = new StorageEvent($content);
            $this->app['dispatcher']->dispatch(StorageEvents::PRE_SAVE, $event);
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
                } elseif (!empty($fieldvalues['slug'])) {
                    $fieldvalues['slug'] = makeSlug($fieldvalues['slug']);
                } elseif (empty($fieldvalues['slug']) && $fieldvalues['id']) {
                    $fieldvalues['slug'] = $fieldvalues['id'];
                }
            }

            if ($values['type'] == "video") {
                foreach (array('html', 'responsive') as $subkey) {
                    if (!empty($fieldvalues[$key][$subkey])) {
                        $fieldvalues[$key][$subkey] = (string)$fieldvalues[$key][$subkey];
                    }
                }
                if (!empty($fieldvalues[$key]['url'])) {
                    $fieldvalues[$key] = json_encode($fieldvalues[$key]);
                } else {
                    $fieldvalues[$key] = "";
                }
            }

            if ($values['type'] == "geolocation") {
                if (!empty($fieldvalues[$key]['address'])) {
                    $fieldvalues[$key] = json_encode($fieldvalues[$key]);
                } else {
                    $fieldvalues[$key] = "";
                }
            }

            if ($values['type'] == "image") {
                 if (!empty($fieldvalues[$key]['file'])) {
                     $fieldvalues[$key] = json_encode($fieldvalues[$key]);
                 } else {
                     $fieldvalues[$key] = "";
                 }
            }

            if (in_array($values['type'], array("imagelist", "filelist")))  {
                if (is_array($fieldvalues[$key])) {
                    $fieldvalues[$key] = json_encode($fieldvalues[$key]);
                } else if (!empty($fieldvalues[$key]) && strlen($fieldvalues[$key]) < 3) {
                    // Don't store '[]'
                    $fieldvalues[$key] = "";
                }
            }

            if ($values['type'] == "integer") {
                $fieldvalues[$key] = round($fieldvalues[$key]);
            }

            if ($values['type'] == "select" && is_array($fieldvalues[$key])) {
                $fieldvalues[$key] = json_encode($fieldvalues[$key]);
            }

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

        // We need to verify if the slug is unique. If not, we update it.
        $fieldvalues['slug'] = $this->getUri($fieldvalues['slug'], isset($fieldvalues['id']) ? $fieldvalues['id'] : null, $contenttype['slug'], false, false);

        // Decide whether to insert a new record, or update an existing one.
        if (empty($fieldvalues['id'])) {
            $id = $this->insertContent($fieldvalues, $contenttype);
            $fieldvalues['id'] = $id;
            $content->setValue('id', $id);
        } else {
            $id = $fieldvalues['id'];
            $this->updateContent($fieldvalues, $contenttype);
        }

        $this->updateTaxonomy($contenttype, $id, $content->taxonomy);
        $this->updateRelation($contenttype, $id, $content->relation);

        if ($this->app['dispatcher']->hasListeners(StorageEvents::POST_SAVE)) {
            $event = new StorageEvent($content);
            $this->app['dispatcher']->dispatch(StorageEvents::POST_SAVE, $event);
        }

        return $id;

    }


    public function deleteContent($contenttype, $id)
    {

        if (empty($contenttype)) {
            echo "Contenttype is required.";

            return false;
        }

        if ($this->app['dispatcher']->hasListeners(StorageEvents::PRE_DELETE)) {
            $event = new StorageEvent(array($contenttype, $id));
            $this->app['dispatcher']->dispatch(StorageEvents::PRE_DELETE, $event);
        }

        // Make sure $contenttype is a 'slug'
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        $this->logDelete($contenttype, $id);

        $tablename = $this->getTablename($contenttype);

        $res = $this->app['db']->delete($tablename, array('id' => $id));

        // Make sure relations and taxonomies are deleted as well.
        if ($res) {
            $this->app['db']->delete($this->prefix . "relations", array('from_contenttype' => $contenttype, 'from_id' => $id));
            $this->app['db']->delete($this->prefix . "relations", array('to_contenttype' => $contenttype, 'to_id' => $id));
            $this->app['db']->delete($this->prefix . "taxonomy", array('contenttype' => $contenttype, 'content_id' => $id));
        }

        if ($this->app['dispatcher']->hasListeners(StorageEvents::POST_DELETE)) {
            $event = new StorageEvent(array($contenttype, $id));
            $this->app['dispatcher']->dispatch(StorageEvents::POST_DELETE, $event);
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
            $seq = $tablename . '_id_seq';
        }
        $id = $this->app['db']->lastInsertId($seq);

        $this->logInsert($contenttype, $id, $content);

        return $id;

    }


    private function updateContent($content, $contenttype)
    {
        // Make sure $contenttype is a 'slug'
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        $tablename = $this->getTablename($contenttype);

        $content['datechanged'] = date('Y-m-d H:i:s');

        // Keep datecreated around, for when we might need to 'insert' instead of 'update' after all
        $datecreated = $content['datecreated'];
        unset($content['datecreated']);

        $res = $this->app['db']->update($tablename, $content, array('id' => $content['id']));

        if ($res == true) {
            $this->logUpdate($contenttype, $content['id'], $content);
            return true;
        } else {
            // Attempt to _insert_ it, instead of updating..
            $content['datecreated'] = $datecreated;
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

        $this->logUpdate($contenttype, $id, array($field => $value));

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

    /**
     * Decode search query into searchable parts
     */
    private function decodeSearchQuery($q)
    {
        $words = preg_split('|[\r\n\t ]+|', trim($q));

        $words = array_map(function ($word) {
            return mb_strtolower($word, "UTF-8");
        }, $words);
        $words = array_filter($words, function ($word) {
            return strlen($word) >= 2;
        });

        return array(
            'valid' => count($words) > 0,
            'in_q' => $q,
            'use_q' => implode(' ', $words),
            'words' => $words
        );
    }

    /**
     * Search through a single contenttype
     *
     * Search, weigh and return the results.
     */
    private function searchSingleContentType($query, $contenttype, $fields, array $filter = null)
    {
        // This could be even more configurable
        // (see also Content->getFieldWeights)
        $searchable_types = array('text', 'textarea', 'html', 'markdown');
        $table = $this->getTablename($contenttype);

        // Build fields 'WHERE'
        $fields_where = array();
        foreach ($fields as $field => $fieldconfig) {
            if (in_array($fieldconfig['type'], $searchable_types)) {
                foreach ($query['words'] as $word) {
                    $fields_where[] = sprintf('%s.%s LIKE %s', $table, $field, $this->app['db']->quote('%' . $word . '%'));
                }
            }
        }

        // make taxonomies work
        $taxonomytable = $this->getTablename('taxonomy');
        $taxonomies    = $this->getContentTypeTaxonomy($contenttype);
        $tags_where    = array();
        $tags_query    = '';
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['behaves_like'] == 'tags') {
                foreach ($query['words'] as $word) {
                    $tags_where[] = sprintf('%s.slug LIKE %s', $taxonomytable, $this->app['db']->quote('%' . $word . '%'));
                }
            }
        }
        // only add taxonomies if they exist
        if (!empty($taxonomies) && !empty($tags_where)) {
            $tags_query_1 = sprintf("%s.contenttype = '%s'", $taxonomytable, $contenttype);
            $tags_query_2 = implode(' OR ', $tags_where);
            $tags_query   = sprintf(' OR (%s AND (%s))', $tags_query_1, $tags_query_2);
        }

        // Build filter 'WHERE"
        // @todo make relations work as well
        $filter_where = array();
        if (!is_null($filter)) {
            foreach ($fields as $field => $fieldconfig) {
                if (isset($filter[$field])) {
                    $filter_where[] = $this->parseWhereParameter($table . '.' . $field, $filter[$field]);
                }
            }
        }

        // Build actual where
        $where = array();
        $where[] = sprintf("%s.status = 'published'", $table);
        $where[] = '(( ' . implode(' OR ', $fields_where) . ' ) '.$tags_query. ' )';
        $where = array_merge($where, $filter_where);

        // Build SQL query
        $select  = sprintf('SELECT   %s.id', $table);
        $select .= ' FROM ' . $table;
        $select .= ' LEFT JOIN ' . $taxonomytable;
        $select .=  sprintf(' ON %s.id = %s.content_id', $table, $taxonomytable);
        $select .= ' WHERE ' . implode(' AND ', $where);

        // Run Query
        $results = $this->app['db']->fetchAll($select);

        if (!empty($results)) {

            $ids = implode(' || ', \util::array_pluck($results, 'id'));

            $results = $this->getContent($contenttype, array('id' => $ids, 'returnsingle' => false));

            // Convert and weight
            foreach ($results as $result) {
                $result->weighSearchResult($query);
            }
        }

        return $results;
    }

    /**
     * Compare by search weights
     *
     * Or fallback to dates or title
     */
    private function compareSearchWeights($a, $b)
    {
        if ($a->getSearchResultWeight() > $b->getSearchResultWeight()) {
            return -1;
        }
        if ($a->getSearchResultWeight() < $b->getSearchResultWeight()) {
            return +1;
        }
        if ($a['datepublish'] > $b['datepublish']) {
            // later is more important
            return -1;
        }
        if ($a['datepublish'] < $b['datepublish']) {
            // earlier is less important
            return +1;
        }

        return strcasecmp($a['title'], $b['title']);
    }

    /**
     * Search through actual content
     *
     * Unless the query is invalid it will always return a 'result array'. It may
     * complain in the log but it won't abort.
     *
     * @param string $q            search string
     * @param array<string> $contenttypes contenttype names to search for
     *                                      null means every searchable contenttype
     * @param array<string,array> $filters additional filters for contenttypes
     *                                      <key is contenttype and array is filter>
     * @param  integer $limit  limit the number of results
     * @param  integer $offset skip this number of results
     * @return mixed   false if query is invalid,
     *                                      an array with results if query was executed
     */
    public function searchContent($q, array $contenttypes = null, array $filters = null, $limit = 100, $offset = 0)
    {
        $query = $this->decodeSearchQuery($q);
        if (!$query['valid']) {
            return false;
        }

        $app_ct = $this->app['config']->get('contenttypes');

        // By default we only search through searchable contenttypes
        if (is_null($contenttypes)) {
            $contenttypes = array_keys($app_ct);
            $contenttypes = array_filter($contenttypes, function ($ct) use ($app_ct) {
                if (isset($app_ct[$ct]['searchable']) && ($app_ct[$ct]['searchable'] == false)) {
                    return false;
                }

                return true;
            });
            $contenttypes = array_map(function ($ct) use ($app_ct) {
                return $app_ct[$ct]['slug'];
            }, $contenttypes);
        }

        // Build our search results array
        $results = array();
        foreach ($contenttypes as $contenttype) {
            $ctconfig = $this->getContentType($contenttype);

            $table = $this->getTablename($contenttype);
            $fields = $ctconfig['fields'];
            $filter = null;

            if (is_array($filters) && isset($filters[$contenttype])) {
                $filter = $filters[$contenttype];
            }

            $sub_results = $this->searchSingleContentType($query, $contenttype, $fields, $filter);

            $results = array_merge($results, $sub_results);
        }


        // Sort the results
        usort($results, array($this, 'compareSearchWeights'));

        $no_of_results = count($results);

        $page_results = array();
        if ($offset < $no_of_results) {
            $page_results = array_slice($results, $offset, $limit);
        }

        return array(
            'query' => $query,
            'no_of_results' => $no_of_results,
            'results' => $page_results
        );
    }

    public function searchAllContentTypes(array $parameters = array(), &$pager = array())
    {
        // Note: we can only apply this kind of results aggregating when we don't
        // use LIMIT and OFFSET! If we'd want to use it, this should be rewritten.
        // Results aggregator
        $result = array();

        foreach ($this->getContentTypes() as $contenttype) {

            $contentTypeSearchResults = $this->searchContentType($contenttype, $parameters, $pager);
            foreach ($contentTypeSearchResults as $searchresult) {
                $result [] = $searchresult;
            }
        }

        return $result;
    }

    public function searchContentType($contenttypename, array $parameters = array(), &$pager = array())
    {
        $tablename = $this->getTablename($contenttypename);

        $contenttype = $this->app['config']->get('contenttypes/' . $contenttypename);

        // If this contenttype has 'searchable: false', we skip it.
        if (isset($contenttype['searchable']) && $contenttype['searchable'] === false) {
            return array();
        }

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

            $filter = $this->app['db']->quote($parameters['filter']);

            $filter_where = array();

            foreach ($contenttype['fields'] as $key => $value) {
                if (in_array($value['type'], array('text', 'textarea', 'html', 'markdown'))) {
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
                $dboptions = $this->app['config']->getDBOptions();
                $queryparams .= " ORDER BY " . $dboptions['randomfunction'];
            } else {
                $order = $this->getEscapedSortorder($parameters['order'], false);
                if (!empty($order)) {
                    $queryparams .= " ORDER BY " . $order;
                }
            }
        }

        // Make the query for the pager..
        $pagerquery = "SELECT COUNT(*) AS count FROM $tablename" . $queryparams;

        // Add the limit
        $queryparams = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($queryparams, $limit, ($page - 1) * $limit);

        // Make the query to get the results..
        $query = "SELECT * FROM $tablename" . $queryparams;

        $rows = $this->app['db']->fetchAll($query);

        // Make sure content is set, and all content has information about its contenttype
        $content = array();
        foreach ($rows as $row) {
            $content[$row['id']] = $this->getContentObject($contenttype, $row);
        }

        // TODO: Check if we need to hydrate here!

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
            'showing_from' => ($page - 1) * $limit + 1,
            'showing_to' => ($page - 1) * $limit + count($content)
        );

        // @todo Need to rewrite pager-code to make the pager work properly
        return $content;
    }

    /**
     * Retrieve content from the database, filtered on taxonomy.
     *
     * Note: we can NOT sort on anything meaningful. Records are fetched from multiple
     * content-types, so we can not do joins. Neither can we sort after fetching,
     * because it would mean fetching _all_ records and _then_ doing the sorting.
     * Instead, we do not sort here. If you need ordering, use the '|order()' in
     * your templates.
     *
     */
    public function getContentByTaxonomy($taxonomyslug, $name, $parameters = "")
    {

        $tablename = $this->getTablename("taxonomy");

        $slug = makeSlug($name);

        $limit = $parameters['limit'] ? : 100;
        $page = $parameters['page'] ? : 1;

        $taxonomytype = $this->getTaxonomyType($taxonomyslug);

        // No taxonomytype, no possible content..
        if (empty($taxonomytype)) {
            return false;
        }

        $where = " WHERE (taxonomytype=" . $this->app['db']->quote($taxonomytype['slug']) . "
        AND (slug=" . $this->app['db']->quote($slug) . " OR name=" . $this->app['db']->quote($name) . ") )";

        // Make the query for the pager..
        $pagerquery = "SELECT COUNT(*) AS count FROM $tablename" . $where;

        // Add the limit
        $query = "SELECT * FROM $tablename" . $where . " ORDER BY id ASC";
        $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, $limit, ($page - 1) * $limit);

        $taxorows = $this->app['db']->fetchAll($query);

        if (!empty($parameters['printquery'])) {
            // @todo formalize this
            echo nl2br(htmlentities($query));
        }

        $content = array();

        if (is_array($taxorows)) {
            foreach ($taxorows as $row) {
                $record = $this->getContent($row['contenttype'] . "/" . $row['content_id']);
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
            'showing_from' => ($page - 1) * $limit + 1,
            'showing_to' => ($page - 1) * $limit + count($taxorows)
        );
        $GLOBALS['pager'][$taxonomytype['slug'] . "/" . $slug] = $pager;

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
        if (isset($this->checkedfortimed["publish-" . $contenttype['slug']])) {
            return;
        }

        $this->checkedfortimed["publish-" . $contenttype['slug']] = true;
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
     * Check (and update) any records that need to be updated from "published" to "held".
     *
     * @param array $contenttype
     */
    public function depublishExpiredRecords($contenttype)
    {
        // We need to do this only once per contenttype, max.
        if (isset($this->checkedfortimed["depublish-" . $contenttype['slug']])) {
            return;
        }

        $this->checkedfortimed["depublish-" . $contenttype['slug']] = true;
        $tablename = $this->getTablename($contenttype['slug']);
        $now = date('Y-m-d H:i:s', time());

        try {

            // Check if there are any records that need depublishing..
            $query = "SELECT id FROM $tablename WHERE status = 'published' and datedepublish < :now and datedepublish > '1900-01-01 00:00:01' ";
            $stmt = $this->app['db']->prepare($query);
            $stmt->bindValue("now", $now);
            $stmt->execute();

            // If there's a result, we need to set these to 'held'..
            if ($stmt->fetch() != false) {
                $query = "UPDATE $tablename SET status = 'held', datechanged = :now, datedepublish = '1900-01-01 00:00:00'  WHERE status = 'published' and datedepublish < :now and datedepublish > '1900-01-01 00:00:01'";
                $stmt = $this->app['db']->prepare($query);
                $stmt->bindValue("now", $now);
                $stmt->execute();
            }

        } catch (\Doctrine\DBAL\DBALException $e) {

            // Oops. Couldn't execute the queries.

        }

    }

    /**
     * Split into meta-parameters and contenttype parameters
     * (tightly coupled to $this->getContent())
     *
     * @see $this->decodeContentQuery()
     */
    private function organizeQueryParameters($in_parameters = null)
    {
        $ctype_parameters = array();
        $meta_parameters = array('order' => false); // order in meta_parameters check again in line: 1530!
        if (is_array($in_parameters)) {
            foreach ($in_parameters as $key => $value) {
                if (in_array($key, array('page', 'limit', 'offset', 'returnsingle', 'printquery', 'paging', 'order'))) {
                    $meta_parameters[$key] = $value;
                } else {
                    $ctype_parameters[$key] = $value;
                }
            }
        }

        if (!isset($meta_parameters['page'])) {
            $meta_parameters['page'] = 1;
        }

        // oof!
        if (!empty($meta_parameters['paging']) && $this->app->raw('request') instanceof Request) {
            $meta_parameters['page'] = $this->app['request']->get('page', $meta_parameters['page']);
        }

        // oof, part deux!
        if ((isset($meta_parameters['order']) && $meta_parameters['order'] == false) && ($this->app->raw('request') instanceof Request)) {
            $meta_parameters['order'] = $this->app['request']->get('order', false);
        }

        return array($meta_parameters, $ctype_parameters);
    }

    /**
     * Decode a contenttypes argument from text
     *
     * (entry,page) -> array('entry', 'page')
     * event -> array('event')
     *
     * @param  string $text text with contenttypes
     * @return array  array with contenttype(slug)s
     */
    private function decodeContentTypesFromText($text)
    {
        $contenttypes = array();

        if ((substr($text, 0, 1) == '(') &&
            (substr($text, -1) == ')')
        ) {
            $contenttypes = explode(',', substr($text, 1, -1));
        } else {
            $contenttypes[] = $text;
        }

        $app_ct = $this->app['config']->get('contenttypes');
        $instance = $this;
        $contenttypes = array_map(function ($name) use ($app_ct, $instance) {
            $ct = $instance->getContentType($name);

            return $ct['slug'];
        }, $contenttypes);

        return $contenttypes;
    }

    /**
     * Return the proper contenttype for a singlular slug
     *
     * @param $singular_slug
     * @return mixed name of contenttype if the singular_slug was found
     *                  false, if singular_slug was not found
     */
    private function searchSingularContentTypeSlug($singular_slug)
    {
        foreach ($this->app['config']->get('contenttypes') as $key => $ct) {
            if (isset($ct['singular_slug']) && ($ct['singular_slug'] == $singular_slug)) {
                return $this->app['config']->get('contenttypes/' . $key . '/slug');
            }
        }

        return false;
    }

    /**
     * Parse textquery into useable arguments
     * (tightly coupled to $this->getContent())
     *
     * @see $this->decodeContentQuery()
     *
     * @param $textquery
     * @param array $decoded a pre-set decoded array to fill
     * @param array $meta_parameters meta parameters
     * @param array $ctype_parameters contenttype parameters
     */
    private function parseTextQuery($textquery, array &$decoded, array &$meta_parameters, array &$ctype_parameters)
    {
        // Our default callback
        $decoded['queries_callback'] = array($this, 'executeGetContentQueries');

        // Some special cases, like 'entry/1' or 'page/about' need to be caught before further processing.
        if (preg_match('#^/?([a-z0-9_-]+)/([0-9]+)$#i', $textquery, $match)) {
            // like 'entry/12' or '/page/12345'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            $decoded['return_single'] = true;
            $ctype_parameters['id'] = $match[2];
        } elseif (preg_match('#^/?([a-z0-9_(\),-]+)/search(/([0-9]+))?$#i', $textquery, $match)) {
            // like 'page/search or '(entry,page)/search'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            $meta_parameters['order'] = array($this, 'compareSearchWeights');
            if (count($match) >= 3) {
                $meta_parameters['limit'] = $match[3];
            }

            $decoded['queries_callback'] = array($this, 'executeGetContentSearch');
        } elseif (preg_match('#^/?([a-z0-9_-]+)/([a-z0-9_-]+)$#i', $textquery, $match)) {
            // like 'page/lorem-ipsum-dolor' or '/page/home'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            $decoded['return_single'] = true;
            $ctype_parameters['slug'] = $match[2];
        } elseif (preg_match('#^/?([a-z0-9_-]+)/(latest|first)/([0-9]+)$#i', $textquery, $match)) {
            // like 'page/latest/5'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            if (!isset($meta_parameters['order'])) {
                $meta_parameters['order'] = 'datepublish ' . ($match[2] == 'latest' ? 'DESC' : 'ASC');
            }
            if (!isset($meta_parameters['limit'])) {
                $meta_parameters['limit'] = $match[3];
            }
        } elseif (preg_match('#^/?([a-z0-9_-]+)/random/([0-9]+)$#i', $textquery, $match)) {
            // like 'page/random/4'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            $dboptions = $this->app['config']->getDBoptions();
            $meta_parameters['order'] = $dboptions['randomfunction']; // 'RAND()' or 'RANDOM()'
            if (!isset($meta_parameters['limit'])) {
                $meta_parameters['limit'] = $match[2];
            }
        } else {
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($textquery);

            if (isset($ctype_parameters['id']) && (is_numeric($ctype_parameters['id']))) {
                $decoded['return_single'] = true;
            }
        }

        // When using from the frontend, we assume (by default) that we only want published items,
        // unless something else is specified explicitly
        if (isset($this->app['end']) && $this->app['end'] == "frontend" && empty($ctype_parameters['status'])) {
            $ctype_parameters['status'] = "published";
        }

        if (isset($meta_parameters['returnsingle'])) {
            $decoded['return_single'] = $meta_parameters['returnsingle'];
            unset($meta_parameters['returnsingle']);
        }

    }

    /**
     * Prepare decoded for actual use
     * (tightly coupled to $this->getContent())
     *
     * @see $this->decodeContentQuery()
     */
    private function prepareDecodedQueryForUse(&$decoded, &$meta_parameters, &$ctype_parameters)
    {
        // If there is only 1 contenttype we assume the where is NOT nested
        if (count($decoded['contenttypes']) == 1) {
            // So we need to add this nesting
            $ctype_parameters = array(
                $decoded['contenttypes'][0] => $ctype_parameters
            );
        } else {
            // We need to set every non-contenttypeslug parameters to each individual contenttypes
            $global_parameters = array();
            foreach ($ctype_parameters as $key => $parameter) {
                if (!in_array($key, $decoded['contenttypes'])) {
                    $global_parameters[$key] = $parameter;
                }
            }
            foreach ($global_parameters as $key => $parameter) {
                unset($ctype_parameters[$key]);
                foreach ($decoded['contenttypes'] as $contenttype) {
                    if (!isset($ctype_parameters[$contenttype])) {
                        $ctype_parameters[$contenttype] = array();
                    }
                    if (!isset($ctype_parameters[$contenttype][$key])) {
                        $ctype_parameters[$contenttype][$key] = $parameter;
                    }
                }
            }

            // In this case query pagination never makes sense!
            $decoded['self_paginated'] = false;
        }

        if ($decoded['order_callback'] !== false) {
            // Callback sorting disables pagination
            $decoded['self_paginated'] = false;
        }

        if (isset($meta_parameters['order']) && $meta_parameters['order'] === false) {
            if (count($decoded['contenttypes']) == 1) {
                if ($this->getContentTypeGrouping($decoded['contenttypes'][0])) {
                    $decoded['order_callback'] = array($this, 'groupingSort');
                }
            }
        }

        if (!isset($meta_parameters['limit'])) {
            $meta_parameters['limit'] = 100;
        }
    }

    /**
     * Get the parameter for the 'order by' part of a query.
     * (tightly coupled to $this->getContent())
     *
     * @param  array $contenttype
     * @param  string $order_value
     * @return string
     */
    private function decodeQueryOrder($contenttype, $order_value)
    {
        $order = false;

        if (($order_value === false) || ($order_value === '')) {
            if ($this->isValidColumn($contenttype['sort'], $contenttype, true)) {
                $order = $this->getEscapedSortorder($contenttype['sort'], false);
            }
        } else {
            $par_order = safeString($order_value);
            if ($par_order == 'RANDOM') {
                $dboptions = $this->app['config']->getDBOptions();
                $order = $dboptions['randomfunction'];
            } elseif ($this->isValidColumn($par_order, $contenttype, true)) {
                $order = $this->getEscapedSortorder($par_order, false);
            }
        }

        return $order;

    }

    /**
     * Decode a content textquery
     * (tightly coupled to $this->getContent())
     *
     * @param $textquery
     * @param null $in_parameters
     * @internal param string $query the query (eg. page/about, entries/latest/5)
     * @internal param array $parameters parameters to the query
     * @return array  decoded query, keys:
     *    contenttypes           - array, contenttypeslugs that will be returned
     *    return_single          - boolean, true if only 1 result should be returned
     *    self_paginated         - boolean, true if already be paginated
     *    order_callback         - callback, sort results post-hydration after everything is merged
     *    queries                - array of SQL query parts
     *       tablename             - tablename
     *       contenttype           - contenttype array
     *       from                  - from part
     *       where                 - where part
     *       order                 - order part
     *       params                - bind-parameters
     *    parameters             - parameters to use after the queries
     */
    private function decodeContentQuery($textquery, $in_parameters = null)
    {
        $decoded = array(
            'contenttypes' => array(),
            'return_single' => false,
            'self_paginated' => true,
            'order_callback' => false,
            'queries' => array(),
            'parameters' => array(),
            'hydrate' => true,
        );

        list($meta_parameters, $ctype_parameters) = $this->organizeQueryParameters($in_parameters);

        $this->parseTextQuery($textquery, $decoded, $meta_parameters, $ctype_parameters);

        $this->prepareDecodedQueryForUse($decoded, $meta_parameters, $ctype_parameters);

        $decoded['parameters'] = $meta_parameters;

        // for all the non-reserved parameters that are fields or taxonomies, we assume people want to do a 'where'
        foreach ($ctype_parameters as $contenttypeslug => $actual_parameters) {
            $contenttype = $this->getContentType($contenttypeslug);
            $tablename = $this->getTablename($contenttype['slug']);
            $where = array();
            $order = array();

            // Set the 'order', if specified in the meta_parameters.
            if (!empty($meta_parameters['order'])) {
                $order[] = $this->getEscapedSortorder($meta_parameters['order'], false);
            }

            $query = array(
                'tablename' => $tablename,
                'contenttype' => $contenttype,
                'from' => sprintf('FROM %s', $tablename),
                'where' => '',
                'order' => '',
                'params' => array()
            );

            if ($contenttype === false) {
                $this->app['log']->add("Storage: No valid contenttype '$contenttypeslug'");
                continue;
            }

            if (is_array($actual_parameters)) {
                // Set the 'FROM' part of the query, without the LEFT JOIN (i.e. no taxonomies..)
                foreach ($actual_parameters as $key => $value) {

                    if ($key == 'order') {
                        $order_value = $this->decodeQueryOrder($contenttype, $value);
                        if ($order_value !== false) {
                            $order[] = $order_value;
                        }
                        continue;
                    }

                    if ($key == 'filter') {

                        $filter_where = array();
                        foreach ($contenttype['fields'] as $name => $fieldconfig) {
                            if (in_array($fieldconfig['type'], array('text', 'textarea', 'html', 'markdown'))) {
                                $filter_where[] = sprintf('%s.%s LIKE %s',
                                    $tablename,
                                    $name,
                                    $this->app['db']->quote('%' . $value . '%')
                                );
                            }
                        }
                        if (count($filter_where) > 0) {
                            $where[] = '(' . implode(' OR ', $filter_where) . ')';
                        }
                        continue;
                    }

                    // build OR parts if key contains "|||"
                    if (strpos($key, " ||| ") !== false) {
                        $keyParts = explode(" ||| ", $key);
                        $valParts = explode(" ||| ", $value);
                        $orPart = '( ';
                        for ($i = 0; $i < count($keyParts); $i++) {
                            if (in_array($keyParts[$i], $this->getContentTypeFields($contenttype['slug'])) ||
                                in_array($keyParts[$i], Content::getBaseColumns()) ) {
                                $rkey = $tablename . '.' . $keyParts[$i];
                                $fieldtype = $this->getContentTypeFieldType($contenttype['slug'], $keyParts[$i]);
                                $orPart.= ' (' . $this->parseWhereParameter($rkey, $valParts[$i], $keyParts[$i], $fieldtype) . ') OR ';
                            }
                        }
                        if (strlen($orPart) > 2)
                            $where[] = substr($orPart, 0, -4) . ') ';
                    }

                    // for all the parameters that are fields
                    if (in_array($key, $this->getContentTypeFields($contenttype['slug'])) ||
                        in_array($key, Content::getBaseColumns())
                    ) {
                        $rkey = $tablename . '.' . $key;
                        $fieldtype = $this->getContentTypeFieldType($contenttype['slug'], $key);
                        $where[] = $this->parseWhereParameter($rkey, $value, $fieldtype);
                    }

                    // for all the  parameters that are taxonomies
                    if (array_key_exists($key, $this->getContentTypeTaxonomy($contenttype['slug']))) {

                        // check if we're trying to use "!" as a way of 'not'. If so, we need to do a 'NOT IN', instead
                        // of 'IN'. And, the parameter in the subselect needs to be without "!" as a consequence.
                        if (strpos($value, "!") !== false) {
                            $notin = "NOT ";
                            $value = str_replace("!", "", $value);
                        } else {
                            $notin = "";
                        }

                        // Set the extra '$where', with subselect for taxonomies..
                        $where[] = sprintf('%s %s IN (SELECT content_id AS id FROM %s where %s AND ( %s OR %s ) AND %s)',
                            $this->app['db']->quoteIdentifier('id'),
                            $notin,
                            $this->getTablename('taxonomy'),
                            $this->parseWhereParameter($this->getTablename('taxonomy') . '.taxonomytype', $key),
                            $this->parseWhereParameter($this->getTablename('taxonomy') . '.slug', $value),
                            $this->parseWhereParameter($this->getTablename('taxonomy') . '.name', $value),
                            $this->parseWhereParameter($this->getTablename('taxonomy') . '.contenttype', $contenttype['slug'])
                        );
                    }

                }
            }

            if (count($order) == 0) {
                // we didn't add table, maybe this is an issue
                $order[] = 'datepublish DESC';
            }

            if (count($where) > 0) {
                $query['where'] = 'WHERE ( ' . implode(' AND ', $where) . ' )';
            }
            if (count($order) > 0) {
                $order = implode(', ', $order);
                if (!empty($order)) {
                    $query['order'] = 'ORDER BY ' . $order;
                }
            }

            $decoded['queries'][] = $query;

            if (isset($in_parameters['hydrate'])) {
                $decoded['hydrate'] = $in_parameters['hydrate'];
            }

        }

        return $decoded;
    }

    /**
     * Run existence and perform publish/depublishes
     *
     * @param array<string> contenttypeslugs to check
     * @return mixed false, if any table doesn't exist
     *                      true, if all is fine
     */
    private function runContenttypeChecks(array $contenttypes)
    {
        foreach ($contenttypes as $contenttypeslug) {
            $contenttype = $this->getContentType($contenttypeslug);

            $tablename = $this->getTablename($contenttype['slug']);

            // If the table doesn't exist (yet), return false..
            if (!$this->tableExists($tablename)) {
                return false;
            }

            // Check if we need to 'publish' any 'timed' records, or 'depublish' any expired records.
            $this->publishTimedRecords($contenttype);
            $this->depublishExpiredRecords($contenttype);
        }

        return true;
    }

    /**
     * Hydrate database rows into objects
     */
    private function hydrateRows($contenttype, $rows, $getTaxoAndRel = true)
    {
        // Make sure content is set, and all content has information about its contenttype
        $objects = array();
        foreach ($rows as $row) {
            $objects[$row['id']] = $this->getContentObject($contenttype, $row);
        }

        if ($getTaxoAndRel) {
            // Make sure all content has their taxonomies and relations
            $this->getTaxonomy($objects);
            $this->getRelation($objects);
        }

        return $objects;
    }

    /**
     * Execute the content queries
     * (tightly coupled to $this->getContent())
     *
     * @see $this->getContent()
     */
    private function executeGetContentSearch($decoded, $parameters)
    {
        $results = $this->searchContent(
            $parameters['filter'],
            $decoded['contenttypes'],
            null,
            isset($decoded['parameters']['limit']) ? $decoded['parameters']['limit'] : 2000
        );

        return array(
            $results['results'],
            $results['no_of_results']
        );
    }

    /**
     * Execute the content queries
     * (tightly coupled to $this->getContent())
     *
     * @see $this->getContent()
     */
    private function executeGetContentQueries($decoded, $parameters)
    {
        // Perform actual queries and hydrate
        $total_results = false;
        $results = false;
        foreach ($decoded['queries'] as $query) {
            $statement = sprintf('SELECT %s.* %s %s %s',
                $query['tablename'],
                $query['from'],
                $query['where'],
                $query['order']
            );

            if ($decoded['self_paginated'] === true) {
                // self pagination requires an extra query to return the actual number of results
                if ($decoded['return_single'] === false) {
                    $count_statement = sprintf('SELECT COUNT(*) as count %s %s',
                        $query['from'],
                        $query['where']
                    );
                    $count_row = $this->app['db']->executeQuery($count_statement)->fetch();
                    $total_results = $count_row['count'];
                }

                $offset = ($decoded['parameters']['page'] - 1) * $decoded['parameters']['limit'];
                $limit = $decoded['parameters']['limit'];

                // @todo this will fail when actually using params on certain databases
                $statement = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($statement, $limit, $offset);
            }

            if (!empty($decoded['parameters']['printquery'])) {
                // @todo formalize this
                echo nl2br(htmlentities($statement));
            }

            $rows = $this->app['db']->fetchAll($statement, $query['params']);

            // Convert the row 'arrays' into \Bolt\Content objects.
            // Only get the Taxonomies and Relations if we have to.
            $rows = $this->hydrateRows($query['contenttype'], $rows, $decoded['hydrate']);

            if ($results === false) {
                $results = $rows;
            } else {
                // We can no longer maintain keys when merging subresults
                $results = array_merge($results, array_values($rows));
            }
        }

        if ($total_results === false) {
            $total_results = count($results);
        }

        return array($results, $total_results);
    }

    /**
     * getContent based on a 'human readable query'
     *
     * Used directly by {% setcontent %} but also in other parts.
     * This code has been split into multiple methods in the spirit of separation of concerns,
     * but the situation is still far from ideal.
     * Where applicable each 'concern' notes the coupling in the local documentation.
     */
    public function getContent($textquery, $parameters = '', &$pager = array(), $whereparameters = array())
    {
        // $whereparameters is passed if called from a compiled template. If present, merge it with $parameters.
        if (!empty($whereparameters)) {
            $parameters = array_merge((array) $parameters, (array) $whereparameters);
        }

        // Decode this textquery
        $decoded = $this->decodeContentQuery($textquery, $parameters);
        if ($decoded === false) {
            $this->app['log']->add("Storage: No valid query '$textquery'");

            return false;
        }

        //$this->app['log']->add('Storage: running textquery: '.$textquery);

        // Run checks and some actions (@todo put these somewhere else?)
        if (!$this->runContenttypeChecks($decoded['contenttypes'])) {
            return false;
        }

        // Run the actual queries
        list($results, $total_results) = call_user_func(
            $decoded['queries_callback'],
            $decoded, $parameters
        );

        // Perform post hydration ordering
        if ($decoded['order_callback'] !== false) {
            if (is_scalar($decoded['order_callback']) && ($decoded['order_callback'] == 'RANDOM')) {
                shuffle($results);
            } else {
                uasort($results, $decoded['order_callback']);
            }
        }

        // Perform pagination if necessary
        $offset = 0;
        $limit = false;
        if (($decoded['self_paginated'] == false) && (isset($decoded['parameters']['page']))) {
            $offset = ($decoded['parameters']['page'] - 1) * $decoded['parameters']['limit'];
            $limit = $decoded['parameters']['limit'];
        }
        if ($limit !== false) {
            $results = array_slice($results, $offset, $limit);
        }

        // Return content
        if ($decoded['return_single']) {
            if (util::array_first_key($results)) {
                return util::array_first($results);
            }

            $msg = sprintf(
                "Storage: requested specific query '%s', not found.",
                $textquery
            );
            $this->app['log']->add($msg);

            return false;
        }

        // Set up the $pager array with relevant values..
        $pager_name = $decoded['contenttypes'][0];
        $pager = array(
            'for' => $pager_name,
            'count' => $total_results,
            'totalpages' => ceil($total_results / $decoded['parameters']['limit']),
            'current' => $decoded['parameters']['page'],
            'showing_from' => ($decoded['parameters']['page'] - 1) * $decoded['parameters']['limit'] + 1,
            'showing_to' => ($decoded['parameters']['page'] - 1) * $decoded['parameters']['limit'] + count($results)
        );
        $GLOBALS['pager'][$pager_name] = $pager;
        $this->app['twig']->addGlobal('pager', $pager);

        return $results;
    }

    /**
     * Check if a given name is a valid column, and if it can be used in queries.
     *
     * @param  string $name
     * @param  array $contenttype
     * @param  bool $allowVariants
     * @return bool
     */
    private function isValidColumn($name, $contenttype, $allowVariants = false)
    {
        // Strip the minus in '-title' if allowed..
        if ($allowVariants) {
            if ((strlen($name) > 0) && ($name[0] == "-")) {
                $name = substr($name, 1);
            }
            $name = $this->getFieldName($name);
        }

        // Check if the $name is in the contenttype's fields.
        if (isset($contenttype['fields'][$name])) {
            return true;
        }

        if (in_array($name, Content::getBaseColumns())) {
            return true;
        }

        return false;

    }

    /**
     * Get field name, stripping possible " DESC" " ASC" etc.
     *
     * @param  string $name
     * @return string
     */
    private function getFieldName($name)
    {
        return preg_replace("/ (desc|asc)$/i", "", $name);
    }

    /**
     * Get an escaped sortorder for use in SQL, from a fieldname like 'title' or '-id'.
     *
     * for example, -id returns `r`.`id` DESC
     *
     * @param  string $name
     * @param string $prefix
     * @return string
     */
    private function getEscapedSortorder($name, $prefix = 'r')
    {
        list ($name, $asc) = $this->getSortOrder($name);

        // If we don't have a name, we can't determine a sortorder.
        if (empty($name)) {
            return false;
        }

        if( strpos($name, 'RAND') !== false ) {
            $order = $name;
        } elseif ($prefix !== false) {
            $order = $this->app['db']->quoteIdentifier($prefix . '.' . $name);
        } else {
            $order = $this->app['db']->quoteIdentifier($name);
        }

        if (!$asc) {
            $order .= " DESC";
        }

        return $order;

    }

    /**
     * Get sorting order of name, stripping possible " DESC" " ASC" etc., and
     * also return the sorting order
     *
     * @param  string $name
     * @return string
     */
    public function getSortOrder($name)
    {
        // If we don't get a string, we can't determine a sortorder.
        if (!is_string($name)) {
            return false;
        }

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
     * Helper function for sorting Records of content that have a Grouping.
     *
     * @param  object $a
     * @param  object $b
     * @return int
     */
    private function groupingSort($a, $b)
    {
        // Same group, sort within group..
        if ($a->group['slug'] == $b->group['slug']) {

            if (!empty($a->sortorder) || !empty($b->sortorder)) {
                if (empty($a->sortorder)) {
                    return -1;
                } elseif (empty($b->sortorder)) {
                    return 1;
                } else {
                    return ($a->sortorder < $b->sortorder) ? -1 : 1;
                }
            }

            // Same group, so we sort on contenttype['sort']
            list($second_sort, $order) = $this->getSortOrder($a->contenttype['sort']);

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
     *
     * @param  string $key
     * @param  string $value
     * @param  mixed $fieldtype
     * @return string
     */
    private function parseWhereParameter($key, $value, $fieldtype = false)
    {

        $value = trim($value);

        // check if we need to split..
        if (strpos($value, " || ") !== false) {
            $values = explode(" || ", $value);
            foreach ($values as $index => $value) {
                $values[$index] = $this->parseWhereParameter($key, $value, $fieldtype);
            }

            return "( " . implode(" OR ", $values) . " )";

        } elseif (strpos($value, " && ") !== false) {
            $values = explode(" && ", $value);
            foreach ($values as $index => $value) {
                $values[$index] = $this->parseWhereParameter($key, $value, $fieldtype);
            }

            return "( " . implode(" AND ", $values) . " )";
        }

        // Set the correct operator for the where clause
        $operator = "=";

        $first = substr($value, 0, 1);

        if ($first == "!") {
            $operator = "!=";
            $value = substr($value, 1);
        } elseif (substr($value, 0, 2) == "<=") {
            $operator = "<=";
            $value = substr($value, 2);
        } elseif (substr($value, 0, 2) == ">=") {
            $operator = ">=";
            $value = substr($value, 2);
        } elseif ($first == "<") {
            $operator = "<";
            $value = substr($value, 1);
        } elseif ($first == ">") {
            $operator = ">";
            $value = substr($value, 1);
        } elseif ($first == "%" || substr($value, -1) == "%") {
            $operator = "LIKE";
        }

        // Use strtotime to allow selections like "< last monday" or "this year"
        if (in_array($fieldtype, array('date', 'datetime')) && ($timestamp = strtotime($value)) !== false) {
            $value = date('Y-m-d H:i:s', $timestamp);
        }

        $parameter = sprintf("%s %s %s", $this->app['db']->quoteIdentifier($key), $operator, $this->app['db']->quote($value));

        return $parameter;
    }


    /**
     * Get the contenttype as an array, based on the given $contenttypeslug
     *
     * @param  string $contenttypeslug
     * @return bool|array
     */
    public function getContentType($contenttypeslug)
    {

        $contenttypeslug = makeSlug($contenttypeslug);

        // Return false if empty, can't find it..
        if (empty($contenttypeslug)) {
            return false;
        }

        // See if we've either given the correct contenttype, or try to find it by name or singular_name.
        if ($this->app['config']->get('contenttypes/' . $contenttypeslug)) {
            $contenttype = $this->app['config']->get('contenttypes/' . $contenttypeslug);
        } else {
            foreach ($this->app['config']->get('contenttypes') as $key => $ct) {
                if (isset($ct['singular_slug']) && ($contenttypeslug == $ct['singular_slug'])) {
                    $contenttype = $this->app['config']->get('contenttypes/' . $key);
                    break;
                }
                if ($contenttypeslug == makeSlug($ct['singular_name']) || $contenttypeslug == makeSlug($ct['name'])) {
                    $contenttype = $this->app['config']->get('contenttypes/' . $key);
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

    /**
     * Get the taxonomy as an array, based on the given $taxonomyslug
     *
     * @param  string $taxonomyslug
     * @return bool|array
     */
    public function getTaxonomyType($taxonomyslug)
    {

        $taxonomyslug = makeSlug($taxonomyslug);

        // Return false if empty, can't find it..
        if (empty($taxonomyslug)) {
            return false;
        }

        // See if we've either given the correct contenttype, or try to find it by name or singular_name.
        if ($this->app['config']->get('taxonomy/' . $taxonomyslug)) {
            $taxonomytype = $this->app['config']->get('taxonomy/' . $taxonomyslug);
        } else {
            foreach ($this->app['config']->get('taxonomy') as $key => $tt) {
                if (isset($tt['singular_slug']) && ($taxonomyslug == $tt['singular_slug'])) {
                    $taxonomytype = $this->app['config']->get('taxonomy/' . $key);
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
        return array_keys($this->app['config']->get('contenttypes'));

    }

    /**
     * Get a value to use in 'assert() with the available contenttypes
     *
     * @param bool $includesingular
     * @return string $contenttypes
     */
    public function getContentTypeAssert($includesingular = false)
    {

        $slugs = array();
        foreach ($this->app['config']->get('contenttypes') as $type) {
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
     * @param bool $includesingular
     * @return string $taxonomytypes
     */
    public function getTaxonomyTypeAssert($includesingular = false)
    {

        $taxonomytypes = $this->app['config']->get('taxonomy');

        // No taxonomies, nothing to assert. The route _DOES_ expect a string, so
        // we return a regex that never matches.
        if (empty($taxonomytypes)) {
            return "$.";
        }

        $slugs = array();
        foreach ($taxonomytypes as $type) {
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
     * Get the fieldtype for a given contenttype and fieldname
     *
     * @param  string $contenttypeslug
     * @param  string $fieldname
     * @return array  $fields
     */
    public function getContentTypeFieldType($contenttypeslug, $fieldname)
    {

        $contenttype = $this->getContentType($contenttypeslug);

        if (in_array($fieldname, array('datecreated', 'datechanged', 'datepublish', 'datedepublish'))) {
            return "datetime";
        } else if (isset($contenttype['fields'][$fieldname]['type'])) {
            return $contenttype['fields'][$fieldname]['type'];
        } else {
            return false;
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
            if ($taxo['behaves_like'] == "grouping") {
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
                if ($this->app['config']->get('taxonomy/' . $key)) {
                    $taxonomy[$key] = $this->app['config']->get('taxonomy/' . $key);
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
        $contenttype = $content[util::array_first_key($content)]->contenttype['slug'];

        $taxonomytypes = $this->app['config']->get('taxonomy');

        // If there are no taxonomytypes, there won't be any results, so we return.
        if (empty($taxonomytypes)) {
            return;
        }

        $query = sprintf(
            "SELECT * FROM %s WHERE content_id IN (?) AND contenttype=? AND taxonomytype IN (?)",
            $tablename
        );
        $rows = $this->app['db']->executeQuery(
            $query,
            array($ids, $contenttype, array_keys($taxonomytypes)),
            array(DoctrineConn::PARAM_INT_ARRAY, \PDO::PARAM_STR, DoctrineConn::PARAM_STR_ARRAY)
        )->fetchAll();

        foreach ($rows as $key => $row) {
            $content[$row['content_id']]->setTaxonomy($row['taxonomytype'], $row['slug'], $row['sortorder']);
        }

        foreach ($content as $key => $value) {
            $content[$key]->sortTaxonomy();
        }

    }

    /**
     * Update / insert taxonomy for a given content-unit.
     *
     * @param string $contenttype
     * @param integer $content_id
     * @param array $taxonomy
     */
    protected function updateTaxonomy($contenttype, $content_id, $taxonomy)
    {

        $tablename = $this->getTablename("taxonomy");
        $configTaxonomies = $this->app['config']->get('taxonomy');

        // Make sure $contenttypeslug is a 'slug'
        if (is_array($contenttype)) {
            $contenttypeslug = $contenttype['slug'];
        } else {
            $contenttypeslug = $contenttype;
        }

        // If our contenttype has no taxonomies, there's nothing for us to do here.
        if (!isset($contenttype['taxonomy'])) {
            return;
        }

        foreach ($contenttype['taxonomy'] as $taxonomytype) {

            // Set 'newvalues to 'empty array' if not defined
            if (!empty($taxonomy[$taxonomytype])) {
                $newslugs = $taxonomy[$taxonomytype];
            } else {
                $newslugs = array();
            }

            // Get the current values from the DB..
            $query = sprintf(
                "SELECT id, slug, sortorder FROM %s WHERE content_id=? AND contenttype=? AND taxonomytype=?",
                $tablename
            );
            $currentvalues = $this->app['db']->executeQuery(
                $query,
                array($content_id, $contenttypeslug, $taxonomytype),
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
            foreach ($newslugs as $slug) {

                // If it's like 'desktop#10', split it into value and sortorder..
                list($slug, $sortorder) = explode('#', $slug . "#");

                if (empty($sortorder)) {
                    $sortorder = 0;
                }

                // Make sure we have a 'name'.
                if (isset($configTaxonomies[$taxonomytype]['options'][$slug])) {
                    $name = $configTaxonomies[$taxonomytype]['options'][$slug];
                } else {
                    $name = "";
                }

                if ((!in_array($slug, $currentvalues) || ($currentsortorder != $sortorder)) && (!empty($slug))) {
                    // Insert it!
                    $row = array(
                        'content_id' => $content_id,
                        'contenttype' => $contenttypeslug,
                        'taxonomytype' => $taxonomytype,
                        'slug' => $slug,
                        'name' => $name,
                        'sortorder' => $sortorder
                    );
                    $this->app['db']->insert($tablename, $row);
                }

            }

            // Delete the ones that have been removed.
            foreach ($currentvalues as $id => $slug) {

                // Make it look like 'desktop#10'
                $valuewithorder = $slug . "#" . $currentsortorder;

                if (!in_array($slug, $newslugs) && !in_array($valuewithorder, $newslugs)) {
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
        $contenttype = $content[util::array_first_key($content)]->contenttype['slug'];

        $query = sprintf(
            "SELECT * FROM %s WHERE from_contenttype=? AND from_id IN (?) ORDER BY id",
            $tablename
        );
        $params = array($contenttype, $ids);
        $paramTypes = array(\PDO::PARAM_STR, DoctrineConn::PARAM_INT_ARRAY);
        $rows = $this->app['db']->executeQuery($query, $params, $paramTypes)->fetchAll();

        foreach ($rows as $row) {
            $content[$row['from_id']]->setRelation($row['to_contenttype'], $row['to_id']);
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
            $content[$row['to_id']]->setRelation($row['from_contenttype'], $row['from_id']);
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
     * @param string $contenttype
     * @param integer $content_id
     * @param array $relation
     */
    protected function updateRelation($contenttype, $content_id, $relation)
    {

        $tablename = $this->getTablename("relations");

        // Get the current values from the DB..
        $query = sprintf(
            "SELECT id, to_contenttype, to_id FROM %s WHERE from_id=? AND from_contenttype=?",
            $tablename
        );
        $currentvalues = $this->app['db']->executeQuery(
            $query,
            array($content_id, $contenttype['slug']),
            array(\PDO::PARAM_INT, \PDO::PARAM_STR)
        )->fetchAll();

        // And the other way around..
        $query = sprintf(
            "SELECT id, from_contenttype AS to_contenttype, from_id AS to_id FROM %s WHERE to_id=? AND to_contenttype=?",
            $tablename
        );
        $currentvalues2 = $this->app['db']->executeQuery(
            $query,
            array($content_id, $contenttype['slug']),
            array(\PDO::PARAM_INT, \PDO::PARAM_STR)
        )->fetchAll();

        // Merge them.
        $currentvalues = array_merge($currentvalues, $currentvalues2);

        // Delete the ones that have been removed, but only if the contenttype defines the relations. For if we have
        // example, if we have a relation from 'pages' to 'entries', do not delete them when editing an 'entry'.
        foreach ($currentvalues as $currentvalue) {

            if ((!isset($relation[$currentvalue['to_contenttype']]) ||
                    !in_array($currentvalue['to_id'], $relation[$currentvalue['to_contenttype']])) &&
                isset($contenttype['relations'][$currentvalue['to_contenttype']])
            ) {
                $this->app['db']->delete($tablename, array('id' => $currentvalue['id']));
            }
        }


        // Make an easier array out of $currentvalues.
        $tempvalues = $currentvalues;
        $currentvalues = array();
        foreach ($tempvalues as $tempvalue) {
            $currentvalues[] = $tempvalue['to_contenttype'] . "/" . $tempvalue['to_id'];
        }

        // Add the ones not yet present..
        if (!empty($relation)) {
            foreach ($relation as $to_contenttype => $newvalues) {

                foreach ($newvalues as $value) {

                    if (!in_array($to_contenttype . "/" . $value, $currentvalues) && (!empty($value))) {
                        // Insert it!
                        $row = array(
                            'from_contenttype' => $contenttype['slug'],
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


    public function getLatestId($contenttypeslug) {

        $tablename = $this->getTablename($contenttypeslug);

        // Get the current values from the DB..
        $query = sprintf(
            "SELECT id FROM %s ORDER BY `datecreated` DESC LIMIT 1;",
            $tablename
        );
        $id = $this->app['db']->executeQuery($query)->fetch();

        if (!empty($id['id'])) {
            return $id['id'];
        } else {
            return false;
        }

    }


    public function getUri($title, $id = 0, $contenttypeslug = "", $fulluri = true, $allowempty = true)
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
                $newslug = $slug . '-' . $i;
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
                $slug = trimText($slug, 32, false, false) . "-" . $this->app['randomgenerator']->generateString(6, 'abcdefghijklmnopqrstuvwxyz01234567890');
                $uri = $prefix . $slug;
            }
        }

        // When storing, we should never have an empty slug/URI. If we can't make a nice one, set it to 'slug-XXXX'.
        if (!$allowempty && empty($uri)) {
            $uri = 'slug-' . $this->app['randomgenerator']->generateString(6, 'abcdefghijklmnopqrstuvwxyz01234567890');
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
        if (!empty($this->app['tables'])) {
            return $this->app['tables'];
        }

        $sm = $this->app['db']->getSchemaManager();

        $this->tables = array();

        foreach ($sm->listTables() as $table) {
            if (strpos($table->getName(), $this->prefix) === 0) {
                foreach ($table->getColumns() as $column) {
                    $this->tables[$table->getName()][$column->getName()] = $column->getType();
                }
                // $output[] = "Found table <tt>" . $table->getName() . "</tt>.";
            }
        }

        // @todo: Fix this!! Move this to '$app->config'..
        $this->app['tables'] = $this->tables;

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


    protected function hasRecords($tablename)
    {

        $count = $this->app['db']->fetchColumn('SELECT COUNT(id) FROM ' . $tablename);

        return intval($count) > 0;

    }

}
