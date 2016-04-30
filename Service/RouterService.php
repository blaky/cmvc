<?php

namespace CacaoFw\Service;

use CacaoFw\Response\AbstractResponse;
use CacaoFw\Response\InternalServerErrorResponse;
use CacaoFw\Response\NotFoundResponse;

class RouterService {
    private static $initialized = false;

    public static function init() {
        if (! self::$initialized) {
            self::$initialized = true;
            return new RouterService();
        }
    }

    private function __construct() {
        // Intentionally left blank.
    }

    /**
     * Request router.
     * Directs the request to the
     * requested controller endpoint.
     *
     * @param array:string $requestParams
     *            the request parameters
     * @return AbstractResponse
     */
    private function findEndpoint($requestParams, $requestPayload) {
        // Check if the requested controller exists.
        try {
            $controllerName = "App\\Controller\\" . ucfirst($requestParams[0]) . 'Controller';
            if (class_exists($controllerName)) {
                $controller = new $controllerName();
            } else {
                return new NotFoundResponse();
            }
        } catch ( \InvalidArgumentException $ex ) {
            return new NotFoundResponse();
        }

        $endPointName = count($requestParams) > 1 ? $requestParams[1] : "index";

        // Get endpoints of the controller.
        $endpoints = $controller->getEndpoints();

        // Check if the requested endpoint exists on the controller
        if (array_key_exists($endPointName, $endpoints)) {

            $endpoint = $endpoints[$endPointName];
            if ($endpoint instanceof AbstractResponse) {
                $endpointResponse = $endpoint;
            } else if (is_callable($endpoint)) {
                /*
                 * Execute endpoint function with request parameters to get an
                 * AbstractResponse object for further processing.
                 */
                $calledEndpoint = $endpoint($requestParams, $requestPayload);
                if ($calledEndpoint instanceof AbstractResponse) {
                    $endpointResponse = $calledEndpoint;
                } else {
                    throw new \Exception("Endpoint return value is not supported!");
                }
            }

            return $endpointResponse;
        } else {
            return new NotFoundResponse();
        }

    }

    public function routeParams($requestParams, $requestPath, $requestPayload) {
        global $DS, $CFW;
        $responseObject;
        $DS->startTransaction();
        try {
            $responseObject = $this->findEndpoint($requestParams, $requestPayload);
            $responseObject->build($requestParams, $CFW);
        } catch ( \Exception $ex ) {
            $DS->rollBack();
            $responseObject = new InternalServerErrorResponse($ex->getMessage(), $ex->getTrace());
            $responseObject->build($requestParams, $CFW);
        }

        $DS->commitChanges();
        return $responseObject;
    }

}