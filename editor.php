<?php
namespace v2;
require_once('v2.php');

git_mode(true, $branch);
$theme = themeFromJson(file_get_contents($file));
$commit = empty($branch)?git_last_commit():$branch;
git_mode(false);

?>
<style>
	.empty .text, .empty .weight{
		opacity: .2;
	}

	.question.empty{
		opacity: .3;
	}

	.question.empty .statement:after{
		content : '...';
	}
	.nogoodanswer label[for="5"]{
		color : red;
	}
	h2{
		margin-bottom: 0px;
	}
	.author{
		font-size: .8em;
		font-style: italic;
	}
	#savezone{
		font-size: .8em;
		margin : .5em;
	}
</style>
<?php

echo "<h1 id=theme contenteditable>{$theme->theme}</h1>";
echo '<button onclick="save()">Sauvegarder</button>';
echo '<div id="savezone"></div>';
echo '<div class="questions">';
foreach($theme->questions as $question){
	echo "<div class=question data-id={$question->id}><h2><span class=difficulty contenteditable>{$question->difficulty}</span>";
	echo " - <span class=statement contenteditable>{$question->statement}</span></h2>";
	if ($question->author)
		echo "<span class=author>{$question->author}</span>";
	echo '<ul>';
	foreach($question->answers as $answer){
		echo "<span class=answer><li class=text contenteditable>{$answer->text}</li>";
		echo '<form class=weight>';
		foreach ([1 => 'Déconne', 'Peu crédible', 'Crédible', 'Très crédible', 'Bonne réponse'] as $weight => $text){
			echo '<input name=weight type=radio value='.$weight.($answer->weight==$weight?' checked' :'').'>';
			echo "<label for=$weight>$text</label>";
		}
		echo '</form></span>';
	}
	echo '</ul></div>';
}
echo '</div>';
?>
<script>
	var commit = "<?= $commit ?>";
	var theme = "<?= $file ?>";

	var emptyDomAnswer = function(){
		var answer = document.querySelector('.answer').cloneNode(true);
		answer.querySelector('.text').textContent = '';
		[].forEach.call(answer.querySelectorAll('.weight input'), function(domCheck){
			domCheck.checked = domCheck.value == 3;
		});
		answer.classList.add('empty');
		return answer;
	}();

	var emptyDomQuestion = function(){
		var question = document.querySelector('.question').cloneNode(true);
		question.dataset.id = '';
		question.querySelector('.statement').textContent = "";
		question.querySelector('.difficulty').textContent = "";
		[].forEach.call(question.querySelectorAll('.answer'),function(domAnswer){
			domAnswer.remove();
		});
		question.querySelector('ul').appendChild(emptyDomAnswer.cloneNode(true));
		question.classList.add('empty');
		return question;
	}();

	function getData() {
		var data = {
			'theme' : document.querySelector('#theme').textContent,
			'tags' : [],
			'questions' : []
		};
		[].forEach.call(document.querySelectorAll('.question:not(.empty)'), function(questionNode) {
			var question = {
				'id' : questionNode.dataset.id,
				'statement' : questionNode.querySelector('.statement').textContent,
				'difficulty' : questionNode.querySelector('.difficulty').textContent,
				'author' : questionNode.querySelector('.author')?questionNode.querySelector('.author').textContent:null,
				'answers' : []
			};
			[].forEach.call(questionNode.querySelectorAll('.answer:not(.empty)'), function(answerNode){
				question.answers.push({
					'weight' : answerNode.querySelector('.weight input:checked').value,
					'text' : answerNode.querySelector('.text').textContent
				});
			});
			data.questions.push(question);
		});
		return data;
	}

	function save() {
		var xhr = new XMLHttpRequest();
		xhr.open('post', 'save.php?theme='+theme+'&commit='+commit, false);
		xhr.send(JSON.stringify(getData()));
		commit = xhr.responseText;
		document.querySelector("#savezone").textContent = "Sauvegardé à " + new Date().toLocaleTimeString();
		if (commit.indexOf(theme) == 0)
			document.querySelector("#savezone").innerHTML +=
				"<br>Ce quiz a été modifié par quelqu'un d'autre depuis le chargement de cette page. " +
				"Les deux versions différentes seront combinées par un modérateur. Vous pouvez continuer " +
				"à éditer votre version du quiz à <a href=?" + commit + ">cette adresse</a>.";
	}

	function checkGoodAnswerPresence(domQuestions){
		var goodAnswers = domQuestions.querySelectorAll('input[value="5"]:checked');
		domQuestions.classList.toggle('nogoodanswer', goodAnswers.length == 0);
		return goodAnswers;
	}

	function onWeightChanged(event) {
		var goodAnswers = checkGoodAnswerPresence(event.target.parentElement.parentElement.parentElement);
		if (event.target.value == 5) {
			[].forEach.call(goodAnswers, function (domRadioButton) {
				domRadioButton.parentElement.querySelector('input[value="4"]').checked = true;
			});
			event.target.checked = true;
		}
	}

	function onAnswerEdited(event){
		var text = event.target;
		var textContent = text.textContent.trim();
		var answer = text.parentElement;
		if (textContent == ''){
			if (!answer.classList.contains('empty')){
				answer.nextSibling.firstChild.focus();
				var parent = answer.parentElement;
				answer.remove();
				checkGoodAnswerPresence(parent);
			}
		}
		else {
			if (answer.classList.contains('empty')){
				addEmptyAnswerAfter(answer);
				answer.classList.remove('empty');
			}
		}
	}

	function onQuestionEdited(event){
		var statement = event.target;
		var textContent = statement.textContent.trim();
		var question = statement.parentElement.parentElement;
		if (textContent == '' && !question.querySelector('.answer:not(.empty)')){
			if (!question.classList.contains('empty')){
				question.nextElementSibling.querySelector('.statement').focus();
				question.remove();
			}
		}
		else {
			if (question.classList.contains('empty')){
				question.classList.remove('empty');
				addEmptyQuestionAfter(question, question.querySelector('.difficulty').textContent);
			}
		}
	}

	function onDifficultyChanged(event){

	}

	function onAnswerKeyPress(event){
		if (event.keyCode == 13) {
			event.preventDefault();
			event.target.parentElement.nextSibling.firstChild.focus();
		}
	}

	function initDomAnswer(domAnswer){
		var text = domAnswer.querySelector('.text');
		text.addEventListener('input', onAnswerEdited);
		text.addEventListener('keypress', onAnswerKeyPress);
		[].forEach.call(domAnswer.querySelectorAll('.weight input'), function (domRadioButton) {
			domRadioButton.addEventListener('click', onWeightChanged);
		});
		checkGoodAnswerPresence(domAnswer.parentNode);
	}

	function initDomQuestion(domQuestion){
		domQuestion.querySelector('.statement').addEventListener('input', onQuestionEdited);
		domQuestion.querySelector('.difficulty').addEventListener('input', onDifficultyChanged);
		[].forEach.call(domQuestion.querySelectorAll('.answer'), initDomAnswer);
	}

	[].forEach.call(document.querySelectorAll('.question .answer:last-child'), function(domLastAnswer){
		addEmptyAnswerAfter(domLastAnswer);
	});

	[].forEach.call(document.querySelectorAll('.question'), function(domQuestion) {
		initDomQuestion(domQuestion);
		var nextDomQuestion = domQuestion.nextElementSibling;
		if (!nextDomQuestion || parseInt(domQuestion.querySelector('.difficulty').textContent) < parseInt(nextDomQuestion.querySelector('.difficulty').textContent)) {
			var newNode = domQuestion.parentNode.insertBefore(emptyDomQuestion.cloneNode(true), nextDomQuestion);
			newNode.querySelector('.difficulty').textContent = domQuestion.querySelector('.difficulty').textContent;
			initDomQuestion(newNode);
		}
	});

	function addEmptyAnswerAfter(domNode){
		var newEmptyAnswer = domNode.parentElement.appendChild(emptyDomAnswer.cloneNode(true));
		initDomAnswer(newEmptyAnswer);
		return newEmptyAnswer;
	}

	function addEmptyQuestionAfter(domNode, level){
		var newEmptyQuestion = domNode.parentElement.insertBefore(emptyDomQuestion.cloneNode(true), domNode.nextSibling);
		initDomQuestion(newEmptyQuestion);
		newEmptyQuestion.querySelector('.difficulty').textContent = level;
		return newEmptyQuestion;
	}

</script>

