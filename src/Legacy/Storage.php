<?php

namespace Bolt\Legacy;

use Bolt;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Exception\StorageException;
use Bolt\Helpers\Arr;
use Bolt\Helpers\Html;
use Bolt\Helpers\Str;
use Bolt\Pager;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\Connection as DoctrineConn;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use utilphp\util;

class Storage
{
    /** @var array */
    public $images;

    /** @var Application */
    private $app;

    /** @var array */
    private $tables = [];

    /** @var string */
    private $prefix;

    /** @var array */
    private $checkedfortimed = [];

    /** @var bool Test to indicate if we're inside a dispatcher. */
    private $inDispatcher = false;

    /** @var array */
    protected static $pager = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get an object for the content of a specific contenttype. This will be
     * \Bolt\Legacy\Content, unless the contenttype defined another class to be
     * used.
     *
     * @param array|string $contenttype
     * @param array        $values
     *
     * @throws \Exception
     *
     * @return \Bolt\Legacy\Content
     */
    public function getContentObject($contenttype, $values = [])
    {
        // Make sure $contenttype is an array, and not just the slug.
        if (!is_array($contenttype)) {
            $contenttype = $this->getContentType($contenttype);
        }

        // If the contenttype has a 'class' specified, and the class exists,
        // Initialize the content as an object of that class.
        if (!empty($contenttype['class']) && class_exists($contenttype['class'])) {
            $content = new $contenttype['class']($this->app, $contenttype, $values);

            // Check if the class actually extends \Bolt\Legacy\Content.
            if (!($content instanceof Content)) {
                throw new \Exception($contenttype['class'] . ' does not extend \\Bolt\\Legacy\\Content.');
            }
        } else {
            $content = new Content($this->app, $contenttype, $values);
        }

        return $content;
    }

    /**
     * Add some records with dummy content.
     *
     * Only fill the contenttypes passed as parameters
     * If the parameters is empty, only fill empty tables
     *
     * @see preFillSingle
     *
     * @param array $contenttypes
     *
     * @return string
     */
    public function preFill($contenttypes = [])
    {
        $output = '';

        // get a list of images.
        $this->images = $this->app['filesystem']->search('*', 'jpg,jpeg,png');

        $emptyOnly = empty($contenttypes);

        foreach ($this->app['config']->get('contenttypes') as $key => $contenttype) {
            $tablename = $this->getContenttypeTablename($contenttype);
            if ($emptyOnly && $this->hasRecords($tablename)) {
                $output .= Trans::__('Skipped <tt>%key%</tt> (already has records)', ['%key%' => $key]) . "<br>\n";
                continue;
            } elseif (!in_array($key, $contenttypes) && !$emptyOnly) {
                $output .= Trans::__('Skipped <tt>%key%</tt> (not checked)', ['%key%' => $key]) . "<br>\n";
                continue;
            }

            $amount = isset($contenttype['prefill']) ? $contenttype['prefill'] : 5;

            for ($i = 1; $i <= $amount; $i++) {
                $output .= $this->preFillSingle($key, $contenttype);
            }
        }

        $output .= "<br>\n\n" . Trans::__('Done!');

        return $output;
    }

    /**
     * Add a record with dummy content.
     *
     * @see preFill
     *
     * @param $key
     * @param $contenttype
     *
     * @return string
     */
    private function preFillSingle($key, $contenttype)
    {
        $content = [];
        $title = '';

        $content['contenttype'] = $key;
        $content['datecreated'] = date('Y-m-d H:i:s', time() - rand(0, 365 * 24 * 60 * 60));
        $content['datepublish'] = date('Y-m-d H:i:s', time() - rand(0, 365 * 24 * 60 * 60));
        $content['datedepublish'] = null;

        $username = array_rand($this->app['users']->getUsers(), 1);
        $user = $this->app['users']->getUser($username);

        $content['ownerid'] = $user['id'];

        $content['status'] = 'published';

        foreach ($contenttype['fields'] as $field => $values) {
            switch ($values['type']) {
                case 'text':
                    $content[$field] = trim(strip_tags($this->app['prefill']->get('/1/veryshort')));
                    if (empty($title)) {
                        $title = $content[$field];
                    }
                    break;
                case 'image':
                    // Get a random image
                    if (!empty($this->images)) {
                        $content[$field]['file'] = $this->images[array_rand($this->images)];
                    }
                    break;
                case 'html':
                case 'textarea':
                case 'markdown':
                    if (in_array($field, ['teaser', 'introduction', 'excerpt', 'intro'])) {
                        $params = '/medium/decorate/link/1';
                    } else {
                        $params = '/medium/decorate/link/ol/ul/3';
                    }

                    $content[$field] = trim($this->app['prefill']->get($params));
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
                    $content[$field] = rand(0, 1);
                    break;
                case 'float':
                case 'number': // number is deprecated
                case 'integer':
                    $content[$field] = rand(-1000, 1000) + (rand(0, 1000) / 1000);
                    break;
            }
        }

        $content['title'] = rtrim($content['title'], '.,;:');
        $content['slug'] = $this->app['slugify']->slugify($content['title']);

        $contentobject = $this->getContentObject($contenttype);
        $contentobject->setValues($content);

        if (!empty($contenttype['taxonomy'])) {
            foreach ($contenttype['taxonomy'] as $taxonomy) {
                if ($this->app['config']->get('taxonomy/' . $taxonomy . '/options')) {
                    $options = $this->app['config']->get('taxonomy/' . $taxonomy . '/options');
                    $key = array_rand($options);
                    $contentobject->setTaxonomy($taxonomy, $key, $options[$key], rand(1, 1000));
                }
                if ($this->app['config']->get('taxonomy/' . $taxonomy . '/behaves_like') == 'tags') {
                    $contentobject->setTaxonomy($taxonomy, $this->getSomeRandomTags(5));
                }
            }
        }

        $this->saveContent($contentobject);

        $output = Trans::__(
            "Added to <tt>%key%</tt> '%title%'",
            ['%key%' => $key, '%title%' => $contentobject->getTitle()]
        ) . "<br>\n";

        return $output;
    }

    /**
     * Get an array of random tags
     *
     * @param integer $num
     *
     * @return string[]
     */
    private function getSomeRandomTags($num = 5)
    {
        $tags = ['action', 'adult', 'adventure', 'alpha', 'animals', 'animation', 'anime', 'architecture', 'art',
            'astronomy', 'baby', 'batshitinsane', 'biography', 'biology', 'book', 'books', 'business', 'business',
            'camera', 'cars', 'cats', 'cinema', 'classic', 'comedy', 'comics', 'computers', 'cookbook', 'cooking',
            'crime', 'culture', 'dark', 'design', 'digital', 'documentary', 'dogs', 'drama', 'drugs', 'education',
            'environment', 'evolution', 'family', 'fantasy', 'fashion', 'fiction', 'film', 'fitness', 'food',
            'football', 'fun', 'gaming', 'gift', 'health', 'hip', 'historical', 'history', 'horror', 'humor',
            'illustration', 'inspirational', 'internet', 'journalism', 'kids', 'language', 'law', 'literature', 'love',
            'magic', 'math', 'media', 'medicine', 'military', 'money', 'movies', 'mp3', 'murder', 'music', 'mystery',
            'news', 'nonfiction', 'nsfw', 'paranormal', 'parody', 'philosophy', 'photography', 'photos', 'physics',
            'poetry', 'politics', 'post-apocalyptic', 'privacy', 'psychology', 'radio', 'relationships', 'research',
            'rock', 'romance', 'rpg', 'satire', 'science', 'sciencefiction', 'scifi', 'security', 'self-help',
            'series', 'software', 'space', 'spirituality', 'sports', 'story', 'suspense', 'technology', 'teen',
            'television', 'terrorism', 'thriller', 'travel', 'tv', 'uk', 'urban', 'us', 'usa', 'vampire', 'video',
            'videogames', 'war', 'web', 'women', 'world', 'writing', 'wtf', 'zombies'];

        shuffle($tags);

        $picked = array_slice($tags, 0, $num);

        return $picked;
    }

    /**
     * Writes a content-changelog entry for a newly-created entry.
     *
     * @param string  $contenttype Slug of the record contenttype
     * @param integer $contentid   ID of the record
     * @param array   $content     Record values
     * @param string  $comment     Editor's comment
     */
    private function logInsert($contenttype, $contentid, $content, $comment = null)
    {
        $this->app['logger.change']->info(
            'Insert record',
            [
                'action'      => 'INSERT',
                'contenttype' => $contenttype,
                'id'          => $contentid,
                'new'         => $content,
                'old'         => null,
                'comment'     => $comment
            ]
        );
    }

    /**
     * Writes a content-changelog entry for an updated entry.
     * This function must be called *before* the actual update, because it
     * fetches the old content from the database.
     *
     * @param string  $contenttype Slug of the record contenttype
     * @param integer $contentid   ID of the record
     * @param array   $newContent  New record values
     * @param array   $oldContent  Old record values
     * @param string  $comment     Editor's comment
     */
    private function logUpdate($contenttype, $contentid, $newContent, $oldContent = null, $comment = null)
    {
        $this->app['logger.change']->info(
            'Update record',
            [
                'action'      => 'UPDATE',
                'contenttype' => $contenttype,
                'id'          => $contentid,
                'new'         => $newContent,
                'old'         => $oldContent,
                'comment'     => $comment
            ]
        );
    }

    /**
     * Writes a content-changelog entry for a deleted entry.
     * This function must be called *before* the actual update, because it.
     *
     * @param string  $contenttype Slug of the record contenttype
     * @param integer $contentid   ID of the record
     * @param array   $content     Record values
     * @param string  $comment     Editor's comment
     */
    private function logDelete($contenttype, $contentid, $content, $comment = null)
    {
        $this->app['logger.change']->info(
            'Delete record',
            [
                'action'      => 'DELETE',
                'contenttype' => $contenttype,
                'id'          => $contentid,
                'new'         => null,
                'old'         => $content,
                'comment'     => $comment
            ]
        );
    }

