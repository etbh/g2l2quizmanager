<?php
foreach (array('WhoWantsToBeAJedi', 'YouVSTheWorld', 'QuaranteDeuxCases') as $type) {
	require_once($type.'.php');
	$name = $type::ProperName;
	$folder = $type::Folder;
	echo "<h1>$name</h1><ul>";
	
	foreach(scandir($folder)as $file){
		if ($file[0] != '.'){
			$quiz = $type::import($folder.'/'.$file);
			$title = htmlentities($quiz->getTitle());


			$verified = 0;
			foreach ($quiz->questions as $level)
				foreach ($level as $question)
					if ($question->isVerified())
						$verified ++;
			$verified = floor($verified * 100 / $quiz->countQuestions());
			echo "<li><a href=\"?type=$type&quiz=$folder%2F$file\">$title</a>".($quiz->getInfo()['are_multiple_questions']?' - '.$quiz->countQuestions().' questions':'')." ($verified% v&eacute;rifi&eacute;)</li>";
		}
	}
	echo "</ul><a href=\"?type=$type\">Nouveau quiz</a>";
}
?>
