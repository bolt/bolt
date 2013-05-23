<?php
// Import WXR (PivotX / Wordpress) for Bolt, by Bob den Otter (bob@twokings.nl

namespace ImportWXR;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Extension extends \Bolt\BaseExtension
{


    /**
     * Info block for RSSFeed Extension.
     */
    function info()
    {

        $data = array(
            'name' => "ImportWXR",
            'description' => "Een importfilter voor 'het oude CMS'.",
            'author' => "Bob den Otter",
            'link' => "http://www.twokings.nl",
            'version' => "0.9",
            'required_bolt_version' => "1.1",
            'highest_bolt_version' => "1.1",
            'type' => "Import",
            'first_releasedate' => "2013-05-21",
            'latest_releasedate' => "2013-05-21"
        );

        return $data;

    }

    function initialize()
    {
        $this->app->match('/bolt/importwxr', array($this, 'importwxr'));
    }

    public function importwxr()
    {

        // \util::var_dump($this->config);

        $filename = __DIR__ . "/" . $this->config['file'];
        $file = realpath(__DIR__ . "/" . $this->config['file']);


        $output = "";

        if (!empty($_GET['action'])) {
            $action = $_GET['action'];
        } else {
            $action = "start";
        }

        require_once("src/parsers.php");
        $parser = new \WXR_Parser();

        switch ($action) {

            case "start":
                if (empty($file) || !is_readable($file)) {
                    $output . "<p>File $filename doesn't exist. Correct this in <code>app/extensions/ImportWXR/config.yml</code>, and refresh this page.</p>";
                } else {

                    $output .= "<p><a class='btn btn-primary' href='?action=dryrun'><strong>Test a few records</strong></a></p>";

                    $output .= "<p>This mapping will be used:</p>";
                    $output .= \util::var_dump($this->config['mapping'], true);
                }
                break;

            case "confirm":

                $res = $parser->parse($file);

                foreach ($res['posts'] as $post) {
                    $output .= $this->importPost($post, false);
                }
                break;

            case "dryrun":

                $counter = 1;

                $res = $parser->parse($file);

                foreach ($res['posts'] as $post) {
                    $output .= $this->importPost($post, true);
                    if ($counter++ >= 5) {
                        break;
                    }
                }

                $output .= sprintf("<p>Looking good? Then click below to import the Records: </p>");

                $output .= "<p><a class='btn btn-primary' href='?action=confirm'><strong>Confirm!</strong></a></p>";



        }


        return $this->app['twig']->render('base.twig', array(
            'title' => "Import WXR (PivotX / Wordpress XML)",
            'content' => $output
        ));


    }

    public function importPost($post, $dryrun = true) {

        // Find out which mapping we should use.
        $mapping = $this->config['mapping'][ $post['post_type'] ];

        $record = new \Bolt\Content($this->app, $mapping['targetcontenttype']);

        foreach ($mapping['fields'] as $from => $to) {

            if (isset($post[$from])) {

                $value = $post[$from];

                switch ($to) {
                    case "username":
                        $value = makeSlug($value);
                        break;
                    case "status":
                        if ($value=="publish") { $value = "published"; }
                        if ($value=="future") { $value = "timed"; }
                        break;
                }

                $record->setValue($to, $value);
            }

        }

        if ($dryrun) {
            $output = "<p>Original WXR Post <b>\"" . $post['post_title'] . "\"</b> -&gt; Converted Bolt Record :</p>";
            $output .= \util::var_dump($post, true);
            $output .= \util::var_dump($record, true);
            $output .= "\n<hr>\n";
        } else {
            $this->app['storage']->saveContent($record);
            $output = "Import: " . $record->get('id') . " - " . $record->get('title') . "<br>";
        }

        return $output;

    }

}
