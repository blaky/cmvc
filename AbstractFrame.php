<?php

namespace CacaoFw;

use CacaoFw\AbstractPageElement;

abstract class AbstractFrame extends AbstractPageElement {

    public abstract function getComponentList();

    public function getTemplateName($params) {
        return $this->getName();
    }
}