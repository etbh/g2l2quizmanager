<?php
class Question implements JsonSerializable
{
	private $statement;
	private $answers = array();
	private $goodanswerindex = -1;
	private $verified = false;
	public function getStatement(){
		return $this->statement;
	}	
	public function getGoodAnswer(){
		return $this->answers[$this->getGoodAnswerIndex()];
	}
	public function getAnswers(){
		$answers = $this->answers;
		return $answers;
	}
	public function resetAnswers(){
		$this->answers = array();
		$this->goodanswerindex = -1;
	}
	public function getGoodAnswerIndex(){
		if ($this->goodanswerindex == -1)
			throw new Exception("Good answer not set.");
		return $this->goodanswerindex;
	}
	public function isVerified(){
		return $this->verified;
	}
	public function setStatement($statement){
		$this->statement = (string) $statement;
	}
	public function addAnswer($text, $good){
		if ($good){
			if($this->goodanswerindex != -1)
				throw new Exception('There is already a good answer.');
			$this->goodanswerindex = count($this->answers);
		}
		$this->answers[] = $text;
	}
	public function getAnswersCount(){
		return count($this->answers);
	}
	public function isGoodAnswerSet(){
		return ($this->goodanswerindex != -1);
	}
	public function setVerified($verified){
		$this->verified = (bool) $verified;
	}
	public function jsonSerialize(){
		return array(
			'statement'			=> $this->statement,
			'answers'			=> $this->answers,
			'goodanswerindex'	=> $this->goodanswerindex,
			'verified'			=> $this->verified
		);
	}
}


?>
