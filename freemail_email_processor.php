<?php
/*
This is a base class for email processors.
It needs to be extended by a specific email processor, stored in the email_processors directory.
*/
abstract class freemail_email_processor {

    // The following hold the raw data from the email.
    var $_subject;
    var $_from_address;
    var $_html_body;
    var $_plain_body;
    var $_charset;
    var $_attachments = array();

    // The following are filled during parsing. 
    var $_images= array();
    var $_userid = null;
    var $_prepared_body;
    var $_prepared_subject;

    var $_importer;

    static function available_email_processors() {

        global $CFG;
        $processor_dir = $CFG->dirroot.'/mod/freemail/email_processors';

        if (!$dh = opendir($processor_dir)) {
            return false;
        }

        $processors = array();

        while (($processor_file = readdir($dh)) !== false) {

            if (preg_match('/^(freemail_\w+_email_processor).php$/', $processor_file, $matches)) {
                
                $clsname = $matches[1];
                require($processor_dir.'/'.$processor_file);
                if (!class_exists($clsname)) {
                    continue;
                }
                if (!$clsname::is_available()) {
                    continue;
                }
                $processors[] = new $clsname;

            }

        }

        return $processors;

    }

    // Adds an attachment.
    // Multi-part emails can have lots of random little attachments.
    // In sloodle we are interested in images
    // ...and narrow down to provide them in get_images().
    function add_attachment($attachment_name, $attachment_data) {
        $this->_attachments[$attachment_name] = $attachment_data;
    }

    function add_image($filename, $data) {
        $this->_images[$filename] = $data;
    }

    // Return true to say that this processor can process the email.
    // This will include finding an importer that can handle the email.
    function is_email_processable() {
        return false;
    }

    // Import the message or return false on failure
    function import() {

        if (!$this->_importer) {
            return false;
        }

        $this->_importer->set_user_id($this->_userid);
        $this->_importer->set_title($this->_prepared_subject);
        $this->_importer->set_body($this->_prepared_body);

        foreach($this->_images as $n => $imgdata) {
            $this->_importer->add_image($n, $imgdata);
        }

        return $this->_importer->import();

    }

    // Transform the raw text from the email into whatever we want to put into Moodle.
    // You probably want to overload this.
    // In the sloodle case this will consist of stripping Second Life advertising 
    // ...and adding a URL pointing to the user's location.
    function prepare() {

        $this->_prepared_body = $this->_plain_body;
        $this->_prepared_subject = $this->_subject;

        if (!$this->_userid = $this->user_id_for_email($this->_from_address)) {
            return false;
        }

        return true;

    }

    // Return the user ID
    function get_user_id() {
        return $this->_userid;
    }

    function set_charset($c) {
        $this->_charset = $c;
    }

    function get_charset() {
        return $this->_charset;
    }

    function get_plain_body() {
        return $this->_plain_body;
    }

    function set_plain_body($b) {
        $this->_plain_body = $b;
    }

    // Return the message body
    function get_html_body() {
        return $this->_html_body;
    }

    function set_html_body($m) {
        $this->_html_body = $m;
    }

    function set_subject($s) {
        $this->_subject = $s;
    }

    function set_from_address($e) {
        $this->_from_address = $e;
    }

    function get_subject() {
        return $this->_subject;
    }

    function load_importer() {
        
        $importers = freemail_moodle_importer::available_moodle_importers();
        if (!count($importers)) {
            return false;
        }

        foreach($importers as $importer) {
            if ($importer->is_email_importable()) {
                $this->_importer = $importer;
                return true;
            }
        }

        return false;
    
    }

    function user_id_for_email($email) {

        if (is_null($email)) {
            return null;
        }

        global $DB;
        $this->_userid = $DB->get_field('user', 'id', array('email'=>$email));

        return $this->_userid;

    }

    // By default, just tell the importer to notify them.
    // This would normally be an email.
    // Some handler may want to do their own handling
    // ..and just get the subject and body from the importer.
    // For example, in SLOODLE we send an in-world instant message.
    function notify_user() {

        if (!$importer = $this->_importer) {
            return false;
        }

        return $importer->notify_user();

    }

}
