<?php
// +1 Button Extension for Bolt, by Till Klocke

namespace PlusOne;

class Extension extends \Bolt\BaseExtension
{

    /**
     * Info block for +1 Button Extension.
     */
    function info()
    {

        $data = array(
            'name' => "+1 Button",
            'description' => "Adds a +1 Button to your page",
            'keywords' => "Google Plus, +1, Social",
            'author' => "Till Klocke",
            'link' => "http://dereulenspiegel.blogger.com",
            'version' => "0.1",
            'required_bolt_version' => "1.0.2",
            'highest_bolt_version' => "1.2",
            'type' => "General",
            'first_releasedate' => "2013-08-21",
            'latest_releasedate' => "2013-08-21",
            'dependencies' => "",
            'priority' => 10
        );

        return $data;

    }

    /**
     * Initialize +1 Button. Called during bootstrap phase.
     */
    function initialize()
    {

        // Add javascript file
	    $snippet = <<< EOM
<script type="text/javascript">
window.___gcfg = {lang: '%lang%'};
(function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
})();
</script>
EOM;
	    $snippet = str_replace("%lang%", $this->config['lang'], $snippet);

        // Add string snippet to endofbody
        $this->insertSnippet('endofbody', $snippet);

        // Initialize the Twig function
        $this->addTwigFunction('plusone', 'twigPlusone');

    }

    /**
     * Twig function {{ plusone() }} in +1 Button extension.
     */
    function twigPlusone()
    {

        $html= <<< EOM
		<div class="g-plusone" data-size="%size%" data-annotation="%annotation%" data-width="%width%"></div>
EOM;
        $html = str_replace("%size%", $this->config['style'], $html);
        $html = str_replace("%annotation%", $this->config['annotation'], $html);
        $html = str_replace("%width%", $this->config['width'], $html);

        return new \Twig_Markup($html, 'UTF-8');

    }

}


