<?php
session_start();

use CacaoFw\CacaoFw;
use CacaoFw\Utils;

global $u, $starttime;
$starttime = microtime(true);

// Windows fix
setlocale(LC_MONETARY, 'en_GB');
if (!function_exists("money_format")) {

    function money_format($format, $number) {
        return number_format($number, 2);
    }
}

function cacaoClassLoader($class) {
    $classPath = preg_split("/\\\\/", $class);

    if ($classPath[0] === 'CacaoFw') {
        // Load framework classes.
        $path = join(array_splice($classPath, 1), "//");
        $path = realpath(__DIR__ . '/' . $path . '.php');
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    } else if ($classPath[0] === 'App') {
        // Load app classes.
        $path = join(array_splice($classPath, 1), "//");
        $path = realpath(__DIR__ . '/../app/src/' . $path . '.php');
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    } else {
        $path = realpath(__DIR__ . '/../app/vendor/' . join($classPath, '/') . '.php');
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
}

function errorHandler($errno, $errstr, $errfile, $errline, array $errcontext) {
    if (0 === error_reporting()) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function string($name, $print = true, $langcode = null) {
    global $LANG;
    $result = $LANG->findString($name, $langcode);

    if ($print) {
        echo $result;
    } else {
        return $result;
    }
}

require_once __DIR__ . '/../app/constants.php';

global $u;
set_error_handler('errorHandler');
spl_autoload_register('cacaoClassLoader');
$u = new Utils();
$cfw = new CacaoFw($u);