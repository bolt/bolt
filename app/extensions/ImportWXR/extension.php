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

        if (!isset($_GET['confirm'])) {

            if (empty($file) || !is_readable($file)) {
                $output . "<p>File $filename doesn't exist. Correct this in <code>app/extensions/ImportWXR/config.yml</code>, and refresh this page.</p>";
            } else {

                $output .= sprintf("<p>Ready to convert <code>%s</code>!</p>", $this->config['file']);

                $output .= "<p>This mapping will be used:</p>";
                $output .= \util::var_dump($this->config['mapping'], true);

                $output .= "<p><a class='btn btn-primary' href='?confirm=1'><strong>Confirm!</strong></a></p>";

            }

        } else {

            require_once("src/parsers.php");

            $parser = new \WXR_Parser();

            $res = $parser->parse($file);

            foreach ($res['posts'] as $post) {
                $output .= $this->importPost($post);
            }

            //            \util::var_dump($res);

        }

        return $this->app['twig']->render('base.twig', array(
            'title' => "Import WXR (PivotX / Wordpress XML)",
            'content' => $output
        ));


    }

    public function importPost($post) {

        //\util::var_dump($post);

        // Find out which mapping we should use.
        $mapping = $this->config['mapping'][ $post['post_type'] ];

        //\util::var_dump($mapping);

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

        // \util::var_dump($record);

        $this->app['storage']->saveContent($record);
        $output = "Import: " . $record->get('id') . " - " . $record->get('title') . "<br>";

        return $output;


    }


    public function import()
    {
        // Oude meuk wissen
        if (!empty($this->config['cleanup'])) {}
        $stmt = $this->app['db']->query($this->config['cleanup']);
        $stmt->closeCursor();

        // \util::var_dump($this->config);

        $query = sprintf("SELECT * FROM %s WHERE %s", $this->config['source_table'], $this->config['where']);

        // \util::var_dump($query);

        $stmt = $this->app['db']->query($query);

        $output = "verwerkt: <br>";

        while ($row = $stmt->fetch()) {
            $content = new \Bolt\Content($this->app, $this->config['target_contenttype']);

            //\util::var_dump($row);

            $row['field1'] = $this->parseCmsTags($row['field1']);
            $row['introduction'] = $this->parseCmsTags($row['introduction']);
            $row['body'] = $this->parseCmsTags($row['body']);
            $row['title'] = trim(str_replace(">", "", $row['title']));

            // \util::var_dump($row['field1']);

            foreach ($this->config['mapping'] as $to => $from) {
                if (isset($row[$from])) {
                    $field = $this->scrubfield($row[$from]);
                    $content->setValue($to, $field);
                } else {
                    $content->setValue($to, $from);
                }
            }

            $partialcode = substr($row['code'], 0, 5);

            switch ($partialcode) {

                case "0001-":
                    $content->setTaxonomy("hoofdindeling", "bezoek", $row['page_uid']);
                    break;

                case "0002-":
                    $content->setTaxonomy("hoofdindeling", "kinderen", $row['page_uid']);
                    break;

                case "0003-":
                    $content->setTaxonomy("hoofdindeling", "projecten", $row['page_uid']);
                    break;

                case "0004-":
                    $content->setTaxonomy("hoofdindeling", "collectie", $row['page_uid']);
                    break;

                case "0006-":
                    $content->setTaxonomy("hoofdindeling", "educatie", $row['page_uid']);
                    break;

                case "0008-":
                    $content->setTaxonomy("hoofdindeling", "onderzoek", $row['page_uid']);
                    break;

                case "0009-":
                    $content->setTaxonomy("hoofdindeling", "museum", $row['page_uid']);
                    break;

                case "0010-":
                    $content->setTaxonomy("hoofdindeling", "vrienden", $row['page_uid']);
                    break;

                case "0020-":
                    $content->setTaxonomy("hoofdindeling", "activiteiten", $row['page_uid']);
                    break;

                default:
                    if ($this->config['target_contenttype'] == "paginas") {
                        $content->setTaxonomy("hoofdindeling", "algemeen", $row['page_uid']);
                    }
                    break;

            }
            //\util::var_dump($content);

            $this->app['storage']->saveContent($content);
            $output = "Import: " . $row['code'] . " - " . $content->get('title') . "<br>";

        }

        $title = "Import Oude CMS";

        $body = $this->app['twig']->render('base.twig', array(
            'title' => $title,
            'content' => $output
        ));

        return $body;

    }

    private function scrubField($field)
    {

        if (strpos($field, "CONT:")===0) {
            $field = str_replace("CONT:/", "oud/", $field);
        }

        $field = str_replace("CONT/", "oud/", $field);
        $field = str_replace("CONT:/", "oud/", $field);

        return $field;

    }


    private function parseCmsTags($field)
    {

        // \util::var_dump($field);

        $match = preg_match_all("/\[\[ afbeelding (.*)\]\]/i", $field, $imgs);

        if ($match) {
            foreach($imgs[1] as $key => $params) {
                $params = $this->parseParameters($params);
                $replacement = sprintf("{{ showimage('%s', '%s', '%s') }}", $params['src'], $params['width'], $params['height']);
                $field = str_replace($imgs[0][$key], $replacement, $field);
            }
        }

        $match = preg_match_all("/\[\[ download (.*)\]\]/i", $field, $imgs);

        if ($match) {
            foreach($imgs[1] as $key => $params) {
                $params = $this->parseParameters($params);
                if (empty($params['title']) && empty($params['desc'])) {
                    $params['title'] = basename($params['src']);
                }
                $replacement = sprintf("<a href='/files/%s'>%s %s</a>", $params['src'], $params['title'], $params['desc']);
                $field = str_replace($imgs[0][$key], $replacement, $field);
            }
        }


        // \util::var_dump($field);

        return $field;

    }


    private function parseParameters($string)
    {

        $string = str_replace("&quot;", '"', $string);

        $match = preg_match_all('/([a-z]+)=(["\'])(.*?)(["\'])/is', $string, $matches);

        // \util::var_dump($matches);

        $params = array(
            'name' => "",
            'src' => "",
            'alt' => "",
            'title' => "",
            'width' => "",
            'height' => "",
            'desc' => ""
        );

        if (!empty($matches)) {
            foreach($matches[0] as $key => $value) {
                $params[$matches[1][$key]] = $matches[3][$key];
            }
        }

        return $params;


    }


}
