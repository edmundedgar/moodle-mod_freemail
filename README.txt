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

Description:
Students can add content to their moodle course by sending email messages with commands to the server.
Available features:
- Add or change images in Personal Profile
- Add images and text to a Moodle Blog
- Upload photos and comments into Gallery 2.0 (Gallery is not part of Moodle but can be partially integrated using the Moodle & Gallery 2 Integration on this page.
- Uploading files to course folder and FileManager block

How to Use:
- Send email with the appropriate command entered in the subject line.
- Add text and attach an image to the email.
- Send the email to the designated POP server.
(samples in mail_samples.txt file)

Installation:
    1. upgrade your Moodle installation to 1.8 or later version
    2. download freemail.zip to your moodle/mod directory and unzip it there
    3. go to http://yoursite.com/admin - all necessary tables will be created
    4. set CHMOD 777 to log.php file. this file will contain FreeMail logs.
       If you want to check logs, you should login to site as admin user and go to http://yoursite.com/mod/freemail/log.php
    5. go to module settings page in admin area and set mail settings.
       If settings are ok, go to http://yoursite.com/mod/freemail/check_mail.php and you will see cheking information. If mail data is wrong or the script has some problem, you will see error message.
       
       
    If the script has problems with mail, then the script will send mail to moodle admins. If you didn't recieve error messages you can select:
    Email activated - "This email address is disabled" in your profile page.

