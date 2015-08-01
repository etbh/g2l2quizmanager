<?php
namespace v2;

/*
    Structure :
    {
        'theme'     : 'xx',
        'tags'      : [ 'xx', 'xx'],
        'questions' : [
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
    public $questions;

}

class Answer
{
    public $text;
    public $width;
}

class Question
{
    public $statement;
    public $difficulty;
    public $author;
    public $verified = false;
    public $verifiedby;
    public $lastused;
    public $answers = array();
}
