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

      $basedir = dirname(__DIR__,5);
      
      if( file_exists("{$basedir}\\{$GLOBALS['config']['webappdir']}\\static\\{$filename}") ){
        $filecontents = file_get_contents("{$basedir}\\{$GLOBALS['config']['webappdir']}\\static\\{$filename}" , true ) ;
        $fileexists = true;
      }elseif( file_exists( "{$basedir}\\core\\webapp\\static\\{$filename}" ) ){
        $filecontents = file_get_contents( "{$basedir}\\core\\webapp\\static\\{$filename}" , true ) ;
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
          // $output = dirname(__DIR__,5)."/core/webapp/static/$filename" ;
      }
      return $output ;
    }


  static function bundle_asset( array $files, string $type = 'js' ){
    $basedir = dirname(__DIR__,5);
    $known_mimes = [
      'css'=>'text/css',
      'js'=>'application/javascript',
    ];
    if(in_array($type,['css','js'])){
      foreach($files as $filename){
        $filepath = explode( "/" , $filename )[ count( explode( "/" , $filename ) ) - 1 ] ;
        $fileext = explode( "." , $filepath )[ count( explode( "." , $filepath ) ) - 1 ] ;
        if( file_exists("{$basedir}\\{$GLOBALS['config']['webappdir']}\\static\\{$filename}") ){
          $filecontents = file_get_contents("{$basedir}\\{$GLOBALS['config']['webappdir']}\\static\\{$filename}" , true ) ;
          $fileexists = true;
        }elseif( file_exists( "{$basedir}\\core\\webapp\\static\\{$filename}" ) ){
          $filecontents = file_get_contents( "{$basedir}\\core\\webapp\\static\\{$filename}" , true ) ;
          $fileexists = true;
        }else{
          $fileexists = false;
        };
        if( $fileexists ){
          if( isset( $known_mimes[ $fileext ] ) AND $known_mimes[ $fileext ] === $known_mimes[ $type ] ){
            ob_start();
            print_r( $filecontents ) ;
            $output .= ob_get_contents();
            ob_clean();
          }
        }
      }
      header( "content-type:{$known_mimes[ $type ]}" );
      ob_start();
      print_r( $output );
      $output = ob_get_contents();
      ob_clean();
      return $output;
    }
  }

}