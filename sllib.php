<?php
/**
    * Sloodle sllib for the freemail mod (for Sloodle 0.4).
    * Various functions to extract info from sl postcards
    *
    * @package sloodle
    * @copyright Copyright (c) 2009 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Paul G. Preibisch - aka Fire Centaur in Second Life
    */
    
  /**
  * This function is used to extact text from the sl postcard email such as sim_name and agent_name
  * Example:
  * @simname= getSlInfo("sim_name",$searchText);
  * $username = getSlInfo("username",$searchText);
  * @param string $findMe is the thing to search for - such as sim_name
  * @param string $searchText is the text to be searched - such as the message body of the email
  * @return string Can return extracted content from the sl postcard - such as sim_name and agent_name 
  *  
  */
  function getSlInfo($findMe,$searchText){
    //now found the beginning of the value ex: sim_name=" so we have to account for the characters =" which is +2 characters
    $findMeStartIndex = strpos($searchText,$findMe) + strlen($findMe) +2;
    $findMeEndIndex = strpos($searchText,'"',$findMeStartIndex);
    $findMeLength=$findMeEndIndex-$findMeStartIndex;
    $findMeValue= substr($searchText,$findMeStartIndex,$findMeLength);
    return $findMeValue; 
    }
  /**
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
function getSlCoords($findMe,$searchText){
    //now found the beginning of the value ex: sim_name=" so we have to account for the characters =" which is +2 characters
    $findMeStartIndex = strpos($searchText,$findMe) + strlen($findMe) +1;
    
    $findMeEndIndex =   strpos($searchText,"\n",$findMeStartIndex);
    $findMeLength=$findMeEndIndex-$findMeStartIndex-1;
    $findMeValue= substr($searchText,$findMeStartIndex,$findMeLength);
    return $findMeValue; 
}

?>
