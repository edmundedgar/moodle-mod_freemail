<?php
/*
* Sloodle email processfor for the freemail mod (for Sloodle 0.4).
* Various functions to extract info from sl postcards
*
* @package freemail
* @copyright Copyright (c) 2009 various contributors 
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributor Paul G. Preibisch - aka Fire Centaur in Second Life
* @contributor Edmund Edgar
*/
class freemail_sloodle_email_processor extends freemail_email_processor {

    // Check if SLOODLE is present.
    // TODO Maybe we should really check if it's installed and active...
    static function is_available() {

        global $CFG;
        if (!file_exists($CFG->dirroot.'/mod/sloodle')) {
            return false;
        }
        return true;

    }

    // Return true to say that this processor can process the email.
    function is_email_processable() {
        return true;
    }

    // Return the user ID
    function get_user_id() {
        return $this->_userid;
    }

    function prepare() {

        //extract and build the slurl - FIRE
        $search_text = $this->_html_body;    

        $sl_info = $this->get_sl_info($search_text);

        $uuid = $sl_info['agent_id'];
        if (!$this->_userid = $this->get_user_id_for_avatar_uuid($uuid)) {
            return false;
        }

        $simname = $sl_info['sim_name'];
        $username = $sl_info['username'];

        $x = intval($sl_info['local_x']);
        $y = intval($sl_info['local_y']);
        $z = intval($sl_info['local_z']);

        $slurl = 'http://slurl.com/secondlife/'.urlencode($simname).'/'.$x.'/'.$y.'/'.$z;

        $messagebody = $this->_plain_body;

        $messagebody = str_replace("Want to try out Second Life for yourself?  Sign up at", "", $messagebody); //SECOND LIFE PATCH--In line
        $messagebody = str_replace("--", "", $messagebody); //SECOND LIFE PATCH--In line
        //add delimiter so we can delete the unwanted text - FIRE                        
        $messagebody .="endhere"; 
        //find start of unwanted text - FIRE

        $cutoffstart=strpos($messagebody,"http://secondlife.com"); 
        $cutoffend=strpos($messagebody,"endhere",$cutoffstart+4);
        $cutlength=$cutoffend-$cutoffstart+7;           
        $toDelete= substr($messagebody,$cutoffstart,$cutlength);

        //now remove unwanted text - FIRE            
        $messagebody = str_replace($toDelete," ",$messagebody);           

        $this->_prepared_body = $messagebody;
        $this->_prepared_subject = $this->_subject;

        $interesting_filenames = array('secondlife-postcard.jpg');

        if (count($this->_attachments)) {
            foreach($this->_attachments as $filename => $data) {
                if (!in_array($filename, $interesting_filenames)) {
                    continue;
                }
                $this->add_image($filename, $data);
            }
        }

        return true;

    }

    function get_user_id_for_avatar_uuid($avuuid) {

        if ( is_null($avuuid) || ($avuuid == '') ) {
            return null;
        }

        global $DB;

        $this->_userid = $DB->get_field('sloodle_users', 'userid', array('uuid'=>$avuuid));
        return $this->_userid;

    }

    /**
    * This method is used to extact text from the sl postcard email such as sim_name and agent_name
    * Example:
    * $simname = $this->get_sl_info("sim_name",$email);
    * @param string $find_me is the thing to search for - such as sim_name
    * @param string $search_text is the text to be searched - such as the message body of the email
    * @return string Can return extracted content from the sl postcard - such as sim_name and agent_name 
    *  
    */
    function get_sl_info($search_text){

        /*
        Text looks like this:
        <!-- BEGIN POSTCARD DETAILS
        agent_id=746ad236-d28d-4aab-93de-1e09a076c5f3
        username="Edmund Earp"
        region_id=5368d895-55d9-4206-9c1c-8660ce8fa306
        sim_name="Cypris Village"
        global_x=274595
        global_y=271440
        local_x=163
        local_y=80
        local_z=25
        END POSTCARD DETAILS -->
        */

        $info = array();

        // start by narrowing down to the postcard section.
        $detail_start = 'BEGIN POSTCARD DETAILS';
        $detail_end   = 'END POSTCARD DETAILS';

        if (!preg_match('/^.*'.preg_quote($detail_start).'(.*)'.preg_quote($detail_end).'.*?$/s', $search_text, $matches)) {
            return null;
        }
        $search_text = $matches[1];

        $lines = explode("\n", $search_text);
        foreach($lines as $line) {

            $line = trim($line);
            if (!preg_match('/^(.*?)\=(.*)$/', $line, $matches)) {
                continue;
            }

            $name = $matches[1];
            $value = $matches[2];

            // remove quotation marks, if they're there.
            if (preg_match('/\"(.*)\"/', $value, $matches)) {
                $value= $matches[1];
            }

            $info[$name] = $value;

        }

        return $info;

    }

    /*
    * This function gets the sl coordinates from the sl postcard email body 
    * Example:
    * $x= getSlCoords("local_x",$searchText);
    * $y= getSlCoords("local_y",$searchText);
    * $z= getSlCoords("local_z",$searchText);
    * $slurl='http://slurl.com/secondlife/'.urlencode($simname).'/'.$x.'/'.$y.'/'.$z;
    * echo $slurl; 
    * 
    * @param string $findMe is the thing to coordinate name search for - such as local_x
    * @param string $searchText is the text to be searched - such as the message body of the email
    * @return string returns the extracted coordinates
    */
    function get_sl_coords($findMe,$searchText){
        //now found the beginning of the value ex: sim_name=" so we have to account for the characters =" which is +2 characters
        $findMeStartIndex = strpos($searchText,$findMe) + strlen($findMe) +1;

        $findMeEndIndex =   strpos($searchText,"\n",$findMeStartIndex);
        $findMeLength=$findMeEndIndex-$findMeStartIndex-1;
        $findMeValue= substr($searchText,$findMeStartIndex,$findMeLength);
        return $findMeValue; 
    }

}
