<?php
// Google Analytics extension for Bolt

namespace GoogleAnalytics;


function info() {

    $data = array(
        'name' =>"Google Analytics",
        'description' => "A small extension to add the scripting for a Google Analytics tracker to your site.",
        'author' => "Bob den Otter",
        'link' => "http://bolt.cm",
        'version' => 0.1,
        'required_bolt_version' => 0.8,
        'type' => "Snippet",
        'releasedate' => "2012-10-10"
    );

    return $data;

}

function init($app) {

    $app['extensions']->insertSnippet('beforeclosehead', "GoogleAnalytics\insertAnalytics");

}


function insertAnalytics() {

    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    if (empty($config['webproperty'])) {
        $config['webproperty'] = "property-not-set";
    }

    $html = <<< EOM

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '%webproperty%']);
  _gaq.push(['_setDomainName', '%domainname%']);
  _gaq.push(['_trackPageview']);

  (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
EOM;

    $html = str_replace("%webproperty%", $config['webproperty'], $html);
    $html = str_replace("%domainname%", $_SERVER['HTTP_HOST'], $html);


    return $html;

}

