<?php

namespace CacaoFw;

use CacaoFw\AbstractPageElement;

abstract class AbstractComponent extends AbstractPageElement {

    public function getTemplateName($params) {
        return $this->getName();
    }
}