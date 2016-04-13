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

    public function exportAction()
    {
        $this->view->placeholder('main.window.title')->set('Listexporter');
        $this->addModuleContext('main.window.listexporter.export');
        OntoWiki::getInstance()->getNavigation()->disableNavigation();

        $resourceQuery = $_GET['resourceQuery'];
        $valueQuery = $_GET['valueQuery'];
        if (isset($_GET['filename'])) {
            $filename = $_GET['filename'] . '_' . date("Ymd-His");
        } else {
            $filename = '_' . date("Ymd-His");
        }

        $query = $this->mergeQueries($resourceQuery, $valueQuery);

        $convertUris = true;
        if (isset($_GET['convertUris'])) {
            $convertUris = false;
        }

        //query selected model
        $result = $this->_owApp->selectedModel->sparqlQuery(
            $query,
            array(
                'result_format' => 'csv'
            )
        );

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

        $resourceQuery = $_GET['resourceQuery'];
        $valueQuery = $_GET['valueQuery'];

        $query = $this->mergeQueries($resourceQuery, $valueQuery);

        $this->view->resourceQuery = $resourceQuery;
        $this->view->valueQuery = $valueQuery;
        $this->view->query = $query;
    }

    /*
     * Merges the value query and the resource query.
     */
    private function mergeQueries($resourceQuery, $valueQuery)
    {
        $this->view->resourceQuery = $resourceQuery;
        $this->view->valueQuery = $valueQuery;

        // cut everything from FILTER (the sameTerm queries) of value query
        $positionOfFilter = strpos($valueQuery, 'FILTER');
        $endOfFilterClause = $this->calculateEndOfFilterClause($valueQuery, $positionOfFilter);
        $queryBeforeFilter = substr($valueQuery, 0, $positionOfFilter);
        $queryAfterFilter = substr($valueQuery, $endOfFilterClause);
        $query = $queryBeforeFilter . $queryAfterFilter;
        $startOfWhereClause = strpos($resourceQuery, 'WHERE {');
        $endOfWhereClause = $this->calculateEndOfWhereClause($query, $startOfWhereClause);
        $query = substr($query,0, $endOfWhereClause);

        // extract where part from resource query
        $positionOfWhere = strpos($resourceQuery, 'WHERE {') + strlen('WHERE {');
        $query = trim($query); // remove trailing whitespaces
        if(strrpos($query, '.') !== strlen($query) - 1){
            $query .= " . ";
        }
        $query .= substr($resourceQuery, $positionOfWhere);

        // remove LIMIT
        $positionOfLimit = strpos($query, 'LIMIT');
        $query = substr($query, 0, $positionOfLimit);
        return $query;
    }

    private function calculateEndOfFilterClause($query, $startOfFilterClause){
        $braceCount = 0;
        $anyBraceFound = false;
        $endOfFilterClause = 0;
        $queryLength = strlen($query);
        for($pos = $startOfFilterClause; $pos < $queryLength; $pos++){
            $char = substr($query, $pos, 1);
            if($char === '('){
                $anyBraceFound = true;
                $braceCount++;
            }
            if($char === ')'){
                $braceCount--;
            }
            if($anyBraceFound && $braceCount === 0){
                $endOfFilterClause = $pos + 4;
                break;
            }
        }
        return $endOfFilterClause;
    }

    private function calculateEndOfWhereClause($query, $startOfWhereClause){
        $braceCount = 0;
        $anyBraceFound = false;
        $endOfWhereClause = 0;
        $queryLength = strlen($query);
        for($pos = $startOfWhereClause; $pos < $queryLength; $pos++){
            $char = substr($query, $pos, 1);
            if($char === '{'){
                $anyBraceFound = true;
                $braceCount++;
            }
            if($char === '}'){
                $braceCount--;
            }
            if($anyBraceFound && $braceCount === 0){
                $endOfWhereClause = $pos;
                break;
            }
        }
        return $endOfWhereClause;
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
