<?php
/**
 * Listexporter component controller. This controller is used for demonstration and export
 * purposes of the listexporter.
 *
 * @category   OntoWiki
 * @package    Extensions_Listexporter
 * @author     Sebastian Nuck
 * @copyright  Copyright (c) 2014, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
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
        $filename = $_GET['filename'];

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
        if (strpos($valueQuery ,'OPTIONAL') !== false) {
            $query = substr($valueQuery, 0, $positionOfFilter + 1);
        } else {
            $query = substr($valueQuery, 0, $positionOfFilter);
        }

        // extract where part from resource query
        $positionOfWhere = strpos($resourceQuery, 'WHERE {') + strlen('WHERE {');
        $query = trim($query); // remove trailing whitespaces
        $query = rtrim($query, "}"); // remove trailing }
//        $query = substr($query, 0, -1); // remove last }
        $query .= substr($resourceQuery, $positionOfWhere);

        // remove LIMIT
        $positionOfLimit = strpos($query, 'LIMIT');
        $query = substr($query, 0, $positionOfLimit); // $positionOfLimit = length - 1 --> } remains
        return $query;
    }

    private function enrichWithTitles($result)
    {
        $titleHelper = new OntoWiki_Model_TitleHelper();
        $lines = explode(PHP_EOL, $result);
        $resultWithTitles = null;
        foreach ($lines as $line) {
            $lineValues = str_getcsv($line);
            $valueWithTitle = array();
            $isFirstElement = true;
            foreach ($lineValues as $value) {
                if ($isFirstElement) {
                    $valueWithTitle[] = $value;
                    $isFirstElement = false;
                } else {
                    $valueWithTitle[] = $titleHelper->getTitle($value);
                }
            }
            $resultWithTitles .= implode(",", $valueWithTitle) . "\r\n";
        }
        return $resultWithTitles;
    }
}
