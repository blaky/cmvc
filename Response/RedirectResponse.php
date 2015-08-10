<?php

namespace CacaoFw\Response;

class RedirectResponse extends AbstractResponse {

    private $url;

    public function __construct($url = '/index.html') {
        $this->url = $url;
    }

    public function build($requestParameters, $cfw) {
        header("Location: $this->url");
    }
}