<?php
define('ga_email','youremail@email.com');
define('ga_password','your password');
define('ga_profile_id','your profile id');

require 'gapi.class.php';

$ga = new gapi(ga_email,ga_password);

$ga->requestReportData(ga_profile_id,array('browser','browserVersion'),array('pageviews','visits'));
?>
<table>
<tr>
  <th>Browser &amp; Browser Version</th>
  <th>Pageviews</th>
  <th>Visits</th>
</tr>
<?php
foreach($ga->getResults() as $result):
?>
<tr>
  <td><?php echo $result ?></td>
  <td><?php echo $result->getPageviews() ?></td>
  <td><?php echo $result->getVisits() ?></td>
</tr>
<?php
endforeach
?>
</table>

<table>
<tr>
  <th>Total Results</th>
  <td><?php echo $ga->getTotalResults() ?></td>
</tr>
<tr>
  <th>Total Pageviews</th>
  <td><?php echo $ga->getPageviews() ?>
</tr>
<tr>
  <th>Total Visits</th>
  <td><?php echo $ga->getVisits() ?></td>
</tr>
<tr>
  <th>Results Updated</th>
  <td><?php echo $ga->getUpdated() ?></td>
</tr>
</table>