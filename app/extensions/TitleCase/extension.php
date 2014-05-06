<?php
// Testing Snippets extension for Bolt

namespace TitleCase;

use Bolt\Extensions\Snippets\Location as SnippetLocation;

class Extension extends \Bolt\BaseExtension
{

    private $smallwords = "a,aan,achter,af,an,and,at,bij,binnen,boven,buiten,but,by,de,door,een,else,en,for,from,het,if,in,in,into,inzake,is,langs,maar,met,na,naar,naast,nabij,namens,nor,of,of,off,om,omtrent,on,ondanks,onder,ook,op,or,out,over,over,per,richting,rond,rondom,te,tegen,the,then,tijdens,to,tot,tussen,uit,van,vanaf,vanuit,vanwege,via,volgens,voor,voorbij,wegens,when,with,zonder";

    function info()
    {

        $data = array(
            'name' =>"TitleCase filter",
            'description' => "A extension to add a 'titlecase' filter to your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "1.0",
            'required_bolt_version' => "1.2",
            'highest_bolt_version' => "1.2",
            'type' => "Twig filter",
            'first_releasedate' => "2013-09-02",
            'latest_releasedate' => "2013-09-02",
            'allow_in_user_content' => true,
        );

        return $data;

    }

    function initialize()
    {

        $this->addTwigFilter('titlecase', 'titleCaseFilter');

    }


    function titleCaseFilter($str)
    {

        $str = $this->titleCase($str);

        return new \Twig_Markup($str, 'UTF-8');

    }


    private function titleCase($str)
    {

        $smallwordsarray = explode(",", $this->smallwords);

        $words = explode(' ', strtolower($str));

        foreach ($words as $key => $word) {
            if ($key == 0 or !in_array($word, $smallwordsarray)) {
                $words[$key] = ucwords($word);
            }
        }

        return implode(' ', $words);

    }

}