    /**
     * Save a record.
     *
     * @param Content $content
     * @param string  $comment
     *
     * @throws \Bolt\Exception\StorageException
     *
     * @return int
     */
    public function saveContent(Content $content, $comment = null)
    {
        $contenttype = $content->contenttype;
        $fieldvalues = $content->values;

        if (empty($contenttype)) {
            $this->app['logger.system']->error('Contenttype is required for ' . __FUNCTION__, ['event' => 'exception']);
            throw new StorageException('Contenttype is required for ' . __FUNCTION__);
        }

        // Test to see if this is a new record, or an update
        if (empty($fieldvalues['id'])) {
            $create = true;
        } else {
            $create = false;
        }

        // We need to verify if the slug is unique. If not, we update it.
        $getId = $create ? null : $fieldvalues['id'];
        $fieldvalues['slug'] = $this->getUri($fieldvalues['slug'], $getId, $contenttype['slug'], false, false);

        // Update the content object
        $content->setValues($fieldvalues);

        // Dispatch pre-save event
        if (! $this->inDispatcher && $this->app['dispatcher']->hasListeners(StorageEvents::PRE_SAVE)) {
            $event = new StorageEvent($content, ['contenttype' => $contenttype, 'create' => $create]);
            $this->app['dispatcher']->dispatch(StorageEvents::PRE_SAVE, $event);
        }

        // Decide whether to insert a new record, or update an existing one.
        if ($create) {
            $this->insertContent($content, $comment);
        } else {
            $this->updateContent($content, $comment);
        }

        // Update taxonomy and record relationships
        $this->updateTaxonomy($contenttype, $content->values['id'], $content->taxonomy);
        $this->updateRelation($contenttype, $content->values['id'], $content->relation);

        // Dispatch post-save event
        if (!$this->inDispatcher && $this->app['dispatcher']->hasListeners(StorageEvents::POST_SAVE)) {
            // Block loops
            $this->inDispatcher = true;

            $event = new StorageEvent($content, ['contenttype' => $contenttype, 'create' => $create]);
            $this->app['dispatcher']->dispatch(StorageEvents::POST_SAVE, $event);

            // Re-enable the dispatcher
            $this->inDispatcher = false;
        }

        return $content->values['id'];
    }

    /**
     * Delete a record.
     *
     * @param string  $contenttype
     * @param integer $id
     *
     * @throws \Bolt\Exception\StorageException
     *
     * @return integer The number of affected rows.
     */
    public function deleteContent($contenttype, $id)
    {
        if (empty($contenttype)) {
            $this->app['logger.system']->error('Contenttype is required for' . __FUNCTION__, ['event' => 'exception']);
            throw new StorageException('Contenttype is required for ' . __FUNCTION__);
        }

        // Make sure $contenttype is a 'slug'
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        // Get the old content record
        $tablename = $this->getContenttypeTablename($contenttype);
        $oldContent = $this->findContent($tablename, $id);

        // Dispatch pre-delete event
        if ($this->app['dispatcher']->hasListeners(StorageEvents::PRE_DELETE)) {
            $event = new StorageEvent($oldContent, ['contenttype' => $contenttype]);
            $this->app['dispatcher']->dispatch(StorageEvents::PRE_DELETE, $event);
        }

        $this->logDelete($contenttype, $id, $oldContent);

        $res = $this->app['db']->delete($tablename, ['id' => $id]);

        // Make sure relations and taxonomies are deleted as well.
        if ($res) {
            $this->app['db']->delete($this->getTablename('relations'), ['from_contenttype' => $contenttype, 'from_id' => $id]);
            $this->app['db']->delete($this->getTablename('relations'), ['to_contenttype' => $contenttype, 'to_id' => $id]);
            $this->app['db']->delete($this->getTablename('taxonomy'), ['contenttype' => $contenttype, 'content_id' => $id]);
        }

        // Dispatch post-delete event
        if ($this->app['dispatcher']->hasListeners(StorageEvents::POST_DELETE)) {
            $event = new StorageEvent($oldContent, ['contenttype' => $contenttype]);
            $this->app['dispatcher']->dispatch(StorageEvents::POST_DELETE, $event);
        }

        return $res;
    }

    /**
     * Insert a new contenttype record in the database.
     *
     * @param \Bolt\Legacy\Content $content Record content to insert
     * @param string               $comment Editor's comment
     *
     * @return boolean
     */
    protected function insertContent(Content $content, $comment = null)
    {
        $tablename = $this->getContenttypeTablename($content->contenttype);

        // Set creation and update dates
        $content->setValue('datecreated', date('Y-m-d H:i:s'));
        $content->setValue('datechanged', date('Y-m-d H:i:s'));

        // id is set to autoincrement, so let the DB handle it
        unset($content->values['id']);

        // Get the JSON database prepared values and make sure it's valid
        $fieldvalues = $this->getValidSaveData($content->getValues(true), $content->contenttype);

        $this->app['db']->insert($tablename, $fieldvalues);

        $seq = null;
        if ($this->app['db']->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $seq = $tablename . '_id_seq';
        }

        $id = $this->app['db']->lastInsertId($seq);

        if ($id > 0) {
            $content->setValue('id', $id);
            $this->logInsert($content->contenttype['slug'], $id, $fieldvalues, $comment);

            return true;
        }
    }

    /**
     * Update a Bolt contenttype record.
     *
     * @param \Bolt\Legacy\Content $content The content object to be updated
     * @param string               $comment Add a comment to save with change.
     *
     * @throws \Bolt\Exception\StorageException
     *
     * @return bool
     */
    private function updateContent(Content $content, $comment = null)
    {
        $tablename = $this->getContenttypeTablename($content->contenttype);

        // Set the date the record was changed
        $content->setValue('datechanged', date('Y-m-d H:i:s'));

        // Test that the record exists in the database
        $oldContent = $this->findContent($tablename, $content['id']);
        if (empty($oldContent)) {
            throw new StorageException('Attempted to update a non-existent record');
        }

        // Get the JSON database prepared values and make sure it's valid
        $fieldvalues = $this->getValidSaveData($content->getValues(true), $content->contenttype);

        // Do the actual update, and log it.
        $res = $this->app['db']->update($tablename, $fieldvalues, ['id' => $content['id']]);
        if ($res > 0) {
            $this->logUpdate($content->contenttype['slug'], $content['id'], $fieldvalues, $oldContent, $comment);

            return true;
        }
    }

    /**
     * Get a valid array to commit.
     *
     * @param array $fieldvalues
     * @param array $contenttype
     *
     * @return array
     */
    private function getValidSaveData(array $fieldvalues, array $contenttype)
    {
        // Clean up fields, check unneeded columns.
        foreach (array_keys($fieldvalues) as $key) {
            if ($this->isValidColumn($key, $contenttype)) {
                if (is_string($fieldvalues[$key])) {
                    // Trim strings
                    $fieldvalues[$key] = trim($fieldvalues[$key]);
                } elseif (is_bool($fieldvalues[$key])) {
                    // Convert literal booleans to 0/1 to ensure cross-db consistency
                    $fieldvalues[$key] = (int)$fieldvalues[$key];
                }
            } else {
                // unset columns we don't need to store.
                unset($fieldvalues[$key]);
            }
        }

        return $fieldvalues;
    }

    /**
     * Update a single value from content.
     *
     * It is called in list of contents.
     *
     * @param string $contenttype Content Type to be edited.
     * @param int    $id          Id of content to be updated.
     * @param string $field       Field name of content to be changed.
     * @param mixed  $value       New value to be defined on field.
     *
     * @return bool Returns true when update is done or false if not.
     */
    public function updateSingleValue($contenttype, $id, $field, $value)
    {
        $id = intval($id);

        if (!$this->isValidColumn($field, $contenttype)) {
            $error = Trans::__('contenttypes.generic.invalid-field', ['%field%' => $field, '%contenttype%' => $contenttype]);
            $this->app['logger.flash']->error($error);

            return false;
        }

        $content = $this->getContent("$contenttype/$id");

        $content->setValue($field, $value);

        $comment = Trans::__(
            'The field %field% has been changed to "%newValue%"',
            [
                '%field%'    => $field,
                '%newValue%' => $value
            ]
        );

        $result = $this->saveContent($content, $comment);

        return $result;
    }

    public function getEmptyContent($contenttypeslug)
    {
        $content = $this->getContentObject($contenttypeslug);

        // don't use 'undefined contenttype' as title/name
        $content->setValues(['name' => '', 'title' => '']);

        return $content;
    }

    /**
     * Decode search query into searchable parts.
     */
    private function decodeSearchQuery($q)
    {
        $words = preg_split('|[\r\n\t ]+|', trim($q));

        $words = array_map(
            function ($word) {
                return mb_strtolower($word);
            },
            $words
        );
        $words = array_filter(
            $words,
            function ($word) {
                return strlen($word) >= 2;
            }
        );

        return [
            'valid' => count($words) > 0,
            'in_q'  => $q,
            'use_q' => implode(' ', $words),
            'words' => $words
        ];
    }

