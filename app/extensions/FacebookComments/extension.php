<?php
// Facebook Like Extension for Bolt

namespace FacebookComments;

class Extension extends \Bolt\BaseExtension
{

    function info()
    {

        $data = array(
            'name' =>"Facebook Comments",
            'description' => "An extension to place Facebook comment threads on your site, when using <code>{{ facebookcomments() }}</code> in your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "1.1",
            'required_bolt_version' => "1.0",
            'highest_bolt_version' => "1.0",
            'type' => "Twig function",
            'first_releasedate' => "2012-10-10",
            'latest_releasedate' => "2013-01-27",
        );

        return $data;

    }

    function initialize()
    {

        //$this->addTwigFunction('facebookcomments', 'facebookcomments');


        // Make sure the script is inserted as well..
        // Outside of the current Extension Class.
        // $this->addSnippet('endofbody', 'facebookScript');

        // Note: the snippet does not _need_ to be in this class..
        // $this->insertSnippet('startofbody', array('FacebookComments\blabla','facebookScript'));

    }

    /**
     * Return the available Snippets
     * @return array
     */
    function getSnippets()
    {
        return array(
            array('endofbody', 'facebookScript')
        );
    }


    /**
     * Return the available Twig Functions
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('facebookcomments', array($this, 'facebookcomments'))
        );
    }



    /**
     * Callback for snippet 'facebookscript'.
     *
     * @return string
     */
    function facebookScript()
    {

        $html = <<< EOM
    <div id="fb-root"></div>
    <script>(function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));</script>
EOM;

        return $html;

    }

    /**
     * Callback for Twig function 'facebookcomments'.
     */
    function facebookcomments($title="")
    {

        echo \util::var_dump($this->app, true);

        $yamlparser = new \Symfony\Component\Yaml\Parser();
        $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

        if (empty($config['width'])) { $config['width'] = "470"; }

        $html = <<< EOM
        <div class="fb-comments" data-href="%url%" data-num-posts="1" data-width="%width%"></div>
EOM;

        if ($title!="") {
            $title = "var disqus_title = '" . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . "';\n";
        } else {
            $title = "";
        }

        $html = str_replace("%width%", $config['width'], $html);
        $html = str_replace("%url%", $this->app['paths']['canonicalurl'], $html);

        return new \Twig_Markup($html, 'UTF-8');

    }




}


