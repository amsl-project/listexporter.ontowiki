<?php

/**
 * Listexporter component controller. This controller is used for demonstration and export
 * purposes of the listexporter.
 *
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Sebastian Nuck
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class ListexporterController extends OntoWiki_Controller_Component
{
    private $resourceQuery;
    private $valueQuery;
    private $mergedQuery;

    public function init()
    {
        parent::init();
        $this->resourceQuery = unserialize(gzinflate($_GET['resourceQuery']));
        $this->valueQuery = unserialize(gzinflate($_GET['valueQuery']));
        $this->mergedQuery = $this->merge($this->resourceQuery, $this->valueQuery);
    }

    public function exportAction()
    {
        $this->view->placeholder('main.window.title')->set('Listexporter');
        $this->addModuleContext('main.window.listexporter.export');
        OntoWiki::getInstance()->getNavigation()->disableNavigation();
        
        if (isset($_GET['filename'])) {
            $filename = $_GET['filename'] . '_' . date("Ymd-His");
        } else {
            $filename = '_' . date("Ymd-His");
        }
        
        $convertUris = true;
        if (isset($_GET['convertUris'])) {
            $convertUris = false;
        }

        //query selected model
        $result = $this->_owApp->selectedModel->sparqlQuery($this->mergedQuery->getSparql(), array(
            'result_format' => 'csv'
        ));
        
        if ($convertUris) {
            $result = $this->enrichWithTitles($result);
        }

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout()->disableLayout();
        $response = $this->getResponse();
        $contentType = 'text/csv';
        $filename = "export_$filename.csv";
        $response->setHeader('Content-Type', $contentType, true);
        $response->setHeader('Content-Disposition', ('filename="' . $filename . '"'));

        $response->setBody($result);
        return;
    }

    public function viewAction()
    {
        $this->view->placeholder('main.window.title')->set('Listexporter');
        $this->addModuleContext('main.window.listexporter.export');
        OntoWiki::getInstance()->getNavigation()->disableNavigation();
        
        $this->view->resourceQuery = $this->resourceQuery;
        $this->view->valueQuery = $this->valueQuery;
        $this->view->mergedQuery = $this->mergedQuery;
    }

    private function merge($resourceQuery, $valueQuery)
    {
        $mergedQuery = new Erfurt_Sparql_Query2();
        
        // selection variables
        foreach ($valueQuery->getProjectionVars() as $value) {
            $mergedQuery->addProjectionVar($value);
        }
        
        // where clause from resourceQuery
        $where = $resourceQuery->getWhere();
        
        // + optionals from valueQuery
        foreach ($valueQuery->getWhere()->getElements() as $value) {
            if ($value instanceof Erfurt_Sparql_Query2_OptionalGraphPattern) {
                $where->addElement($value);
            }
        }
        $mergedQuery->setFroms($resourceQuery->getFroms());
        $mergedQuery->setWhere($where);
        $order = new Erfurt_Sparql_Query2_OrderClause();
        $mergedQuery->setOrder($resourceQuery->getOrder());
        
        return $mergedQuery;
    }

    private function enrichWithTitles($result)
    {
        $titleHelper = new OntoWiki_Model_TitleHelper();
        $lines = explode("\r\n", $result);
        $resultWithTitles = null;
        foreach ($lines as $line) {
            $lineValues = str_getcsv($line);
            $rowWithTitle = array();
            $isFirstElement = true;
            foreach ($lineValues as $value) {
                if ($isFirstElement) {
                    $rowWithTitle[] = $value;
                    $isFirstElement = false;
                } else {
                    if ($this->isUrl($value)) {
                        $title = $titleHelper->getTitle($value);
                        $rowWithTitle[] = $title;
                    } else {
                        $rowWithTitle[] = $value;
                    }
                }
            }
            $resultWithTitles .= $this->getCSV($rowWithTitle);
        }
        return $resultWithTitles;
    }


    /**
     *  outputCSV creates a line of CSV and outputs it to browser
     */
    function outputCSV($row) {
        $fp = fopen('php://output', 'w'); // this file actual writes to php output
        fputcsv($fp, $row);
        fclose($fp);
    }

    /**
     *  getCSV creates a line of CSV and returns it.
     */
    function getCSV($row) {
        ob_start(); // buffer the output ...
        $this->outputCSV($row);
        return ob_get_clean(); // ... then return it as a string!
    }

    /**
     * FInd out if a string is a url.
     * @param $text
     * @return bool
     */
    function isUrl( $text ) {
        return filter_var( $text, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) !== false;
    }
}
