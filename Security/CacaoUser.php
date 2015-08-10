<?php

namespace CacaoFw\Security;

interface CacaoUser {

    public function getUID();

    public function getAccessLevel();
}