<?php

namespace CacaoFw;

use CacaoFw\Security\CacaoUser;

abstract class AbstractController {
    
    protected $cfw;
    
    /**
     *
     * @var CacaoUser
     */
    protected $user;

    /**
     *
     * @param CacaoFw $cfw            
     */
    public function __construct() {
        global $CFW;
        $this->cfw = $CFW;
        $this->user = $this->cfw->getCurrentUser();
    }

    /**
     * Returns a string indexed function array for the controller endpoints.
     * The array key is the name of the endpoint, the array value is the callback function which will be called on request with the url parameters.
     * This function need to return a
     *
     * @return multitype:AbstractResponse
     */
    public function getEndpoints() {
        return array("index" => function ($params) {
            return new ViewResponse($this->getName(), array(), null);
        });
    }

}