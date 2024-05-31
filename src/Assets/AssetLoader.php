<?php

namespace Cxis\Assets;

class AssetLoader{

    static function load_asset( $filename ){

        $filepath = explode( "/" , $filename )[ count( explode( "/" , $filename ) ) - 1 ] ;
        $fileext = explode( "." , $filepath )[ count( explode( "." , $filepath ) ) - 1 ] ;
        $known_mimes = [
          'css'=>'text/css',
          'js'=>'application/javascript',
          'json'=>'application/json',
          'png'=>'image/png',
          'jpg'=>'image/jpg',
          'jpeg'=>'image/jpeg',
          'gif'=>'image/gif',
          'woff'=>'font/woff',
          'woff2'=>'font/woff2',
          'ttf'=>'font/ttf',
          'svg'=>'image/svg+xml',
        ];
        
        if( file_exists( __BASEDIR__."/".$GLOBALS['config']['webappdir']."/static/$filename" ) ){
          $filecontents = file_get_contents( __BASEDIR__."/".$GLOBALS['config']['webappdir']."/static/$filename" , true ) ;
          $fileexists = true;
        }elseif( file_exists( __DIR__ . "/webapp/static/$filename" ) ){
          $filecontents = file_get_contents( __DIR__."/webapp/static/$filename" , true ) ;
          $fileexists = true;
        }else{
          $fileexists = false;
        };
        
        if( $fileexists ){
            if( isset( $known_mimes[ $fileext ] ) ){
                header( "content-type:{$known_mimes[ $fileext ]}" );
                ob_start();
                print_r( $filecontents ) ;
                $output = ob_get_contents();
                ob_clean();
            }else{
                $output = \Cxis\Utils\Errors::error( 415, 1 ) ;
            }
        }
        else{
            $output = \Cxis\Utils\Errors::error( 404, 1 ) ;
        }
        return $output ;
      }

}