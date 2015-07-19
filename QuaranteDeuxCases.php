<?php
require_once('Question.php');
require_once('Quiz.php');
class QuaranteDeuxCases extends Quiz
{
	const ProperName = '42 Cases';
	const Folder = '42';
	public function __construct(){
		parent::__construct(
			7,
			1,
			false,
			array_fill(0, 7, 'difficile')
		);
	}

	public static function import($file){
		$fd = fopen($file, 'r');
		$quiz = new QuaranteDeuxCases();
		$quiz->title = utf8_encode(fgets($fd));
		$quiz->filename = $file;
		$quiz->savetime = filemtime($file);
		$level_count = 0;
		while (!feof($fd)){
			$lines = array();
			while (($line = trim(utf8_encode(fgets($fd)))) != ''){
				$lines[] = $line;
			}
			if (count($lines) > 0){
				$question = new Question();
				$question->setStatement(array_shift($lines));
				$question->addAnswer(implode(' ', $lines), true);
				$quiz->addQuestion($question, $level_count ++);
			}
		}
		while ($level_count < 7){
			$question = new Question();
			$question->setStatement('');
			$question->addAnswer('', true);
			$quiz->addQuestion($question, $level_count ++);
		}
		fclose($fd);
		if (!$quiz->isCompleted())
			throw new Exception("Quiz file incomplete");
		return $quiz;
	}

	public function export($file){
		$fd = fopen($file, 'w');
		$ret = fwrite($fd, utf8_decode($this->title."\n\n"));
		foreach ($this->questions as $level){
			$question = $level[0];
			fwrite($fd, utf8_decode($question->getStatement()."\n".$question->getGoodAnswer()."\n\n"));
		}
		fclose($fd);
	}
}