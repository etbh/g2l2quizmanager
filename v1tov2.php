<?php
require_once('Quiz.php');
require_once('v2.php');

foreach (array('WhoWantsToBeAJedi', 'YouVSTheWorld', 'QuaranteDeuxCases') as $type) {
    require_once($type.'.php');
    $folder = $type::Folder;
    foreach(scandir($folder)as $file){
        if ($file[0] != '.'){
            $quiz = $type::import($folder.'/'.$file);

            $v2theme = new v2\Theme();
            $v2theme->theme = $quiz->getTitle();

            foreach ($quiz->questions as $level => $levelquestions)
                foreach ($levelquestions as $question){
                    $v2question = new \v2\Question();
                    $v2question->statement = $question->getStatement();
                    $v2question->difficulty = $level;
                    $v2question->author = '?';
                    ($v2question->verified = $question->isVerified())
                        && $v2question->verifiedby = '?';

                    foreach ($question->getAnswers() as $answerid => $answer){
                        $v2answer = new \v2\Answer();
                        $v2answer->text = $answer;
                        $v2answer->width = ($answerid == $question->getGoodAnswerIndex()) ? 5 : 3;

                        $v2question->answers[] = $v2answer;
                    }

                    if ($type == 'WhoWantsToBeAJedi')
                        shuffle($v2question->answers);
                    if ($type == 'QuaranteDeuxCases')
                        $v2question->difficulty = 10;

                    $v2theme->questions[] = $v2question;
                }

            file_put_contents(
                'data/'.implode(explode(' ', strtolower($v2theme->theme))),
                json_encode($v2theme, JSON_PRETTY_PRINT)
            );

        }
    }
}