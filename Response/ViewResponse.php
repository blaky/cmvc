<?php

/**
 * Simple Page View response
 *
 * @author Bence
 *
 */
namespace CacaoFw\Response;

use CacaoFw\Vendor\MobileDetect;

class ViewResponse extends AbstractResponse {
    
    /**
     *
     * @var string
     */
    private $viewName;
    private $dataMap;
    private $frameName;
    private $globalParams;

    /**
     * Create a ViewResponse
     *
     * @param string $viewName
     *            the path of the view that will be used for rendering, it must be located in
     *            src/template/views folder
     * @param array:mixed $dataMap
     *            the data map for the view that will be put into the $m variable when rendering
     * @param string $frameName
     *            the name of the frame that the view will be rendered in. If set to null, the
     *            default frame will be used
     */
    public function __construct($viewName, $dataMap = array(), $frameName = "default", $globalParams = array()) {
        $this->viewName = $viewName;
        $this->dataMap = $dataMap;
        $mobileDetect = new MobileDetect();
        $this->frameName = $frameName == "default" ? ($mobileDetect->isMobile() ? "mobileMain" : "main") : $frameName;
        $this->globalParams = $globalParams;
    }

    /**
     * The path/name of the view file which will be used for the view response
     *
     * @return string
     */
    public function getViewName() {
        return $this->viewName;
    }

    /**
     * The name of the frame that the view will be rendered in, if
     * not provided, it will be rendered in the default one.
     *
     * @return string
     */
    public function getFrameName() {
        return isset($this->frameName) ? $this->frameName : "mainFrame";
    }

    public function getComponentList() {
        return array();
    }

    public function getGlobalParams() {
        return $this->globalParams;
    }

    public function build($requestParameters, $cfw) {
        $frameClass = "App\\Frame\\" . ucfirst($this->frameName) . "Frame";
        $frame = new $frameClass($cfw);
        $frameComponents = $frame->getComponentList();
        
        $compiledComponents = $this->renderComponents(
            array_unique(array_merge($this->getComponentList(), $frameComponents), SORT_REGULAR), 
            $requestParameters, $cfw);
        
        $compiledView = $this->renderView($compiledComponents, $requestParameters);
        
        $assembledPage = $this->renderFrame($frame, $compiledView, $compiledComponents, 
            $requestParameters);
        
        if ($cfw->config["debug"]) {
            global $DS;
            $assembledPage = substr($assembledPage, 0, stripos($assembledPage, '</html>'));
            $assembledPage .= "<pre>SQL Queries: \n";
            foreach ( $DS->getQueryHistory() as $query ) {
                $assembledPage .= "$query\n";
            }
            $assembledPage .= '</pre></html>';
        }
        echo $assembledPage;
    }

    /**
     *
     * @param array:AbstractComponent $components            
     * @param array:string $params            
     * @param MapperService $mapperService            
     * @throws Exception
     * @return multitype:string
     */
    private function renderComponents($components, $params, $cfw) {
        $returnArray = array();
        global $u;
        foreach ( $components as $componentName ) {
            $componentClass = "App\\Component\\" . ucfirst($componentName) . "Component";
            $component = new $componentClass($cfw);
            $templatePath = $u->getTemplateDir() . "components/" .
                 $component->getTemplateName($params) . ".php";
            
            if (file_exists($templatePath)) {
                ob_start();
                extract(
                    array('m' => $component->getDataMap($params),'gp' => $this->getGlobalParams()));
                include ($templatePath);
                $returnArray[$componentName] = ob_get_contents();
                ob_end_clean();
                unset($m, $v, $c, $gp);
            } else {
                throw new \Exception("Template file was not found for '$componentName' component");
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
    private function renderView($compiledComponents, $params) {
        global $u;
        $templatePath = $u->getTemplateDir() . "/views/" . $this->viewName . ".php";
        
        if (file_exists($templatePath)) {
            ob_start();
            extract(array('m' => $this->dataMap,'c' => $compiledComponents));
            include ($templatePath);
            $compiledView = ob_get_contents();
            ob_end_clean();
            unset($m, $c, $u);
            return $compiledView;
        } else {
            throw new \Exception(
                "Template file was not found for '" . $this->viewName . "' component");
        }
    }

    private function renderFrame($frame, $compiledView, $compiledComponents, $params) {
        global $u;
        $templatePath = $u->getTemplateDir() . "/frames/" . $frame->getTemplateName($params) . ".php";
        
        if (file_exists($templatePath)) {
            ob_start();
            global $u;
            extract(
                array('m' => $frame->getDataMap($params),'v' => $compiledView,
                    'c' => $compiledComponents));
            include ($templatePath);
            $compiledFrame = ob_get_contents();
            ob_end_clean();
            unset($m, $v, $c, $u);
            return $compiledFrame;
        } else {
            throw new \Exception("Template file was not found for '$componentName' frame");
        }
    }

}