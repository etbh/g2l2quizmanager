<?php
require_once($_GET['type'].'.php');
$quiz = isset($_GET['quiz']) ? $_GET['type']::import($_GET['quiz']) : (new $_GET['type']())->clear();
// $quiz;
// if (isset($_GET['quiz']))
// 	$quiz = $_GET['type']::import($_GET['quiz']);
// else{
// 	$quiz = new $_GET['type']();
// 	$quiz->empty();
//}

?>
<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<script type="application/json" id=data><?php echo json_encode($quiz) ?></script>
	<style>
	body{
		font-family : sans-serif;
	}
	table{
		border-collapse: collapse;
	}
	td, th{
		border: 1px solid lightgrey;
	}
	tr:last-child td, th{
		border-bottom-color: grey;
	}
	.goodanswer{
		font-weight : bold;
	}
	b, i, u, span{
		font-weight:inherit;
		font-style:inherit;
		text-decoration:inherit;
	}
	.clickable{
		cursor:pointer;
	}
	/* to fix
	.edited{
		background-color: lavender;
	}
	.justedited{
		background-color: lightgrey;
	}
	*/
	.hidden{
		visibility: hidden;
	}
	h1, h2 {
		display: inline;
	}
	em{
		color: grey;
	}
	</style>
<body>
	<div><a href="?">Retour</a></div>
	<h1>Quiz - </h1>
	<h2 id=title contenteditable></h2>
	<table border="1" id="quiztable">
		<thead><tr>
			<th colspan=4 id="levelheader">Niveau</th>
			<th>Question</th>
			<th id="responseheader">Réponses</th>
			<th>Vérifié?</th>
		</tr></thead>
	</table>
	<button id=save></button>
	<button id=grab></button>
	<p>/\ et \/ permettent de changer le niveau des questions</p>
	<p>+ permet d&apos;ajouter une question</p>
	<p>x permet de supprimer une question</p>
	<p>> permet de modifier la bonne réponse</p>
