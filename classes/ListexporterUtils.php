<?php
/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Sebastian Nuck
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
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