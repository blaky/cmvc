<?php

namespace CacaoFw;

use CacaoFw\Service\BuilderService;
use CacaoFw\Service\DataService;
use CacaoFw\Service\MapperService;
use CacaoFw\Service\RouterService;
use CacaoFw\Response\InternalServerErrorResponse;
use CacaoFw\Response\RedirectResponse;
use CacaoFw\Security\AccessControl;
use CacaoFw\Vendor\MobileDetect;
use CacaoFw\Vendor\SimpleImage;


/**
 *
 * @author Bence
 *
 */
class CacaoFw {

    /**
     *
     * @var array Application config file
     */
    public $config;

    /**
     *
     * @var Utils
     */
    public $u;

    /**
     *
     * @var Mobile_Detect
     */
    public $mobileDetect;

    /**
     *
     * @var DataService
     */
    private $db;

    /**
     *
     * @var string
     */
    public $fbLoginUrl;

    /**
     *
     * @var User
     */
    public $currentUser;

    /**
     *
     * @var CacaoFw\Security\AccessControl;
     */
    public $accessControl;

    /**
     *
     * @var BuilderService
     */
    public $builderService;

    /**
     *
     * @var MapperService
     */
    public $mapperService;

    /**
     *
     * @var RouterService
     */
    public $routerService;

    public function __construct(\CacaoFw\Utils $u) {
        global $DS, $CFW;

        $this->u = $u;

        $this->setUpConfig();
        $this->setUpLocalisation();

        // Prepare data access.
        $this->ds = new DataService($this->config);
        $DS = $this->ds;

        $this->mobileDetect = new MobileDetect();

        $this->builderService = new BuilderService($this, $this->u);

        // read components and set up routing
        // $this->mapperService = new MapperService($this);

        $this->routerService = new RouterService($this);

        // prepare security
        $this->accessControl = new AccessControl($this);

        $CFW = $this;
        $this->processRequest();
    }

    private function setUpConfig() {
        $this->config = parse_ini_file(__DIR__ . '/../app/config.ini');
        // security rules
        // no authentication required
        $this->config["authpage"] = array();

        // password hasing settings
        $this->config["hashOptions"] = ['cost' => 11, 'salt' => $this->config["passwordsalt"]
        ];
    }

    private function setUpLocalisation() {
        $langpref = 'en';

        if (isset($_REQUEST['lang'])) {
            // TODO: VALIDATE
            $langpref = $_REQUEST['lang'];
        } else if (isset($_COOKIE['lang'])) {
            $langpref = $_COOKIE['lang'];
        }

        setcookie('lang', $langpref, 100000000000);

        global $LANG;
        $LANG = new \stdClass();
        $LANG->code = $langpref;
        $LANG->strings = array();
        foreach (scandir($this->u->getAppDir() . '/lang/') as $file) {
            $info = pathinfo ($file);
            if ($info["extension"] == 'php') {
                $strings = array();
                require_once $this->u->getAppDir() . '/lang/' . $file;
                $LANG->strings[$info["filename"]] = $strings;
            }
        }

    }

    /**
     *
     * @param array $file
     * @param string $prefix
     */
    public function saveFile($file, $prefix, $path) {
        global $u;
        if (!$file['error']) {
            $name = uniqid($prefix) . '.' . end((explode(".", $file["name"])));
            $newPath = realpath($u->getAppDir() . $path) . "/" . $name;
            $result = move_uploaded_file($file['tmp_name'], $newPath);
            if (!$result) {
                throw new Exception("Failed to save file.");
            }
            return $name;
        } else {
            return null;
        }
    }

    /**
     *
     * @param unknown $requestedPath
     * @return array:string
     */
    public function getRequestParams($requestedPath) {
        $params = explode("/", $requestedPath);
        array_shift($params);
        return $params;
    }

    private function streamResourceFile($requestUri) {
        $filePath = __DIR__ . '/../public_html' . $_SERVER["REQUEST_URI"];
        if (file_exists($filePath)) {
            $extension = strtolower(pathinfo($filePath)['extension']);
            if ($extension == "php") {
                throw new Exception("php files are not allowed to be downloaded.");
            } else if ($extension == "js") {
                header('Content-Type: text/javascript');
            } else if ($extension == "css") {
                header('Content-Type: text/css');
            } else if ($extension == "jpg") {
                header('Content-Type: image/jpeg');
            } else if ($extension == "png") {
                header('Content-Type: image/png');
            } else if ($extension == "gif") {
                header('Content-Type: image/gif');
            } else if ($extension == "mp4") {
                header('Content-Type: video/mp4');
            } else if ($extension == "pdf") {
                header('Content-Type: application/pdf');
            } else if ($extension == "pdf") {
                header('Content-Type: text/html');
            }

            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit();
        } else {
            return new NotFoundResponse();
        }
    }

    private function processRequest() {
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestPath = preg_replace(
                '/' . preg_quote(".html", '/') . '|' . preg_quote(".json", '/') . '|' .
                         preg_quote(".do", '/') . '*$/', '', strtok($requestUri, '?'));

        // Handle index request.
        if ($requestPath == "/") {
            $requestPath = "/index";
        }

        $requestParams = $this->getRequestParams($requestPath);
        $readyToExit = false;

        while (!$readyToExit) {
            $result = $this->routeParams($requestParams, $requestPath);
            if (!is_null($result) && get_class($result) == "InternalRedirectResponse") {
                $requestPath = $result->getPath();
                $requestParams = $this->getRequestParams($result->getPath());
            } else {
                $readyToExit = true;
                $this->ds->close();
            }
        }
    }

    private function routeParams($requestParams, $requestPath) {
        $responseObject;
        $this->ds->startTransaction();
        try {
            if ($requestParams[0] == "res") {
                $responseObject = $this->streamResourceFile($requestPath);
            } else if ($this->accessControl->isAuthorisedToProcess($requestPath)) {
                $responseObject = $this->routerService->routeRequest($requestParams);
            } else {
                $responseObject = new RedirectResponse("/index.html");
            }
            $responseObject->build($requestParams, $this);
        } catch (\Exception $ex) {
            $this->ds->rollBack();
            $responseObject = new InternalServerErrorResponse($ex->getMessage(), $ex->getTrace());
            $responseObject->build($requestParams, $this);
        }

        $this->ds->commitChanges();
        return $responseObject;
    }

    /**
     *
     * @return DataService
     */
    public function getDb() {
        return $this->ds;
    }

    /**
     *
     * @return User
     */
    public function getCurrentUser() {
        return $this->accessControl->currentUser;
    }

    public function isUserLoggedIn() {
        return !!$this->accessControl->currentUser;
    }
}