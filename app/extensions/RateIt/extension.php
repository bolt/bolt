<?php
// RateIt extension for Bolt

namespace RateIt;

// For JS and CSS insertion
use Bolt\Extensions\Snippets\Location as SnippetLocation;

// For AJAX handling
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

// For DB access
use Doctrine\DBAL\Schema\Schema;

class Extension extends \Bolt\BaseExtension
{
    public $table_name;

    function info() {

        $data = array(
            'name' =>"RateIt",
            'description' => "An extentions to add RateIt to your site when using <code>{{ rateit('slider_name') }}</code> in your templates.",
            'author' => "Gawain Lynch",
            'link' => "http://bolt.cm",
            'version' => "1.3",
            'required_bolt_version' => "1.5",
            'highest_bolt_version' => "2.0",
            'type' => "Twig function",
            'first_releasedate' => "2014-03-04",
            'latest_releasedate' => "2014-03-27",
        );

        return $data;
    }

    function initialize() {

        // Check the database table is up and working
        $this->dbRegister();

        // Set up a controller for AJAX requests
        $this->ajax_path = '/ajax/' . __NAMESPACE__;
        $this->app->post($this->ajax_path, array($this, 'ajaxRateIt'))->bind('ajaxRateIt');

        if (empty($this->config['stylesheet']) || file_exists(realpath($this->app['paths']['extensionspath']) . $this->config['stylesheet']) === false) {
            $this->config['stylesheet'] = 'css/rateit.css';
        }

        // Make sure the css is inserted as well..
        if ($this->config['location'] == 'body') {
            $this->addCSS($this->config['stylesheet'], true);

        } else {
            $this->addCSS($this->config['stylesheet']);
        }


        // Sane defaults
        if (empty($this->config['stars'])) {
            $this->config['stars'] = 5;
        }
        if (empty($this->config['increment'])) {
            $this->config['increment'] = 0.5;
        }
        if (!empty($this->config['size']) && $this->config['size'] == 'large') {
            $this->config['px'] = 32;
            $this->config['class'] = 'bigstars';
        }
        else {
            $this->config['px'] = 16;
            $this->config['class'] = '';
        }
        if (empty($this->config['reponse_class'])) {
            $this->config['reponse_class'] = '';
        }
        if (empty($this->config['response_msg'])) {
            $this->config['response_msg'] = '';
        }
        if (empty($this->config['logging'])) {
            $this->config['logging'] = 'off';
        }

        $this->path = $this->app['paths']['app'] . 'extensions/' . $this->namespace;

        $html = '
            <script type="text/javascript" src="' . $this->path . '/js/jquery.rateit.min.js"></script>
            <script type="text/javascript" src="' . $this->path . '/js/bolt.rateit.js"></script>
                ';
        $this->insertSnippet(SnippetLocation::END_OF_HTML, $html);

        $this->addTwigFunction('rateit', 'twigRateIt');
        $this->app->after(array($this, "afterCallback"), 1);
    }

    function twigRateIt() {
        $max = $this->config['stars'];
        $inc = $this->config['increment'];

        if ($this->isCookieSet()) {
            $readonly = 'data-rateit-readonly="true"';
        }

        // Customisation goes here. See http://rateit.codeplex.com/documentation
        $html = '
            <input type="range" min="0" max="' . $max . '" value="0" step="' . $inc . '" id="boltrateit">
            <div class="rateit ' . $this->config['class'] .'" data-bolt-record-id="" data-rateit-backingfld="#boltrateit" data-rateit-starwidth="' . $this->config['px'] .'" data-rateit-starheight="' . $this->config['px'] .'" ' . $readonly. '></div>
            <div class="' . $this->config['reponse_class'] . '" id="rateit_response" hidden></div>
                ';

        return new \Twig_Markup($html, 'UTF-8');
    }

    function afterCallback()
    {
        $record = $this->getRecord();

        $this->insertRateItJS($record);
    }

    /**
     *
     *
     * @since 1.0
     *
     * @param array|string $vars Do something
     * @return NULL
     */
    private function getRecord()
    {

        if (isset($this->record)) {
            return $this->record;
        }

        $globalTwigVars = $this->app['twig']->getGlobals('record');

        if (isset($globalTwigVars['record'])) {
            $record = $globalTwigVars['record'];
        } else {
            $record = false;
        }

        return $record;
    }

