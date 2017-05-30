<html>
<head>
<meta charset="utf-8">
<style>
body {font-size:90%;font-family:sans-serif;}
.activity {width:800px; margin:0 auto;}
.day {margin-bottom:30px;}
.day-headline {}
.commit {margin-left:20px;margin-bottom:0px;border:1px solid #eee;padding:10px;margin-top:-1px;}
.commit p {margin:0;}
.commit-headline {font-size:90%;}
.commit-message {font-weight:bold;}
.commit:hover {background-color:#f9f9f9}
</style>
</head>
<body>
<div class="activity">
<?php
include('include/init.inc.php');

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
  WHERE occured_on > SUBDATE(NOW(), INTERVAL 30 DAY)
  ORDER BY occured_on DESC
;';
$commits = query2array($query);
// echo '<pre>'; print_r($commits); echo '</pre>';

$current_day = null;

foreach ($commits as $commit)
{
  $day = date('Y-m-d', strtotime($commit['occured_on']));

  if ($day != $current_day)
  {
    if (isset($current_day))
    {
      echo '</div>';
    }
    
    echo '
<div class="day">
  <p class="day-headline">* '.$day.'</p>
';

    $current_day = $day;
  }

  $commit_id = $commit['local_id'];
  if (strlen($commit['local_id']) == 40)
  {
    $commit_id = substr($commit['local_id'], 0, 8);
  }

  if (preg_match('{http://piwigo.org/svn}', $commit['url']))
  {
    $commit_url = 'http://piwigo.org/dev/changeset/'.$commit['local_id'];
  }
  elseif (preg_match('{https://github.com}', $commit['url']))
  {
    $commit_url = $commit['url'].'/commit/'.$commit['local_id'];
  }

  echo '
<div class="commit">
  <p class="commit-message">'.$commit['message'].'</p>
  <p class="commit-headline">'.$commit['name'].', <a href="'.$commit_url.'">commit '.$commit_id.'</a> by '.$commit['author'].'</p>
</div>
';
}

echo '</div>';
?>
</div>
</body>
</html>
