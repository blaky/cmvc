<?php

namespace CacaoFw\Service;

use CacaoFw\Response\AbstractResponse;
use CacaoFw\Response\NotFoundResponse;

class RouterService {

    private $cfw;

    public function __construct($cfw) {
        $this->cfw = $cfw;
    }

    /**
     * Request router.
     * Directs the request to the
     * requested controller endpoint.
     *
     * @param array:string $requestParams the request parameters
     * @return AbstractResponse
     */
    public function routeRequest($requestParams) {
        // Check if the requested controller exists.
        try {
            $controllerName = "App\\Controller\\" . ucfirst($requestParams[0]) . 'Controller';
            $controller = new $controllerName($this->cfw);
        } catch (\InvalidArgumentException $ex) {
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
                /* run endpoint function with request parameters to get an
                 * AbstractResponse object for further processing. */
                $calledEndpoint = $endpoint($requestParams);
                if ($calledEndpoint instanceof AbstractResponse) {
                    $endpointResponse = $calledEndpoint;
                } else {
                    throw new \Exception("Endpoint return value is not supported!!!");
                }
            }
            
            return $endpointResponse;
        } else {
            return new NotFoundResponse();
        }
    }
}