    private function isCookieSet() {
        $record = $this->getRecord();
        $bolt_record_id = $record->id;
        $bolt_contenttype = strtolower( $record->contenttype['name'] );

        if (isset($_COOKIE['rateit'][$bolt_contenttype][$bolt_record_id])) {
            return true;
        }
        return false;
    }

    /**
     *
     *
     * @since 1.0
     *
     * @param array|string $vars Do something
     * @return NULL
     */
    private function insertRateItJS($record)
    {
        $bolt_record_id = $record->id;
        $bolt_contenttype = strtolower( $record->contenttype['name'] );

        if (empty($bolt_record_id) || empty($bolt_contenttype)) {
            return;
        }

        // Get the current value of the rating
        try {
            $lookup = $this->dbLookupRating(array('contenttype' => $bolt_contenttype, 'record_id' => $bolt_record_id));
            if(!empty($lookup) && isset($lookup[0]['vote_avg'])) {
                $current_val = $lookup[0]['vote_avg'];
            }
            else {
                $current_val = 0;
            }
        } catch (\Exception $e) {
            $current_val = 0;
        }

        // Set data that stores the Contenttype and record ID/number so that
        // votes can be associated with this record
        //
        // Also we set the existing value here
        $js = "
            <script type =\"text/javascript\">

            $(document).ready(function(){
                $('.rateit').data('bolt-record-id', '" . $bolt_record_id . "');
                $('.rateit').data('bolt-contenttype', '" . $bolt_contenttype . "');

                $('.rateit').rateit('value', " . $current_val . ");

            });

            </script>
            ";

        // If 'tooltips' is set in config, insert them here
        if (!empty($this->config['tooltips'])) {
            $js .= "
            <script type=\"text/javascript\">

            var tooltipvalues = " . json_encode($this->config['tooltips']) . ";

            $('.rateit').bind('over', function(e, value) {
                $(this).attr('title', tooltipvalues[value - 1]);
            });

            </script>";
        }

        $this->insertSnippet(SnippetLocation::END_OF_HTML, $js );
    }

    /**
     * Register, setup and index our database table
     *
     * @since Bolt 1.5.1
     */
    private function dbRegister() {
        $prefix = $this->app['config']->get('general/database/prefix', "bolt_");
        $this->table_name = $prefix . 'rateit';
        $this->log_table_name = $prefix . 'rateit_log';
        $me = $this;

        // Rating table
        $this->app['integritychecker']->registerExtensionTable(
            function(Schema $schema) use ($me) {
                // Define table
                $table = $schema->createTable($me->table_name);

                // Add primary column
                $table->addColumn("id", "integer", array('autoincrement' => true));
                $table->setPrimaryKey(array("id"));

                // Add working columns
                $table->addColumn("content_id", "integer", array("length" => 11));
                $table->addColumn("contenttype", "string", array("length" => 32));
                $table->addColumn("vote_num", "integer");
                $table->addColumn("vote_sum", "decimal", array("scale" => '2'));
                $table->addColumn("vote_avg", "decimal", array("scale" => '2'));

                // Index column(s)
                $table->addIndex(array('content_id'));
                $table->addIndex(array('contenttype'));
                return $table;
            });

        // Log table
        $this->app['integritychecker']->registerExtensionTable(
            function(Schema $schema) use ($me) {
                // Define table
                $table = $schema->createTable($me->log_table_name);

                // Add primary column
                $table->addColumn("id", "integer", array('autoincrement' => true));
                $table->setPrimaryKey(array("id"));

                // Add working columns
                $table->addColumn("datetime", "datetime");
                $table->addColumn("ip", "string", array("length" => 39));
                $table->addColumn("cookie", "string");
                $table->addColumn("content_id", "integer", array("length" => 11));
                $table->addColumn("contenttype", "string", array("length" => 32));
                $table->addColumn("vote", "decimal", array("scale" => '2'));

                return $table;
            });
    }

