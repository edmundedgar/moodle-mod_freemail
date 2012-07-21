<?php
/*
This is a base class for email processors.
It needs to be extended by a specific moodle importer, stored in the moodle_importers directory.
*/
abstract class freemail_moodle_importer {

    var $_title;
    var $_body;
    var $_images = array();
    var $_userid;

    // Return true to say that this processor can process the email.
    // For example, if we have a rule that blog subjects have to begin with "b:", we'll check for that.
    function can_process() {
        return false;
    }

    // Return the user ID
    function get_user_id() {
        return $this->_userid;
    }

    function set_user_id($u) {
        $this->_userid = $u;
    }

    // Return the body body
    function get_body() {
        return $this->_body;
    }

    function set_body($m) {
        $this->_body = $m;
    }

    function set_title($s) {
        $this->_title = $s;
    }

    function get_title() {
        return $this->_title;
    }

    function add_image($filename, $imgdata) {
        $this->_images[$filename] = $imgdata;
    }

    function get_images() {
        return $this->_images;
    }

    static function available_moodle_importers() {

        global $CFG;
        $importer_dir = $CFG->dirroot.'/mod/freemail/moodle_importers';

        if (!$dh = opendir($importer_dir)) {
            return false;
        }

        $importers = array();

        while (($importer_file = readdir($dh)) !== false) {
            print "looking at $importer_file";

            if (preg_match('/^(freemail_\w+_moodle_importer).php$/', $importer_file, $matches)) {
                
                $clsname = $matches[1];
                require($importer_dir.'/'.$importer_file);
                if (!class_exists($clsname)) {
                    continue;
                }
                if (!$clsname::is_available()) {
                    continue;
                }
                $importers[] = new $clsname;

            }

        }

        return $importers;

    }
 

}
