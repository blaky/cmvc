<?php

namespace CacaoFw;

use CacaoFw\Security\AccessControl;
use CacaoFw\Service\DataService;
use CacaoFw\Service\LocalisationService;
use CacaoFw\Service\RouterService;
use CacaoFw\Service\RequestProcessorService;

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
     * @var RouterService
     */
    private $routerService;

    public function __construct(\CacaoFw\Utils $u) {
        global $DS, $CFW, $LANG;

        $this->u = $u;

        $this->setUpConfig();

        $LANG = LocalisationService::init($this->config);

        // Prepare data access.
        $DS = DataService::init($this->config);

        // prepare security
        $this->accessControl = AccessControl::init();

        $this->routerService = RouterService::init();

        $CFW = $this;

        RequestProcessorService::init($this->accessControl, $this->routerService)->processRequest();
    }

    /**
     *
     * @param array $file
     * @param string $prefix
     */
    public function saveFile($file, $prefix, $path) {
        global $u;
        if (! $file['error']) {
            $name = uniqid($prefix) . '.' . end((explode(".", $file["name"])));
            $newPath = realpath($u->getAppDir() . $path) . "/" . $name;
            $result = move_uploaded_file($file['tmp_name'], $newPath);
            if (! $result) {
                throw new Exception("Failed to save file.");
            }
            return $name;
        } else {
            return null;
        }
    }

    /**
     * Load config file.
     */
    private function setUpConfig() {
        $this->config = parse_ini_file(__DIR__ . '/../app/config.ini');
    }

    /**
     *
     * @return User
     */
    public function getCurrentUser() {
        return $this->accessControl->currentUser;
    }

    public function isUserLoggedIn() {
        return ! ! $this->accessControl->currentUser;
    }

}