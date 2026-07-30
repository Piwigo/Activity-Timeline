<?php
include('../include/init.inc.php');

$secret_key_file = '../secret_key.php';
if (file_exists($secret_key_file))
{
  include($secret_key_file);

  if (!isset($_GET['secret_key']))
  {
    die('missing secret_key');
  }

  if ($secret_key != $_GET['secret_key'])
  {
    die('invalid secret_key');
  }
}

$query = '
SELECT
    project_id,
    name,
    url
  FROM project
;';
$projects = query2array($query);

header('Content-Type: application/json');
echo json_encode($projects);
exit();
