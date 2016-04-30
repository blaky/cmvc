<?php

namespace CacaoFw\Service;

class RequestProcessorService {
    private static $initialized = false;

    /**
     *
     * @var CacaoFw\Security\AccessControl;
     */
    private $accessControl;

    /**
     *
     * @var RouterService
     */
    private $routerService;

    public static function init($accessControl, $routerService) {
        if (! self::$initialized) {
            self::$initialized = true;
            return new RequestProcessorService($accessControl, $routerService);
        }
    }

    private function __construct($accessControl, $routerService) {
        $this->accessControl = $accessControl;
        $this->routerService = $routerService;
    }

    public function processRequest() {
        global $DS, $CFW;

        $requestUri = $_SERVER['REQUEST_URI'];
        $requestPath = preg_replace(
                '/' . preg_quote(".html", '/') . '|' . preg_quote(".json", '/') . '|' .
                preg_quote(".do", '/') . '*$/', '', strtok($requestUri, '?'));

        // Handle index request.
        if ($requestPath == "/") {
            $requestPath = "/index";
        }

        $requestParams = $this->getRequestParams($requestPath);

        // Read request content.
        $requestPayload = $this->getRequestPayload();

        $readyToExit = false;

        // Loop until we get something back other than InternalRedirectReponse.
        ob_start();
        while ( ! $readyToExit ) {
            if ($this->accessControl->isAuthorisedToProcess($requestPath)) {
                $result = $this->routerService->routeParams($requestParams, $requestPath,
                        $requestPayload);
            } else {
                $result = new RedirectResponse("/index.html");
            }
            if (! is_null($result) && get_class($result) == "InternalRedirectResponse") {
                // Update request path based on the redirected response.
                $requestPath = $result->getPath();
                $requestParams = $this->getRequestParams($result->getPath());
            } else {

                // Print some debug information.
                if ($CFW->config["debug"]) {
                    global $starttime;
                    $rendertime = (microtime(true) - $starttime) * 1000;
                    header("X-RENDERTIME: $rendertime ms");
                    header("X-FILESINCLUDED: " . count(get_included_files()));
                }
                $readyToExit = true;

            }
        }

        // Close database connection and exit.
        $DS->close();
        exit();
    }

     /**
     *
     * @param string $requestedPath
     * @return array:string
     */
    private function getRequestParams($requestedPath) {
        $params = explode("/", $requestedPath);
        array_shift($params);
        return $params;
    }

    /**
     * Read the request payload and transforms it to data structure.
     *
     * @return mixed The request content with optimal format.
     */
    private function getRequestPayload() {
        $requestPayload = file_get_contents('php://input');
        if (isset($_SERVER["CONTENT_TYPE"])) {

            if (strpos($_SERVER["CONTENT_TYPE"], "application/json") !== - 1) {

                // Handle JSON.
                $requestPayload = json_decode($requestPayload);

            } else if (strpos($_SERVER["CONTENT_TYPE"], "application/xml") !== - 1 ||
                    strpos($_SERVER["CONTENT_TYPE"], "text/xml") !== - 1) {

                // Handle XML.
                $doc = new DOMDocument();
                $doc->loadXML($requestPayload);
                $requestPayload = $doc;
            }
        }

        return $requestPayload;
    }

}