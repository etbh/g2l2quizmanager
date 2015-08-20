<?php
namespace v2;

/*
    Structure :
    {
        'theme'     : 'xx',
        'tags'      : [ 'xx', 'xx'],
        'questions' : [
                        'id'            : xxxxxxx
                        'statement'     : 'xx',
                        'difficulty'    : x,
                        'author'        : 'xx',
                        'verified'      : bool,
                        'verifiedby'    : 'xx',
                        'lastused'      : xxxxxxx,
                        'answers'       : [
                                            'text'      : 'xx',
                                            'weight'    : x
                        ]
        ]
    }

*/

class Theme
{
    public $theme;
    public $tags = array();
    public $questions = array();

}

class Answer
{
    public $text;
    public $weight;
}

class Question
{
    public $id;
    public $statement;
    public $difficulty;
    public $author;
    public $verified = false;
    public $verifiedby;
    public $lastused;
    public $answers = array();
}


function themeFromJson($json){
    $toobject = function($parsedjson, $object){
        foreach ($parsedjson as $key => $value){
            $object->$key = $value;
        }
    };
    $parsed = json_decode($json);
    $theme = new Theme();
    $toobject($parsed, $theme);
    foreach ($theme->questions as $qid => $parsedquestion){
        $question = new Question();
        $toobject($parsedquestion, $question);

        foreach ($question->answers as $aid => $parsedanswer){
            $answer = new Answer();
            $toobject($parsedanswer, $answer);
            $question->answers[$aid] = $answer;
        }

        $theme->questions[$qid] = $question;
    }
    usort($theme->questions, function($a,$b) {return $a->difficulty > $b->difficulty;});

    return $theme;
}

function jsonFromTheme($theme){
    $theme = clone $theme;
    usort($theme->questions, function($a,$b) {return $a->id > $b->id;});
    return json_encode($theme, JSON_PRETTY_PRINT);
}
