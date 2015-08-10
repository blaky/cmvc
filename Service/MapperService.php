<?php

namespace CacaoFw\Service;

/**
 * Maps the application's components added by
 * the user
 *
 * @author Bence
 *
 */
class MapperService {

    /**
     *
     * @var CacaoFw
     */
    private $cfw;

    private $controllerRegistry;

    private $frameRegistry;

    private $componentRegistry;

    private $daoRegistry;

    public function __construct($cfw) {
        $this->cfw = $cfw;
        
        $this->controllerRegistry = array();
        $this->componentRegistry = array();
        $this->frameRegistry = array();
        $this->daoRegistry = array();
        
        // load model classes
        // $this->mapDaoClasses();
        // load components
        $this->mapComponentClasses();
        // load frames
        $this->mapFrameClasses();
        // load views
        $this->mapControllerClasses();
    }

    private function getAppFolder() {
        return __DIR__ . "/../../src/";
    }

    private function mapDaoClasses() {
        $folderPath = realpath($this->getAppFolder() . "dao/");
        $files = array_diff(scandir($folderPath), array(
                '..', 
                '.'
        ));
        foreach ($files as $file) {
            /* $filePath = realpath("$folderPath/$file");
             * require_once $filePath; */
            $class = "App\\DAO\\" . basename($file, '.php');
            $component = new $class($this->cfw->getDb());
            if (array_key_exists($class, $this->daoRegistry)) {
                throw new \Exception("Duplicated model table name!");
            } else if (!($component instanceof DataAccessObject)) {
                throw new \Exception("$component is not a DataAccessObject subclass!");
            } else {
                $this->daoRegistry[$class] = $component;
                $modelVarName = lcfirst($class);
                $this->cfw->{$modelVarName} = $component;
            }
        }
    }

    private function mapFrameClasses() {
        $folderPath = $this->getAppFolder() . "frames/";
        $files = array_diff(scandir($folderPath), array(
                '..', 
                '.'
        ));
        foreach ($files as $file) {
            $filePath = $folderPath . $file;
            require_once $filePath;
            $class = basename($file, '.php');
            $component = new $class($this->cfw);
            $componentName = $component->getName();
            if (array_key_exists($componentName, $this->frameRegistry)) {
                throw new Exception("Duplicated frame element name!");
            } else {
                $this->frameRegistry[$componentName] = $component;
            }
        }
    }

    private function mapComponentClasses() {
        $folderPath = $this->getAppFolder() . "components/";
        $files = array_diff(scandir($folderPath), array(
                '..', 
                '.'
        ));
        foreach ($files as $file) {
            $filePath = $folderPath . $file;
            require_once $filePath;
            $class = basename($file, '.php');
            $component = new $class($this->cfw);
            $componentName = $component->getName();
            if (array_key_exists($componentName, $this->componentRegistry)) {
                throw new Exception("Duplicated frame element name!");
            } else {
                $this->componentRegistry[$componentName] = $component;
            }
        }
    }

    private function mapControllerClasses() {
        $folderPath = $this->getAppFolder() . "controllers/";
        $files = array_diff(scandir($folderPath), array(
                '..', 
                '.'
        ));
        foreach ($files as $file) {
            $filePath = $folderPath . $file;
            require_once $filePath;
            $class = basename($file, '.php');
            $component = new $class($this->cfw);
            $componentName = $component->getName();
            if (array_key_exists($componentName, $this->controllerRegistry)) {
                throw new Exception("Duplicated frame element name!");
            } else {
                $this->controllerRegistry[$componentName] = $component;
            }
        }
    }

    public function hasController($controllerName) {
        return array_key_exists($controllerName, $this->$controllerRegistry);
    }

    public function hasComponent($componentName) {
        return array_key_exists($componentName, $this->componentRegistry);
    }

    public function hasFrame($frameName) {
        return array_key_exists($frameName, $this->frameRegistry);
    }

    public function getControllerRegistry() {
        return $this->controllerRegistry;
    }

    public function getComponentRegistry() {
        return $this->componentRegistry;
    }

    public function getFrameRegistry() {
        return $this->frameRegistry;
    }
}