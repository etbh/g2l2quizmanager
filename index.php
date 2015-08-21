<?php
namespace v2;

$query = urldecode($_SERVER['QUERY_STRING']);
$file = explode('_', $query)[0];
$branch;
if (!empty($file)){
	if ($file != $query)
		$branch = $query;
	include 'editor.php';
}
else
	include 'manager.php';

?>
