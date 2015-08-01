<?php
require_once('Quiz.php');
require_once('v2.php');

shell_exec('git init data');

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

            if ($type == 'QuaranteDeuxCases')
                $v2theme->theme .= ' 42';

            file_put_contents(
                'data/'.($file = preg_replace('/[^\x20-\x7E]/','', implode(explode(' ', strtolower($v2theme->theme))))),
                json_encode($v2theme, JSON_PRETTY_PRINT)
            );

            chdir('data');
            shell_exec('git add '.escapeshellarg($file));
            chdir('..');
        }
    }
}

chdir('data');
shell_exec("git config user.name manager");
shell_exec("git config user.email chimyx@g2l2corp.com");
shell_exec("git commit -m \"data from old system\"");