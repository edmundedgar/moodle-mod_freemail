<?php
/*
Importer for getting the results of a message into a blog.
*/
class freemail_blog_moodle_importer extends freemail_moodle_importer {

    /*
    var $_title;
    var $_body;
    var $_images = array();
    var $_userid;
    */

    function is_available() {
        return true;
    }

    function is_email_importable() {
        return true;
    }

    // Return true to say that this processor can process the email.
    // For example, if we have a rule that blog subjects have to begin with "b:", we'll check for that.
    // For now we'll take anything with a subject and a body.
    function can_process() {

        if ($this->_title == '') {
            return false;
        }
        if ($this->_body == '') {
            return false;
        }
        if (!$this->_userid) {
            return false;
        }
        return true;

    }

    function import() {

        global $CFG;

        if (!file_exists($CFG->dirroot.'/blog/locallib.php')) {
            return false;
        }

        require_once($CFG->dirroot.'/blog/locallib.php');
        require_once($CFG->dirroot.'/tag/lib.php');


        $data = array(
            'subject' => $this->_title,
            'summary' => $this->_body,
            'userid'  => $this->_userid,
            'publishstate' => 'draft'
            //'tags' => array('SLOODLE')
        );

        $blogentry = new blog_entry(null, $data, $blogeditform);
        $blogentry->add();

        if (!$id = $blogentry->id) {
            return false;
        }

        $fs = get_file_storage();

        foreach($this->_images as $name => $data) {
            
            $fileinfo = array(
                'contextid' => 1, // Hope this is right...
                'component' => 'blog',     // 
                'filearea' => 'attachment',     // 
                'itemid' => $id,
                'userid' => $this->_userid,
                'filepath' => '/',
                'filename' => time().'-'.$name
            );

            $fs->create_file_from_string( $fileinfo, $data);

        }

        return true;

    }

}
