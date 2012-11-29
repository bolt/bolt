<style type="text/css">
/* IN PAGE ANALYTICS */
#page-analtyics {
    clear: left;
}
#page-analtyics .metric {
    background: #fefefe; /* Old browsers */
        background: -moz-linear-gradient(top, #fefefe 0%, #f2f3f2 100%); /* FF3.6+ */
        background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#fefefe), color-stop(100%,#f2f3f2)); /* Chrome,Safari4+ */
        background: -webkit-linear-gradient(top, #fefefe 0%,#f2f3f2 100%); /* Chrome10+,Safari5.1+ */
        background: -o-linear-gradient(top, #fefefe 0%,#f2f3f2 100%); /* Opera 11.10+ */
        background: -ms-linear-gradient(top, #fefefe 0%,#f2f3f2 100%); /* IE10+ */
        background: linear-gradient(top, #fefefe 0%,#f2f3f2 100%); /* W3C */
        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#fefefe', endColorstr='#f2f3f2',GradientType=0 ); /* IE6-9 */
    border: 1px solid #ccc;
    float: left;
    font-size: 12px;
    margin: -4px 0 1em -1px;
    padding: 10px;
    width: 105px;
}
#page-analtyics .metric:hover {
    background: #fff;
    border-bottom-color: #b1b1b1;
}
#page-analtyics .metric .legend {
    background-color: #058DC7;
    border-radius: 5px;
        -moz-border-radius: 5px;
        -webkit-border-radius: 5px;
    font-size: 0;
    margin-right: 5px;
    padding: 10px 5px 0;
}
#page-analtyics .metric strong {
    font-size: 16px;
    font-weight: bold;
}
#page-analtyics .range {
    color: #686868;
    font-size: 11px;
    margin-bottom: 7px;
    width: 100%;
}
</style>
<?php

// http://ga-dev-tools.appspot.com/explorer/
// http://code.google.com/p/gapi-google-analytics-php-interface/
// http://www.codediesel.com/php/reading-google-analytics-data-from-php/
// http://code.google.com/p/gapi-google-analytics-php-interface/wiki/UsingFilterControl

echo "hoi";


require('gapi/gapi.class.php');


$ga_email = '';
$ga_password = '';
$ga_profile_id = '64242754';
$ga_url = $_SERVER['REQUEST_URI'];

/* Create a new Google Analytics request and pull the results */
$ga = new gapi($ga_email,$ga_password);
$ga->requestReportData(
        $ga_profile_id, 
        array('date'),
        array('pageviews', 'visitors', 'uniquePageviews', 'pageviewsPerVisit', 'exitRate', 'avgTimeOnPage', 'entranceBounceRate', 'newVisits'),
        'date',
        '',
        date('Y-m-d', strtotime('-14 day')),
        date('Y-m-d')
    );
$results = $ga->getResults();

?>   

<!-- Create an empty div that will be filled using the Google Charts API and the data pulled from Google -->
<div id="chart" style="border: 1px solid #F09; width: auto; display: inline-block;"></div>

<!-- Include the Google Charts API -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>

<!-- Create a new chart and plot the pageviews for each day -->
<script type="text/javascript">
  google.load("visualization", "1", {packages:["corechart"]});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
    var data = new google.visualization.DataTable();

    <!-- Create the data table -->
    data.addColumn('string', 'Day');
    data.addColumn('number', 'Pageviews');
    data.addColumn('number', 'Visitors');
    // data.addColumn('number', 'Visitors');

    <!-- Fill the chart with the data pulled from Analtyics. Each row matches the order setup by the columns: day then pageviews -->
    data.addRows([
      <?php
      foreach($results as $result) {
            printf('["%s", %s, %s], ', 
                date('M j',strtotime($result->getDate())), 
                $result->getPageviews(), 
                $result->getVisitors()
                 );
      }
      ?>
    ]);

 

    var chart = new google.visualization.AreaChart(document.getElementById('chart'));
    chart.draw(data, {
        width: 330, 
        height: 180, 
        colors:['#0099CC','#00BB88','#FF0000'],
        areaOpacity: 0.1,
        hAxis: {textPosition: 'in', showTextEvery: 4, slantedText: true, textStyle: { color: '#058dc7', fontSize: 10 } },
        pointSize: 5,
        chartArea:{left:30,top:5,width:"290",height:"170"}
    });
  }
</script>

<?php

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
foreach($results as $result) {
    $aggr['pageviews'] += $result->getPageviews();
    $aggr['pageviewspervisit'] += $result->getPageviewsPerVisit();
    $aggr['visitors'] += $result->getVisitors();
    $aggr['uniquePageviews'] += $result->getUniquepageviews();
    $aggr['timeonpage'] += $result->getAvgtimeonpage();
    $aggr['bouncerate'] += $result->getEntrancebouncerate();
    $aggr['exitrate'] += $result->getExitrate();
}

$aggr['pageviewspervisit'] = round($aggr['pageviewspervisit'] / count($results), 1);
$aggr['timeonpage'] = round($aggr['timeonpage'] / count($results), 1);
$aggr['bouncerate'] = round($aggr['bouncerate'] / count($results), 1);
$aggr['exitrate'] = round($aggr['exitrate'] / count($results), 1);

echo "<pre>";
print_r($aggr);
echo "</pre>";

function secondMinute($seconds) {
    $minResult = floor($seconds/60);
    if($minResult < 10){$minResult = 0 . $minResult;}
    $secResult = ($seconds/60 - $minResult)*60;
    if($secResult < 10){$secResult = 0 . round($secResult);}
    else { $secResult = round($secResult); }
    return $minResult.":".$secResult;
}


$ga->requestReportData(
        $ga_profile_id, 
        array('source','referralPath'),
        array('visits'),
        '-visits',
        'source !@ "bolt.cm"',
        date('Y-m-d', strtotime('-14 day')),
        date('Y-m-d'),
        1,
        15
    );
$results = $ga->getResults();

foreach($results as $result) {
    printf("<a href='http://%s%s'>%s: %s</a><br>\n", $result->getSource(), $result->getReferralPath(), $result->getVisits(), $result->getSource() );
}


$ga->requestReportData(
        $ga_profile_id, 
        array('hostname','pagePath'),
        array('visits'),
        '-visits',
        '',
        date('Y-m-d', strtotime('-14 day')),
        date('Y-m-d'),
        1,
        15
    );
$results = $ga->getResults();

foreach($results as $result) {
    printf("%s: %s%s</a><br>\n", $result->getVisits(), $result->gethostname(), $result->getPagePath() );
}


?>
