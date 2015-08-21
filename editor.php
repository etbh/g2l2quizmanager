<?php
namespace v2;
require_once('v2.php');


git_mode(true, $branch);
$theme = themeFromJson(file_get_contents($file));
$commit = empty($branch)?git_last_commit():$branch;
git_mode(false);

?>
<style>
	.answer.empty .text, .answer.empty .weight{
		opacity: .2;
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
	//echo "<li class=answer contenteditable></li>";
	echo '</ul></div>';

}
?>
<script>
	var commit = "<?= $commit ?>";
	var theme = "<?= $file ?>";
	function getData() {
		var data = {
			'theme' : document.querySelector('#theme').textContent,
			'tags' : [],
			'questions' : []
		};
		[].forEach.call(document.querySelectorAll('.question'), function(questionNode) {
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

	function onChangeWeight(event) {
		var fullForm = event.target.parentElement.parentElement.parentElement;
		var goodAnswers = fullForm.querySelectorAll('input[value="5"]:checked');
		if (goodAnswers.length == 0)
			fullForm.classList.add('nogoodanswer');
		else
			fullForm.classList.remove('nogoodanswer');
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
		var form = answer.parentElement;
		if (textContent == ''){
			if (!answer.classList.contains('empty')){
				answer.nextSibling.firstChild.focus();
				answer.remove();
			}
		}
		else {
			if (answer.classList.contains('empty')){
				addEmptyAnswerAfter(answer);
				answer.classList.remove('empty');
			}
		}
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
			domRadioButton.addEventListener('click', onChangeWeight);
		});
	}

	[].forEach.call(document.querySelectorAll('.answer'), function (domAnswer) {
		initDomAnswer(domAnswer);
	});

	[].forEach.call(document.querySelectorAll('.question .answer:last-child'), function(domLastAnswer){
		addEmptyAnswerAfter(domLastAnswer);
	});

	function addEmptyAnswerAfter(domAnswer){
		var newEmptyAnswer = domAnswer.parentElement.appendChild(domAnswer.cloneNode(true));
		newEmptyAnswer.querySelector('.text').textContent = '';
		[].forEach.call(newEmptyAnswer.querySelectorAll('.weight input'), function(domCheck){
			domCheck.checked = domCheck.value == 3;
		});
		newEmptyAnswer.classList.add('empty');
		initDomAnswer(newEmptyAnswer);
		return newEmptyAnswer;
	}

</script>

