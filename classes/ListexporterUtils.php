<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2014, {@link http://aksw.org AKSW}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class ListexporterUtils {

    /*
     * Extracts those values of an array with the key that matches the pattern.
     */
    public static function extractMatchingValuesFromArray($pattern, $array) {
        // extract the keys.
        $keys = array_keys($array);

        // convert the preg_grep() returned array to int..and return.
        // the ret value of preg_grep() will be an array of values
        // that match the pattern.
        return preg_grep($pattern,$keys);
    }

} 