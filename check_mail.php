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
require_once "../../config.php";
require_once "lib_.php";
require_once "readmail.php";

//-----------No Edit------------------//

// Display the Moodle page header -- keeps the page consistent with other Moodle features
print_header_simple('SLOODLE Freemail - Postcard Blogger', 'SLOODLE Freemail - Postcard Blogger');
echo "<div>&nbsp;</div>\n";

$commands = array("HELP");

$noticeTable = new stdClass();
$noticeTable->head = array('SLOODLE Freemail - Postcard Blogger');
$r = array();
$r[] = get_string('freemail:cronnotice','freemail');

$courseTable = new stdClass();
$courseTable->class="course-view";

$noticeTable->cellpadding=10;
$noticeTable->cellspacing=10; 
$noticeTable->data[] = $r;  
print_table($noticeTable);

if (empty($usermailcontent)) {
    echo "<br/><h3 style=\"text-align:center;\">No mail was found on the server</h3><br/>";
    $mails = null;
} else {
    $mails = $usermailcontent;
}



if (is_array($mails)) {

    echo get_string("freemail_024", "freemail");
    echo sizeof($mails)."\r<br /><br />";

  foreach ($mails as $mail) {
    $datetext = date("F j, Y, g:i a", time());
    freemail_setlog("{$datetext} PROCESS START | email:{$mail['email']} | subject:*********");
    $noerrors = empty($mail['error']);
    
    if ($noerrors) {
        $mail['subject'] = strtolower($mail['subject']);
        $mail['subject'] = str_replace(" ", "", $mail['subject']);
        
        if ($CFG->freemail_2bytes == 1) {
            $mail['subject'] = str_replace("&#65306;", ":", $mail['subject']);
            $mail['subject'] = str_replace("&#65351;", "g", $mail['subject']);
        }
  
        $subline = explode (":", $mail['subject']);
        $subline[0] = strtolower($subline[0]);
        $mail['subject'] = trim($mail['subject']);
        

        
        $mail['messages'] = str_replace("=20", "\r\n", strip_tags($mail['messages']));
         
        if ($CFG->freemail_subjectline == 1) {
            freemail_setlog("    | check and convert subject line | new subject:**********");
    
            $problem = false;
            /*
            foreach (preg_split('//', $mail['subject'], -1, PREG_SPLIT_NO_EMPTY) as $sl) {
                if (mb_detect_encoding($sl) == "UTF-8" ) {
                    $problem = true;
                }
            }
            */
    
            if ($problem) {
                freemail_setlog("    | NOTICE: not all characters converted, you can have problem");
            }
        }
    
        //Save attached image
        if (!empty($mail['image_name']) && ($subline[0] == "p" || $subline[0] == "b")) {
            if (!file_exists($CFG->dataroot."/1")) { 
                if (!make_upload_directory("1")) {
                    $error = "The site administrator needs to fix the file permissions";
                }
            }
         
            $imgnameutf8 = $mail['image_name'];
            $imgnameutf8 = imap_utf8($imgnameutf8);
            $imgnameutf8 = utf8_decode($imgnameutf8);
         
            list ($imgname, $imgtype) = explode (".", $imgnameutf8);
         
            $mail['image_name'] = rand (9999,9999999);
            $mail['image_name'] .= ".".$imgtype;
            $mail['image_name'] = strtolower($mail['image_name']);
            
            $ifp = fopen($CFG->dataroot."/1/".$mail['image_name'], "w+" ); 
            fwrite( $ifp, base64_decode($mail['image'])); 
            fclose( $ifp ); 
        
            freemail_setlog("    | save attached image | image name:{$CFG->dataroot}/1/{$mail['image_name']}");
        }

        if (@in_array("HELP", $mail['subcommands'])||@in_array("HELP", $mail['mescommands'])) { 
            freemail_sendmail ($CFG->freemail_mess_007, $mail['email']);
            echo $mail['email'] . " - ";
            echo get_string("freemail_025", "freemail");
            echo " {true}\r<br />";
        
            freemail_setlog("    | found HELP command, help file sent");
        } 
  
        if ($subline[0] == "p") {
            freemail_setlog("    | found profile image command | start");
        
            if (empty($mail['image_name'])) {
                freemail_sendmail ($CFG->freemail_mess_005, $mail['email']);
                $result = "ERROR: No image";
            }
            else
            {
                $result = freemail_profile($mail['image_name'], $mail['email'], $mail['subject']);
            }
        
            echo $mail['email'] . " - ";
            echo get_string("freemail_026", "freemail");
            echo " {".$result."}\r<br />";
        
            freemail_setlog("    | found profile image command | result:{$result}");
        }

        if ($subline[0] == "b") {
            freemail_setlog("    | found blog command | start");
    
            $result = freemail_blog($mail['image_name'], $mail['email'], $mail['subject'], $mail['messages'],$mail['slurl']);
          
            echo $mail['email'] . " - ";
            echo get_string("freemail_027", "freemail");
            echo " {".$result."}\r<br />";
        
            freemail_setlog("    | found blog command | result:{$result}");
        }
    
        if ($subline[0] == "g") {
            freemail_setlog("    | found gallery command | start");
    
            $result = freemail_gallery($mail['attachments'], $mail['email'], $mail['subject'], $mail['messages']);
          
            echo $mail['email'] . " - ";
            echo get_string("freemail_028", "freemail");
            echo " {".$result."}\r<br />";
          
            freemail_setlog("    | found gallery command | result:{$result}");
        }
    
        if ($subline[0] == "f") {
            freemail_setlog("    | found file command | start");
    
            $result = freemail_uploadattachedfiles($mail['attachments'], $mail['email'], $mail['subject'], $mail['messages']);
          
            echo $mail['email'] . " - ";
            echo get_string("freemail_030", "freemail");
            echo " {".$result."}\r<br />";
          
            freemail_setlog("    | found file command | result:{$result}");
        }
    
        if ($subline[0] == "fm") {
            freemail_setlog("    | found file manager command | start");
    
            $result = freemail_uploadattachedfilestofm($mail['attachments'], $mail['email'], $mail['subject'], $mail['messages']);
          
            echo $mail['email'] . " - ";
            echo get_string("freemail_030", "freemail");
            echo " {".$result."}\r<br />";
          
            freemail_setlog("    | found file manager command | result:{$result}");
        }
        
        @unlink($CFG->dataroot."/1/".$mail['image_name']);
        
        if (strstr($result, "ERROR")) {
            echo "<br />Send report to admin: ";
            $adminEmail =$CFG->freemail_mail_admin_email;
            freemail_sendmail ("Bug report form FreeMail ({$result})\r\n" . "This users email message was not processed\r\nUserEmail: " . $mail['email']."\r\nSubject: " . $mail['subject']."\r\nBody:\r\n".$mail['body'], $adminEmail);
               
            
        }
    
    }
    else
    {
        if (!empty($mail['error'])) {
            if ($mail['error'] == 'bigmailsize') {
                freemail_setlog("    | attachment is too large | result: false");
                freemail_sendmail("Your attachments is too large.", $mail['email']);
            }
        }
    }
  }

    echo "<br />";
    echo get_string("freemail_029", "freemail");
}

// Display the Moodle page footer
print_footer();

// Disable notices from being reported -- there is a bug in the IMAP system causing a notice to be reported on shutdown.
if (error_reporting() >= E_NOTICE) error_reporting(E_PARSE);

?>
