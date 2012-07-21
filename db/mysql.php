<?php // $Id: mysql.php,v 1.3 2006/08/28 16:41:20 mark-nielsen Exp $
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
function freemail_upgrade($oldversion) {
    global $CFG;

    if ($oldversion < 2009021100) {

       
        //add extra field to freemail to fix bug
        echo "adding course field to freemail<br/>";               
        $table = new XMLDBTable('freemail');
        $field = new XMLDBField('course');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'id');  
      
    /// Launch add field format
       $result = add_field($table, $field); 
       return $result;
    
 

    }

    return true;
}

?>
