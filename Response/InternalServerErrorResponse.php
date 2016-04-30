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

    /**
     *
     * {@inheritDoc}
     * @see \CacaoFw\Response\AbstractResponse::build()
     */
    public function build($params, $cfw) {
        http_response_code(500);
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            $jsondata = new \stdClass();
            $jsondata->errormessage = $this->exception;
            if ($cfw->config["debug"]) {
                $jsondata->stack = $this->stackTrace;
            }
            $jsonresponse = new JsonResponse($jsondata);
            $jsonresponse->build($params, $cfw);
        } else {
            global $DS;
            $exmsg = $this->exception;
            $stackTrace = $this->stackTrace;
            $debug = $cfw->config["debug"];
            $sql = $DS->getQueryHistory();
            include __DIR__ . '/../Resources/internalServerError.php';
        }

    }

}