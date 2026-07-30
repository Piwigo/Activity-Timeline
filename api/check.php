<?php
include('../include/init.inc.php');

$query = '
SELECT
    COUNT(*) AS counter
  FROM commit
;';
$nb_commits = query2array($query, null, 'counter')[0];

$output = 'failure';
if (preg_match('/^\d+$/', $nb_commits))
{
  $output = 'success';
}

echo $output;
exit();
