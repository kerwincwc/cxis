<?php

namespace Cxis\Utils;

class Flash{
    public static function set($msg, $class='info'){
        $classes = ['info','success','danger','primary','warning','error'];
        $class = in_array($class,$classes)?($classes=='error'?'danger':$class):'info';
        $_SESSION[$GLOBALS['_config']['session_key'].'_flashbag'][] = ['msg'=>$msg,'class'=>$class];
        return $_SESSION[$GLOBALS['_config']['session_key'].'_flashbag'];
    }
}