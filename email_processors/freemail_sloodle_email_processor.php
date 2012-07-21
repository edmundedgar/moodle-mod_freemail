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
        return 1;
        return null;
    }

    function prepare() {

        //extract and build the slurl - FIRE
        $searchText = $this->_html_body;    

        $simname= $this->get_sl_info("sim_name",$searchText);
        $username = $this->get_sl_info("username",$searchText);

        $x= $this->get_sl_coords("local_x",$searchText);
        $y= $this->get_sl_coords("local_y",$searchText);
        $z= $this->get_sl_coords("local_z",$searchText);

        $slurl='http://slurl.com/secondlife/'.urlencode($simname).'/'.$x.'/'.$y.'/'.$z;

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
                print "adding";
            }
        }

        return true;

    }

    /**
    * This method is used to extact text from the sl postcard email such as sim_name and agent_name
    * Example:
    * @simname= getSlInfo("sim_name",$searchText);
    * $username = getSlInfo("username",$searchText);
    * @param string $findMe is the thing to search for - such as sim_name
    * @param string $searchText is the text to be searched - such as the message body of the email
    * @return string Can return extracted content from the sl postcard - such as sim_name and agent_name 
    *  
    */
    function get_sl_info($findMe,$searchText){
        //now found the beginning of the value ex: sim_name=" so we have to account for the characters =" which is +2 characters
        $findMeStartIndex = strpos($searchText,$findMe) + strlen($findMe) +2;
        $findMeEndIndex = strpos($searchText,'"',$findMeStartIndex);
        $findMeLength=$findMeEndIndex-$findMeStartIndex;
        $findMeValue= substr($searchText,$findMeStartIndex,$findMeLength);
        return $findMeValue; 
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
