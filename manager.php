<?php
namespace v2;
require_once('v2.php');

echo '<h1>Thèmes de quiz</h1>';
echo '<ul>';

foreach(scandir('data')as $file) if ($file[0] != '.'){
	$theme = themeFromJson(file_get_contents('data/'.$file));
	echo "<li><a href=\"?$file\">{$theme->theme}</a></li>";
}
echo '</ul>';
