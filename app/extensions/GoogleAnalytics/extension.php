<?php
// Google Analytics extension for Bolt

namespace GoogleAnalytics;


function info() {

    $data = array(
        'name' =>"Google Analytics",
        'description' => "A small extension to add the scripting for a Google Analytics tracker to your site.",
        'author' => "Bob den Otter",
        'link' => "http://bolt.cm",
        'version' => "0.1",
        'required_bolt_version' => "0.8",
        'highest_bolt_version' => "0.8",
        'type' => "Snippet",
        'first_releasedate' => "2012-10-10",
        'latest_releasedate' => "2012-10-19",
    );

    return $data;

}

function init($app) {

    $app['extensions']->insertSnippet('endofhead', 'GoogleAnalytics\insertAnalytics');

    $additionalhtml = '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
    $additionalhtml .= '<script>google.load("visualization", "1", {packages:["corechart"]}); </script>';

    $app['extensions']->insertWidget('dashboard', 'right_first', 'GoogleAnalytics\insertWidget', $additionalhtml, 3600);

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




function insertWidget()
{
    global $app;

    // http://ga-dev-tools.appspot.com/explorer/
    // http://code.google.com/p/gapi-google-analytics-php-interface/
    // http://www.codediesel.com/php/reading-google-analytics-data-from-php/
    // http://code.google.com/p/gapi-google-analytics-php-interface/wiki/UsingFilterControl


    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    if (empty($config['ga_email'])) { return "ga_email not set in config.yml."; }
    if (empty($config['ga_password'])) { return "ga_password not set in config.yml."; }
    if (empty($config['ga_profile_id'])) { return "ga_profile_id not set in config.yml."; }
    if (!empty($config['filter_referral'])) {
        $filter_referral = 'source !@ "'.$config['filter_referral'].'"';
    } else {
        $filter_referral = '';
    }
    if (empty($config['number_of_days'])) {
        $config['number_of_days'] = 14;
    }

    require_once(__DIR__.'/gapi/gapi.class.php');

    //echo "<pre>\n" . \util::var_dump($config, true) . "</pre>\n";

    /* Create a new Google Analytics request and pull the results */
    $ga = new \gapi($config['ga_email'], $config['ga_password']);
    $ga->requestReportData(
        $config['ga_profile_id'],
        array('date'),
        array('pageviews', 'visitors', 'uniquePageviews', 'pageviewsPerVisit', 'exitRate', 'avgTimeOnPage', 'entranceBounceRate', 'newVisits'),
        'date',
        '',
        date('Y-m-d', strtotime('-' . $config['number_of_days'] .' day')),
        date('Y-m-d')
    );

    $pageviews = array();

    $tempresults = $ga->getResults();

    $aggr = array(
        'pageviews' => 0,
        'pageviewspervisit' => 0,
        'visitors' => 0,
        'uniquePageviews' => 0,
        'timeonpage' => 0,
        'bouncerate' => 0,
        'exitrate' => 0
    );

    // aggregate data:
    foreach($tempresults as $result) {

        $pageviews[] = array(
            'date' => date('M j',strtotime($result->getDate())),
            'pageviews' => $result->getPageviews(),
            'visitors' => $result->getVisitors()
        );

        $aggr['pageviews'] += $result->getPageviews();
        $aggr['pageviewspervisit'] += $result->getPageviewsPerVisit();
        $aggr['visitors'] += $result->getVisitors();
        $aggr['uniquePageviews'] += $result->getUniquepageviews();
        $aggr['timeonpage'] += $result->getAvgtimeonpage();
        $aggr['bouncerate'] += $result->getEntrancebouncerate();
        $aggr['exitrate'] += $result->getExitrate();
    }

    $aggr['pageviewspervisit'] = round($aggr['pageviewspervisit'] / count($tempresults), 1);
    $aggr['timeonpage'] = secondMinute(round($aggr['timeonpage'] / count($tempresults), 1));
    $aggr['bouncerate'] = round($aggr['bouncerate'] / count($tempresults), 1);
    $aggr['exitrate'] = round($aggr['exitrate'] / count($tempresults), 1);

    //echo "<pre>\n" . \util::var_dump($aggr, true) . "</pre>\n";

    // echo "<pre>\n" . \util::var_dump($pageviews, true) . "</pre>\n";

    // Get the 'populair sources'
    $ga->requestReportData(
        $config['ga_profile_id'],
        array('source','referralPath'),
        array('visits'),
        '-visits',
        $filter_referral,
        date('Y-m-d', strtotime('-' . $config['number_of_days'] .' day')),
        date('Y-m-d'),
        1,
        12
    );
    $results = $ga->getResults();

    $sources = array();

    foreach($results as $result) {
        if ($result->getReferralPath() == "(not set)") {
            $sources[] = array(
                'link' => false,
                'host' => $result->getSource(),
                'visits' => $result->getVisits()
            );
        } else {
            $sources[] = array(
              'link' => true,
              'host' => $result->getSource() . $result->getReferralPath(),
              'visits' => $result->getVisits()
            );
        }
    }

    // echo "<pre>\n" . \util::var_dump($sources, true) . "</pre>\n";

    // Get the 'popular pages'
    $ga->requestReportData(
        $config['ga_profile_id'],
        array('hostname','pagePath'),
        array('visits'),
        '-visits',
        '',
        date('Y-m-d', strtotime('-' . $config['number_of_days'] .' day')),
        date('Y-m-d'),
        1,
        12
    );
    $results = $ga->getResults();

    $pages = array();

    foreach($results as $result) {
        $pages[] = array(
            'host' => $result->gethostname() . $result->getPagePath(),
            'visits' => $result->getVisits()
        );
    }

    //echo "<pre>\n" . \util::var_dump($pages, true) . "</pre>\n";

    //$app['twig.path'] = __DIR__;

    $caption = sprintf("Google Analytics for %s - %s.",
        date('M d', strtotime('-' . $config['number_of_days'] .' day')),
        date('M d')
    );

    $html = $app['twig']->render("GoogleAnalytics/widget.twig", array(
        'caption' => $caption,
        'aggr' => $aggr,
        'pageviews' => $pageviews,
        'sources' => $sources,
        'pages' => $pages
    ));

    return $html;

}

// TODO: Rewrite this. yuck.
function secondMinute($seconds) {
    $minResult = floor($seconds/60);
    if($minResult < 10){$minResult = 0 . $minResult;}
    $secResult = ($seconds/60 - $minResult)*60;
    if($secResult < 10){$secResult = 0 . round($secResult);}
    else { $secResult = round($secResult); }
    return $minResult.":".$secResult;
}
