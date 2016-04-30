<?php

namespace CacaoFw;

/**
 * Database entity representation.
 *
 * This class contains the basic CURD operation controller functions, making
 * the model simple to hadle. All model classes must extend this class.
 *
 * @author Bence
 *        
 */
abstract class AbstractModel {
    public static $links = array();
    
    /**
     * Record identifer.
     *
     * @var int
     */
    public $id;

    /**
     * Save the entity to the database.
     */
    public function create() {
        global $DS;
        $DS::dao($this->getClassName())->create($this);
    
    }

    /**
     * Save the object to the database and populate the ID field with the
     * new ID value.
     */
    public function createObject() {
        global $DS;
        $DS::dao($this->getClassName())->createObject($this);
    
    }

    /**
     * Persist changes to object to the database based on the ID field.
     */
    public function update() {
        global $DS;
        $DS::dao($this->getClassName())->update($this);
    
    }

    /**
     * Delete the object from the database.
     *
     * @param string $deep
     *            Deep cleaning childr object. If set to true all
     *            chiled objects will be removed.
     */
    public function delete($deep = true) {
        global $DS;
        $DS::dao($this->getClassName())->delete($this->id, $deep);
    
    }

    private function getClassName() {
        return substr(get_called_class(), 10);
    
    }

}