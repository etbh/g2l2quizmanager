<?php
require_once('Question.php');
require_once('Quiz.php');
class WhoWantsToBeAJedi extends Quiz
{
	const ProperName = 'Who Wants to be a Jedi?';
	const Folder = 'jedi';
	public function __construct(){
		parent::__construct(
			15,
			4,
			true,
			array_merge(
				array_fill(0,5,'facile'),
				array_fill(5, 5, 'moyen'),
				array_fill(10, 5, 'difficile')
			)
		);
	}

	public function export($file){
		$fd = fopen($file, 'w');
		fwrite($fd, "title=\"".addslashes($this->title)."\";\n\n");
		foreach ($this->questions as $level => $questions_at_level) {
			$level_questions_count = count($questions_at_level);
			$level_write = $level + 1;
			fwrite($fd, "questions$level_write=$level_questions_count;\n");
			foreach ($questions_at_level as $index => $question) {
				$index_write = $index + 1;
				$statement = addslashes($question->getStatement());
				$answers = $question->getAnswers();
				$verified = $question->isVerified();
				$good_answer_index = $question->getGoodAnswerIndex();
				$good_answer = $answers[$good_answer_index];
				$answers[$good_answer_index] = $answers[0];
				$answers[0] = $good_answer;
				foreach ($answers as $answerid => $answer)
					$answers[$answerid] = addslashes($answer);
				fwrite($fd, "s${level_write}q${index_write}=\"$statement\";\n");
				fwrite($fd, "s${level_write}a${index_write}=new Array(\"${answers[0]}\", \"${answers[1]}\", \"${answers[2]}\", \"${answers[3]}\");\n");
				fwrite($fd, "s${level_write}v${index_write}=".($verified?'true':'false').";\n");
			}
			fwrite($fd, "\n");
		}
		fclose($fd);
	}

	public static function import($file){
		$fd = fopen($file, 'r');
		$quiz = new WhoWantsToBeAJedi();
		$quiz->title = $file;
		$quiz->filename = $file;
		$quiz->savetime = filemtime($file);
		$questions = array_fill(1, 15, array());
		while (!feof($fd)){
			$matches = array();
			$line = fgets($fd);
			preg_match("#title\s*=\s*\"(.+)\";#" , $line, $matches);
			if (count($matches) != 0){
				$quiz->title = stripslashes($matches[1]);
				continue;
			}
			preg_match("#questions(\d+)\s*=\s*(\d+)#" , $line, $matches);
			if (count($matches) != 0){
				//$questions[$matches[1]] = array_fill(1, $matches[2], new Question());
				for($i=1; $i<=$matches[2]; $i++)
					$questions[$matches[1]][$i] = new Question();
				continue;
			}
			preg_match("#s(\d+)q(\d+)\s*=\s*\"(.+)\"#", $line, $matches);
			if (count($matches) != 0){
				$questions[$matches[1]][$matches[2]]->setStatement(stripslashes($matches[3]));
				continue;
			}
			preg_match("#s(\d+)a(\d+)\s*=\s*new\s+Array\s*\(\s*\"(.*)\"\s*,\s*\"(.*)\"\s*,\s*\"(.*)\"\s*,\s*\"(.*)\"\s*\)\s*;#",  $line, $matches);
			if (count($matches) != 0){
				$questions[$matches[1]][$matches[2]]->resetAnswers();
				$questions[$matches[1]][$matches[2]]->addAnswer(stripslashes($matches[3]), true);
				$questions[$matches[1]][$matches[2]]->addAnswer(stripslashes($matches[4]), false);
				$questions[$matches[1]][$matches[2]]->addAnswer(stripslashes($matches[5]), false);
				$questions[$matches[1]][$matches[2]]->addAnswer(stripslashes($matches[6]), false);
				continue;
			}
			preg_match("#s(\d+)v(\d+)\s*=\s*(true|false)\s*;#", $line, $matches);
			if (count($matches) != 0){
				$truefalse = array('true' => true, 'false' => false);
				$questions [$matches [1] ] [$matches [2] ] -> setVerified($truefalse[$matches[3]]);
				continue;
			}
		}
		fclose($fd);
		foreach ($questions as $level => $questions_at_level)
			foreach ($questions_at_level as $question)
				$quiz->addQuestion($question, $level - 1);
		if (!$quiz->isCompleted())
			throw new Exception("Quiz file incomplete");
		return $quiz;
			
	}

}

// $q = WhoWantsToBeAJedi::import('jedi.txt');
// echo var_dump(json_decode(json_encode($q)));
// echo json_last_error();
//$q->export('jedi2.txt');

?>
