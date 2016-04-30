<?php

namespace CacaoFw\Security;

use CacaoFw\CacaoFw;
use CacaoFw\Response\NotFoundResponse;

/*
 * Basic levels of authentication, it can be extended with further levels.
 *
 */
define("UNAUTHENTICATED", 0);
define("AUTHENTICATED", 1);
define("EDITOR", 2);
define("ADMIN", 3);

define("UID", "UID");
define("USERCLASS", "USERCLASS");
/**
 * This class is in charge of security and access control.
 * All request (excluding the "res" folder) is checked by this class and
 * tested based on the rules defined in "app/securityRules.php".
 *
 * It is also in charge of logging in and out users.
 *
 * @author Bence
 *        
 */
class AccessControl {
    private static $initialized = false;
    
    /**
     *
     * @var array:array
     */
    private $restrictedPages;
    
    /**
     *
     * @var CacaoUser
     */
    public $currentUser;

    public static function init() {
        if (! self::$initialized) {
            self::$initialized = true;
            return new AccessControl();
        }
    }

    private function __construct() {
        global $u;
        
        // Load access roles.
        $securityRules = array();
        $securityRulesFile = "{$u->getAppDir()}/securityRules.php";
        if (file_exists($securityRulesFile)) {
            include $securityRulesFile;
            $this->restrictedPages = array_reverse($securityRules);
            
            if (isset($_SESSION[UID])) {
                $this->loadUser();
            }
        } else {
            throw new \Exception("Security rules file doesn't exist, should be located at {$securityRulesFile}.");
        }
    
    }

    /**
     * Fetches the current user defined in the session and loads it to the
     * memory to provide easy access across the application.
     */
    private function loadUser() {
        global $DS;
        // Fetch the user.
        $user = $DS::dao($_SESSION[USERCLASS])->byId($_SESSION[UID], false);
        if ($user) {
            $this->currentUser = $user;
        } else {
            /*
             * If the current user is not found in on the records any more,
             * just terminate the user session to prevent any unexpected
             * behaviour.
             */
            $this->logOutUser();
        }
    
    }

    /**
     *
     * @param CacaoFw\Security\CacaoUser $user
     *            User to log in to the session
     */
    public function logInUser($user) {
        if ($user instanceof CacaoUser) {
            $_SESSION[UID] = $user->getUID();
            $_SESSION[USERCLASS] = substr(get_class($user), 10);
            $this->currentUser = $user;
        } else {
            throw new \InvalidArgumentException(get_class($user) . " does not implement the CacaoUser interface, therefore cannot be used for authentication");
        }
    
    }

    /**
     * Removes the user from the session and the memory.
     */
    public function logOutUser() {
        unset($_SESSION["UID"]);
        $this->currentUser = null;
    
    }

    /**
     * Finds rule for the current request.
     *
     * @param string $requestPath            
     * @return int The required access level.
     */
    private function getSecurityRuleForRequest($requestPath) {
        $requestPath = substr($requestPath, 1);
        foreach ( $this->restrictedPages as $path => $accessLevel ) {
            if (preg_match('/' . str_replace('/', '\/', $path) . '/i', $requestPath)) {
                return $accessLevel;
            }
        }
        // If no security rule found, the page can be accessed by anyone.
        return 0;
    
    }

    /**
     * Determinate whether the request is authorised to go through.
     *
     * @param string $requestPath
     *            Relative request path.
     * @return boolean
     */
    public function isAuthorisedToProcess($requestPath) {
        // Find secuirty rule for request
        $levelRestriction = $this->getSecurityRuleForRequest($requestPath);
        
        if ($levelRestriction) {
            // If there is a restriction but the user is missing the request can't go through
            if (is_null($this->currentUser)) {
                return false;
            } else {
                return $this->currentUser->getAccessLevel() >= $levelRestriction;
            }
        } else {
            // If no security rule found for the path, the request can progress.
            return true;
        }
    
    }

}