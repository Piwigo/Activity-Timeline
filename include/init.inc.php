<?php
include('include/functions_mysqli.inc.php');

$conf['db_host'] = 'localhost';
$conf['db_user'] = 'root';
$conf['db_password'] = 'conway';
$conf['db_base'] = 'piwigo_dev_activity';
$conf['show_queries'] = false;
$conf['repos_dir'] = '/tmp/git';

pwg_db_connect(
  $conf['db_host'],
  $conf['db_user'],
  $conf['db_password'],
  $conf['db_base']
  );

pwg_db_check_charset();
?>