<?php
namespace v2;
require_once('v2.php');

$theme = themeFromJson(file_get_contents('data/'.$_SERVER['QUERY_STRING']));

echo "<h1>{$theme->theme}</h1>";
foreach($theme->questions as $question){
	echo "<h2>{$question->statement}</h2>";
	echo '<ul>';
	foreach($question->answers as $answer){
		echo "<li>{$answer->text}</li>";
	}
	echo '</ul>';

}