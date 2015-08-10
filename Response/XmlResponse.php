<?php

/**
 *
 * @author Bence <bence.laky@gmail.com>
 *
 */
namespace CacaoFw\Response;

class XmlResponse extends AbstractResponse {

    /**
     *
     * @var DOMDocument
     */
    private $data;

    private $formatOutput;

    public function __construct($data, $formatOutput = true) {
        $this->data = $data;
        $this->formatOutput = $formatOutput;
    }

    public function getData() {
        return $this->data;
    }

    public function build($requestParameters, $cfw) {
        $this->data->formatOutput = $this->formatOutput;
        header("Content-Type: application/xml");
        echo $this->data->saveXML();
    }
}