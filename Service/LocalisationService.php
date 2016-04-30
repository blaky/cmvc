<?php

namespace CacaoFw\Service;

use CacaoFw\CacaoFw;

class LocalisationService {
    private static $initialized = false;

    /**
     *
     * @var CacaoFw;
     */
    private $config;
    public $strings;
    public $code;
    public $supportedLanguages;

    public static function init($config) {
        if (! self::$initialized) {
            self::$initialized = true;
            return new LocalisationService($config);
        }

    }

    private function __construct($config) {
        global $u;

        $this->langcode = null;

        $this->config = $config;

        // List available languages.
        $this->supportedLanguages = array_filter(array_map(function ($value) {
            $info = pathinfo($value);
            if ($info["extension"] == 'php') {
                return $info["filename"];
            } else {
                return false;
            }
        }, scandir($u->getAppDir() . '/lang/')));

        // Check whether user just tries to change the display language.
        if (isset($_REQUEST['lang']) && in_array($_REQUEST['lang'], $this->supportedLanguages)) {
            $this->langcode = $_REQUEST['lang'];
        }

        if (!$this->langcode && isset($_SESSION['lang'])) {
            // If not try to find previous config in the cookies.
            $this->langcode = $_SESSION['lang'];
        }

        // Fall back to default one.
        if (! $this->langcode) {
            $browserlang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (in_array($browserlang, $this->supportedLanguages)) {
                $this->langcode = $browserlang;
            } else {
                $this->langcode = $config["defaultlang"];
            }
        }

        // Save it to the session.
        $_SESSION['lang'] = $this->langcode;

        $this->strings = array();

    }

    private function loadLanguage($language) {
        global $u;
        if (! array_key_exists($language, $this->strings) && in_array($language, $this->supportedLanguages)) {
            $strings = array();
            require_once $u->getAppDir() . '/lang/' . $language . ".php";
            $this->strings[$language] = $strings;
        }

    }

    public function findString($name, $language = false) {
        // If not specific language requested, use the current one.
        if (! $language) {
            $language = $this->langcode;
        }

        // Validate language selection.
        if (! in_array($language, $this->supportedLanguages)) {
            $language = $this->config["defaultlang"];
        }

        // Esnure the language file is loaded.
        $this->loadLanguage($language);

        if (isset($this->strings[$language][$name])) {
            return $this->strings[$language][$name];
        } else {
            return "#NF-$language-$name#";
        }

    }

    public function getAllStrings($language = false) {
        // If not specific language requested, use the current one.
        if (! $language) {
            $language = $this->langcode;
        }

        // Validate language selection.
        if (! in_array($language, $this->supportedLanguages)) {
            $language = $this->config["defaultlang"];
        }

        // Esnure the language file is loaded.
        $this->loadLanguage($language);

        return $this->strings[$language];

    }

}