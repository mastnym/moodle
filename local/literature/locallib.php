<?php



// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * link literature
 * 
 *

 *
 * @package local
 * @subpackage literature
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once '../../config.php';

function findIsbn($str)
{
	$regex = '/\b(?:ISBN(?:: ?| ))?((?:97[89])?\d{9}[\dx])\b/i';

	if (preg_match($regex, str_replace('-', '', $str), $matches)) {
		return (10 === strlen($matches[1]))
		? 1   // ISBN-10
		: 2;  // ISBN-13
	}
	return false; 
}

function downloadCSV($url,$path){
	$ch = curl_init(CSV_URL);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	$fp = fopen($path, 'w');
    curl_setopt($ch, CURLOPT_FILE, $fp);
 
   	curl_exec($ch);
 
    curl_close($ch);
    fclose($fp);
}