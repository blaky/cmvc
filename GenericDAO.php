<?php

namespace CacaoFw;

class GenericDAO extends DataAccessObject {

    private $baseClassName;

    public function __construct($db, $baseClassName) {
        parent::__construct($db, $baseClassName);
        $this->baseClassName = $baseClassName;
    }

    public function __toString() {
        return $baseClassName . "DAO";
    }
}