</body>
	<script type="text/javascript">
		var quizzes = new Array();
		var quiz = JSON.parse(document.getElementById('data').innerHTML);
		var table = document.getElementById('quiztable');
		var edited = false;
		var editedQuestions = new Array();
		var savebutton = document.getElementById('save');
		var grabbutton = document.getElementById('grab');
		var xhr = new XMLHttpRequest();
		var title = document.getElementById('title');
		title.textContent = quiz.title;
		title.addEventListener('input',function(){quiz.title = title.textContent; hasBeenEdited();} , false);
		window.onbeforeunload=function(){
			if (edited)
				return "Les modifications apportées au quiz n'ont pas été enregistrées.";
		};
		grabbutton.show = function(){
			grabbutton.classList.remove('hidden');
		};
		grabbutton.hide = function(){
			grabbutton.classList.add('hidden');
		};
		grabbutton.textContent = "Cliquez ici pour récupérer le fichier contenant le quiz afin de l'envoyer plus tard.";
		grabbutton.addEventListener('click',function(){
			saveAs(new Blob([JSON.stringify(quiz)], {type: "application/json"}), "test.quiz");
		} , false);
		savebutton.switchmode_nodedited = function(){
			savebutton.textContent = "Sauvegarder";
			savebutton.disabled = true;
			grabbutton.hide();
		};
		savebutton.switchmode_edited = function(){
			savebutton.textContent = "Sauvegarder";
			savebutton.disabled = false;
			grabbutton.hide();
		};
		savebutton.switchmode_saving = function(){
			savebutton.textContent = "Envoi en cours...";
			savebutton.disabled = true;
			grabbutton.hide();
		};
		savebutton.switchmode_saved = function(){
			savebutton.textContent = "Sauvegardé :)";
			savebutton.disabled = true;
			grabbutton.hide();
		};
		savebutton.switchmode_failedsaving = function(){
			savebutton.textContent = "Échec de la sauvegarde.";
			savebutton.disabled = true;
			grabbutton.show();
		};
		savebutton.addEventListener('click', function(){
			xhr.abort();
			xhr = new XMLHttpRequest();
			xhr.open("POST", "save.php", true);
			document.getElementById('');
			edited = false;
			savebutton.switchmode_saving();
			xhr.onreadystatechange = function(){
				if (xhr.readyState == 4){
					if (xhr.status == 200){
						var result = JSON.parse(xhr.responseText);
						if (result.success){
							quiz.savetime = result.newsavetime;
							savebutton.switchmode_saved();
							if (result.conflict)
								alert("Ce quiz a été modifié récemment par quelqu'un d'autre. Les deux versions ont été sauvegardées.");
						}
						else
							savebutton.switchmode_failedsaving();
					}
					else
						savebutton.switchmode_failedsaving();
				}
			};
			xhr.send(JSON.stringify(quiz));
		}, false);
		savebutton.switchmode_nodedited();
		function hasBeenEdited(question){
			edited = true;
			if (quiz.title != null)
				savebutton.switchmode_edited();
			xhr.abort();
			editedQuestions.unshift(question);
		}
		function gcf(a, b) { 
			return ( b == 0 ) ? (a):( gcf(b, a % b) ); 
		}
		function _lcm(a, b) { 
			return ( a / gcf(a,b) ) * b;
		}
		function lcm(){
			var  m = arguments[0];
			for (var i=1; i<arguments.length; i++)
				m = _lcm(m, arguments[i]);
			return m;
		}
		function callback_input(domrow, data, info){
			return function(){
				hasBeenEdited(data);
				updateData(domrow, data, info);
			}
		}
		function callback_changeLevel(domrow, data, info, shiftsign){
			return function(){
				shiftsign = Math.abs(shiftsign) / shiftsign;
				hasBeenEdited(domrow);
				var oldlevel = data.level;
				var oldindex = data.index;
				var questions = quizzes[data.quizid].questions;
				var newlevel = oldlevel + shiftsign;

				var swapquestion = null;
				if (questions[oldlevel].length == 1)
					swapquestion = questions[newlevel][(shiftsign==1)?'shift':'pop']();
				
				quizzes[data.quizid].questions[oldlevel].splice(oldindex, 1)[0];
				questions[newlevel][(shiftsign==1)?'unshift':'push'](data);

				if (swapquestion != null)
					questions[oldlevel].push(swapquestion);

				updateLevelsData(questions, info);
				updateTable(domrow, questions, info);
			};
		}
		function callback_shiftGoodAnswer(domrow, data, info){
			return function(){
				hasBeenEdited(domrow);
				data.goodanswerindex += 1;
				if (data.goodanswerindex == info.answers_by_level[data.level])
					data.goodanswerindex = 0;
				updateRow(domrow, data, info);
			};
		}
		function callback_addQuestion(domrow, data, info){
			return function(){
				var questions = quizzes[data.quizid].questions;
				var newquestion = new Object();
				newquestion.statement = '';
				newquestion.answers = new Array();
				for (var i=0; i<info.answers_by_level[data.level]; i++)
					newquestion.answers.push('');
				newquestion.goodanswerindex = 0;
				newquestion.quizid = data.quizid;
				questions[data.level].push(newquestion);
				hasBeenEdited(newquestion);
				updateTable(domrow, questions, info);
			}
		}
		function callback_deleteQuestion(domrow, data, info){
			return function(){
				quizzes[data.quizid].questions[data.level].splice(data.index, 1);
				updateTable(domrow, quizzes[data.quizid].questions, info)
			}
		}
		function createRow(domrow, info){
			domrow.levelminus	= domrow.insertCell(-1);
			domrow.levelplus	= domrow.insertCell(-1);
			domrow.leveladd		= domrow.insertCell(-1);
			domrow.leveldel		= domrow.insertCell(-1);
			if (info.are_multiple_questions){
				document.getElementById('levelheader').colSpan=6;
			}
			else
			{
				domrow.leveladd.parentNode.removeChild(domrow.leveladd);
				domrow.leveldel.parentNode.removeChild(domrow.leveldel);
			}
			domrow.level		= domrow.insertCell(-1);
			domrow.label		= domrow.insertCell(-1);
			domrow.statement	= domrow.insertCell(-1);
			domrow.answers		= new Array();
			domrow.ansswitch	= domrow.insertCell(-1);
			domrow.verify		= domrow.insertCell(-1);
			
			domrow.statement.contentEditable=true;

			domrow.levelminus	.textContent = '/\\';
			domrow.levelplus	.textContent = '\\/';
			if (domrow.leveladd)
				domrow.leveladd	.textContent = '+';
			if (domrow.leveldel)
				domrow.leveldel	.textContent = 'x';
			domrow.ansswitch	.textContent = '>';
			domrow.verifycheckbox = document.createElement('input');
			domrow.verifycheckbox.setAttribute('type', 'checkbox');
			domrow.verify		.appendChild(domrow.verifycheckbox);


			domrow.levelminus	.classList.add('clickable');
			domrow.levelplus	.classList.add('clickable');
			domrow.leveladd		.classList.add('clickable');
			domrow.leveldel		.classList.add('clickable');
			domrow.ansswitch	.classList.add('clickable');

			domrow.ansswitch.answerindex = null;
		}
		function attachEventsToRow(domrow, data, info){
			if (domrow				.inputListener)
				domrow				.removeEventListener('input', domrow.inputListener, false);
			domrow					.inputListener = callback_input(domrow, data, info);
			domrow					.addEventListener('input', domrow.inputListener, false);

			if (domrow.levelminus	.clickListener)
				domrow.levelminus	.removeEventListener('click', domrow.levelminus.clickListener, false);
			domrow.levelminus		.clickListener = callback_changeLevel(domrow, data, info, -1);
			domrow.levelminus		.addEventListener('click', domrow.levelminus.clickListener, false);

			if (domrow.levelplus	.clickListener)
				domrow.levelplus	.removeEventListener('click', domrow.levelplus.clickListener, false);
			domrow.levelplus		.clickListener = callback_changeLevel(domrow, data, info, +1);
			domrow.levelplus		.addEventListener('click', domrow.levelplus.clickListener, false);

			if (domrow.leveladd		.clickListener)
				domrow.leveladd		.removeEventListener('click', domrow.leveladd.clickListener, false);
			domrow.leveladd			.clickListener = callback_addQuestion(domrow, data, info);
			domrow.leveladd			.addEventListener('click', domrow.leveladd.clickListener, false);

			if (domrow.leveldel		.clickListener)
				domrow.leveldel		.removeEventListener('click', domrow.leveldel.clickListener, false);
			domrow.leveldel			.clickListener = callback_deleteQuestion(domrow, data, info);
			domrow.leveldel			.addEventListener('click', domrow.leveldel.clickListener, false);

			if (domrow.ansswitch	.clickListener)
				domrow.ansswitch	.removeEventListener('click', domrow.ansswitch.clickListener, false);
			domrow.ansswitch		.clickListener = callback_shiftGoodAnswer(domrow, data, info);
			domrow.ansswitch		.addEventListener('click', domrow.ansswitch.clickListener, false);

			if (domrow.verifycheckbox.changeListener)
				domrow.verifycheckbox.removeEventListener('change', domrow.verifycheckbox.changeListener, false);
			domrow.verifycheckbox	.changeListener = callback_input(domrow, data, info);
			domrow.verifycheckbox	.addEventListener('change', domrow.verifycheckbox.changeListener, false)

		}
		function updateRow(domrow, data, info){
			console.log('updateRow');
			domrow.level	.textContent = data.level + 1;
			domrow.label	.textContent = info.questions_labels[data.level];
			domrow.statement.textContent = data.statement;
			if (data.level == 0){
				domrow.levelminus.classList.add('hidden');
			}
			else if (data.level== info.levels_count - 1){
				domrow.levelplus.classList.add('hidden');
			}
			else{
				domrow.levelplus .classList.remove('hidden');
				domrow.levelminus.classList.remove('hidden');
			}
			if (data.index == quizzes[data.quizid].questions[data.level].length - 1)
				domrow.leveladd.classList.remove('hidden');
			else
				domrow.leveladd.classList.add('hidden');
			if (quizzes[data.quizid].questions[data.level].length == 1)
				domrow.leveldel.classList.add('hidden');
			else
				domrow.leveldel.classList.remove('hidden');
			while (domrow.answers.length < data.answers.length){
				var newanswercell = domrow.insertCell(domrow.cells.length-2);
				newanswercell.contentEditable=true;
				domrow.answers.push(newanswercell);
			}
			while (domrow.answers.length > info.answers_by_level[data.level]){
				domrow.removeChild(domrow.answers.pop());
			}
			var colspan = lcm.apply(null, info.answers_by_level) / info.answers_by_level[data.level];
			for (var i in domrow.answers){
				domrow.answers[i].colSpan = colspan;
			}
			for (var i in domrow.answers){
				domrow.answers[i].textContent = data.answers[i];
				domrow.answers[i].className = data.goodanswerindex == i ? 'goodanswer' : '';
			}
			domrow.verifycheckbox.checked = data.verified;
			var editrank = editedQuestions.indexOf(data);
			if (editrank == 0)
				domrow.className = 'justedited';
			else if(editrank > 0)
				domrow.className = 'edited';
			else
				domrow.className = '';
		}
		function updateTable(domtable, data, info){
			console.log('updateTable');
			while (domtable.tagName != 'TABLE')
					domtable = domtable.parentNode;
			// var totalquestions = 0;
			// for (var i in data)
			// 	totalquestions += data[i].length;
			// while (domtable.rows.length -1 > totalquestions){
			// 	domtable.deleteRow(-1);
			// }
			// while (domtable.rows.length -1 < totalquestions){
			// 	var row = table.insertRow(-1);
			// 	createRow(row, info);
			// }
			// updateLevelsData(data, info);
			while (domtable.tBodies.length < data.length)
				domtable.appendChild(document.createElement('tbody'));
			while (domtable.tBodies.length > data.length)
				document.removeChild(domtable.tBodies[domtable.tBodies.length]);
			updateLevelsData(data, info);
			for (var level in data){
				while (domtable.tBodies[level].rows.length < data[level].length){
					var row = domtable.tBodies[level].insertRow(-1);
					createRow(row, info);
				}
				while (domtable.tBodies[level].rows.length > data[level].length){
					domtable.tBodies[level].deleteRow(-1);
				}
				for (index in data[level]){
					attachEventsToRow(domtable.tBodies[level].rows[index], data[level][index], info);
					updateRow(domtable.tBodies[level].rows[index], data[level][index], info);
				}
			}
			// var rowindex = 1;
			// for (var level in data){
			// 	for (var index in data[level]){
			// 		attachEventsToRow(domtable.rows[rowindex], data[level][index], info);
			// 		updateRow(domtable.rows[rowindex++], data[level][index], info);
			// 	}
			// }
		}
		function updateData(domrow, data, info){
			console.log('updateData');
			data.statement	= domrow.statement.textContent;
			for (var i in data.answers)
				if (domrow.answers[i])
					data.answers[i] = domrow.answers[i].textContent;
			data.verified = domrow.verifycheckbox.checked;
		}
		function updateLevelsData(data, info){
			for (var level in data){
				for (var index in data[level]){
					data[level][index].level = parseInt(level);
					data[level][index].index = parseInt(index);
					while (data[level][index].answers.length < info.answers_by_level[level]){
						data[level][index].answers.push('');
					}
					if (data[level][index].goodanswerindex >= info.answers_by_level[level]){
						var goodanswer = data[level][index].answers.splice(data[level][index].goodanswerindex, 1)[0];
						var newgoodanswerindex = info.answers_by_level[level] - 1;
						data[level][index].answers.splice(newgoodanswerindex, 0, goodanswer);
						data[level][index].goodanswerindex = newgoodanswerindex;
					}
				}
			}
		}
		document.getElementById('responseheader').colSpan = lcm.apply(null, quiz.info.answers_by_level) + 1;
		var quizid = quizzes.push(quiz) - 1;
		for (var level in quiz.questions)
			for (var index in quiz.questions[level])
				quiz.questions[level][index].quizid = quizid;
		updateTable(table, quiz.questions, quiz.info);
		if (quiz.title == null)
			title.innerHTML='Nouveau quiz (éditez le titre)';
	</script>
	<script type="application/ecmascript" async="" src="https://raw.github.com/eligrey/FileSaver.js/master/FileSaver.js"></script>
</html>
