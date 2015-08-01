<?php
namespace v2;
echo (urldecode($_SERVER['QUERY_STRING']));
if (strlen($_SERVER['QUERY_STRING']) && file_exists('data/'.urldecode($_SERVER['QUERY_STRING'])))
    include 'editor.php';
else
    include 'manager.php';

?>
