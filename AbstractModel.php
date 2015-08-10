<?php

namespace CacaoFw;

abstract class AbstractModel {

    /**
     * Record identifer.
     *
     * @var int
     */
    public $id;

    public function create() {
        global $DS;
        $DS::dao($this->getClassName())->create($this);
    }

    public function createObject() {
        global $DS;
        $DS::dao($this->getClassName())->createObject($this);
    }

    public function update() {
        global $DS;
        $DS::dao($this->getClassName())->update($this);
    }

    public function delete() {
        global $DS;
        $DS::dao($this->getClassName())->delete($this->id);
    }

    private function getClassName() {
        return substr(get_called_class(), 10);
    }
}