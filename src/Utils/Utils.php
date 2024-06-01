<?php

namespace Cxis\Utils;

class Utils{

	public static function redirectTo($url){
        echo "<script>location.href='{$url}'</script>";
    }
  
    public static function isHTMX(){
        $output = false;
        if(isset($_SERVER['HTTP_HX_REQUEST']) AND $_SERVER['HTTP_HX_REQUEST']){ $output = true ; }
        return $output;
    }

    public static function isAssigned($required='any',$userrole=null){
        $userroles=!is_null($userrole)?$userrole:(!isset($GLOBALS['sessions']['userroles'])?'guest':strtolower($GLOBALS['sessions']['userroles']));
        $userkeys=explode(",", $userroles);
        $reqroles=strpos('_'.$required, 'role_')>0?$GLOBALS['security_group']['roles'][$required]['value']:strtolower($required);
        $reqkeys=explode(",", 'master,sm_master,'.$reqroles);
        $ok=0; $bad=0;
        if(in_array('any',$reqkeys) OR (in_array('master',$userkeys) OR in_array('sm_master', $userkeys))){
            $ok==$ok+1;
        }else{
            foreach($userkeys as $role){
                if(in_array($role,$reqkeys)){ $ok=$ok+1; }
                else{ $bad=$bad+1; }
            }
        }
        return $ok>0?true:false;
    }

    public static function isLogin(){
        return (isset($GLOBALS['sessions']['logged_in']) AND $GLOBALS['sessions']['logged_in']) ? true : false ;
    }


	public static function inSelection( $key, $against ){
		$keys = explode(",",$key);
		$_ok = 0; $_bad = 0;
		$needles = explode(",",$against);
		foreach($needles as $needle) :
			if( in_array($needle, $keys) ){ $_ok++; }
			else{ $_bad++; }
		endforeach ;
		return $_ok > 0 ? true : false ;
	}

    public static function isNotAssigned($required,$userrole=null){
        $userroles=!is_null($userrole)?$userrole:(!isset($GLOBALS['sessions']['userroles'])?'guest':strtolower($GLOBALS['sessions']['userroles']));
        $userkeys=explode(",", $userroles);
        $reqroles=strpos('_'.$required, 'role_')>0?$GLOBALS['security_group'][$required]:strtolower($required);
        $reqkeys=explode(",", $reqroles);
        $ok=0; $bad=0;
        foreach($userkeys as $role){
            if(in_array($role,$reqkeys)){ $bad=$bad+1; }
            else{ $ok=$ok+1; }
        }
        return $ok>0?true:false;
    }

	public static function unset_session( $prefix = null , $keys = null ){
		$prefix = is_null( $prefix ) ? $GLOBALS['config']['session_key'] : $prefix ;
		foreach($_SESSION as $key => $value) {
            if( $keys != null ){
                if (strpos($key, "{$prefix}") === 0 AND in_array( $key , $keys ) ){ unset($_SESSION[$key]); }
            }else{ 
                unset($_SESSION[$key]);
            }
		}
	}

	public static function get_session( $prefix = null , $keys = null ){
		$prefix = is_null( $prefix ) ? $GLOBALS['config']['session_key'] : $prefix ;
		$session_key = [];
		foreach($_SESSION as $key => $value) {
			if( $keys != null ){
				if (strpos($key, "{$prefix}") === 0 AND in_array( $key , $keys ) ){ $session_key[ str_replace($prefix.'_','' ,$key) ] = $_SESSION[$key]; }
			}else{
				$session_key[ str_replace($prefix.'_','' ,$key) ] = $_SESSION[$key];
			}
		}
		return $session_key;
	}


	public static function validateToken($token=null,$lockdown=null){
		$valid = false ;
		if(!isset($_POST['_token']) or !isset($_SESSION[$GLOBALS['config']['session_key'].'_token']) or (!is_null($lockdown) and !isset($_SESSION[$GLOBALS['config']['session_key'].'_token2'])) ){
			$valid = false ;
		}else{
			$token = is_null( $token ) ? $_POST['_token'] : $token ;
			if( is_null($lockdown) ){
				if( hash_equals($_SESSION[$GLOBALS['config']['session_key'].'_token'],$token) ){ $valid = true ; }
			}else{
				$calc = hash_hmac('sha256', $lockdown, $_SESSION[$GLOBALS['config']['session_key'].'_token2']);
				if( hash_equals($calc, $token) ) { $valid = true ; }
			}
		}
		return $valid ;
	}

