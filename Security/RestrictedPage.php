<?php

namespace CacaoFw\Security;

class RestrictedPage {

    public $url;

    public $method;

    public $level;

    public function __construct($level, $url, $method) {
        $this->url = $url;
        $this->level = $level;
        $this->method = $method;
    }
}