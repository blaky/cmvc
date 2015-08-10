<?php

/**
 * JsonResponse class is for API endpoints which server JSON data.
 * Just return this object in the controllers and serialisation is done
 * automatically by the framework.
 *
 * @author Bence
 *
 */
namespace CacaoFw\Response;

class JsonResponse extends AbstractResponse {

    /**
     *
     * @var mixed
     */
    private $data;

    /**
     * Constructior of JsonResponse
     *
     * @param mixed $data PHP object/array that you wish to be serialised
     * into a JSON object/array
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * PHP object or array for serialisation
     *
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    public function build($requestParameters, $cfw) {
        $jsonFormat = json_encode($this->data);
        header('Content-Type: application/json');
        echo $jsonFormat;
    }
}