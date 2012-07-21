<?php
   /**
* Freemail v1.1 with SL patch
*
* @package freemail
* @copyright Copyright (c) 2008 Serafim Panov
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
* @author Serafim Panov
* @contributor: Paul G. Preibisch - aka Fire centaur in Second Life
*
*/
$_PATHS["base"]      = dirname(__FILE__) . "/";
$_PATHS["pear"]      = $_PATHS["base"] . "PEAR/";

ini_set("include_path", "$_PATHS[pear]");

require_once 'Mail/IMAPv2.php';
require_once "sllib.php";   
$connection = $CFG->freemail_pop3_or_imap.'://'.$CFG->freemail_mail_user_name.':'.$CFG->freemail_mail_user_pass.'@'.$CFG->freemail_mail_box_settings;






//use below for gmail and gmail accounts
$msg = new Mail_IMAPv2($connection);
$attachments = array();

$msgcount = $msg->messageCount(); 

$commands = array("HELP");

if ($msgcount > 0)  
{  
    if ($msgcount > $CFG->freemail_mail_maxcheck) {
        $msgcount = $CFG->freemail_mail_maxcheck;
    }

    for ($mid = 1; $mid <= $msgcount; $mid++)
    {
    
        $msg->getHeaders($mid);
        $msg->getParts($mid);
        $mailbody = $msg->getRawMessage($mid);  //SECOND LIFE PATCH--In line
        //extract and build the slurl - FIRE
        $searchText=$mailbody;    
        $simname= getSlInfo("sim_name",$searchText);
        $username = getSlInfo("username",$searchText);
        $x= getSlCoords("local_x",$searchText);
        $y= getSlCoords("local_y",$searchText);
        $z= getSlCoords("local_z",$searchText);
        //build slurl - FIRE
        $slurl='http://slurl.com/secondlife/'.urlencode($simname).'/'.$x.'/'.$y.'/'.$z;

        if ($msg->header[$mid]['Size'] > $CFG->freemail_mail_maxsize) {
            $usermailcontent[] = array('email' => $msg->header[$mid]['from'][0], 'subject' => $msg->header[$mid]['subject'], 'error' => 'bigmailsize');
        }
        else
        {

            $messagebody = $msg->getBody($mid);                                          
            $messagebody['message'] = str_replace("Want to try out Second Life for yourself?  Sign up at", "", $messagebody['message']); //SECOND LIFE PATCH--In line
            $messagebody['message'] = str_replace("--", "", $messagebody['message']); //SECOND LIFE PATCH--In line
            //add delimiter so we can delete the unwanted text - FIRE                        
            $messagebody['message'] .="endhere"; 
            //find start of unwanted text - FIRE
            $cutoffstart=strpos($messagebody['message'],"http://secondlife.com"); 
            $cutoffend=strpos($messagebody['message'],"endhere",$cutoffstart+4);
            $cutlength=$cutoffend-$cutoffstart+7;           
            $toDelete= substr($messagebody['message'],$cutoffstart,$cutlength);
            //now remove unwanted text - FIRE            
            $messagebody['message'] = str_replace($toDelete," ",$messagebody['message']);           
            
            //SECOND LIFE PATCH--BEGIN
            $c = -1;
            
            $secondimage = "";
            
            $mailbody = explode("\r", $mailbody);
            foreach ($mailbody as $mailbody_) {
                if (strstr($mailbody_, 'Content-Disposition: inline; filename="secondlife-postcard.jpg"')) {
                    $c = 0;
                }
                if ($c >= 0) {
                    if ($c > 3) {
                        if (strlen($mailbody_) == 61) {
                            $secondimage .= $mailbody_;
                        }
                        else
                        {
                            if (strlen($mailbody_) >0) {
                                $secondimage .= $mailbody_;
                            }
                            $c = -1;
                        }
                        
                    }
                    else
                    {
                        $c++;
                    }
                }
            }
            //SECOND LIFE PATCH---END
            
            list($subcomm, $messcomm) = freemail_getcommands($msg->header[$mid]['subject'], $messagebody['message'], $commands);

            $imagebody = "";
            $imagename = "";

            // Now the attachments
            if (isset($msg->msg[$mid]['at']['pid']) && count($msg->msg[$mid]['at']['pid']) > 0)
            {
                foreach ($msg->msg[$mid]['at']['pid'] as $i => $aid)
                {
                    $fname = (isset($msg->msg[$mid]['at']['fname'][$i]))? $msg->msg[$mid]['at']['fname'][$i] : NULL;
                    
                    $body = $msg->getBody($mid, $aid);  
                    
                    if (empty($imagename)) {
                        $imagename = $fname;
                    }
                    if (empty($imagebody)) {
                        $imagebody = $body['message'];
                    }
                    
                    $attachments[] = array("size"=>$msg->msg[$mid]['at']['fsize'][$i], "type"=>$msg->msg[$mid]['at']['ftype'][$i], "filename"=>$fname, "filecontant"=>base64_encode($body['message']));
                    
                }
            }
            
            
            //SECOND LIFE PATCH--BEGIN
            if (empty($imagename)) {
                $imagename = "secondlife-postcard.jpg";
                $imagebody = $secondimage;
            }
            //SECOND LIFE PATCH--END

            print "Skipping delete";
            //$msg->delete($mid);
            $usermailcontent[] = array('size'=>$msg->header[$mid]['Size'], 'email'=>$msg->header[$mid]['from'][0], 'subject'=>$msg->header[$mid]['subject'], 'messages'=>$messagebody['message'], 'subcommands'=>$subcomm, 'mescommands'=>$messcomm, 'image'=>$imagebody, 'image_name'=>$imagename, 'body'=>$messagebody['message'], 'attachments'=>$attachments,'slurl'=>$slurl);
        }
    }
}

$msg->expunge();
$msg->close(); 

function freemail_getcommands($subject, $messages, $commands) {
    $submail= Array();
    $mesmail= Array();
    foreach ($commands as $name => $value) {
        if (strpos(strtoupper($subject), $value) === false) { } else { $submail[] = $value; }
        if (strpos(strtoupper($messages), $value) === false) { } else { $mesmail[] = $value; }
    }
    
    return array($submail, $mesmail);
}



?>
