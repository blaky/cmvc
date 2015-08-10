<?php

namespace CacaoFw\Response;

class InternalRedirectResponse extends AbstractResponse {

    private $path;

    public function __construct($path) {
        $this->path = $path;
    }

    public function build($requestParameters, $cfw) {
        // do nothing;
    }

    public function getPath() {
        return $this->path;
    }
}