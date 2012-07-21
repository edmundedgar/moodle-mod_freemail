<?php
/**
* Freemail v1.1 with SL patch
*
* @package freemail
* @copyright Copyright (c) 2008 Serafim Panov
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
* @author Serafim Panov
* 
*
*/
require_once("../../config.php");
require_login();
if (!isteacher()) {
    die();
}
header("Content-type: text");
?>   