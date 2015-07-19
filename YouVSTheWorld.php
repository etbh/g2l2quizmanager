<?php
require_once('Question.php');
require_once('Quiz.php');
class YouVSTheWorld extends Quiz
{
	const ProperName = 'You VS The World';
	const Folder = 'ytw';
	public function __construct(){
		parent::__construct(
			15,
			array_merge(
				array_fill(0, 10, 2),
				array_fill(10, 5, 3)
			),
			false,
			array_merge(
				array_fill(0,5,'facile'),
				array_fill(5, 5, 'moyen'),
				array_fill(10, 5, 'difficile')
			)
		);
	}
	public function export($file){
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace=true;
		$defi=$doc->createElement('defi');
		$defi->setAttribute('titre', $this->title);
		$doc->appendChild($defi);
		for ($i=0; $i<15; $i++){
			$question = $doc->createElement('question');
			$question->appendChild($doc->createElement('texte', htmlspecialchars($this->questions[$i][0]->getStatement())));
			$question->appendChild($reponse_list = $doc->createElement('reponse_list'));
			$reponse_list->setAttribute('rep_valid',1 + $this->questions[$i][0]->getGoodAnswerIndex());
			$reponse_list->setAttribute('verified', $this->questions[$i][0]->isVerified()?'true': 'false');
			$answers = $this->questions[$i][0]->getAnswers();
			for ($j=0; $j < count($answers); $j++)
				$reponse_list->appendChild($doc->createElement('reponse', htmlspecialchars($answers[$j])));
			$defi->appendChild($question);
		}
		$doc->formatOutput = true;
		file_put_contents($file, $doc->saveXML());
	}

	public static function import($file){
		$quiz = new YouVSTheWorld();
		$quiz->filename = $file;
		$quiz->savetime = filemtime($file);
		$dom = new DOMDocument();
		$dom->loadXML(file_get_contents($file));
		$quiz->title = $dom->getElementsByTagName('defi')->item(0)->getAttribute('titre');
		if ($quiz->title == ''){
			$quiz->title = trim(str_replace(array('YTW', '.xml'), '', basename($file)));
		}
		$questionsnodes = $dom->getElementsByTagName('question');
		foreach ($questionsnodes as $level => $questionnode) {
			$question = new Question();
			foreach ($questionnode->childNodes as $node) {
				switch ($node->nodeName){
					case 'texte':
						$question->setStatement($node->textContent);
						break;
					case 'reponse_list':
						$goodanswerindex = (int) $node->attributes->getNamedItem('rep_valid')->value - 1;
						$answerindex = 0;
						$question->setVerified($node->attributes->getNamedItem('verified')->value == 'true');
						foreach ($node->childNodes as $answer) if ($answer->nodeName == 'reponse') {
							$question->addAnswer($answer->textContent,$goodanswerindex==$answerindex++);
						}
						break;
				}
			}
			$quiz->addQuestion($question, $level);
		}
		if (!$quiz->isCompleted())
			throw new Exception("Quiz file incomplete");
		return $quiz;
	}

}

// $y = YouVSTheWorld::import('YTW.xml');
// //echo json_encode($y->getInfo(), JSON_PRETTY_PRINT);
// $y->export("YTW2.xml");
