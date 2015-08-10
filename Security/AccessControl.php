<?php

namespace CacaoFw\Security;

use CacaoFw\CacaoFw;

define("UNAUTHORIZED", 0);
define("AUTHORIZED", 1);
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
 * @author bence
 *
 */
class AccessControl {

    /**
     *
     * @var CacaoFw
     */
    private $cfw;

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

    public function __construct($cfw) {
        $this->cfw = $cfw;
        // Load access roles.
        $securityRules = array();
        include __DIR__ . "/../../app/securityRules.php";
        $this->restrictedPages = array_reverse($securityRules);
        
        if (isset($_SESSION[UID])) {
            $this->loadUser();
        }
    }

    private function loadUser() {
        global $DS;
        $user = $DS::dao($_SESSION[USERCLASS])->byId($_SESSION[UID]);
        if ($user) {
            $this->currentUser = $user;
        } else {
            throw new Exception("Unable to load current user.");
        }
    }

    /**
     *
     * @param CacaoFw\Security\CacaoUser $user
     */
    public function logInUser($user) {
        if ($user instanceof CacaoUser) {
            $_SESSION[UID] = $user->getUID();
            $_SESSION[USERCLASS] = substr(get_class($user), 10);
            $this->currentUser = $user;
        } else {
            throw new \InvalidArgumentException(
                    get_class($user) . " does not implement the CacaoUser interface, therfore cannot be used for authentication");
        }
    }

    public function logOutUser() {
        unset($_SESSION["UID"]);
        $this->currentUser = null;
    }

    /**
     *
     * @param string $requestPath
     * @return RestrictedPage NULL
     */
    private function _getSecurityRuleForRequest($requestPath) {
        $requestPath = substr($requestPath, 1);
        foreach ($this->restrictedPages as $path => $accessLevel) {
            if (preg_match('/' . str_replace('/', '\/', $path) . '/i', $requestPath)) {
                return $accessLevel;
            }
        }
        // If no security rule found, the page can be accessed by anyone.
        return 0;
    }

    /**
     *
     * @param CacaoUser $user
     * @param string $requestPath
     * @return boolean
     */
    public function isAuthorisedToProcess($requestPath) {
        // Find secuirty rule for request
        $levelRestriction = $this->_getSecurityRuleForRequest($requestPath);
        
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