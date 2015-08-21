<?php
namespace v2;
require_once('v2.php');

date_default_timezone_set('Europe/Paris');

$filename = $_GET['theme'];
$branch = (explode('_',$_GET['commit'])[0] == $filename) ? $_GET['commit'] : '';
$tomerge = empty($branch)? 'master' : $branch;
$theme = jsonFromTheme(themeFromJson(file_get_contents('php://input')));
$date = date('\t\h\e d/m \a\t H:i:s');


git_mode(true, $branch);

if (git_last_commit() == $_GET['commit']){
	file_put_contents($filename, $theme);
	shell_exec("git add $filename");
	shell_exec("git commit -m \"$filename edited $date\"");
	echo git_last_commit();
}
else {
	$branch = $filename.'_'.time();
	shell_exec("git checkout {$_GET['commit']}");
	shell_exec("git checkout -b $branch");
	file_put_contents($filename, $theme);
	shell_exec("git add $filename");
	shell_exec("git commit -m \"$filename edited $date\"");

	shell_exec("git checkout $tomerge");
	$merge_status = 0; $dummy;
	exec("git merge $branch", $dummy, $merge_status);
	if ($merge_status == 0){
		shell_exec("git branch -D $branch");
		echo git_last_commit();
	}
	else {
		shell_exec('git merge --abort');
		echo $branch;
	}
	echo $ret;
}


git_mode(false);

?>
