<?php

namespace CacaoFw\Response;

class InternalServerErrorResponse extends AbstractResponse {

    private $exception;

    private $stackTrace;

    public function __construct($exception, $stackTrace) {
        $this->exception = $exception;
        $this->stackTrace = $stackTrace;
    }

    public function getExceptionMessage() {
        return $this->exception;
    }

    public function getStackTrace() {
        return $this->stackTrace;
    }

    public function build($params, $cfw) {
        http_response_code(500);
        $exmsg = $this->exception;
        $stackTrace = $this->stackTrace;
        include __DIR__ . '/../Resources/internalServerError.php';
    }
}