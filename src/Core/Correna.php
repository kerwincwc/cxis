<?php

namespace Cxis\Core;

class Correna extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{

    public function getOperators()
    {
        return [
            [
                '!' => ['precedence' => 50, 'class' => \Twig\Node\Expression\Unary\NotUnary::class],
            ],
            [
                '||' => ['precedence' => 10, 'class' => \Twig\Node\Expression\Binary\OrBinary::class, 'associativity' => \Twig\ExpressionParser::OPERATOR_LEFT],
                '&&' => ['precedence' => 15, 'class' => \Twig\Node\Expression\Binary\AndBinary::class, 'associativity' => \Twig\ExpressionParser::OPERATOR_LEFT],
            ],
        ];
    }


    public function getGlobals(): array
    {
        return [
            'sessions' => $GLOBALS['sessions'],
            'server' => $GLOBALS['http'],
            'app' => $GLOBALS['config'],
            'groups' => $GLOBALS['security_group'],
            'reference_lists' => $GLOBALS['customlist'],
            'config' => $GLOBALS['config'],
            'session' => $GLOBALS['sessions'],
            'http' => $GLOBALS['http'],
        ];
    }


    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('toFilesize', function ($size) { return utils::fileSize($size); }),
        ];
    }


    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('randomText', function ($length=10) {
                $length = [ $length ];
                return \Cxis\Utils\Utils::uuid($length);
            }),
            new \Twig\TwigFunction('flash', function () {
                $flashbag = isset($_SESSION[$GLOBALS['config']['session_key'].'_flashbag'])?$_SESSION[$GLOBALS['config']['session_key'].'_flashbag']:[];
                unset( $_SESSION[$GLOBALS['config']['session_key'].'_flashbag'] );
                return $flashbag;
            }),
            new \Twig\TwigFunction('json_to_list', function($json){
                try{ $result = json_decode($json, true); }
                catch(Exception $e){ $result = []; }
                return $result;
            }),
            new \Twig\TwigFunction('form_token',function($lock_to = null) {
                if (empty($_SESSION[$GLOBALS['config']['session_key'].'_token'])) { $_SESSION[$GLOBALS['config']['session_key'].'_token'] = bin2hex(random_bytes(32)); }
                if (empty($_SESSION[$GLOBALS['config']['session_key'].'_token2'])) { $_SESSION[$GLOBALS['config']['session_key'].'_token2'] = random_bytes(32); }
                if (empty($lock_to)) { return $_SESSION[$GLOBALS['config']['session_key'].'_token']; }
                return hash_hmac('sha256', $lock_to, $_SESSION[$GLOBALS['config']['session_key'].'_token2']);
            }),
            new \Twig\TwigFunction('isAssigned',function($required='any',$userrole=null) {
                $userroles=!is_null($userrole)?$userrole:(!isset($GLOBALS['sessions']['userroles'])?'guest':strtolower($GLOBALS['sessions']['userroles']));
                $userkeys=explode(",", $userroles);
                $reqroles=strpos('_'.$required, 'role_')>0?$GLOBALS['security_group'][$required]:strtolower($required);
                $reqkeys=explode(",", 'master,sm_master,'.$reqroles);
                $ok=0; $bad=0;
                if(in_array('any',$reqkeys)){
                    $ok++;
                }elseif(in_array('master',$userkeys) OR in_array('sm_master', $userkeys)){
                    $ok++;
                }else{
                    foreach($userkeys as $role){
                        if(in_array($role,$reqkeys)){ $ok=$ok+1; }
                        else{ $bad++; }
                    }
                }
                return $ok>0?true:false;
            }),
            new \Twig\TwigFunction('isLogIn',function() {
                return (isset($GLOBALS['sessions']['logged_in']) AND $GLOBALS['sessions']['logged_in']) ? true : false ;
            }),
            new \Twig\TwigFunction('getListName',function($value,$list='customlist_yesno') {
                return isset($GLOBALS['customlist'][$list][$value])?(is_array($GLOBALS['customlist'][$list][$value])?$GLOBALS['customlist'][$list][$value][0]:$GLOBALS['customlist'][$list][$value]):null ;
            }),
            new \Twig\TwigFunction('getName',function($value,$tbl,$ret_col='name',$col='id') {
                $db = new PhpOrm\DB;
                $db = $db->table($tbl)->where($col,$value)->first() ;
                return $db[$ret_col];
            }),
            new \Twig\TwigFunction('getValue',function($value,$tbl,$ret_col='name',$col='id') {
                $db = new PhpOrm\DB;
                $db = $db->table($tbl)->where($col,$value)->first() ;
                return $db[$ret_col];
            }),
            new \Twig\TwigFunction('toMaskDate',function($date,$dateTime=0) {
                return utils::toMaskDateTime($date,$dateTime) ;
            }),
            new \Twig\TwigFunction('QRCode',function($text,$type,$size=6) {
                return $GLOBALS['config']['root']."/document/qrcode.html?i={$text}&t={$type}".($size==6?'':"&s=$size") ;
            })
        ];
    }

    
}