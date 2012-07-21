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
global $CFG;
require_once $CFG->dirroot . "/config.php";
require_once $CFG->dirroot ."/lib/datalib.php";

  
  
  
  
  
  function freemail_profile($image_name, $email, $subject) 
  {
    global $CFG;
     
    $dir = "users";
    $subj = explode (":", $subject);
    
    $userid = $subj[1];
    $userpass = $subj[2];
    
    $valid = freemail_validimage ($image_name, $email, 0);
    
    if ($valid == "true") {
        if ($CFG->freemail_usepassword == 1) {
            $user = get_record_sql ("SELECT * FROM ".$CFG->prefix."user WHERE username = '".$userid."' and password = '".md5($userpass)."'");
        }
        else
        {
            $user = get_record_sql ("SELECT * FROM ".$CFG->prefix."user WHERE username = '".$userid."'");
        }
        $id = $user->id;
        if (!empty($id)) {
          $destination = $dir .'/'. $id;

          if (!is_dir ($destination)) {
              make_upload_directory($destination);
          }

          freemail_createsmall($CFG->dataroot."/1/".$image_name, $destination, '100', '100', 'f1.jpg', 'true');
          freemail_createsmall($CFG->dataroot."/1/".$image_name, $destination, '35', '35', 'f2.jpg', 'true');
          
          if (!is_dir ($CFG->dataroot.'/user/0/'.$id)) {
              make_upload_directory('user/0/'.$id);
          }
          
          freemail_createsmall($CFG->dataroot."/1/".$image_name, $CFG->dataroot.'/user/0/'.$id, '100', '100', 'f1.jpg', 'true');
          freemail_createsmall($CFG->dataroot."/1/".$image_name, $CFG->dataroot.'/user/0/'.$id, '35', '35', 'f2.jpg', 'true');
          
          chmod ($CFG->dataroot.'/user/0/'.$id."/f1.jpg", 0777);
          chmod ($CFG->dataroot.'/user/0/'.$id."/f2.jpg", 0777);
          
          chmod ($destination."/f1.jpg", 0777);
          chmod ($destination."/f2.jpg", 0777);

          set_field('user', 'picture', 1, 'id', $user->id);
        
          $mess = "true";
        
          freemail_sendmail($CFG->freemail_mess_001, $email);
          add_to_log(SITEID, 'freemail', ' ', "../../admin/module.php?module=freemail", 'changing profile', 0, $id);
          
          freemail_setlog("    OK: profile image changed | login:{$userid}");
        } else {
          freemail_sendmail($CFG->freemail_mess_003, $email);
          $mess = "user login or password";
          freemail_setlog("    ERROR: invalide user login or password | login:{$userid} password:*****");
        }
        
    } 
    else 
    {
      $mess = $valid;
      freemail_setlog("    ERROR: no valid profile image | {$image_name}");
    }
    
    if ($mess != "true") { $mess = "ERROR: ".$mess; } 
    @unlink($CFG->dataroot."/1/".$image_name);
    
    return $mess;
  }
  

  function freemail_blog($image_name, $email, $subject, $body,$slurl) 
  {
    global $CFG;
    $entrypablish = '';
    if (!file_exists ($CFG->dataroot."/1/site_mod_files/blog")) {
        make_upload_directory("1/site_mod_files/blog");
    }
    
    $subj = explode (":", $subject);
    
    $userid = $subj[1];
    $userpass = $subj[2];
    
    //-------READ Email Blog text--------------//
    
    $messagetext = explode ("\r", $body);
    $entrytitle='';   
    $entrytext ='';
    foreach ($messagetext as $messagetext) {
      if (strstr(strtoupper($messagetext), "TITLE:")) 
      { 
        
        $entrytitle = trim(substr(trim($messagetext), 6)); 
      }
      else if (strstr(strtoupper($messagetext), "PUBLISH:"))
      {
        $entrypablish = trim(substr(trim($messagetext), 8)); 
      } else {
                                                                                                   
      
        
      //  $messagetext = str_replace("Want to try out Second Life for yourself?"," ",$messagetext);  
        $messagetext = htmlspecialchars($messagetext); // Delete all tags in body

        $entrytext .= $messagetext;// . "<br />"; commented this out so SLURL displays - FIRE
      }
      
    }
    //strip quotes so check_mail doesnt break - FIRE
        $entrytext = addslashes($entrytext);
    if ((strtoupper($entrypablish) != 'SITE')&&(strtoupper($entrypablish) != 'DRAFT')&&(strtoupper($entrypablish) != 'PUBLIC'))
    {
      $entrypablish = 'site';
    }
    else
    {
      $entrypablish = strtolower($entrypablish);
    }
    
    //-------READ END--------------------------//
    
    $valid = "true";
    
    if ($valid == "true") {
        if ($CFG->freemail_usepassword == 1) {
            $user1 = get_record_sql ("SELECT * FROM ".$CFG->prefix."user WHERE username = '".$userid."' and password = '".md5($userpass)."'");
        }
        else
        {
            $user1 = get_record_sql ("SELECT * FROM ".$CFG->prefix."user WHERE username = '".$userid."'");
        }
        
        $id = $user1->id;
        
        if (!empty($id)) {
          if (!empty($image_name)) 
          {
            $dir = "1/site_mod_files/blog";
          
            if (!file_exists($CFG->dataroot .'/'. $dir . "/" . $image_name)) {
              copy($CFG->dataroot."/1/".$image_name, $CFG->dataroot .'/'. $dir . "/" . $image_name);
              $image_name_1 = $image_name;
            } else {
              $image_name_1 = explode(".", $image_name);
              $image_name_1[0] = $image_name_1[0] . rand(999,9999);
              $image_name_1 = $image_name_1[0].".".$image_name_1[1];
              copy($CFG->dataroot."/1/".$image_name, $CFG->dataroot .'/'. $dir . "/" . $image_name_1);
            }
            // 
            if (!empty($image_name_1))
            {
                $videotypes_media = Array ( "ASF", "ASX", "WAX", "WM", "WMA", "WMD", "WMP", "WMV", "WMX", "WPL", "WVX", "AVI", "WAV", "MPEG", "MPG", "MP3" );
        
                $videotypes_quick = Array ( "MOV", "QT", "3GP" );

                $videotypes_real = Array ( "MP4", "RT", "RA", "RM", "RP", "RV", "M4A", "MPGA", "SMI", "SSM", "AMR", "AWB", "3G2", "DIVX" );
                
                $videotypes_flash = Array ( "FLV" );

                $videotypes_image = Array ( "JPEG", "JPG", "PNG", "GIF" );
        
                $filetype = str_replace (".", "", substr($image_name_1, strrpos($image_name_1, ".")));
                
                $fullpath = $CFG->wwwroot.'/file.php/'.$dir.'/'.$image_name_1;
                
                $formated = 0;
    
                if (in_array(strtoupper($filetype),$videotypes_quick))
                {
                    //$vstavkav = '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" align="left" height="300" width="300" codebase="http://www.apple.com/qtactivex/qtplugin.cab"> <param name="src" value="'.$fullpath.'"> <param name="autoplay" value="false"> <param name="controller" value="true"><param name="bgcolor" value="#ffffff"><embed src="'.$fullpath.'" align ="left" height="300" width="300" autoplay="false" controller="true" bgcolor="#ffffff" pluginspage="http://www.apple.com/quicktime/download/" ></object></td></tr><td><tr><a href="'.$fullpath.'">Download movie</a>';
                    $vstavkav = '<a href="'.$fullpath.'">Download movie</a>';
        
                }
                else if (in_array(strtoupper($filetype),$videotypes_media))
                {
                    //$vstavkav = '<object id="movie" classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701" standby="Loading Microsoft Windows Media Player components..." type="application/x-oleobject" name="movie" ><param name="FileName" value="'.$fullpath.'"><param name="AutoStart" value="false"><param name="ShowControls" value="true"><param name="autoSize" value="true"><param name="displaySize" value="true"><param name="loop" value="true"><param name="ShowStatusBar" value="true"><embed type="application/x-mplayer2" pluginspage="http://www.microsoft.com/Windows/MediaPlayer/" filename="'.$fullpath.'" autostart="0" showcontrols="1" autosize="1" displaysize="1" loop="1" ShowStatusBar="1" src="'.$fullpath.'" name="movie"></embed></object></td></tr><td><tr><a href="'.$fullpath.'">Download movie</a>';
                    $vstavkav = '<a href="'.$fullpath.'">Download movie</a>';

                }
                else if (in_array(strtoupper($filetype),$videotypes_real))
                {
                    //$vstavkav = '<object id="rvocx" classid="clsid:cfcdaa03-8be4-11cf-b84b-0020afbbccfa" ><param name="src" value="'.$fullpath.'"><param name="controls" value="imagewindow,controlpanel,statusbar"><param name="console" value="one"><param name="autostart" value="false"><embed src="'.$fullpath.'" controls="imagewindow,controlpanel,statusbar" console="one" autostart="false" type="audio/x-pn-realaudio-plugin"></object></td></tr><td><tr><a href="'.$fullpath.'">Download movie</a>';
                    $vstavkav = '<a href="'.$fullpath.'">Download movie</a>';
                }
                else if (in_array(strtoupper($filetype),$videotypes_flash))
                {
                    //$vstavkav = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="320" height="310" id="videoplayer" align="middle"><param name="allowScriptAccess" value="sameDomain" /><param name="movie" value="'.$CFG->wwwroot.'/mod/freemail/videoplayer.swf?file='.$fullpath.'" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" /><embed src="'.$CFG->wwwroot.'/mod/freemail/videoplayer.swf?file='.$fullpath.'" quality="high" bgcolor="#ffffff" width="320" height="310" name="videoplayer" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /></object>';
                    $vstavkav = '<a href="'.$fullpath.'">Download movie</a>';
                }
                else if (in_array(strtoupper($filetype),$videotypes_image))
                {
                    $imagesize = GetImageSize($CFG->dataroot .'/'. $dir . '/' . $image_name_1);
                    $image_name_2 = $image_name_1;
                    $image_name_1 = explode(".", $image_name_1);
                    
                    if ($imagesize[1] > 400 || $imagesize[0] > 400)
                    {
                        if ($imagesize[1] > 600 || $imagesize[0] > 800)
                        {
                            freemail_createsmall($CFG->dataroot .'/'. $dir . '/' . $image_name_2, $CFG->dataroot .'/'. $dir, "800", "600", $image_name_2);
                            freemail_createsmall($CFG->dataroot .'/'. $dir . '/' . $image_name_2, $CFG->dataroot .'/'. $dir, "400", "400", $image_name_1[0]."_s.jpg");
                            $vstavkav = '<a href="'.$fullpath.'" target="_blank"><img title="'.$image_name_2.'" alt="'.$image_name_2.'" src="'.$CFG->wwwroot.'/file.php/'.$dir.'/'.$image_name_1[0].'_s.jpg" border="0" hspace="0" vspace="0" /></a>';
                        }
                        else
                        {
                            freemail_createsmall($CFG->dataroot .'/'. $dir . '/' . $image_name_2, $CFG->dataroot .'/'. $dir, "400", "400", $image_name_1[0]."_s.jpg");
                            $vstavkav = '<a href="'.$fullpath.'" target="_blank"><img title="'.$image_name_2.'" alt="'.$image_name_2.'" src="'.$CFG->wwwroot.'/file.php/'.$dir.'/'.$image_name_1[0].'_s.jpg" border="0" hspace="0" vspace="0" /></a>';
                        }
                    }
                    else
                    {
                        $vstavkav = '<img title="'.$image_name_2.'" alt="'.$image_name_2.'" src="'.$fullpath.'" border="0" height="'.$imagesize[1].'" hspace="0" vspace="0" width="'.$imagesize[0].'" />';
                    }
                    $image_name_1 = $image_name_1[0].".".$image_name_1[1];
                }
                freemail_setlog("    | insert attached media file");
                
            }   
            
    
            //video END
        
            if (strstr($entrytext, $image_name)) {
              //$entrytext = '<div><table><tr><td>'.str_replace($image_name, '</td></tr><tr><td>'.$vstavkav.'</td></tr><tr><td>',$entrytext).'</td></tr></table></div>';
              $entrytext = str_replace($image_name, '<br />'.$vstavkav, $entrytext);
            } else {
              //$entrytext = '<div><table><tr><td>'.$vstavkav.'</td></tr><tr><td><br />'.$entrytext.'</td></tr></table></div>';
              $entrytext = $vstavkav.'<br />'.$entrytext;
            }  
            $entrytext .= "SLURL: " . $slurl;
            
            // Add field to "mdl_blog_files"
            //get_records_sql("INSERT INTO ".$CFG->prefix."blog_files (userid, filename, filesize, time) VALUES ('".$id."', '".$image_name_2."', '".filesize ($CFG->dataroot .'/1/' . $image_name_2)."', '".time()."')");
          }
          else
          {
              $formated = 1;
          }
        
        
          $time = time();
          
          
          get_records_sql("INSERT INTO ".$CFG->prefix."post (module, userid, courseid, groupid, moduleid, coursemoduleid, subject, summary, rating, format, publishstate, lastmodified, created) VALUES ('blog', '".$user1->id."', '0', '0', '0', '0', '".htmlspecialchars($entrytitle)."', '".$entrytext."', '0', $formated, '".$entrypablish."', '".$time."', '".$time."')");
        
          $mess = "true";
        
          freemail_sendmail($CFG->freemail_mess_008, $email);
          add_to_log(SITEID, 'freemail', ' ', "../../admin/module.php?module=freemail", 'adding blog', 0, $id);
          
          freemail_setlog("    OK: blog added | login:{$userid}");
        
        } else {
          freemail_sendmail($CFG->freemail_mess_009, $email);
          $mess = "user login or password";
          freemail_setlog("    ERROR: invalide user login or password | login:{$userid} password:*******");
        }
        
    } 
    else 
    {
      $mess = $valid;
    }
    
    if ($mess != "true") { $mess = "ERROR: ".$mess; } 
    
    @unlink($CFG->dataroot."/1/".$image_name);
    
    return $mess;
  }
  
  
  
  function freemail_gallery($attachments, $email, $subject, $body) 
  {
    global $CFG;
    
    $subj = explode (":", $subject);
    
    $validtypes = array ("jpg", "jpeg");
    
    $userid = $subj[1];
    $userpass = $subj[2];
    
    if ($CFG->freemail_usepassword == 0) {
        $user = get_record_sql ("SELECT * FROM ".$CFG->prefix."user WHERE username = '".$userid."'");
    }
    else
    {
        $user = get_record_sql ("SELECT * FROM ".$CFG->prefix."user WHERE username = '".$userid."' and password = '".md5($userpass)."'");
    }
    $id = $user->id;
        
        if (!empty($id)) {
          if (is_array($attachments)) 
          {
              make_upload_directory("1/site_mod_files/attachments");
              foreach ($attachments as $attachment) {
                  $imgnameutf8 = imap_utf8($attachment['filename']);
                  list ($imgname, $imgtype) = explode (".", $imgnameutf8);
                  if (in_array(strtolower($imgtype), $validtypes)) {
                      if (file_exists ($CFG->dataroot."/1/site_mod_files/attachments/".$imgnameutf8)) {
                          $imgnameutf8 = $imgname.rand (9999,9999999);
                          $imgnameutf8 .= ".".$imgtype;
                          $imgnameutf8 = strtolower($imgnameutf8);
                      }
                      $image_name = $imgnameutf8; 
                      $ifp = fopen($CFG->dataroot."/1/site_mod_files/attachments/".$imgnameutf8, "w+" ); 
                      fwrite( $ifp, base64_decode($attachment['filecontant'])); 
                      fclose( $ifp ); 
                      
                      freemail_setlog("    save image | image: {$imgnameutf8}");
                  }

              require_once ("../../gallery2/embed.php");
              $embed = new GalleryEmbed;

              //$embed->init();
              $embed->init(array('activeUserId' => 6 )); 
              $gapi = new GalleryCoreApi;
              
              list($ret,$usr) = GalleryCoreApi::fetchUserByUserName($userid);
              $userid2 = $usr->getId();

              list($ret,$rootid) = $gapi->getPluginParameter('module', 'core', 'id.rootAlbum');
              list($status, $val) = $gapi->loadEntitiesById($rootid);
              list ($ret, $childCounts) = $gapi->fetchChildItemIds($val);
              if (count($childCounts) == 0) {
                  $embed->init();
                  list ($ret, $childCounts) = $gapi->fetchChildItemIds($val);
              }

              $albumId = 0;
              $found = 0;
              $c = 0;
              while (list($k,$v) = each ($childCounts)) {
                $c++;
                list($status, $album) = $gapi->loadEntitiesById($v);
                $albomsdata[$c]['pathComponent'] = $album->pathComponent;
                $albomsdata[$c]['id'] = $album->id;
                if (GalleryUtilities::isA($album, 'GalleryAlbumItem')) { 
                  list ($ret, $childCounts2) = $gapi->fetchChildItemIds($album);
                  while (list($k2,$v2) = each ($childCounts2)) {
                    $c++;
                    list($status, $album2) = $gapi->loadEntitiesById($v2);
                    //$albomsdata[$c]['pathComponent'] = $album->pathComponent."/".$album2->pathComponent;
                    $albomsdata[$c]['pathComponent'] = $album2->pathComponent;
                    $albomsdata[$c]['id'] = $album2->id;
                  
                    //--Load Groups too
                    if (GalleryUtilities::isA($album2, 'GalleryAlbumItem')) { 
                      list ($ret, $childCounts3) = $gapi->fetchChildItemIds($album2);
                      while (list($k3,$v3) = each ($childCounts3)) {
                        $c++;
                        list($status, $album3) = $gapi->loadEntitiesById($v3);
                        //$albomsdata[$c]['pathComponent'] = $album->pathComponent."/".$album2->pathComponent;
                        $albomsdata[$c]['pathComponent'] = $album3->pathComponent;
                        $albomsdata[$c]['id'] = $album3->id;
                      }
                    }
                  }
                }
              }
              //-------READ Email Blog text--------------//
    
              $messagetext = explode ("\r", $body);
    
              foreach ($messagetext as $messagetext) {
                if (strstr(strtoupper($messagetext), "ALBUM:")) 
                { 
                  $albumname = trim(substr(trim($messagetext), 6)); 
                }
                else 
                {
                  $messagetext = htmlspecialchars($messagetext); // Delete all tags in body
                  $albumtext .= $messagetext . ""; //<br />
                }
              }

              //-------READ END--------------------------//
              
              $successadding = "false";

              $albumname = $userid;  // Delete FOR NOT QUICK ADDED!!
              
                  foreach ($albomsdata as $data) {
                    if ($data['pathComponent'] == $albumname) {
                      $successadding = "true";
                      //FINED MIME TYPE//
                      $ext = strtolower(substr($image_name, 1 + strrpos($image_name, ".")));
                      list ($ret, $mime_type) = $gapi->convertExtensionToMime($ext);
                      //---------------//
                      freemail_addItemToAlbum ($usr, $CFG->dataroot."/1/site_mod_files/attachments/".$image_name, $image_name, $image_name, $albumtext, $albumtext, $mime_type, $data['id']);
                      freemail_setlog("    image to album added | image: {$image_name} mime:{$mime_type}");
                    }
                  }
                  
                  if ($successadding == "true") {
                      $embed->done();
        
                      $mess = "true";
            
                      freemail_sendmail($CFG->freemail_mess_011, $email);
                      add_to_log(SITEID, 'freemail', ' ', "../../admin/module.php?module=freemail", 'adding gallary', 0, $id);
                  }
                  else
                  {
                      freemail_sendmail($CFG->freemail_mess_013, $email);
                      $mess = "no correct album short name";
                  }
                  
                  $albumtext = "";
              
                  @unlink($CFG->dataroot."/1/site_mod_files/attachments/".$image_name);
              
              }
          }
          else
          {
            freemail_sendmail($CFG->freemail_mess_010, $email);
            $mess = "no file";
            
            freemail_setlog("    ERROR: no found attached files");
          }
        } else {
          freemail_sendmail($CFG->freemail_mess_009, $email);
          $mess = "user login or password";
          
          freemail_setlog("    ERROR: invalide user login or password | login:{$userid} password:***********");
        }

    if ($mess != "true") { $mess = "ERROR: ".$mess; } 
    
    unlink($CFG->dataroot."/1/".$image_name);
    
    return $mess;
  }
  
  
  
  function freemail_uploadattachedfiles($attachments, $email, $subject, $body) 
  {
    global $CFG;
    
    $subj = explode (":", $subject);
    
    $validtypes = array ("jpg", "jpeg", "gif", "mp4", "mp3", "3gp");
    
    $coursedata = get_record ("course", "shortname", $subj[1]);
        
    if (!empty($coursedata)) 
    {
      if (is_array($attachments)) {
        make_upload_directory($coursedata->id."/course_mod_files/attachments");
    
        foreach ($attachments as $attachment) {
            $imgnameutf8 = imap_utf8($attachment['filename']);
            
            list ($imgname, $imgtype) = explode (".", $imgnameutf8);
            
            if (in_array(strtolower($imgtype), $validtypes)) {
                if (file_exists ($CFG->dataroot."/".$coursedata->id."/course_mod_files/attachments/".$imgnameutf8)) {
                    $imgnameutf8 = $imgname.rand (9999,9999999);
                    $imgnameutf8 .= ".".$imgtype;
                    $imgnameutf8 = strtolower($imgnameutf8);
                }
            
                $ifp = fopen($CFG->dataroot."/".$coursedata->id."/course_mod_files/attachments/".$imgnameutf8, "w+" ); 
                fwrite( $ifp, base64_decode($attachment['filecontant'])); 
                fclose( $ifp ); 
                
                freemail_setlog("    save attached file | file: {$imgnameutf8}");
            }
        }
        
        $mess = "true";
      }
      else
      {
          freemail_sendmail($CFG->freemail_mess_010, $email);
          $mess = "no file";
          
          freemail_setlog("    ERROR: no found attached files");
      }
    }
    else
    {
        freemail_setlog("    ERROR: course no found | course shortname: {$subj[1]}");
    }
    
    if ($mess != "true") { $mess = "ERROR: ".$mess; } 

    return $mess;
  }
  
  
  
  function freemail_uploadattachedfilestofm($attachments, $email, $subject, $body) 
  {
    global $CFG;
    
    $subj = explode (":", $subject);
    
    $validtypes = array ("jpg", "jpeg", "gif", "mp4", "mp3", "3gp");
    
    if ($CFG->freemail_usepassword == 1) {
        $userdata = get_record ("user", "username", $subj[1], "password", md5($subj[2]));
    }
    else
    {
        $userdata = get_record ("user", "username", $subj[1]);
    }
        
    if (!empty($userdata)) 
    {
      if (is_array($attachments)) {
        make_upload_directory("file_manager/users/".$userdata->id);
    
        foreach ($attachments as $attachment) {
            $imgnameutf8 = imap_utf8($attachment['filename']);
            
            list ($imgname, $imgtype) = explode (".", $imgnameutf8);
            
            if (in_array(strtolower($imgtype), $validtypes)) {
                if (file_exists ("{$CFG->dataroot}/file_manager/users/{$userdata->id}/{$imgnameutf8}")) {
                    $imgnameutf8 = $imgname.rand (9999,9999999);
                    $imgnameutf8 .= ".".$imgtype;
                    $imgnameutf8 = strtolower($imgnameutf8);
                }
           
                $ifp = fopen("{$CFG->dataroot}/file_manager/users/{$userdata->id}/{$imgnameutf8}", "w+" ); 
                fwrite( $ifp, base64_decode($attachment['filecontant'])); 
                fclose( $ifp ); 
                
                freemail_setlog("    save attached file | file: {$imgnameutf8}");
                
                $filedata               = new object;
                $filedata->owner        = $userdata->id;
                $filedata->type         = 1;
                $filedata->name         = $imgnameutf8;
                $filedata->description  = $body;
                $filedata->link         = $imgnameutf8;
                $filedata->timemodified = time();
                
                insert_record ("fmanager_link", $filedata);
            }
        }
        
        freemail_sendmail("Files are uploaded to file manager", $email);
        
        $mess = "true";
        
        freemail_setlog("    OK: file added | file: {$imgnameutf8}");
      }
      else
      { 
          freemail_sendmail($CFG->freemail_mess_010, $email);
          $mess = "no file";
          
          freemail_setlog("    ERROR: no found attached files");
      } 
    }
    else
    {
        freemail_sendmail($CFG->freemail_mess_009, $email);
        $mess = "user login or password";
        
        freemail_setlog("    ERROR: invalide user login or password | login:{$userid} password:********");
    }
    
    if ($mess != "true") { $mess = "ERROR: ".$mess; } 

    return $mess;
  }
  

  
  function freemail_createsmall($file, $realpath_foto, $width, $height, $name, $resampled = "false")
  {
        global $CFG;

        $fullpath = $realpath_foto . "/" . $name;

        $fotodata = explode (".", $file);
        $pathinfo['extension'] = end($fotodata);

        if(!isset($pathinfo['extension']))
        {
            $pathinfo['extension']="";
        }
        $pathinfo['extension']=strtolower($pathinfo['extension']);
        if($pathinfo['extension']=="jpg" or $pathinfo['extension']=="jpeg")
        {
            @$image=imagecreatefromjpeg($file);
        }elseif($pathinfo['extension']=="gif")
        {
            @$image=imagecreatefromgif($file);
        }elseif($pathinfo['extension']=="png")
        {
            @$image=imagecreatefrompng($file);
        }
        $w=getimagesize($file);
        
        if ($resampled == "true") {
            if ($w[0] > $w[1]) {
                $src_w = $src_h = $w[1];
                $src_y = 0;
                $src_x = round(($w[0] - $w[1]) / 2);
            } else {
                $src_w = $src_h = $w[0];
                $src_x = 0;
                $src_y = round(($w[1] - $w[0]) / 2);
            }

            //now create and resample

            $dst_image = imagecreatetruecolor($width, $height);

            if(!function_exists("imagecopyresampled"))
            {
                imagecopyresized($dst_image, $image, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h );
            }else
            {
                imagecopyresampled($dst_image, $image, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h );
            }

            unlink ($fullpath);
            
            imagejpeg($dst_image, $fullpath, 90);
            imagedestroy($image);
            imagedestroy($dst_image);
        }
        else
        {

            if ($w[0]>$w[1])
            {

                $height2=round(($width/$w[0])*$w[1]);

                $image2=imagecreatetruecolor($width, $height2);
                if(!function_exists("imagecopyresampled"))
                {
                    imagecopyresized($image2, $image, 0, 0, 0, 0, $width, $height2, $w[0], $w[1]);
                }else
                {
                    imagecopyresampled($image2, $image, 0, 0, 0, 0, $width, $height2, $w[0], $w[1]);
                }
                imagejpeg($image2, $fullpath, 90);
                imagedestroy($image);
                imagedestroy($image2);

            } else {

                $width2=round(($height/$w[1])*$w[0]);
        
                $image2=imagecreatetruecolor($width2, $height);
                if(!function_exists("imagecopyresampled"))
                {
                    imagecopyresized($image2, $image, 0, 0, 0, 0, $width2, $height, $w[0], $w[1]);
                }else
                {
                    imagecopyresampled($image2, $image, 0, 0, 0, 0, $width2, $height, $w[0], $w[1]);
                }
                imagejpeg($image2, $fullpath, 90);
                imagedestroy($image);
                imagedestroy($image2);

            }
        }
        
        freemail_setlog("    create small image");
  }

  function freemail_validimage ($filename, $email, $sizewh = 1)
  {
      global $CFG;
  
      $max_image_width    = 404;
      $max_image_height    = 404;
      //$max_image_size        = 8000 * 1024;
      $max_image_size        = get_max_upload_file_size($CFG->maxbytes);
      $valid_types         =  array("jpg", "jpeg", "JPG", "JPEG", "gif", "GIF", "png", "PNG");

      $ext = substr($filename,
          1 + strrpos($filename, "."));
      if (filesize($filename) > $max_image_size) {
          $error = "Image Size";
          freemail_sendmail($CFG->freemail_mess_006, $email);
      } elseif (!in_array($ext, $valid_types)) {
          $error = "Image Type";
          freemail_sendmail($CFG->freemail_mess_005, $email);
      } else {
           if ($sizewh == 1) { $size = GetImageSize($filename); } else { $size[0] = 1; $size[1] = 1; }
           
           if (($size) && ($size[0] < $max_image_width)
              && ($size[1] < $max_image_height)) {
              
              $error = "true";
              
          } else {
              $error = "Image Size";
              freemail_sendmail($CFG->freemail_mess_006, $email);
          }
      }
  
      return $error;
  }


  function freemail_sendmail ($message, $to)
  {
      require_once("../../config.php");
      require_once("../../lib/phpmailer/class.phpmailer.php");
      
      global $CFG;
      
      $mail = new PHPMailer();
      
      $datasmtphosts = get_record('config', 'name', 'smtphosts');
      $datasmtpuser = get_record('config', 'name', 'smtpuser');
      $datasmtppass = get_record('config', 'name', 'smtppass');
      $datanoreply = get_record('config', 'name', 'noreplyaddress');
      $dataemailadress = get_record('freemail', 'name', 'mail_emailadress'); 
      
      
      if (!empty($datasmtphosts->value)) {
          $mail->IsSMTP();
          $mail->Host = $datasmtphosts->value;  // specify main and backup server
          $mail->SMTPAuth = true;     // turn on SMTP authentication
          $mail->Username = $datasmtpuser->value;  // SMTP username
          $mail->Password = $datasmtppass->value; // SMTP password
      }
      else
      {
          $mail->IsMail();
      }

      //$mail->From = $datanoreply->value;
      //$mail->From = $CFG->supportemail;
      
      if (!empty($dataemailadress->value)) 
        $mail->From     = $dataemailadress->value;
      $mail->FromName = "FreeMail Robot";
      $mail->AddAddress($to, "");

      $mail->Subject = $CFG->freemail_mess_subject;
      $mail->Body    = $CFG->freemail_mess_header.$message.$CFG->freemail_mess_footer;

      if(!$mail->Send())
      {
         //print_r ($mail);
         echo "<br />Mailer Error: " . $mail->ErrorInfo . "<br />";
      }

  }
  
  
