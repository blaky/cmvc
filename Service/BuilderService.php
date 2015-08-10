<?php

namespace CacaoFw\Service;

class BuilderService {

    public $cfw;

    /**
     *
     * @var Utils
     */
    public $u;

    /**
     *
     * @param BenceFw $cfw
     * @param Utils $u
     */
    public function __construct($cfw, $u) {
        /* $this->u = $u;
         * $this->cfw = $cfw;
         * $this->controllerRegistry = $cfw->mapperService->getControllerRegistry();
         * $this->componentRegistry = array();
         * $this->frameRegistry = array(); */
    }

    private function renderComponents($components, $params, $globalParams) {
        $returnArray = array();
        foreach ($components as $componentName) {
            if (!array_key_exists($componentName, $this->componentRegistry)) {
                throw new Exception("Unknown component: " . $componentName);
            } else {
                $component = $this->componentRegistry[$componentName];
                $templatePath = __DIR__ . "/../../src/template/components/" . $component->getTemplateName($params) . ".php";
                
                if (file_exists($templatePath)) {
                    ob_start();
                    $m = array_merge($component->getDataMap($params), $globalParams);
                    include ($templatePath);
                    $returnArray[$componentName] = ob_get_contents();
                    ob_end_clean();
                    unset($m, $v, $c, $gp);
                } else {
                    throw new Exception("Template file was not found for '$componentName' component");
                }
            }
        }
        
        return $returnArray;
    }

    /**
     *
     * @param AbstractView $view
     * @param string[] $components
     * @return string
     */
    private function renderView($view, $compiledComponents, $params) {
        $templatePath = __DIR__ . "/../../src/template/views/" . $view->getTemplate($params)->getViewName() . ".php";
        
        if (file_exists($templatePath)) {
            ob_start();
            extract(array(
                    'm' => $view->getDataMap($params), 
                    'c' => $compiledComponents
            ));
            include ($templatePath);
            $compiledView = ob_get_contents();
            ob_end_clean();
            unset($m, $c);
            return $compiledView;
        } else {
            throw new Exception("Template file was not found for '" . $view->getName() . "' component");
        }
    }

    private function renderFrame($frame, $compiledView, $compiledComponents, $params) {
        $templatePath = __DIR__ . "/../../src/template/frames/" . $frame->getTemplateName($params) . ".php";
        
        if (file_exists($templatePath)) {
            ob_start();
            extract(array(
                    'm' => $frame->getDataMap($params), 
                    'v' => $compiledView, 
                    'c' => $compiledComponents
            ));
            include ($templatePath);
            $compiledFrame = ob_get_contents();
            ob_end_clean();
            unset($m, $v, $c);
            return $compiledFrame;
        } else {
            throw new Exception("Template file was not found for '$componentName' frame");
        }
    }
}