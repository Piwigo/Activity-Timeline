<?php
include('../include/init.inc.php');

$filter = array(
  'max_age' => 30*24*60*60, // 30 days, in seconds
  'author' => null,
  'project' => null,
  'limit' => null,
  );

if (isset($_GET['max_age']))
{
  $filter['max_age'] = (int)$_GET['max_age'];
}

if (isset($_GET['author']))
{
  $filter['author'] = $_GET['author'];
}

if (isset($_GET['project']))
{
  $filter['project'] = $_GET['project'];
}

if (isset($_GET['limit']))
{
  $filter['limit'] = (int)$_GET['limit'];
}

$query = '
SELECT
    project_id,
    name,
    url,
    local_id,
    author,
    occured_on,
    message
  FROM commit
    JOIN project ON project_idx = project_id
  WHERE 1=1';

if (isset($filter['max_age']))
{
  $query.= '
    AND occured_on > SUBDATE(NOW(), INTERVAL '.$filter['max_age'].' SECOND)';
}

if (isset($filter['author']))
{
  $query.= '
    AND author = "'.pwg_db_real_escape_string($filter['author']).'"';
}

if (isset($filter['project']))
{
  $query.= '
    AND project = "'.pwg_db_real_escape_string($filter['project']).'"';
}


$query.= '
  ORDER BY occured_on DESC';

if (isset($filter['limit']))
{
  $query.= '
  LIMIT '.$filter['limit'];
}

$query.= '
;';
$commits = query2array($query);

header('Content-Type: application/json');
echo json_encode($commits);
exit();
