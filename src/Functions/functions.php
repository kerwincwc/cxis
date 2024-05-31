<?php

/* CLEAN HTML OUTPUT */
function tidyHTML($buffer) {
    $buffer = str_ireplace("\n\n", "", str_ireplace("\n ", "", str_ireplace("\t", "", str_ireplace("\t ", "", str_ireplace("  ", "", $buffer ) ) ) ) );
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadHTML($buffer, LIBXML_NOERROR);
    $dom->formatOutput = true;
    return($dom->saveHTML());
}
  
/* MINIFY CSS */
function minify_css($css) {
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    $css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $css);
    $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '',$css);
    return $css;
}
  
/* RENDER TEMPLATE */
function render_template($template,$params=[]){
    $template = "$template.twig.html";
    if($GLOBALS['ENV']['template']['env']=='prod'){
      try{ print_r( tidyHTML( $GLOBALS['twig']->render( $template, $params ) ) ) ; }
      catch(\Exception $e){ print_r( \Cxis\Utils\Errors::error( 404 , 1 ) ) ; }
    }else{
      print_r( tidyHTML( $GLOBALS['twig']->render( $template, $params ) ) ) ;
    }
}
  
/* PAGE REDIRECTION */
function redirect($url,$ext=false){
    if(!$ext){ return \Cxis\Utils\Utils::redirectTo($GLOBALS['config']['root'] . '/' . $url) ; }
    else{ return \Cxis\Utils\Utils::redirectTo($url) ; }
}