    /**
     * Search through a single contenttype.
     *
     * Search, weigh and return the results.
     *
     * @param       $query
     * @param       $contenttype
     * @param       $fields
     * @param array $filter
     *
     * @return \Bolt\Legacy\Content
     */
    private function searchSingleContentType($query, $contenttype, $fields, array $filter = null)
    {
        // This could be even more configurable
        // (see also Content->getFieldWeights)
        $searchableTypes = ['text', 'textarea', 'html', 'markdown'];
        $table = $this->getContenttypeTablename($contenttype);

        // Build fields 'WHERE'
        $fieldsWhere = [];
        foreach ($fields as $field => $fieldconfig) {
            if (in_array($fieldconfig['type'], $searchableTypes)) {
                foreach ($query['words'] as $word) {
                    $fieldsWhere[] = sprintf('%s.%s LIKE %s', $table, $field, $this->app['db']->quote('%' . $word . '%'));
                }
            }
        }

        // make taxonomies work
        $taxonomytable = $this->getTablename('taxonomy');
        $taxonomies    = $this->getContentTypeTaxonomy($contenttype);
        $tagsWhere     = [];
        $tagsQuery     = '';
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['behaves_like'] == 'tags') {
                foreach ($query['words'] as $word) {
                    $tagsWhere[] = sprintf('%s.slug LIKE %s', $taxonomytable, $this->app['db']->quote('%' . $word . '%'));
                }
            }
        }
        // only add taxonomies if they exist
        if (!empty($taxonomies) && !empty($tagsWhere)) {
            $tagsQueryA = sprintf("%s.contenttype = '%s'", $taxonomytable, $contenttype);
            $tagsQueryB = implode(' OR ', $tagsWhere);
            $tagsQuery   = sprintf(' OR (%s AND (%s))', $tagsQueryA, $tagsQueryB);
        }

        // Build filter 'WHERE"
        // @todo make relations work as well
        $filterWhere = [];
        if (!is_null($filter)) {
            foreach ($fields as $field => $fieldconfig) {
                if (isset($filter[$field])) {
                    $filterWhere[] = $this->parseWhereParameter($table . '.' . $field, $filter[$field]);
                }
            }
        }

        // Build actual where
        $where = [];
        $where[] = sprintf("%s.status = 'published'", $table);
        $where[] = '(( ' . implode(' OR ', $fieldsWhere) . ' ) ' . $tagsQuery . ' )';
        $where = array_merge($where, $filterWhere);

        // Build SQL query
        $select = sprintf(
            'SELECT %s.id FROM %s LEFT JOIN %s ON %s.id = %s.content_id WHERE %s GROUP BY %s.id',
            $table,
            $table,
            $taxonomytable,
            $table,
            $taxonomytable,
            implode(' AND ', $where),
            $table
        );

        // Run Query
        $results = $this->app['db']->fetchAll($select);

        if (!empty($results)) {
            $ids = implode(' || ', util::array_pluck($results, 'id'));

            $results = $this->getContent($contenttype, ['id' => $ids, 'returnsingle' => false]);

            // Convert and weight
            foreach ($results as $result) {
                $result->weighSearchResult($query);
            }
        }

        return $results;
    }

    /**
     * Compare by search weights.
     *
     * Or fallback to dates or title
     *
     * @param \Bolt\Legacy\Content $a
     * @param \Bolt\Legacy\Content $b
     *
     * @return int
     */
    private function compareSearchWeights(Content $a, Content $b)
    {
        if ($a->getSearchResultWeight() > $b->getSearchResultWeight()) {
            return -1;
        }
        if ($a->getSearchResultWeight() < $b->getSearchResultWeight()) {
            return 1;
        }
        if ($a['datepublish'] > $b['datepublish']) {
            // later is more important
            return -1;
        }
        if ($a['datepublish'] < $b['datepublish']) {
            // earlier is less important
            return 1;
        }

        return strcasecmp($a['title'], $b['title']);
    }

    /**
     * Search through actual content.
     *
     * Unless the query is invalid it will always return a 'result array'. It may
     * complain in the log but it won't abort.
     *
     * @param string  $q            Search string
     * @param array   $contenttypes Contenttype names to search for:
     *                              - string: Specific contenttype
     *                              - null:   Every searchable contenttype
     * @param array   $filters      Additional filters for contenttypes
     *                              - key is contenttype
     *                              - value is filter
     * @param integer $limit        limit the number of results
     * @param integer $offset       skip this number of results
     *
     * @return mixed false if query is invalid, an array with results if query was executed
     */
    public function searchContent($q, array $contenttypes = null, array $filters = null, $limit = 9999, $offset = 0)
    {
        $query = $this->decodeSearchQuery($q);
        if (!$query['valid']) {
            return false;
        }

        $appCt = $this->app['config']->get('contenttypes');

        // By default we only search through searchable contenttypes
        if (is_null($contenttypes)) {
            $contenttypes = array_keys($appCt);
            $contenttypes = array_filter(
                $contenttypes,
                function ($ct) use ($appCt) {
                    if ((isset($appCt[$ct]['searchable']) && $appCt[$ct]['searchable'] === false) ||
                        (isset($appCt[$ct]['viewless']) && $appCt[$ct]['viewless'] === true)
                    ) {
                        return false;
                    }

                    return true;
                }
            );
            $contenttypes = array_map(
                function ($ct) use ($appCt) {
                    return $appCt[$ct]['slug'];
                },
                $contenttypes
            );
        }

        // Build our search results array
        $results = [];
        foreach ($contenttypes as $contenttype) {
            $ctconfig = $this->getContentType($contenttype);

            $fields = $ctconfig['fields'];
            $filter = null;

            if (is_array($filters) && isset($filters[$contenttype])) {
                $filter = $filters[$contenttype];
            }

            $subResults = $this->searchSingleContentType($query, $contenttype, $fields, $filter);

            $results = array_merge($results, $subResults);
        }

        // Sort the results
        usort($results, [$this, 'compareSearchWeights']);

        $noOfResults = count($results);

        $pageResults = [];
        if ($offset < $noOfResults) {
            $pageResults = array_slice($results, $offset, $limit);
        }

        return [
            'query'         => $query,
            'no_of_results' => $noOfResults,
            'results'       => $pageResults
        ];
    }

    public function searchAllContentTypes(array $parameters = [], &$pager = [])
    {
        // Note: we can only apply this kind of results aggregating when we don't
        // use LIMIT and OFFSET! If we'd want to use it, this should be rewritten.
        // Results aggregator
        $result = [];

        foreach ($this->getContentTypes() as $contenttype) {
            $contentTypeSearchResults = $this->searchContentType($contenttype, $parameters, $pager);
            foreach ($contentTypeSearchResults as $searchresult) {
                $result[] = $searchresult;
            }
        }

        return $result;
    }

    public function searchContentType($contenttypename, array $parameters = [], &$pager = [])
    {
        $where = [];
        $tablename = $this->getContenttypeTablename($contenttypename);

        $contenttype = $this->app['config']->get('contenttypes/' . $contenttypename);

        // If this contenttype has 'searchable: false', we skip it.
        if (isset($contenttype['searchable']) && $contenttype['searchable'] === false) {
            return [];
        }

        // for all the non-reserved parameters that are fields, we assume people want to do a 'where'
        foreach ($parameters as $key => $value) {
            if (in_array($key, ['order', 'where', 'limit', 'offset'])) {
                continue; // Skip this one.
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

            $filterWhere = [];

            foreach ($contenttype['fields'] as $key => $value) {
                if (in_array($value['type'], ['text', 'textarea', 'html', 'markdown'])) {
                    $filterWhere[] = sprintf("%s LIKE '%%%s%%'", $key, $filter);
                }
            }

            if (!empty($filterWhere)) {
                $where[] = '(' . implode(' OR ', $filterWhere) . ')';
            }
        }

        $limit = !empty($parameters['limit']) ? $parameters['limit'] : 9999;
        $page = !empty($parameters['page']) ? $parameters['page'] : 1;

        // If we're allowed to use pagination, use the 'page' parameter.
        if (!empty($parameters['paging']) && $this->app->raw('request') instanceof Request) {
            $page = $this->app['request']->get('page', $page);
        }

        $queryparams = "";

        // implode 'where'
        if (!empty($where)) {
            $queryparams .= sprintf(' WHERE (%s)', implode(" AND ", $where));
        }

        // Order, with a special case for 'RANDOM'.
        if (!empty($parameters['order'])) {
            if ($parameters['order'] == "RANDOM") {
                $dboptions = $this->app['db']->getParams();
                $queryparams .= sprintf(' ORDER BY %s', $dboptions['randomfunction']);
            } else {
                $order = $this->getEscapedSortorder($parameters['order'], false);
                if (!empty($order)) {
                    $queryparams .= sprintf(' ORDER BY %s', $order);
                }
            }
        }

        // Make the query for the pager.
        $pagerquery = sprintf('SELECT COUNT(*) AS count FROM %s %s', $tablename, $queryparams);

        // Add the limit
        $queryparams = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($queryparams, $limit, ($page - 1) * $limit);

        // Make the query to get the results.
        $query = "SELECT * FROM $tablename" . $queryparams;

        $rows = $this->app['db']->fetchAll($query);

        // Make sure content is set, and all content has information about its contenttype
        $content = [];
        foreach ($rows as $row) {
            $content[$row['id']] = $this->getContentObject($contenttype, $row);
        }

        // TODO: Check if we need to hydrate here!

        // Make sure all content has their taxonomies and relations
        $this->getTaxonomy($content);
        $this->getRelation($content);

        // Set up the $pager array with relevant values.
        $rowcount = $this->app['db']->executeQuery($pagerquery)->fetch();
        $pager = [
            'for'          => 'search',
            'count'        => $rowcount['count'],
            'totalpages'   => ceil($rowcount['count'] / $limit),
            'current'      => $page,
            'showing_from' => ($page - 1) * $limit + 1,
            'showing_to'   => ($page - 1) * $limit + count($content)
        ];

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
     * @param string $taxonomyslug
     * @param string $name
     * @param string $parameters
     *
     * @return array
     */
    public function getContentByTaxonomy($taxonomyslug, $name, $parameters = "")
    {
        $tablename = $this->getTablename('taxonomy');

        $slug = $this->app['slugify']->slugify($name);

        $limit = $parameters['limit'] ?: 9999;
        $page = $parameters['page'] ?: 1;

        $taxonomytype = $this->getTaxonomyType($taxonomyslug);

        // No taxonomytype, no possible content.
        if (empty($taxonomytype)) {
            return false;
        }

        $where = sprintf(
            ' WHERE (taxonomytype = %s AND (slug = %s OR name = %s))',
            $this->app['db']->quote($taxonomytype['slug']),
            $this->app['db']->quote($slug),
            $this->app['db']->quote($name)
        );

        // Make the query for the pager.
        $pagerquery = sprintf('SELECT COUNT(*) AS count FROM %s %s', $tablename, $where);

        // Sort on either 'ascending' or 'descending'
        // Make sure we set the order.
        $order = 'ASC';
        $taxonomysort = strtoupper($this->app['config']->get('general/taxonomy_sort'));
        if ($taxonomysort == 'DESC') {
            $order = 'DESC';
        }

        // Add the limit
        $query = sprintf('SELECT * FROM %s %s ORDER BY id %s', $tablename, $where, $order);
        $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, $limit, ($page - 1) * $limit);

        $taxorows = $this->app['db']->fetchAll($query);

        if (!empty($parameters['printquery'])) {
            // @todo formalize this
            echo nl2br(htmlentities($query));
        }

        $content = [];

        if (is_array($taxorows)) {
            foreach ($taxorows as $row) {
                $record = $this->getContent($row['contenttype'] . '/' . $row['content_id']);
                if ($record instanceof Content && !empty($record->id)) {
                    $content[] = $record;
                }
            }
        }

        // Set up the $pager array with relevant values.
        $rowcount = $this->app['db']->executeQuery($pagerquery)->fetch();
        $pager = [
            'for'          => $taxonomytype['singular_slug'] . '_' . $slug,
            'count'        => $rowcount['count'],
            'totalpages'   => ceil($rowcount['count'] / $limit),
            'current'      => $page,
            'showing_from' => ($page - 1) * $limit + 1,
            'showing_to'   => ($page - 1) * $limit + count($taxorows)
        ];

        $this->app['storage']->setPager($taxonomytype['singular_slug'] . '_' . $slug, $pager);

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
        if (isset($this->checkedfortimed['publish-' . $contenttype['slug']])) {
            return;
        }

        $this->checkedfortimed["publish-" . $contenttype['slug']] = true;
        $tablename = $this->getContenttypeTablename($contenttype);

        try {
            // Check if there are any records that need publishing.
            $stmt = $this->app['db']->executeQuery(
                "SELECT id FROM $tablename WHERE status = 'timed' and datepublish < CURRENT_TIMESTAMP"
            );

            // If there's a result, we need to set these to 'publish'.
            if ($stmt->fetch() !== false) {
                // This is where we do black magic voodoo, because `datechanged` has the server
                // time, which is not necessarily the same as `CURRENT_TIMESTAMP()`. Awesome!
                $query = sprintf(
                    "UPDATE %s SET status = 'published', datechanged = '%s' WHERE status = 'timed' and datepublish < CURRENT_TIMESTAMP",
                    $tablename,
                    date('Y-m-d H:i:s')
                );
                $this->app['db']->query($query);
            }
        } catch (DBALException $e) {
            $message = "Timed publication of records for $contenttype failed: " . $e->getMessage();
            $this->app['logger.system']->critical($message, ['event' => 'exception', 'exception' => $e]);
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
        $tablename = $this->getContenttypeTablename($contenttype);

        try {

            // Check if there are any records that need depublishing.
            $stmt = $this->app['db']->executeQuery(
                "SELECT id FROM $tablename WHERE status = 'published' and datedepublish <= CURRENT_TIMESTAMP and datedepublish > '1900-01-01 00:00:01' and datechanged < datedepublish"
            );

            // If there's a result, we need to set these to 'held'.
            if ($stmt->fetch() !== false) {
                $query = sprintf(
                    "UPDATE %s SET status = 'held', datechanged = '%s' WHERE status = 'published' and datedepublish <= CURRENT_TIMESTAMP and datedepublish > '1900-01-01 00:00:01' and datechanged < datedepublish",
                    $tablename,
                    date('Y-m-d H:i:s')
                );
                $this->app['db']->query($query);
            }
        } catch (DBALException $e) {
            $message = "Timed de-publication of records for $contenttype failed: " . $e->getMessage();
            $this->app['logger.system']->critical($message, ['event' => 'exception', 'exception' => $e]);
        }
    }

    /**
     * Split into meta-parameters and contenttype parameters.
     *
     * This is tightly coupled to $this->getContent()
     *
     * @param array|string|null $inParameters
     *
     * @see $this->decodeContentQuery()
     */
    private function organizeQueryParameters($inParameters = null)
    {
        $ctypeParameters = [];
        $metaParameters = [];
        if (is_array($inParameters)) {
            foreach ($inParameters as $key => $value) {
                if (in_array($key, ['page', 'limit', 'offset', 'returnsingle', 'printquery', 'paging', 'order'])) {
                    $metaParameters[$key] = $value;
                } else {
                    $ctypeParameters[$key] = $value;
                }
            }
        }

        return [$metaParameters, $ctypeParameters];
    }

    /**
     * Decode a contenttypes argument from text.
     *
     * (entry,page) -> ['entry', 'page']
     * event -> ['event']
     *
     * @param string $text text with contenttypes
     *
     * @return array array with contenttype(slug)s
     */
    private function decodeContentTypesFromText($text)
    {
        $contenttypes = [];

        if ((substr($text, 0, 1) == '(') &&
            (substr($text, -1) == ')')
        ) {
            $contenttypes = explode(',', substr($text, 1, -1));
        } else {
            $contenttypes[] = $text;
        }

        $instance = $this;
        $contenttypes = array_map(
            function ($name) use ($instance) {
                $ct = $instance->getContentType($name);

                return $ct['slug'];
            },
            $contenttypes
        );

        return $contenttypes;
    }

    /**
     * Parse textquery into useable arguments.
     *
     * This is tightly coupled to $this->getContent()
     *
     * @see $this->decodeContentQuery()
     *
     * @param $textquery
     * @param array $decoded         a pre-set decoded array to fill
     * @param array $metaParameters  meta parameters
     * @param array $ctypeParameters contenttype parameters
     */
    private function parseTextQuery($textquery, array &$decoded, array &$metaParameters, array &$ctypeParameters)
    {
        // Our default callback
        $decoded['queries_callback'] = [$this, 'executeGetContentQueries'];

        // Some special cases, like 'entry/1' or 'page/about' need to be caught before further processing.
        if (preg_match('#^/?([a-z0-9_-]+)/([0-9]+)$#i', $textquery, $match)) {
            // like 'entry/12' or '/page/12345'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            $decoded['return_single'] = true;
            $ctypeParameters['id'] = $match[2];
        } elseif (preg_match('#^/?([a-z0-9_(\),-]+)/search(/([0-9]+))?$#i', $textquery, $match)) {
            // like 'page/search or '(entry,page)/search'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            $metaParameters['order'] = [$this, 'compareSearchWeights'];
            if (count($match) >= 3) {
                $metaParameters['limit'] = $match[3];
            }

            $decoded['queries_callback'] = [$this, 'executeGetContentSearch'];
        } elseif (preg_match('#^/?([a-z0-9_-]+)/([a-z0-9_-]+)$#i', $textquery, $match)) {
            // like 'page/lorem-ipsum-dolor' or '/page/home'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            $decoded['return_single'] = true;
            $ctypeParameters['slug'] = $match[2];
        } elseif (preg_match('#^/?([a-z0-9_-]+)/(latest|first)/([0-9]+)$#i', $textquery, $match)) {
            // like 'page/latest/5'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            if (!isset($metaParameters['order']) || $metaParameters['order'] === false) {
                $metaParameters['order'] = 'datepublish ' . ($match[2] == 'latest' ? 'DESC' : 'ASC');
            }
            if (!isset($metaParameters['limit'])) {
                $metaParameters['limit'] = $match[3];
            }
        } elseif (preg_match('#^/?([a-z0-9_-]+)/random/([0-9]+)$#i', $textquery, $match)) {
            // like 'page/random/4'
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($match[1]);
            $dboptions = $this->app['config']->getDBoptions();
            $metaParameters['order'] = $dboptions['randomfunction']; // 'RAND()' or 'RANDOM()'
            if (!isset($metaParameters['limit'])) {
                $metaParameters['limit'] = $match[2];
            }
        } else {
            $decoded['contenttypes'] = $this->decodeContentTypesFromText($textquery);

            if (isset($ctypeParameters['id']) && (is_numeric($ctypeParameters['id']))) {
                $decoded['return_single'] = true;
            }
        }

        // When using from the frontend, we assume (by default) that we only want published items,
        // unless something else is specified explicitly
        if (isset($this->app['end']) && $this->app['end'] != "backend" && empty($ctypeParameters['status'])) {
            $ctypeParameters['status'] = "published";
        }

        if (isset($metaParameters['returnsingle'])) {
            $decoded['return_single'] = $metaParameters['returnsingle'];
            unset($metaParameters['returnsingle']);
        }
    }

    /**
     * Prepare decoded for actual use.
     *
     * This is tightly coupled to $this->getContent()
     *
     * @see $this->decodeContentQuery()
     *
     * @param $decoded
     * @param $metaParameters
     * @param $ctypeParameters
     */
    private function prepareDecodedQueryForUse(&$decoded, &$metaParameters, &$ctypeParameters)
    {
        // If there is only 1 contenttype we assume the where is NOT nested
        if (count($decoded['contenttypes']) == 1) {
            // So we need to add this nesting
            $ctypeParameters = [$decoded['contenttypes'][0] => $ctypeParameters];
        } else {
            // We need to set every non-contenttypeslug parameters to each individual contenttypes
            $globalParameters = [];
            foreach ($ctypeParameters as $key => $parameter) {
                if (!in_array($key, $decoded['contenttypes'])) {
                    $globalParameters[$key] = $parameter;
                }
            }
            foreach ($globalParameters as $key => $parameter) {
                unset($ctypeParameters[$key]);
                foreach ($decoded['contenttypes'] as $contenttype) {
                    if (!isset($ctypeParameters[$contenttype])) {
                        $ctypeParameters[$contenttype] = [];
                    }
                    if (!isset($ctypeParameters[$contenttype][$key])) {
                        $ctypeParameters[$contenttype][$key] = $parameter;
                    }
                }
            }

            // In this case query pagination never makes sense!
            $decoded['self_paginated'] = false;
        }

        if (($decoded['order_callback'] !== false) || ($decoded['return_single'] === true)) {
            // Callback sorting disables pagination
            $decoded['self_paginated'] = false;
        }

        if (!isset($metaParameters['order']) || $metaParameters['order'] === false) {
            if (count($decoded['contenttypes']) == 1) {
                if ($this->getContentTypeGrouping($decoded['contenttypes'][0])) {
                    $decoded['order_callback'] = [$this, 'groupingSort'];
                }
            }
        }

        if ($decoded['return_single']) {
            $metaParameters['limit'] = 1;
        } elseif (!isset($metaParameters['limit'])) {
            $metaParameters['limit'] = 9999;
        }
    }

    /**
     * Get the parameter for the 'order by' part of a query.
     *
     * This is tightly coupled to $this->getContent()
     *
     * @param array  $contenttype
     * @param string $orderValue
     *
     * @return string
     */
    private function decodeQueryOrder($contenttype, $orderValue)
    {
        $order = false;

        if (($orderValue === false) || ($orderValue === '')) {
            if ($this->isValidColumn($contenttype['sort'], $contenttype, true)) {
                $order = $this->getEscapedSortorder($contenttype['sort'], false);
            }
        } else {
            $parOrder = Str::makeSafe($orderValue);
            if ($parOrder == 'RANDOM') {
                $dboptions = $this->app['db']->getParams();
                $order = $dboptions['randomfunction'];
            } elseif ($this->isValidColumn($parOrder, $contenttype, true)) {
                $order = $this->getEscapedSortorder($parOrder, false);
            }
        }

        return $order;
    }

    /**
     * Decode a content textquery
     *
     * This is tightly coupled to $this->getContent()
     *
     * @param string $textquery
     * @param array  $inParameters
     *
     * @internal param string $query the query (eg. page/about, entries/latest/5)
     * @internal param array  $parameters parameters to the query
     *
     * @return array decoded query, keys:
     *               contenttypes   - array, contenttypeslugs that will be returned
     *               return_single  - boolean, true if only 1 result should be returned
     *               self_paginated - boolean, true if already be paginated
     *               order_callback - callback, sort results post-hydration after everything is merged
     *               queries        - array of SQL query parts:
     *               * tablename   - tablename
     *               * contenttype - contenttype array
     *               * from        - from part
     *               * where       - where part
     *               * order       - order part
     *               * params      - bind-parameters
     *               parameters     - parameters to use after the queries
     */
    private function decodeContentQuery($textquery, $inParameters = null)
    {
        $decoded = [
            'contenttypes'   => [],
            'return_single'  => false,
            'self_paginated' => true,
            'order_callback' => false,
            'queries'        => [],
            'parameters'     => [],
            'hydrate'        => true,
        ];

        list($metaParameters, $ctypeParameters) = $this->organizeQueryParameters($inParameters);

        $this->parseTextQuery($textquery, $decoded, $metaParameters, $ctypeParameters);

        // $decoded['contettypes'] gotten here
        // get page nr. from url if has
        $metaParameters['page'] = $this->decodePageParameter(implode('_', $decoded['contenttypes']));

        $this->prepareDecodedQueryForUse($decoded, $metaParameters, $ctypeParameters);

        $decoded['parameters'] = $metaParameters;

        // for all the non-reserved parameters that are fields or taxonomies, we assume people want to do a 'where'
        foreach ($ctypeParameters as $contenttypeslug => $actualParameters) {
            $contenttype = $this->getContentType($contenttypeslug);
            $tablename = $this->getContenttypeTablename($contenttype);
            $where = [];
            $order = [];

            // Set the 'order', if specified in the meta_parameters.
            if (!empty($metaParameters['order'])) {
                $order[] = $this->getEscapedSortorder($metaParameters['order'], false);
            }

            $query = [
                'tablename'   => $tablename,
                'contenttype' => $contenttype,
                'from'        => sprintf('FROM %s', $tablename),
                'where'       => '',
                'order'       => '',
                'params'      => []
            ];

            if ($contenttype === false) {
                /*
                 * We were logging here, but a couple of places like
                 * TwigExtension::menuHelper() would trigger lots of hits,
                 * filling logs and impacting performance as a result.
                 * @see #1799 https://github.com/bolt/bolt/issues/1799
                 *
                 * When we refactor we need to address the callers, as this is a
                 * valid error state.
                 */
                continue;
            }

            if (is_array($actualParameters)) {
                // Set the 'FROM' part of the query, without the LEFT JOIN (i.e. no taxonomies..)
                foreach ($actualParameters as $key => $value) {
                    if ($key == 'order') {
                        $orderValue = $this->decodeQueryOrder($contenttype, $value);
                        if ($orderValue !== false) {
                            $order[] = $orderValue;
                        }
                        continue;
                    }

                    if ($key == 'filter' && !empty($value)) {
                        $filterWhere = [];
                        foreach ($contenttype['fields'] as $name => $fieldconfig) {
                            if (in_array($fieldconfig['type'], ['text', 'textarea', 'html', 'markdown'])) {
                                $filterWhere[] = sprintf(
                                    '%s.%s LIKE %s',
                                    $tablename,
                                    $name,
                                    $this->app['db']->quote('%' . $value . '%')
                                );
                            }
                        }
                        if (count($filterWhere) > 0) {
                            $where[] = '(' . implode(' OR ', $filterWhere) . ')';
                        }
                        continue;
                    }

                    // build OR parts if key contains "|||"
                    if (strpos($key, " ||| ") !== false) {
                        $keyParts = explode(" ||| ", $key);
                        $valParts = explode(" ||| ", $value);
                        $orPart = '( ';
                        $countParts = count($keyParts);
                        for ($i = 0; $i < $countParts; $i++) {
                            if (in_array($keyParts[$i], $this->getContentTypeFields($contenttype['slug'])) ||
                                in_array($keyParts[$i], Content::getBaseColumns())) {
                                $rkey = $tablename . '.' . $keyParts[$i];
                                $fieldtype = $this->getContentTypeFieldType($contenttype['slug'], $keyParts[$i]);
                                $orPart .= ' (' . $this->parseWhereParameter($rkey, $valParts[$i], $fieldtype) . ') OR ';
                            }
                        }
                        if (strlen($orPart) > 2) {
                            $where[] = substr($orPart, 0, -4) . ') ';
                        }
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

                        // Set the extra '$where', with subselect for taxonomies.
                        $where[] = sprintf(
                            '%s %s IN (SELECT content_id AS id FROM %s where %s AND ( %s OR %s ) AND %s)',
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
                $order[] = $this->decodeQueryOrder($contenttype, false) ?: 'datepublish DESC';
            }

            if (count($where) > 0) {
                $query['where'] = sprintf('WHERE (%s)', implode(' AND ', $where));
            }
            if (count($order) > 0) {
                $order = implode(', ', $order);
                if (!empty($order)) {
                    $query['order'] = sprintf('ORDER BY %s', $order);
                }
            }

            $decoded['queries'][] = $query;

            if (isset($inParameters['hydrate'])) {
                $decoded['hydrate'] = $inParameters['hydrate'];
            }
        }

        return $decoded;
    }

    /**
     * Decodes contextual page number from current request url if found.
     *
     * @param string $context Pager id/name in url which value we find
     *
     * @return mixed Page number in context
     */
    protected function decodePageParameter($context = '')
    {
        $param = Pager::makeParameterId($context);
        /* @var $query \Symfony\Component\HttpFoundation\ParameterBag */
        $query = $this->app['request']->query;
        $page = ($query) ? $query->get($param, $query->get('page', 1)) : 1;

        return $page;
    }

    /**
     * Run existence and perform publish/depublishes.
     *
     * @param array ContentType slugs to check
     *
     * @return mixed false, if any table doesn't exist
     *               true, if all is fine
     */
    private function runContenttypeChecks(array $contenttypes)
    {
        $checkedcontenttype = [];

        foreach ($contenttypes as $contenttypeslug) {

            // Make sure we do this only once per contenttype
            if (isset($checkedcontenttype[$contenttypeslug])) {
                continue;
            }

            $contenttype = $this->getContentType($contenttypeslug);
            $tablename = $this->getContenttypeTablename($contenttype);

            // If the table doesn't exist (yet), return false.
            if (!$this->tableExists($tablename)) {
                return false;
            }

            // Check if we need to 'publish' any 'timed' records, or 'depublish' any expired records.
            $this->publishTimedRecords($contenttype);
            $this->depublishExpiredRecords($contenttype);

            // "mark" this one as checked.
            $checkedcontenttype[$contenttypeslug] = true;
        }

        return true;
    }

    /**
     * Hydrate database rows into objects.
     *
     * @param array|string $contenttype
     * @param array        $rows
     * @param boolean      $getTaxoAndRel
     *
     * @throws \Exception
     *
     * @return array
     */
    private function hydrateRows($contenttype, $rows, $getTaxoAndRel = true)
    {
        // Make sure content is set, and all content has information about its contenttype
        $objects = [];
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
     * Execute the content queries.
     *
     * This is tightly coupled to $this->getContent()
     *
     * @see $this->getContent()
     *
     * @param array $decoded
     * @param array $parameters
     *
     * @return array
     */
    protected function executeGetContentSearch($decoded, $parameters)
    {
        $limit = 2000;
        $offset = 0;

        // set correct limit and offset if these are set.
        if (isset($decoded['parameters']['limit'])) {
            $limit = $decoded['parameters']['limit'];
        }

        if ($decoded['parameters']['paging'] === true && isset($decoded['parameters']['page'])) {
            // Pagenumbers are one-based, not zero-based.
            $offset = $limit * ($decoded['parameters']['page'] - 1);
        }

        $results = $this->searchContent(
            $parameters['filter'],
            $decoded['contenttypes'],
            null,
            $limit,
            $offset
        );

        return [
            $results['results'],
            $results['no_of_results']
        ];
    }

    /**
     * Execute the content queries.
     *
     * This is tightly coupled to $this->getContent()
     *
     * @see $this->getContent()
     *
     * @param array $decoded
     *
     * @return array
     */
    protected function executeGetContentQueries($decoded)
    {
        // Perform actual queries and hydrate
        $totalResults = false;
        $results = false;
        foreach ($decoded['queries'] as $query) {
            $statement = sprintf(
                'SELECT %s.* %s %s %s',
                $query['tablename'],
                $query['from'],
                $query['where'],
                $query['order']
            );

            if ($decoded['self_paginated'] === true) {
                // self pagination requires an extra query to return the actual number of results
                if ($decoded['return_single'] === false) {
                    $countStatement = sprintf(
                        'SELECT COUNT(*) as count %s %s',
                        $query['from'],
                        $query['where']
                    );
                    $countRow = $this->app['db']->executeQuery($countStatement)->fetch();
                    $totalResults = $countRow['count'];
                }

                if (isset($decoded['parameters']['paging']) && $decoded['parameters']['paging'] === true) {
                    $offset = ($decoded['parameters']['page'] - 1) * $decoded['parameters']['limit'];
                } else {
                    $offset = null;
                }
                $limit = $decoded['parameters']['limit'];

                // @todo this will fail when actually using params on certain databases
                $statement = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($statement, $limit, $offset);
            } elseif (!empty($decoded['parameters']['limit'])) {
                // If we're not paging, but we _did_ provide a limit.
                $limit = $decoded['parameters']['limit'];
                $statement = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($statement, $limit);
            }

            if (!empty($decoded['parameters']['printquery'])) {
                // @todo formalize this
                echo nl2br(htmlentities($statement));
            }

            $rows = $this->app['db']->fetchAll($statement, $query['params']);

            // Convert the row 'arrays' into \Bolt\Legacy\Content objects.
            // Only get the Taxonomies and Relations if we have to.
            $rows = $this->hydrateRows($query['contenttype'], $rows, $decoded['hydrate']);

            if ($results === false) {
                $results = $rows;
            } else {
                // We can no longer maintain keys when merging subresults
                $results = array_merge($results, array_values($rows));
            }
        }

        if ($totalResults === false) {
            $totalResults = count($results);
        }

        return [$results, $totalResults];
    }

    /**
     * getContent based on a 'human readable query'.
     *
     * Used directly by {% setcontent %} but also in other parts.
     *
     * This code has been split into multiple methods in the spirit of
     * separation of concerns, but the situation is still far from ideal.
     *
     * Where applicable each 'concern' notes the coupling in the local
     * documentation.
     *
     * @param string $textquery
     * @param string $parameters
     * @param array  $pager
     * @param array  $whereparameters
     *
     * @return array
     */
    public function getContent($textquery, $parameters = '', &$pager = [], $whereparameters = [])
    {
        // Start the 'stopwatch' for the profiler.
        $this->app['stopwatch']->start('bolt.getcontent', 'doctrine');

        // $whereparameters is passed if called from a compiled template. If present, merge it with $parameters.
        if (!empty($whereparameters)) {
            $parameters = array_merge((array) $parameters, (array) $whereparameters);
        }

        $logNotFound = false;
        if (isset($parameters['log_not_found'])) {
            $logNotFound = $parameters['log_not_found'];
            unset($parameters['log_not_found']);
        }

        // Decode this textquery
        $decoded = $this->decodeContentQuery($textquery, $parameters);
        if ($decoded === false) {
            $this->app['logger.system']->error("Not a valid query: '$textquery'", ['event' => 'storage']);
            $this->app['stopwatch']->stop('bolt.getcontent');

            return false;
        }

        // Run checks and some actions (@todo put these somewhere else?)
        if (!$this->runContenttypeChecks($decoded['contenttypes'])) {
            $this->app['stopwatch']->stop('bolt.getcontent');

            return false;
        }

        // Run the actual queries
        list($results, $totalResults) = call_user_func(
            $decoded['queries_callback'],
            $decoded,
            $parameters
        );

        // Perform post hydration ordering
        if ($decoded['order_callback'] !== false) {
            if (is_scalar($decoded['order_callback']) && ($decoded['order_callback'] == 'RANDOM')) {
                shuffle($results);
            } else {
                uasort($results, $decoded['order_callback']);
            }
        }

        // Perform pagination if necessary, but never paginate when 'returnsingle' is used.
        $offset = 0;
        $limit = false;
        if (($decoded['self_paginated'] === false) && (isset($decoded['parameters']['page'])) && (!$decoded['return_single'])) {
            $offset = ($decoded['parameters']['page'] - 1) * $decoded['parameters']['limit'];
            $limit = $decoded['parameters']['limit'];
        }
        if ($limit !== false) {
            $results = array_slice($results, $offset, $limit);
        }

        // Return content
        if ($decoded['return_single']) {
            if (util::array_first_key($results)) {
                $this->app['stopwatch']->stop('bolt.getcontent');

                return util::array_first($results);
            }

            if ($logNotFound) {
                $msg = sprintf(
                    "Requested specific query '%s', not found.",
                    $textquery
                );
                $this->app['logger.system']->error($msg, ['event' => 'storage']);
            }
            $this->app['stopwatch']->stop('bolt.getcontent');

            return false;
        }

        // Set up the $pager array with relevant values, but only if we requested paging.
        if (isset($decoded['parameters']['paging'])) {
            $pagerName = implode('_', $decoded['contenttypes']);
            $pager = [
                'for'          => $pagerName,
                'count'        => $totalResults,
                'totalpages'   => ceil($totalResults / $decoded['parameters']['limit']),
                'current'      => $decoded['parameters']['page'],
                'showing_from' => ($decoded['parameters']['page'] - 1) * $decoded['parameters']['limit'] + 1,
                'showing_to'   => ($decoded['parameters']['page'] - 1) * $decoded['parameters']['limit'] + count($results)
            ];
            $this->setPager($pagerName, $pager);
            $this->app['twig']->addGlobal('pager', $this->getPager());
        }

        $this->app['stopwatch']->stop('bolt.getcontent');

        return $results;
    }

    /**
     * Check if a given name is a valid column, and if it can be used in queries.
     *
     * @param string  $name
     * @param array   $contenttype
     * @param boolean $allowVariants
     *
     * @return boolean
     */
    private function isValidColumn($name, $contenttype, $allowVariants = false)
    {
        $isValidColumn = false;

        if ($this->isMultiOrderQuery($name)) {
            $separatedOrders = $this->getOrderBys($name);
        } else {
            $separatedOrders = [$name];
        }

        //Loop through each term if multiple
        foreach ($separatedOrders as $index => $name) {

            $name = trim($name);

            // Strip the minus in '-title' if allowed.
            if ($allowVariants) {
                if ((strlen($name) > 0) && ($name[0] == "-")) {
                    $name = substr($name, 1);
                }
                $name = $this->getFieldName($name);
            }

            // Check if the $name is in the contenttype's fields.
            if (isset($contenttype['fields'][$name])) {
                $isValidColumn = true;
            }

            if (in_array($name, Content::getBaseColumns())) {
                $isValidColumn = true;
            }

            if (! $isValidColumn) {
                return false;
            }

        }

        return true;
    }

    /**
     * Get field name, stripping possible " DESC" " ASC" etc.
     *
     * @param string $name
     *
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
     * @param string $name
     * @param string $prefix
     *
     * @return string
     */
    private function getEscapedSortorder($name, $prefix = 'r')
    {
        $order = '';

        $separatedOrders = $this->getOrderBys($name);

        $totalOrderByElements = count($separatedOrders);

        foreach ($separatedOrders as $index => $name) {
            list($name, $asc) = $this->getSortOrder(trim($name));

            // If we don't have a name, we can't determine a sortorder.
            if (empty($name)) {
                return false;
            }
            if (strpos($name, 'RAND') !== false) {
                $order .= $name;
            } elseif ($prefix !== false) {
                $order .= $this->app['db']->quoteIdentifier($prefix . '.' . $name);
            } else {
                $order .= $this->app['db']->quoteIdentifier($name);
            }

            if (! $asc) {
                $order .= ' DESC';
            }

            if ($this->isNotLastItemInArray($totalOrderByElements, $index)) {
                $order .= ',';
            }
        }

        return $order;
    }

    /**
     * Check if this is the last element in the array.
     *
     * @param $totalOrderByElements
     * @param $index
     *
     * @return boolean
     */
    protected function isNotLastItemInArray($totalOrderByElements, $index)
    {
        return $totalOrderByElements !== ($index + 1);
    }

    /**
     * Get the ORDER BY parameters for the query.
     *
     * @param $order
     *
     * @return array
     */
    protected function getOrderBys($order)
    {
        $separatedOrders = [$order];

        if ($this->isMultiOrderQuery($order)) {
            $separatedOrders = explode(',', $order);
        }

        return $separatedOrders;
    }

    /**
     * Check if this query has multiple ORDER BY parameters.
     *
     * @param $order
     *
     * @return boolean
     */
    protected function isMultiOrderQuery($order)
    {
        return strpos($order, ',') !== false;
    }

    /**
     * Get sorting order of name, stripping possible " DESC" " ASC" etc., and
     * also return the sorting order.
     *
     * @param string $name
     *
     * @return string
     */
    public function getSortOrder($name = '-datepublish')
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

        if ($fieldname[0] == '-') {
            $fieldname = substr($fieldname, 1);
            $sort = 'DESC';
        }

        return [$fieldname, (strtoupper($sort) == 'ASC')];
    }

    /**
     * Helper function for sorting Records of content that have a Grouping.
     *
     * @param Content $a
     * @param Content $b
     *
     * @return int
     */
    private function groupingSort(Content $a, Content $b)
    {
        // Same group, sort within group.
        if ($a->group['slug'] == $b->group['slug']) {
            if (!empty($a->sortorder) || !empty($b->sortorder)) {
                if (!isset($a->sortorder)) {
                    return 1;
                } elseif (!isset($b->sortorder)) {
                    return -1;
                } else {
                    return ($a->sortorder < $b->sortorder) ? -1 : 1;
                }
            }

            if (!empty($a->contenttype['sort'])) {
                // Same group, so we sort on contenttype['sort']
                list($secondSort, $order) = $this->getSortOrder($a->contenttype['sort']);

                $vala = strtolower($a->values[$secondSort]);
                $valb = strtolower($b->values[$secondSort]);

                if ($vala == $valb) {
                    return 0;
                } else {
                    $result = ($vala < $valb) ? -1 : 1;
                    // if $order is false, the 'getSortOrder' indicated that we used something like '-id'.
                    // So, perhaps we need to inverse the result.
                    return $order ? $result : -$result;
                }
            }
        }
        // Otherwise, sort based on the group. Or, more specifically, on the index of
        // the item in the group's taxonomy definition.
        return ($a->group['index'] < $b->group['index']) ? -1 : 1;
    }

    /**
     * Helper function to set the proper 'where' parameter,
     * when getting values like '<2012' or '!bob'.
     *
     * @param string $key
     * @param string $value
     * @param mixed  $fieldtype
     *
     * @return string
     */
    private function parseWhereParameter($key, $value, $fieldtype = false)
    {
        $value = trim($value);

        // check if we need to split.
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
        if (in_array($fieldtype, ['date', 'datetime']) && ($timestamp = strtotime($value)) !== false) {
            $value = date('Y-m-d H:i:s', $timestamp);
        }

        $parameter = sprintf("%s %s %s", $this->app['db']->quoteIdentifier($key), $operator, $this->app['db']->quote($value));

        return $parameter;
    }

    /**
     * Get the contenttype as an array, based on the given $contenttypeslug.
     *
     * @param string $contenttypeslug
     *
     * @return boolean|array
     */
    public function getContentType($contenttypeslug)
    {
        $contenttypeslug = $this->app['slugify']->slugify($contenttypeslug);

        // Return false if empty, can't find it.
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
                if ($contenttypeslug == $this->app['slugify']->slugify($ct['singular_name']) || $contenttypeslug == $this->app['slugify']->slugify($ct['name'])) {
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
     * Get the taxonomy as an array, based on the given $taxonomyslug.
     *
     * @param string $taxonomyslug
     *
     * @return boolean|array
     */
    public function getTaxonomyType($taxonomyslug)
    {
        $taxonomyslug = $this->app['slugify']->slugify($taxonomyslug);

        // Return false if empty, can't find it.
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
     * Get an array of the available contenttypes.
     *
     * @return array $contenttypes
     */
    public function getContentTypes()
    {
        return array_keys($this->app['config']->get('contenttypes'));
    }

    /**
     * Get an array of the available fields for a given contenttype.
     *
     * @param string $contenttypeslug
     *
     * @return array $fields
     */
    public function getContentTypeFields($contenttypeslug)
    {
        $contenttype = $this->getContentType($contenttypeslug);

        if (empty($contenttype['fields'])) {
            return [];
        } else {
            return array_keys($contenttype['fields']);
        }
    }

    /**
     * Get the fieldtype for a given contenttype and fieldname.
     *
     * @param string $contenttypeslug
     * @param string $fieldname
     *
     * @return array $fields
     */
    public function getContentTypeFieldType($contenttypeslug, $fieldname)
    {
        $contenttype = $this->getContentType($contenttypeslug);

        if (in_array($fieldname, ['datecreated', 'datechanged', 'datepublish', 'datedepublish'])) {
            return "datetime";
        } elseif (isset($contenttype['fields'][$fieldname]['type'])) {
            return $contenttype['fields'][$fieldname]['type'];
        } else {
            return false;
        }
    }

    /**
     * Check if a given contenttype has a grouping, and if it does, return it.
     *
     * @param string $contenttypeslug
     *
     * @return mixed $grouping
     */
    public function getContentTypeGrouping($contenttypeslug)
    {
        $grouping = false;
        $taxonomy = $this->getContentTypeTaxonomy($contenttypeslug);
        foreach ($taxonomy as $tax) {
            if ($tax['behaves_like'] === 'grouping') {
                $grouping = $tax['slug'];
                break;
            }
        }

        return $grouping;
    }

    /**
     * Get an array of the available taxonomytypes for a given contenttype.
     *
     * @param string $contenttypeslug
     *
     * @return array $taxonomy
     */
    public function getContentTypeTaxonomy($contenttypeslug)
    {
        $contenttype = $this->getContentType($contenttypeslug);

        if (empty($contenttype['taxonomy'])) {
            return [];
        } else {
            $taxokeys = $contenttype['taxonomy'];

            $taxonomy = [];

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
     * @param \Bolt\Legacy\Content[] $content
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
            [$ids, $contenttype, array_keys($taxonomytypes)],
            [DoctrineConn::PARAM_INT_ARRAY, \PDO::PARAM_STR, DoctrineConn::PARAM_STR_ARRAY]
        )->fetchAll();

        foreach ($rows as $row) {
            $content[$row['content_id']]->setTaxonomy($row['taxonomytype'], $row['slug'], $row['name'], $row['sortorder']);
        }

        foreach ($content as $key => $value) {
            $content[$key]->sortTaxonomy();
        }
    }

    /**
     * Update / insert taxonomy for a given content-unit.
     *
     * @param string  $contenttype
     * @param integer $contentId
     * @param array   $taxonomy
     */
    protected function updateTaxonomy($contenttype, $contentId, $taxonomy)
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
                $newslugs = [];
            }

            // Get the current values from the DB.
            $query = sprintf(
                "SELECT id, slug, sortorder FROM %s WHERE content_id=? AND contenttype=? AND taxonomytype=?",
                $tablename
            );
            $currentvalues = $this->app['db']->executeQuery(
                $query,
                [$contentId, $contenttypeslug, $taxonomytype],
                [\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR]
            )->fetchAll();

            if (!empty($currentvalues)) {
                $currentsortorder = $currentvalues[0]['sortorder'];
                $currentvalues = Arr::makeValuePairs($currentvalues, 'id', 'slug');
            } else {
                $currentsortorder = 0;
                $currentvalues = [];
            }

            // Add the ones not yet present.
            foreach ($newslugs as $slug) {

                // If it's like 'desktop#10', split it into value and sortorder.
                list($slug, $sortorder) = explode('#', $slug . "#");

                // @todo clean up and/or refactor
                // If you save this content via anything other than the Bolt
                // backend (see Content->setFromPost), then taxonomies that
                // behave like groupings, will have their sortorders reset to 0.
                if ($configTaxonomies[$taxonomytype]['behaves_like'] == 'grouping' && empty($sortorder) && $sortorder !== '0') {
                    $sortorder = $currentsortorder;
                }

                if (empty($sortorder)) {
                    $sortorder = 0;
                }

                // Make sure we have a 'name'.
                if (isset($configTaxonomies[$taxonomytype]['options'][$slug])) {
                    $name = $configTaxonomies[$taxonomytype]['options'][$slug];
                } else {
                    $name = $slug;
                }

                // Make sure the slug is also set correctly
                if (!isset($configTaxonomies[$taxonomytype]['options'][$slug])) {

                    // Assume we passed a value, instead of a slug. Turn it back into a proper slug
                    if (isset($configTaxonomies[$taxonomytype]['options']) &&
                        is_array($configTaxonomies[$taxonomytype]['options']) &&
                        array_search($slug, $configTaxonomies[$taxonomytype]['options'])) {
                        $slug = array_search($slug, $configTaxonomies[$taxonomytype]['options']);
                    } else {
                        // make sure it's at least a slug-like value.
                        $slug = $this->app['slugify']->slugify($slug);
                    }
                }

                if ((!in_array($slug, $currentvalues) || ($currentsortorder != $sortorder)) && (!empty($slug))) {
                    // Insert it!
                    $row = [
                        'content_id'   => $contentId,
                        'contenttype'  => $contenttypeslug,
                        'taxonomytype' => $taxonomytype,
                        'slug'         => $slug,
                        'name'         => $name,
                        'sortorder'    => (int) $sortorder
                    ];

                    $this->app['db']->insert($tablename, $row);
                }
            }

            // Convert new slugs to lowercase to compare in the delete process
            $newSlugsNormalised = [];
            foreach ($newslugs as $slug) {
                // If it's like 'desktop#10', split it into value and sortorder.
                list($slug, $sortorder) = explode('#', $slug . "#");
                $slug = $this->app['slugify']->slugify($slug);

                if (!empty($sortorder)) {
                    $slug = $slug . '#' . $sortorder;
                }

                $newSlugsNormalised[] = $slug;
            }

            // Delete the ones that have been removed.
            foreach ($currentvalues as $id => $slug) {

                // Make it look like 'desktop#10'
                $valuewithorder = $slug . "#" . $currentsortorder;
                $slugkey = '/' . $configTaxonomies[$taxonomytype]['slug'] . '/' . $slug;

                if (!in_array($slug, $newSlugsNormalised) && !in_array($valuewithorder, $newSlugsNormalised) && !array_key_exists($slugkey, $newSlugsNormalised)) {
                    $this->app['db']->delete($tablename, ['id' => $id]);
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
        $params = [$contenttype, $ids];
        $paramTypes = [\PDO::PARAM_STR, DoctrineConn::PARAM_INT_ARRAY];
        $rows = $this->app['db']->executeQuery($query, $params, $paramTypes)->fetchAll();

        foreach ($rows as $row) {
            $content[$row['from_id']]->setRelation($row['to_contenttype'], $row['to_id']);
        }

        // switch it, flip it and reverse it. wop wop wop.
        $query = sprintf(
            "SELECT * FROM %s WHERE to_contenttype=? AND to_id IN (?) ORDER BY id",
            $tablename
        );
        $params = [$contenttype, $ids];
        $paramTypes = [\PDO::PARAM_STR, DoctrineConn::PARAM_INT_ARRAY];
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
     *   "showcases" => arr(3)
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
     *     "to_contenttype" => str(12) "showcases"
     *     "to_id"          => str(2) "15"
     *   ]
     *   1 => arr(3)
     *   [
     *     "id"             => str(1) "6"
     *     "to_contenttype" => str(12) "showcases"
     *     "to_id"          => str(1) "9"
     *   ]
     * ]
     *
     * @param string  $contenttype
     * @param integer $contentId
     * @param array   $relation
     */
    protected function updateRelation($contenttype, $contentId, $relation)
    {
        $tablename = $this->getTablename("relations");

        // Get the current values from the DB.
        $query = sprintf(
            "SELECT id, to_contenttype, to_id FROM %s WHERE from_id=? AND from_contenttype=?",
            $tablename
        );
        $currentvalues = $this->app['db']->executeQuery(
            $query,
            [$contentId, $contenttype['slug']],
            [\PDO::PARAM_INT, \PDO::PARAM_STR]
        )->fetchAll();

        // And the other way around.
        $query = sprintf(
            "SELECT id, from_contenttype AS to_contenttype, from_id AS to_id FROM %s WHERE to_id=? AND to_contenttype=?",
            $tablename
        );
        $currentvalues2 = $this->app['db']->executeQuery(
            $query,
            [$contentId, $contenttype['slug']],
            [\PDO::PARAM_INT, \PDO::PARAM_STR]
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
                $this->app['db']->delete($tablename, ['id' => $currentvalue['id']]);
            }
        }

        // Make an easier array out of $currentvalues.
        $tempvalues = $currentvalues;
        $currentvalues = [];
        foreach ($tempvalues as $tempvalue) {
            $currentvalues[] = $tempvalue['to_contenttype'] . "/" . $tempvalue['to_id'];
        }

        // Add the ones not yet present.
        if (!empty($relation)) {
            foreach ($relation as $toContenttype => $newvalues) {
                foreach ($newvalues as $value) {
                    if (!in_array($toContenttype . "/" . $value, $currentvalues) && (!empty($value))) {
                        // Insert it!
                        $row = [
                            'from_contenttype' => $contenttype['slug'],
                            'from_id'          => $contentId,
                            'to_contenttype'   => $toContenttype,
                            'to_id'            => $value
                        ];
                        $this->app['db']->insert($tablename, $row);
                    }
                }
            }
        }
    }

    public function getLatestId($contenttypeslug)
    {
        $tablename = $this->getContenttypeTablename($contenttypeslug);

        // Get the current values from the DB.
        $query = sprintf(
            "SELECT id FROM %s ORDER BY datecreated DESC LIMIT 1;",
            $tablename
        );
        $id = $this->app['db']->executeQuery($query)->fetch();

        if (!empty($id['id'])) {
            return $id['id'];
        } else {
            return false;
        }
    }

    /**
     * Get a unique URL for a record
     *
     * @param string  $title
     * @param integer $id
     * @param string  $contenttypeslug
     * @param boolean $fulluri
     * @param boolean $allowempty
     * @param boolean $slugfield
     *
     * @return string
     */
    public function getUri($title, $id = 0, $contenttypeslug = "", $fulluri = true, $allowempty = true, $slugfield = 'slug')
    {
        $contenttype = $this->getContentType($contenttypeslug);
        $tablename = $this->getContenttypeTablename($contenttype);
        $id = intval($id);
        $slug = $this->app['slugify']->slugify($title);

        // Don't allow strictly numeric slugs.
        if (is_numeric($slug)) {
            $slug = $contenttype['singular_slug'] . "-" . $slug;
        }

        // Only add '{contenttype}/' if $full is requested.
        if ($fulluri) {
            $prefix = '/' . $contenttype['singular_slug'] . '/';
        } else {
            $prefix = '';
        }

        $fields = $this->getContentTypeFields($contenttypeslug);
        //check if the fieldname exists, otherwise use 'slug' as fallback
        if (!in_array($slugfield, $fields)) {
            $slugfield = 'slug';
        }

        $query = sprintf(
            "SELECT id from %s WHERE %s=? and id!=?",
            $tablename,
            $slugfield
        );

        $res = $this->app['db']->executeQuery(
            $query,
            [$slug, $id],
            [\PDO::PARAM_STR, \PDO::PARAM_INT]
        )->fetch();

        if (!$res) {
            $uri = $prefix . $slug;
        } else {
            for ($i = 1; $i <= 10; $i++) {
                $newslug = Html::trimText($slug, 127 - strlen($i), false) . '-' . $i;
                $res = $this->app['db']->executeQuery(
                    $query,
                    [$newslug, $id],
                    [\PDO::PARAM_STR, \PDO::PARAM_INT]
                )->fetch();
                if (!$res) {
                    $uri = $prefix . $newslug;
                    break;
                }
            }

            // otherwise, just get a random slug.
            if (empty($uri)) {
                $suffix = '-' . $this->app['randomgenerator']->generateString(6, 'abcdefghijklmnopqrstuvwxyz01234567890');
                $slug = Html::trimText($slug, 128 - strlen($suffix), false) . $suffix;
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
     * Check if the table $name exists. We use our own queries here, because it's _much_
     * faster than Doctrine's getSchemaManager().
     *
     * @param $name
     *
     * @return bool
     */
    protected function tableExists($name)
    {
        // We only should check each table once.
        if (isset($this->tables[$name])) {
            return true;
        }

        // See if the table exists.
        $platform = $this->app['db']->getDatabasePlatform()->getName();
        $database = $this->app['db']->getDatabase();
        if ($platform === 'sqlite') {
            $query = "SELECT count(*) FROM sqlite_master WHERE type='table' AND name='$name';";
        } elseif ($platform === 'postgresql') {
            $query = "SELECT count(*) FROM information_schema.tables WHERE table_catalog = '$database' AND table_name = '$name';";
        } else {
            $query = "SELECT count(*) FROM information_schema.tables WHERE table_schema = '$database' AND table_name = '$name';";
        }

        $res = $this->app['db']->fetchColumn($query);

        if (empty($res)) {
            return false;
        }

        $this->tables[$name] = true;

        return true;
    }

    /**
     * Get the table name with prefix from a given $name.
     *
     * @param string $name
     *
     * @return string
     */
    public function getTablename($name)
    {
        if ($this->prefix === null) {
            $this->prefix = $this->app['config']->get('general/database/prefix', 'bolt_');
        }

        $name = str_replace('-', '_', $this->app['slugify']->slugify($name));
        $tablename = sprintf('%s%s', $this->prefix, $name);

        return $tablename;
    }

    /**
     * Get the tablename with prefix from a given Contenttype.
     *
     * @param string|array $contenttype
     *
     * @return string
     */
    public function getContenttypeTablename($contenttype)
    {
        if (is_string($contenttype)) {
            $contenttype = $this->getContentType($contenttype);
        }

        return $this->getTablename($contenttype['tablename']);
    }

    protected function hasRecords($tablename)
    {
        $count = $this->app['db']->fetchColumn(sprintf('SELECT COUNT(id) FROM %s', $tablename));

        return intval($count) > 0;
    }

    /**
     * Find record from Content Type and Content Id.
     *
     * @param string $tablename Table name
     * @param int    $contentId Content Id
     *
     * @return array
     */
    protected function findContent($tablename, $contentId)
    {
        $oldContent = $this->app['db']->fetchAssoc("SELECT * FROM $tablename WHERE id = ?", [$contentId]);

        return $oldContent;
    }

    /**
     * Setter for pager storage element.
     *
     * @param string      $name
     * @param array|Pager $pager
     */
    public function setPager($name, $pager)
    {
        static::$pager[$name] = ($pager instanceof Pager) ? $pager : new Pager($pager, $this->app);

        return $this;
    }

    /**
     * Getter of a pager element. Pager can hold a paging snapshot map.
     *
     * @param string $name Optional name of a pager element. Whole pager map returns if no name given.
     *
     * @return array
     */
    public function &getPager($name = null)
    {
        if ($name) {
            if (array_key_exists($name, static::$pager)) {
                return static::$pager[$name];
            } else {
                return false;
            }
        } else {
            return static::$pager;
        }
    }

    public function isEmptyPager()
    {
        return (count(static::$pager) === 0);
    }
}
