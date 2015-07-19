<?php
require_once('Quiz.php');
$parsedquiz = json_decode($HTTP_RAW_POST_DATA);
$quiz = Quiz::importFromParsedJson($parsedquiz);
$savetime;
$conflict = !($quiz->save($savetime));
echo json_encode(array(	'success' => true, 
						'conflict' => $conflict,
						'newsavetime' => $savetime));
?>