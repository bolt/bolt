RateIt
=============

RateIt is extension to display a RateIt page rating widget in your template by adding:

    {{ rateit() }}

Ratings are stored in the extensions own table in the Bolt database and by default show the average of previously cast votes.

In your configuration file you can control:
  * CSS
  * Number of stars
  * Tool-tip text
  * Size (large or small)
  * Logging (separate table)
  * User feed back message text and styling

This extension uses the jQuery RateIt library from http://rateit.codeplex.com/
