<?php

namespace Bolt;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Silex;
use Bolt;
use util;
use Doctrine\DBAL\Connection as DoctrineConn;
use Symfony\Component\EventDispatcher\Event;

class Storage
{

    private $app;
    private $prefix;
    private $checkedfortimed = array();

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;

        $this->prefix = isset($this->app['config']['general']['database']['prefix']) ? $this->app['config']['general']['database']['prefix'] : "bolt_";

        // Make sure prefix ends in '_'. Prefixes without '_' are lame..
        if ($this->prefix[ strlen($this->prefix)-1 ] != "_") {
            $this->prefix .= "_";
        }

    }

    /**
     * Check if just the users table is present.
     *
     * @return boolean
     */
    public function checkUserTableIntegrity()
    {

        $tables = $this->getTables();

        // Check the users table..
        if (!isset($tables[$this->prefix."users"])) {
            return false;
        }

        return true;

    }

    /**
     * Check if all required tables and columns are present in the DB
     *
     * @return boolean
     */
    public function checkTablesIntegrity()
    {

        $messages = array();

        $tables = $this->getTables();

        // Check the users table..
        if (!isset($tables[$this->prefix."users"])) {
            $messages[] = "Table <tt>" . $this->prefix."users" . "</tt> is not present.";
        }

        // Check the log table..
        if (!isset($tables[$this->prefix."log"])) {
            $messages[] = "Table <tt>" . $this->prefix."log" . "</tt> is not present.";
        }

        // Check the taxonomy table..
        if (!isset($tables[$this->prefix."taxonomy"])) {
            $messages[] = "Table <tt>" . $this->prefix."taxonomy" . "</tt> is not present.";
        }

        // Check the relations table..
        if (!isset($tables[$this->prefix."relations"])) {
            $messages[] = "Table <tt>" . $this->prefix."relations" . "</tt> is not present.";
        }

        // Now, iterate over the contenttypes, and create the tables if they don't exist.
        foreach ($this->app['config']['contenttypes'] as $key => $contenttype) {

            $tablename = $this->prefix . makeSlug($key);

            if (!isset($tables[$tablename])) {
                $messages[] = "Table <tt>" . $tablename . "</tt> is not present.";
            }
            if (!isset($tables[$tablename]['datepublish'])) {
                $messages[] = "Field <tt>" . 'datepublish' . "</tt> in table <tt>" . $tablename . "</tt> is not present.";
            }

            // Check if all the fields are present in the DB..
            foreach ($contenttype['fields'] as $field => $values) {
                if (!isset($tables[$tablename][$field])) {
                    $messages[] = "Field <tt>" . $field . "</tt> in table <tt>" . $tablename . "</tt> is not present.";
                }
            }

        }

        if (empty($messages)) {
            return true;
        } else {
            return $messages;
        }

    }


    public function repairTables()
    {

        $output = array();

        $currentTables = $this->getTableObjects();

        $dboptions = getDBOptions($this->app['config']);
        /** @var $schemaManager AbstractSchemaManager */
        $schemaManager = $this->app['db']->getSchemaManager();

        $comparator = new Comparator();

        $tables = array();

        $schema = new \Doctrine\DBAL\Schema\Schema();
        $usersTable = $schema->createTable($this->prefix."users");
        $usersTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
        $usersTable->setPrimaryKey(array("id"));
        $usersTable->addColumn("username", "string", array("length" => 32));
        $usersTable->addIndex( array( 'username' ) );
        $usersTable->addColumn("password", "string", array("length" => 64));
        $usersTable->addColumn("email", "string", array("length" => 64));
        $usersTable->addColumn("lastseen", "datetime");
        $usersTable->addColumn("lastip", "string", array("length" => 32, "default" => ""));
        $usersTable->addColumn("displayname", "string", array("length" => 32));
        $usersTable->addColumn("userlevel", "string", array("length" => 32));
        $usersTable->addColumn("contenttypes", "string", array("length" => 256));
        $usersTable->addColumn("enabled", "boolean");
        $usersTable->addIndex( array( 'enabled' ) );
        $tables[] = $usersTable;

        $taxonomyTable = $schema->createTable($this->prefix."taxonomy");
        $taxonomyTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
        $taxonomyTable->setPrimaryKey(array("id"));
        $taxonomyTable->addColumn("content_id", "integer", array("unsigned" => true));
        $taxonomyTable->addIndex( array( 'content_id' ) );
        $taxonomyTable->addColumn("contenttype", "string", array("length" => 32));
        $taxonomyTable->addIndex( array( 'contenttype' ) );
        $taxonomyTable->addColumn("taxonomytype", "string", array("length" => 32));
        $taxonomyTable->addIndex( array( 'taxonomytype' ) );
        $taxonomyTable->addColumn("slug", "string", array("length" => 64));
        $taxonomyTable->addColumn("name", "string", array("length" => 64, "default" => ""));
        $taxonomyTable->addColumn("sortorder", "integer", array("unsigned" => true, "default" => 0));
        $taxonomyTable->addIndex( array( 'sortorder' ) );
        $tables[] = $taxonomyTable;

        $relationsTable = $schema->createTable($this->prefix."relations");
        $relationsTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
        $relationsTable->setPrimaryKey(array("id"));
        $relationsTable->addColumn("from_contenttype", "string", array("length" => 32));
        $relationsTable->addIndex( array( 'from_contenttype' ) );
        $relationsTable->addColumn("from_id", "integer", array("unsigned" => true));
        $relationsTable->addIndex( array( 'from_id' ) );
        $relationsTable->addColumn("to_contenttype", "string", array("length" => 32));
        $relationsTable->addIndex( array( 'to_contenttype' ) );
        $relationsTable->addColumn("to_id", "integer", array("unsigned" => true));
        $relationsTable->addIndex( array( 'to_id' ) );
        $tables[] = $relationsTable;

        $logTable = $schema->createTable($this->prefix."log");
        $logTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
        $logTable->setPrimaryKey(array("id"));
        $logTable->addColumn("level", "integer", array("unsigned" => true));
        $logTable->addIndex( array( 'level' ) );
        $logTable->addColumn("date", "datetime");
        $logTable->addIndex( array( 'date' ) );
        $logTable->addColumn("message", "string", array("length" => 1024));
        $logTable->addColumn("username", "string", array("length" => 64, "default" => ""));
        $logTable->addIndex( array( 'username' ) );
        $logTable->addColumn("requesturi", "string", array("length" => 128));
        $logTable->addColumn("route", "string", array("length" => 128));
        $logTable->addColumn("ip", "string", array("length" => 32, "default" => ""));
        $logTable->addColumn("file", "string", array("length" => 128, "default" => ""));
        $logTable->addColumn("line", "integer", array("unsigned" => true));
        $logTable->addColumn("contenttype", "string", array("length" => 32));
        $logTable->addColumn("content_id", "integer", array("unsigned" => true));
        $logTable->addColumn("code", "string", array("length" => 32));
        $logTable->addIndex( array( 'code' ) );
        $logTable->addColumn("dump", "string", array("length" => 1024));
        $tables[] = $logTable;

        // Now, iterate over the contenttypes, and create the tables if they don't exist.
        foreach ($this->app['config']['contenttypes'] as $key => $contenttype) {

            // create the table if necessary..
            $tablename = $this->prefix . makeSlug($key);

            $myTable = $schema->createTable($tablename);
            $myTable->addColumn("id", "integer", array("unsigned" => true, 'autoincrement' => true));
            $myTable->setPrimaryKey(array("id"));
            $myTable->addColumn("slug", "string", array("length" => 128));
            $myTable->addIndex( array( 'slug' ) );
            $myTable->addColumn("datecreated", "datetime");
            $myTable->addIndex( array( 'datecreated' ) );
            $myTable->addColumn("datechanged", "datetime");
            $myTable->addIndex( array( 'datechanged' ) );
            $myTable->addColumn("datepublish", "datetime");
            $myTable->addIndex( array( 'datepublish' ) );
            $myTable->addColumn("username", "string", array("length" => 32));
            $myTable->addColumn("status", "string", array("length" => 32));
            $myTable->addIndex( array( 'status' ) );

            // Check if all the fields are present in the DB..
            foreach ($contenttype['fields'] as $field => $values) {

                if (in_array($field, $dboptions['reservedwords'])) {
                    $error = sprintf("You're using '%s' as a field name, but that is a reserved word in %s. Please fix it, and refresh this page.",
                        $field,
                        $dboptions['driver']
                    );
                    $this->app['session']->getFlashBag()->set('error', $error);
                    continue;
                }

                switch ($values['type']) {
                    case 'text':
                    case 'templateselect':
                    case 'select':
                    case 'image':
                    case 'file':
                        $myTable->addColumn($field, "string", array("length" => 256, "default" => ""));
                        break;
                    case 'float':
                        $myTable->addColumn($field, "float", array("default" => 0));
                        break;
                    case 'number': // deprecated..
                        $myTable->addColumn($field, "decimal", array("precision" => "18", "scale" => "9", "default" => 0));
                        break;
                    case 'integer':
                        $myTable->addColumn($field, "integer", array("default" => 0));
                        break;
                    case 'html':
                    case 'textarea':
                    case 'video':
                    case 'markdown':
                    case 'geolocation':
                    case 'imagelist':
                        $myTable->addColumn($field, "text");
                        break;
                    case 'datetime':
                        $myTable->addColumn($field, "datetime");
                        break;
                    case 'date':
                        $myTable->addColumn($field, "date");
                        break;
                    case 'slug':
                    case 'id':
                    case 'datecreated':
                    case 'datechanged':
                    case 'datepublish':
                    case 'username':
                    case 'status':
                        // These are the default columns. Don't try to add these.
                        break;
                    default:
                        $output[] = "Type <tt>" . $values['type'] . "</tt> is not a correct field type for field <tt>$field</tt> in table <tt>$tablename</tt>.";
                }

                if (isset($values['index']) && $values['index'] == 'true') {
                    $myTable->addIndex( array( $field ) );
                }

            }
            $tables[] = $myTable;

        }

        /** @var $table Table */
        foreach($tables as $table) {
            // Create the users table..
            if (!isset($currentTables[$table->getName()])) {

                /** @var $platform AbstractPlatform */
                $platform = $this->app['db']->getDatabasePlatform();
                $queries = $platform->getCreateTableSQL($table);
                $queries = implode("; ", $queries);
                $this->app['db']->query($queries);

                $output[] = "Created table <tt>" . $table->getName() . "</tt>.";

            } else {

                $diff = $comparator->diffTable( $currentTables[$table->getName()], $table );
                if ( $diff ) {
                    if (!in_array($table->getName(),array($this->prefix."users"))) {
                        // we don't remove fields from contenttype tables to prevent accidental data removal
                        if ($diff->removedColumns) {
                            //var_dump($diff->removedColumns);
                            /** @var $column Column */
                            foreach($diff->removedColumns as $column) {
                                //$output[] = "<i>Field <tt>" . $column->getName() . "</tt> in <tt>" . $table->getName() . "</tt> " .
                                //    "is no longer defined in the config, delete manually if no longer needed.</i>";
                            }
                        }
                        $diff->removedColumns = array();
                    }
                    // diff may be just deleted columns which we have reset above
                    // only exec and add output if does really alter anything
                    if ($this->app['db']->getDatabasePlatform()->getAlterTableSQL($diff)) {
                        $schemaManager->alterTable( $diff );
                        $output[] = "Updated <tt>" . $table->getName() . "</tt> table to match current schema.";
                    }
                }
            }
        }

        return $output;

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

            $tablename = $this->prefix . $key;
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

        $contentobject = new Bolt\Content($this->app, $contenttype);
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

        // add the fields for this contenttype,
        foreach ($contenttype['fields'] as $key => $values) {

            // Set the slug, while we're at it..
            if ($values['type'] == "slug") {
                if (!empty($values['uses']) && empty($fieldvalues['slug'])) {
                    $fieldvalues['slug'] = makeSlug($fieldvalues[ $values['uses'] ]);
                } else if (!empty($fieldvalues['slug'])) {
                    $fieldvalues['slug'] = makeSlug($fieldvalues['slug']);
                } else {
                    echo "wut";
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

            // parse 'formatted dates'.. Wednesday, 15 August 2012 -> 2012-08-15
            if (strpos($key, "-dateformatted") !== false) {
                $newkey = str_replace("-dateformatted", "", $key);

                // See if we need to add the time..
                if (isset($fieldvalues[$newkey.'-timeformatted']) && !empty($fieldvalues[$newkey.'-timeformatted'])) {
                    $value .= " - " . $fieldvalues[$newkey.'-timeformatted'];
                } else {
                    $value .= " - 00:00";
                }

                $timestamp = \DateTime::createFromFormat("l, d F Y - H:i", $value);

                if ($timestamp instanceof \DateTime) {
                    $fieldvalues[$newkey] = $timestamp->format('Y-m-d H:i:00');
                } else {
                    $fieldvalues[$newkey] = "";
                }

            }

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
            $content->setValue('id', $id);
        } else {
            $id = $fieldvalues['id'];
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

        $tablename = $this->prefix . $contenttype;

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

        $tablename = $this->prefix . $contenttype;

        $content['datecreated'] = date('Y-m-d H:i:s');
        $content['datechanged'] = date('Y-m-d H:i:s');

        // id is set to autoincrement, so let the DB handle it
        unset($content['id']);

        $res = $this->app['db']->insert($tablename, $content);

        $id = $this->app['db']->lastInsertId();

        return $id;

    }


    private function updateContent($content, $contenttype)
    {

        // Make sure $contenttype is a 'slug'
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        $tablename = $this->prefix . $contenttype;

        unset($content['datecreated']);
        $content['datechanged'] = date('Y-m-d H:i:s');

        return $this->app['db']->update($tablename, $content, array('id' => $content['id']));

    }


    public function updateSingleValue($contenttype, $id, $field, $value)
    {

        $tablename = $this->prefix . $contenttype;

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

        $content = new Bolt\Content($this->app, $contenttypeslug);

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

    public function searchContentType($contenttypename, array $parameters = array(), &$pager = array()){
        $tablename = $this->prefix . $contenttypename;

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
        if (!empty($parameters['paging'])) {
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
        $queryparams .= sprintf(" LIMIT %s, %s;", ($page - 1) * $limit, $limit);

        // Make the query to get the results..
        $query = "SELECT * FROM $tablename" . $queryparams;

        $rows = $this->app['db']->fetchAll($query);

        // Make sure content is set, and all content has information about its contenttype
        $content = array();
        foreach ($rows as $row) {
            $content[ $row['id'] ] = new Bolt\Content($this->app, $contenttype, $row);
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
            $contenttypetable = $this->prefix . $contenttypename;
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
        if (!empty($parameters['paging'])) {
            $page = $this->app['request']->get('page', $page);
        }
        //$tablename = $this->prefix . $contenttypename;
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
        $queryparams .= sprintf(" LIMIT %s, %s;", ($page - 1) * $limit, $limit);

        // Make the query to get the results..
        $query = "SELECT * FROM $tablename" . $queryparams;

        $rows = $this->app['db']->fetchAll($query);

        // Make sure content is set, and all content has information about its contenttype
        $content = array();
        foreach ($rows as $row) {
            // @todo Make sure contenttype is set properly..
            $content[ $row['id'] ] = new Bolt\Content($this->app, '', $row);
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
    public function getContentByTaxonomy($taxonomytype, $slug, $parameters = "")
    {

        $tablename = $this->prefix . "taxonomy";

        $limit = $parameters['limit'] ?: 100;
        $page = $parameters['page'] ?: 1;

        $where = " WHERE (taxonomytype=". $this->app['db']->quote($taxonomytype) . " AND slug=". $this->app['db']->quote($slug) .")";

        // Make the query for the pager..
        $pagerquery = "SELECT COUNT(*) AS count FROM $tablename" . $where;

        // Add the limit
        $query = "SELECT * FROM $tablename" . $where . sprintf(" ORDER BY id DESC LIMIT %s, %s;", ($page-1)*$limit, $limit);

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
            'for' => $taxonomytype."/".$slug,
            'count' => $rowcount['count'],
            'totalpages' => ceil($rowcount['count'] / $limit),
            'current' => $page,
            'showing_from' => ($page-1)*$limit + 1,
            'showing_to' => ($page-1)*$limit + count($taxorows)
        );
        $GLOBALS['pager'][$taxonomytype."/".$slug] = $pager;

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
        $tablename = $this->prefix . $contenttype['slug'];
        $now = date('Y-m-d H:i:s', time());

        // Check if there are any records that need publishing..
        $query = "SELECT id FROM $tablename WHERE status = 'timed' and datepublish < :now";
        $stmt = $this->app->db->prepare($query);
        $stmt->bindValue("now", $now);
        $stmt->execute();

        // If there's a result, we need to set these to 'publish'..
        if ($stmt->fetch() != false) {
            $query = "UPDATE $tablename SET status = 'published', datechanged = :now, datepublish = :now  WHERE status = 'timed' and datepublish < :now";
            $stmt = $this->app->db->prepare($query);
            $stmt->bindValue("now", $now);
            $stmt->execute();
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
        if (!empty($parameters['paging'])) {
            $page = $this->app['request']->get('page', $page);
        }

        $contenttype = $this->getContentType($contenttypeslug);

        // If we can't match to a valid contenttype, return (undefined) content;
        if (!$contenttype) {
            $emptycontent = new Bolt\Content($this->app, $contenttypeslug);
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

        $tablename = $this->prefix . $contenttype['slug'];

        // for all the non-reserved parameters that are fields, we assume people want to do a 'where'
        foreach ($parameters as $key => $value) {
            if (in_array($key, array('order', 'where', 'limit', 'offset'))) {
                continue; // Skip this one..
            }
            if (!in_array($key, $this->getContentTypeFields($contenttype['slug'])) &&
                !in_array($key, array("id", "slug", "datecreated", "datechanged", "datepublish", "username", "status")) ) {
                continue; // Also skip if 'key' isn't a field in the contenttype.
            }

            $where[] = $this->parseWhereParameter($key, $value);

        }

        // If we need to filter, add the WHERE for that.
        // InnoDB doesn't support full text search. WTF is up with that shit?
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

        // Order, with a special case for 'RANDOM'.
        $queryparams .= $this->queryParamOrder($parameters, $contenttype);

        // Make the query for the pager..
        $pagerquery = "SELECT COUNT(*) AS count FROM $tablename" . $queryparams;

        // Add the limit
        $queryparams .= sprintf(" LIMIT %s, %s;", ($page-1)*$limit, $limit);

        // Make the query to get the results..
        $query = "SELECT * FROM $tablename" . $queryparams;

        $rows = $this->app['db']->fetchAll($query);

        // Make sure content is set, and all content has information about its contenttype
        $content = array();
        foreach ($rows as $row) {
            $content[ $row['id'] ] = new Bolt\Content($this->app, $contenttype, $row);
        }

        // Make sure all content has their taxonomies and relations
        $this->getTaxonomy($content);
        $this->getRelation($content);

        // Iterate over the contenttype's taxonomy, check if there's one we can use for grouping.
        // But only if we're not sorting manually (i.e. have a ?order=.. parameter or $parameter['order'] )
        $order = $this->app['request']->query->get('page', isset($parameters['order'])?$parameters['order']:null);
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
            $name = preg_replace("/ (desc|asc)/i", "", $name);
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
     * Get the parameter for the 'order by' part of a query.
     *
     * @param array $parameters
     * @param array $contenttype
     * @return string
     */
    private function queryParamOrder($parameters, $contenttype) {

        if (empty($parameters['order'])) {
            if ($this->isValidColumn($contenttype['sort'], $contenttype, true)) {
                $order = $contenttype['sort'];
            }
        } else {
            $parameters['order'] = safeString($parameters['order']);
            if ($parameters['order'] == "RANDOM") {
                $dboptions = getDBOptions($this->app['config']);
                $order = $dboptions['randomfunction'];
            } elseif ($this->isValidColumn($parameters['order'], $contenttype, true)) {
                $order = $parameters['order'];
            }
        }

        if (!empty($order)) {
            if ($order[0] == "-") {
                $order = substr($order, 1) . " DESC";
            }
            $param = " ORDER BY " . $order;
        } else {
            $param = " ORDER BY datepublish DESC";
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
            $second_sort = $a->contenttype['sort'];
            if ($a->values[$second_sort] == $b->values[$second_sort]) {
                return 0;
            } else {
                return ($a->values[$second_sort] < $b->values[$second_sort]) ? -1 : 1;
            }
        }
        return ($a->group < $b->group) ? -1 : 1;
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
                if ($contenttypeslug == makeSlug($ct['singular_name']) || $contenttypeslug == makeSlug($ct['name'])) {
                    $contenttype = $this->app['config']['contenttypes'][$key];
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
                $taxonomy[$key] = $this->app['config']['taxonomy'][$key];
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

        $tablename = $this->prefix . "taxonomy";

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

        $tablename = $this->prefix . "taxonomy";

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

        $tablename = $this->prefix . "relations";

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
        $params = array($this->app['db']->quote($contenttype), $ids);
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
        $params = array($this->app['db']->quote($contenttype), $ids);
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

        $tablename = $this->prefix . "relations";

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
        $tablename = $this->prefix . $contenttype['slug'];

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

        $sm = $this->app['db']->getSchemaManager();

        $tables = array();

        foreach ($sm->listTables() as $table) {
            if ( strpos($table->getName(), $this->prefix) === 0 ) {
                foreach ($table->getColumns() as $column) {
                    $tables[ $table->getName() ][ $column->getName() ] = $column->getType();
                }
                // $output[] = "Found table <tt>" . $table->getName() . "</tt>.";
            }
        }

        return $tables;

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
