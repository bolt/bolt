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
        // Set up routing for the extension.
        $path = $this->app['config']->get('general/branding/path') . '/importwxr';
        $this->app->match($path, array($this, 'importwxr'));
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

                    $output .= sprintf("<p>File <code>%s</code> selected for import.</p>", $this->config['file']);

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
                    if ($counter++ >= 4) {
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

    public function importPost($post, $dryrun = true)
    {

        // If the mapping is not defined, ignore it.
        if (empty($this->config['mapping'][ $post['post_type'] ])) {
            return "<p>No mapping defined for posttype '" . $post['post_type'] . "'.</p>";
        }

        // Find out which mapping we should use.
        $mapping = $this->config['mapping'][ $post['post_type'] ];

        // If the mapped contenttype doesn't exist in Bolt.
        if (!$this->app['storage']->getContentType($mapping['targetcontenttype'])) {
            return "<p>Bolt contenttype '". $mapping['targetcontenttype'] . "' for posttype '" . $post['post_type'] . "' does not exist.</p>";
        }

        // Create the new Bolt Record.
        $record = new \Bolt\Content($this->app, $mapping['targetcontenttype']);

        // 'expand' the postmeta fields to regular fields.
        if (!empty($post['postmeta']) && is_array($post['postmeta'])) {
            foreach ($post['postmeta'] as $id => $keyvalue) {
                $post[$keyvalue['key']] = $keyvalue['value'];
            }
        }

        // Iterate through the mappings, see if we can find it.
        foreach ($mapping['fields'] as $from => $to) {

            if (isset($post[$from])) {
                // It's present in the fields.

                $value = $post[$from];


                switch ($from) {
                    case "post_parent":
                        if (!empty($value)) {
                            $value = $mapping['fields']['post_parent_contenttype'] . "/" . $value;
                        }
                        break;
                    case "post_date":
                        if (!empty($value)) {
                            // WXR seems to use only one date value.
                            $record->setValue('datechanged', $value);
                            $record->setValue('datecreated', $value);
                            $record->setValue('datepublish', $value);
                        }
                        break;
                }

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
