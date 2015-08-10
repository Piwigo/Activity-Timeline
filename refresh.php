<?php
if (isset($_SERVER['HTTP_HOST']))
{
  die('this is a CLI script, bye');
}

include(dirname(__FILE__).'/include/init.inc.php');

// +-----------------------------------------------------------------------+
// | Basic page settings                                                   |
// +-----------------------------------------------------------------------+

$page['script'] = basename(__FILE__);
$page['lock_file'] = '/tmp/'.$page['script'].'.lock';

$opt = getopt('', array('verbose'));

// +-----------------------------------------------------------------------+
// | Single instance                                                       |
// +-----------------------------------------------------------------------+

if (file_exists($page['lock_file']))
{
  # is the script really running?
  $pid = file_get_contents($page['lock_file']);

  if (file_exists('/proc/'.$pid))
  {
    echo '['.$page['script'].' pid='.$pid.'] in progress, another instance already running';
    exit();
  }
  else
  {
    echo '['.$page['script'].'] notify admin because the previous execution seems to go wrong;';
    echo ' lock file removed automatically';
    echo "\n";
    unlink($page['lock_file']);
  }
}

file_put_contents($page['lock_file'], getmypid());

// +-----------------------------------------------------------------------+
// | Main loop                                                             |
// +-----------------------------------------------------------------------+

// fetch projects
$query = '
SELECT *
  FROM project
  ORDER BY project_id DESC
;';
$projects = query2array($query);

// loop on projects
foreach ($projects as $project)
{
  $commits = array();
  $output = null;
  $last_commit = null;
  
  // get latest commit of the project
  $query = '
SELECT
    local_id,
    occured_on
  FROM commit
  WHERE project_idx = '.$project['project_id'].'
  ORDER BY commit_id DESC
  LIMIT 50
;';
  $old_commits = query2array($query, 'local_id');
  foreach ($old_commits as $commit)
  {
    $last_commit = $commit;
    break;
  }
  // print_r($last_commit); exit();

  if (isset($project['local_dir']))
  {
    chdir($project['local_dir']);
  }
  
  // SVN or Git?
  if (preg_match('{piwigo.org/svn}', $project['url']))
  {
    if (!isset($last_commit))
    {
      $last_commit = array(
        'local_id' => 1,
        );
    }

    $command = 'svn log --xml -r'.$last_commit['local_id'].':HEAD '.$project['url'];
    // echo $command."\n";
    
    exec($command, $output);
    // print_r($output); exit();

    $xml_string = implode('', $output);
    // print ($xml_string);

    $xml = simplexml_load_string($xml_string);
    // print_r($xml);

    foreach ($xml->logentry as $logentry)
    {
      $logentry = (array)$logentry;
      // print_r($logentry);

      if (isset($old_commits[$logentry['@attributes']['revision']]))
      {
        continue;
      }
    
      $commit = array(
        'project_idx' => $project['project_id'],
        'local_id' => $logentry['@attributes']['revision'],
        'author' => pwg_db_real_escape_string($logentry['author']),
        'occured_on' => $logentry['date'],
        'message' => pwg_db_real_escape_string($logentry['msg']),
        );
      
      $commits[] = $commit;
    }
  }
  elseif (preg_match('{github.com}', $project['url']))
  {
    // if (!file_exists($project['local_dir'].'/.git'))
    if (!isset($project['local_dir']))
    {
      // we have to clone the repo
      chdir($conf['repos_dir']);

      // $github_path = str_replace('https://github.com/', 'git@github.com:', $project['url']);
      $github_path = $project['url'];

      $command = 'git clone '.$github_path;
      echo $command."\n";
      exec($command);

      $local_dir = $conf['repos_dir'].'/'.array_pop(explode('/', $project['url']));

      single_update(
        'project',
        array('local_dir' => $local_dir),
        array('project_id' => $project['project_id'])
        );

      chdir($local_dir);
    }
    

    $command = 'git pull';
    exec($command);

    $command = 'git log --reverse --pretty=format:"%H~#~%an~#~%ad~#~%s" --date=iso8601';
    
    if (isset($last_commit))
    {
      $command.= ' --since="'.$last_commit['occured_on'].'"';
    }
    
    // echo $command."\n";
    
    exec($command, $output);
    // print_r($output); exit();

    foreach ($output as $logentry)
    {
      $logentry = explode('~#~', $logentry);
      // print_r($logentry); exit();

      if (isset($old_commits[ $logentry[0] ]))
      {
        continue;
      }

      $commit = array(
        'project_idx' => $project['project_id'],
        'local_id' => $logentry[0],
        'author' => pwg_db_real_escape_string($logentry[1]),
        'occured_on' => $logentry[2],
        'message' => pwg_db_real_escape_string($logentry[3]),
        );
      
      $commits[] = $commit;
    }
  }
  else
  {
    die('project ['.$project['project_id'].'] '.$project['local_dir'].' is neither SVN nor Git, please check!');
  }

  foreach ($commits as $commit)
  {
    single_insert('commit', $commit);
  }

  if (count($commits) > 0)
  {
    echo '['.$project['name'].'] '.count($commits).' commits added'."\n";
  }
  else
  {
    if (isset($opt['verbose']))
    {
      echo '['.$project['name'].'] no new commit'."\n";
    }
  }
}

// +-----------------------------------------------------------------------+
// | Unlock script                                                         |
// +-----------------------------------------------------------------------+

@unlink($page['lock_file']);
exit();
?>