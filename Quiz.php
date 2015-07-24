<?php
require_once('Question.php');


abstract class Quiz implements JsonSerializable
{
	public		$questions;
	protected	$title;
	protected	$filename;
	protected	$savetime;
	private		$levels_count;
	private		$answers_by_level;
	private		$are_multiple_questions;
	private		$questions_labels;
	
	public function __construct($levels, $answers, $multiple, $labels){
		$this->levels_count = $levels;
		$this->answers_by_level = is_array($answers)?$answers:array_fill(0, $levels, $answers);
		$this->are_multiple_questions = $multiple;
		$this->questions_labels = $labels;
		$this->questions = array_fill(0, $levels, array());
		$this->title = null;
	}

	public function clear(){
		for($level=0; $level<$this->levels_count; $level ++) {
			$question = new Question();
			$question->setStatement('');
			for ($i = 0; $i < $this->answers_by_level[$level]; $i++)
				$question->addAnswer('', $i == 0);
		 	$this->questions[$level] = array($question);
		 }
		 return $this;
	}

	public function getTitle(){
		return $this->title;
	}

	public final function addQuestion(Question $question, $level)	{
		if ($level < 0 || $level >= $this->levels_count)
			throw new Exception('Invalid level number');
		if (!$this->are_multiple_questions && count($this->questions[$level]) != 0)
			throw new Exception('There is already a question for this level.');
		if ($question->getAnswersCount() != $this->answers_by_level[$level])
			throw new Exception('The question contains a wrong number of answers');
		if (!$question->isGoodAnswerSet())
			throw new Exception("The question doesn't have any good answer.");
		$this->questions[$level][] = $question;
	}

	public final function countQuestions()	{
		$total = 0;
		foreach ($this->questions as $level) {
			$total += count($level);
		}
		return $total;
	}

	public final function isCompleted(){
		for($i=0; $i<$this->levels_count; $i++)
			if (count($this->questions[$i]) == 0)
				return false;
		return $this->title != null;
	}

	public function save(&$newsavetime){
		$conflict = false;
		if ($this->filename == null){
			$class = get_class($this);
			$this->filename = $class::Folder.'/'.implode(explode(' ', strtolower($this->title)));
		}
		else{
			$backup = dirname($this->filename).'/.'.$this->savetime.'-'.basename($this->filename);
			copy($this->filename, $backup);
			$conflict = (filemtime($this->filename) != $this->savetime);
		}
		$this->export($this->filename);
		clearstatcache();
		$newsavetime = filemtime($this->filename);
		$this->savetime = $newsavetime;
		if ($conflict){
			$secondbackup = dirname($this->filename).'/.'.$this->savetime.'-'.basename($this->filename);
			copy($this->filename, $secondbackup);
			file_put_contents('conflicts', "$backup $secondbackup\n"  , FILE_APPEND | LOCK_EX);
		}
		return !$conflict;
	}


	public function jsonSerialize(){
		return array(
			'info'		=> $this->getInfo(),
			'questions'	=> $this->questions,
			'title'		=> $this->title,
			'filename'	=> $this->filename,
			'savetime'	=> $this->savetime
		);
	}

	public function getInfo(){
		return array(
			"type"					=> get_class($this),
			"levels_count" 			=> $this->levels_count,
			"answers_by_level" 		=> $this->answers_by_level,
			"are_multiple_questions"=> $this->are_multiple_questions,
			"questions_labels" 		=> $this->questions_labels
		);
	}
	public static function importFromParsedJson(stdClass $object){
		require_once($object->info->type.'.php');
		$quiz = new $object->info->type();
		$quiz->title	= $object->title;
		$quiz->filename	= $object->filename;
		$quiz->savetime	= $object->savetime;
		foreach ($object->questions as $levelnumber => $level) {
			foreach ($level as $questionimport) {
				$question = new Question();
				$question->setStatement($questionimport->statement);
				foreach ($questionimport->answers as $answerindex => $answer) {
					if ($answerindex < $quiz->answers_by_level[$levelnumber])
						$question->addAnswer($answer, $questionimport->goodanswerindex == $answerindex);
				}
				$question->setVerified($questionimport->verified);
				$quiz->addQuestion($question, $levelnumber);
			}
		}
		if (!$quiz->isCompleted())
			throw new Exception("Invalid quiz data");
		return $quiz;
	}
}

