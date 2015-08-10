<?php

namespace CacaoFw\Response;

/**
 * Only for development purposes.
 * It displays a view with dumping (using var_dump)
 * important variables, such as $_GET, $POST.
 * You also can pass in an object to the constructor, that also gonna be dumped.
 *
 * @author Bence
 *
 */
class DebugResponse extends AbstractResponse {

    private $objectToPrint;

    /**
     * Construct the debug response.
     *
     * @param unknown $objectToPrint optional variable to be dumped
     */
    public function __construct($objectToPrint = array()) {
        $this->objectToPrint = $objectToPrint;
    }

    public function build($requestParameters, $cfw) {
        $params = $this->objectToPrint;
        include __DIR__ . '/../Resources/debugView.php';
    }
}