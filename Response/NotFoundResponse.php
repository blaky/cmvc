<?php

namespace CacaoFw\Response;

class NotFoundResponse extends AbstractResponse {

    private $message;

    public function __construct($message = "The requested content is not found.") {
        $this->message = $message;
    }

    public function getResponseCode() {
        return 404;
    }

    public function build($requestParameters, $cfw) {
        http_response_code(404);
        $msg = $this->message;
        include __DIR__ . '/../Resources/notFoundError.php';
    }
}