	public static function uuid( $input = [10] ){
		$src_str  = ((isset($input[0]) AND $input[0]!='') AND !is_numeric($input[0])) ? $input[0] : '1234567890abcdef';
		$cnt      = count($input);
		$chk_last = is_numeric($input[count($input)-1]) ? '' : $input[count($input)-1] ;
		$stt_item = ((isset($input[0]) AND $input[0]!='') AND !is_numeric($input[0])) ? 1 : 0;
		$min_item = is_numeric($input[count($input)-1]) ? 0 : 1 ;
		$input_length = strlen($src_str);
		$rand_string  = '';
		for($i = $stt_item ; $i<count($input)-$min_item ; $i++){
			for($x = 0; $x < $input[$i]; $x++){
				$rand_character = $src_str[mt_rand(0, $input_length - 1)];
				$rand_string .= $rand_character;
			}
			$rand_string .= ($i == (count($input)-( $min_item + 1 ))) ? '' : $chk_last ;
		}
		return $rand_string;
	}

    public static function uuid_v4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function uuid_v5( $array = [5,5,6,'-'] ){
        $separator = is_numeric( $array[count($array) - 1] ) ? '' : $array[count($array) - 1] ;
        $arrLength = is_numeric( $array[count($array) - 1] ) ? count($array) - 2 : count($array) - 1 ;
        $output = null ;
        for($i = 0 ; $i < $arrLength ; $i++ ){
            $output .= ( $output == null ? '' : $separator ) . bin2hex( random_bytes( ( $array[$i] - ( $array[$i] % 2 ) ) / 2 ) ) ;
        }
        return strtoupper( $output ) ;
    }

    public static function str_rand( int $length = 16 ){
        $length = ( $length < 4 ) ? 4 : $length;
        return bin2hex( random_bytes( ( $length - ( $length % 2 ) ) / 2 ) );
    }

	public static function clean( string $string ){
        $string = trim($string);
        $string = preg_replace('/[^a-zA-Z0-9\,\/_|+ .-:]/', '', $string);
        return str_ireplace(' ','_',$string);
    }

	public static function toJson( $data , $errorTxt = null ){
        $payload = isset( $data ) ? $data : $errorTxt ;
        return json_encode( $payload , JSON_PRETTY_PRINT ) ;
    }

	public static function enc( $string ){
		$substr = substr( $string , 0 , 6) ;
		if( $substr == '$_json' ){ return substr( $string , 6 , strlen( $string ) - 6 ) ; }
        else{ return htmlspecialchars( $string, ENT_QUOTES ) ; }
	}

	public static function dtNow(){
		return date('Y-m-d h:i:s');
	}

	public static function fileSize($size){
		if( $size > 1000000000 ){ return number_format($size/1024000000,2) . ' GB'; }
		if( $size > 1000000 ){ return number_format($size/1024000,2) . ' MB'; }
		return number_format($size/1024,2) . ' KB';
	}

	public static function toDatetime($string,$dt=0){
		if($dt==0){ return self::toDTFunction($string,0); }
		elseif($dt==1){ return self::toDTFunction($string,1); }
		elseif($dt==2){ 
			$date=explode(" ",$string)[0];
			return self::toDTFunction($date,0).' '.self::toDTFunction($string,1); 
		}
	}

	public static function toDTFunction($string,$dt=0){
		if($dt==0){
			$dateparts=explode('/',$string);
			if( count($dateparts) > 1 ){
				$i=0;
				if( $string != null and ( is_countable($dateparts) and count($dateparts)>0 ) ){
					foreach(explode("/",CONFIG['date_format']) as $part){
								if($part=='m' or $part=='mm'){ $month=$i; }
								elseif($part=='d' or $part=='dd'){ $day=$i; }
								elseif($part=='y' or $part=='yy' or $part=='yyyy'){ $year=$i; }
								$i++;
						}
						$date=checkdate($dateparts[$month] , $dateparts[$day], $dateparts[$year] ) ? ( $dateparts[$year] .'-'. $dateparts[$month] .'-'. $dateparts[$day] ) : null ;
				}else{
					$date=null;
				}
			}else{
				$date=$string;
			}
		}elseif($dt==1){
			$date=date("H:i:s",strtotime($string));
		}
		return $date;
	}

	public static function toMaskDateTime($date,$dateTime=0){
		$dateFormat = str_replace('yyyy','Y',str_replace('mm','m',str_replace('dd','d',$GLOBALS['config']['date_format'])));
		$timeFormat = str_replace('HH','H',str_replace('hh','h',str_replace('MM','i',str_replace('SS','s',str_replace('tt','a',str_replace('TT','A',$GLOBALS['config']['time_format']))))));
		$dateTimeFormat = $dateFormat.' '.$timeFormat;
		return $dateTime==2 ? date( $dateTimeFormat , strtotime($date) ) : ( $dateTime==1 ? date( $timeFormat , strtotime($date) ) : date( $dateFormat , strtotime($date) ) ) ;
	}

}