function freemail_addItemToAlbum($userId,$fileName, $itemName, $title, $summary,$description, $mimeType, $albumId)
{
    require_once ("../../gallery2/embed.php");
    
    $embed = new GalleryEmbed;

    //$embed->init();
    $embed->init(array('activeUserId' => 6 )); 

    //passing in an album id lets make sure it exists
    list ($ret, $album) = GalleryCoreApi::loadEntitiesById($albumId);
    if ($ret && !($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
        $msg = $ret->getAsText();
    }
    //write lock on album so we can add item
    list ($ret, $lockId) = GalleryCoreApi::acquireReadLock($albumId);
    //check for error
    if ($ret) {
        $msg = $ret->getAsText();
    }
    //using gallery core api to add the item
    list($ret,$item) = GalleryCoreApi::addItemToAlbum($fileName,$itemName, $title, $summary,$description, $mimeType, $albumId);
    
    //SetPermission
    $ret = GalleryCoreApi::addUserPermission($item->getId(), $userId->getID(),'core.all', false);
    
    if ($ret) {
        $msg = $ret->getAsText();
    }
    //if item name exists add _ to it
    if ($ret && $ret->getErrorCode() & ERROR_COLLISION) {
        $itemName .= '_';
    }
    //save and release lock
    $ret = $album->save();

    list ($ret, $lockId) = GalleryCoreApi::acquireWriteLock($item->getId());
    if ($ret) {
        $msg = $ret->getAsText();
    }

    /* Set owner for item and permissions I just want users to comment and simple edit do whatever you want.. */
    $item->setOwnerId($userId->getID());
    if ($ret) {
        $msg = $ret->getAsText();
    }

    $ret = $item->save();
    if ($ret) {
        $msg = $ret->getAsText();
    }
    //i want to return the item id to do other things with it
    $itemId = $item->getId();
    
    $embed->done();
 
return $itemId;
} 



function freemail_setlog($text)
{
    global $CFG;
     if (!file_exists($CFG->dataroot."/freemail_logs")) { 
                if (!make_upload_directory("freemail_logs")) {
                    $error = "The site administrator needs to fix the file permissions of the moodle dataroot - so that the freemail script lib_.php  can create a freemail_log forlder to store logs";
                }
     }
    $fp = fopen($CFG->dataroot."/freemail_logs/freemail_log.php", "a+");
    fwrite($fp, $text."\r\n");
    fclose($fp);
}

?>