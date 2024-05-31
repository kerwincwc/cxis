<?php

namespace Cxis\Utils;

class Errors{

    protected $logEvents = false;
    protected $dbTable;

    public static function errorList(){
        $errorList = [
          "400" => 'The requested resource was not found on this server.',
          '401' => 'You are not authorized to view this content.',
          '415' => 'The requested resource is of an unsupported file type.',
          '405' => 'The requested method is not allowed.',
          '425' => 'This resource is not yet available at this time.',
          '503' => 'We are currently undergoing maintenance. We will be right back.'
        ] ;
        return $errorList;
    }

    public static function error( $code, $bool = 1) {
        $errors = self::errorList();
        $error = ['code'=>$code,'message'=>$errors[$code]];
        return self::generateHTML($error,$bool);
    }

    public static function generateHTML($error,$bool=1){
        $uuid = strtoupper(utils::str_rand(8));
        $error['code'] = isset($error['code'])?$error['code']:'Application Error';
        $error['message'] = isset($error['message'])?$error['message']:'Sorry we encountered an error.';
        $error['detail'] = isset($error['detail'])?$error['detail']:$error['message'];
        $error['error_code'] = $uuid;
        $errorMsg = [ 'code'=>$error['code'],'message'=>$error['message'],'hash'=>$uuid  ] ;
        if( $bool ) :
          header( 'content-type:text/html' );
          ob_start();
          echo tidyHTML( $GLOBALS['twig']->render( '@macros/errors.html', ['config'=>$GLOBALS['config'],'error'=>$errorMsg] ) );
          $output = ob_get_contents();
          ob_clean();
        else:
          $output = json_encode( $error , JSON_PRETTY_PRINT ) ;
        endif;
        self::errLog(['code'=>$error['code'],'msg'=>$error['message'],'detail'=>$error['detail']],$uuid);
        return $output;
    }

    public static function errLog($error,$uuid){
        if( self::$logEvents === true ){
            $payload = ['uuid'=>$uuid,'`table`'=>'error','action'=>'log','old_value'=>json_encode($error)];
            $db = new \Cxis\ORM\DB();
            $db->table( self::dbTable )->begin();
            $db->insert( $payload );
            $db->commit();
            return true;
        }
    }

}