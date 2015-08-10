<?php

namespace CacaoFw\Response;

use CacaoFw\CacaoFw;

/**
 * Abstract response is the root of all responses of the
 * framework.
 *
 * @author Bence
 *
 */
abstract class AbstractResponse {

    /**
     * The response code that the framework delivers to the
     * browser.
     *
     * @return int response code;
     */
    public function getResponseCode() {
        return 200;
    }

    /**
     *
     * @param array:string $requestParameters
     * @param CacaoFw $cfw
     */
    public abstract function build($requestParameters, $cfw);
}