    /**
     * Lookup extension database to see if a rating exists for an existing
     * record and return it.
     *
     * @since Bolt 1.5.1
     *
     * @param string $contenttype The Bolt contenttype being rated
     * @param string $record_id The record ID being rated
     * @return array
     */
    private function dbLookupRating(Array $rating) {

        $query =
            "SELECT vote_num, vote_sum, vote_avg " .
            "FROM `{$this->table_name}` " .
            "WHERE ( `contenttype` = '{$rating['contenttype']}' AND `content_id` = '{$rating['record_id']}' ) " .
            "LIMIT 1";

        $rating = $this->app['db']->fetchAll($query);

        return $rating;
    }

    /**
     * Update extension database rating for an existing record with results of
     * incomming vote
     *
     * @since Bolt 1.5.1
     *
     * @param array $rating Array of details about the vote that was made
     * @return array        Array to be returned to AJAX client
     */
    private function dbUpdateRating(Array $rating) {
        $response = array();

        if ($rating['create'] === true) {
            $query = "INSERT INTO `{$this->table_name}` " .
                     "(`contenttype`, `content_id`, `vote_num`, `vote_sum`, `vote_avg`) " .
                     "VALUES (:type, :id, :num, :sum, :avg)";
        }
        else {
            $query = "UPDATE `{$this->table_name}` " .
                     "SET `vote_num` = :num, `vote_sum` = :sum, `vote_avg` = :avg " .
                     "WHERE (`contenttype` = :type AND `content_id` = :id) ";
        }
        $map = array(
                ':num'  => $rating['vote_num'],
                ':sum'  => $rating['vote_sum'],
                ':avg'  => $rating['vote_avg'],
                ':type' => $rating['contenttype'],
                ':id'   => $rating['record_id'],
                );

        $ra = $this->app['db']->executeUpdate($query, $map);

        if ($ra === 1) {
            $response['retval'] = 0;
            $response['msg'] = str_replace( '%RATING%', $rating['vote'], $this->config['response_msg']);
            setcookie("rateit[{$rating['contenttype']}][{$rating['record_id']}]", true, time()+31536000, '/');
        }
        else {
            $response['retval'] = 1;
            $response['msg'] = 'Sorry, something went wrong';
        }
        return $response;
    }

    /**
     * Log the readers rating vote
     *
     * @since Bolt 1.5.1
     *
     * @param array|string $vars Do something
     * @return NULL
     */
    private function dbLogVote(Request $request)
    {
        $query = "INSERT INTO `{$this->log_table_name}` " .
                 "(`datetime`, `ip`, `cookie`, `content_id`, `contenttype`, `vote`) " .
                 "VALUES (:datetime, :ip, :cookie, :content_id, :contenttype, :vote)";
        $map = array(
                ':datetime' => date("Y-m-d H:i:s", time()),
                ':ip' => $request->getClientIp(),
                ':cookie' => $request->cookies->get('bolt_session'),
                ':content_id' => $request->get('record_id'),
                ':contenttype' => $request->get('contenttype'),
                ':vote' => floatval($request->get('value'))
                );

        $ra = $this->app['db']->executeUpdate($query, $map);
    }

    /**
     *
     * @since 1.0
     *
     * @param array|string $vars Do something
     * @return NULL
     */
    function ajaxRateIt(Request $request, $errors = null)
    {

        // If we were passed an error, exit
        if (is_array($errors)) {
            return;
        }

        // Check that we're here for a POST instead of a programmer typo
        if ($request->getMethod() === 'POST') {
            if ($this->config['logging'] == 'on') {
                $this->dbLogVote($request);
            }

            $rating['contenttype'] = $request->get('contenttype');
            $rating['record_id'] = $request->get('record_id');
            $rating['vote'] = floatval($request->get('value'));

            $db_rating = $this->dbLookupRating($rating);

            if (empty($db_rating)) {
                $rating['create'] = true;
                $rating['vote_num'] = 1;
                $rating['vote_sum'] = $rating['vote'];
                $rating['vote_avg'] = $rating['vote_sum'];
            }
            else {
                $rating['create'] = false;
                $rating['vote_num'] = $db_rating[0]['vote_num'] + 1;
                $rating['vote_sum'] = $db_rating[0]['vote_sum'] + $rating['vote'];
                $rating['vote_avg'] = round($db_rating[0]['vote_sum'] / $db_rating[0]['vote_num'], 2);
            }

            // Write it back
            $response = $this->dbUpdateRating($rating);

            echo json_encode($response);
        }

        exit;
    }
}
