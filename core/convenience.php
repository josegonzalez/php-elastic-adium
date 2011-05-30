<?php
define('DS', DIRECTORY_SEPARATOR);

function out($message = null, $newlines = 1) {
    if (is_array($message)) {
        $message = implode(nl(), $message);
    }
    echo $message . nl($newlines);
}

function nl($multiplier = 1) {
    return str_repeat("\n", $multiplier);
}

function debug($var = false, $showHtml = false, $showFrom = true) {
    $file = '';
    $line = '';
    if ($showFrom) {
        $calledFrom = debug_backtrace();
        $file = $calledFrom[0]['file'];
        $line = $calledFrom[0]['line'];
    }
    $html = <<<HTML
<strong>%s</strong> (line <strong>%s</strong>)
<pre class="debug">
%s
</pre>
HTML;
    $text = <<<TEXT

%s (line %s)
########## DEBUG ##########
%s
###########################

TEXT;
    $template = $html;
    if (php_sapi_name() == 'cli') {
        $template = $text;
    }
    if ($showHtml === null && $template !== $text) {
        $showHtml = true;
    }
    $var = print_r($var, true);
    if ($showHtml && php_sapi_name() != 'cli') {
        $var = str_replace(array('<', '>'), array('&lt;', '&gt;'), $var);
    }
    printf($template, $file, $line, $var);

}
function diebug($var = false, $showHtml = false, $showFrom = true) {
    $file = '';
    $line = '';
    if ($showFrom) {
        $calledFrom = debug_backtrace();
        $file = $calledFrom[0]['file'];
        $line = $calledFrom[0]['line'];
    }
    $html = <<<HTML
<strong>%s</strong> (line <strong>%s</strong>)
<pre class="debug">
%s
</pre>
HTML;
    $text = <<<TEXT

%s (line %s)
########## DEBUG ##########
%s
###########################

TEXT;
    $template = $html;
    if (php_sapi_name() == 'cli') {
        $template = $text;
    }
    if ($showHtml === null && $template !== $text) {
        $showHtml = true;
    }
    $var = print_r($var, true);
    if ($showHtml && php_sapi_name() != 'cli') {
        $var = str_replace(array('<', '>'), array('&lt;', '&gt;'), $var);
    }
    printf($template, $file, $line, $var);
	die;
}