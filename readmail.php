<?php
/**
* Freemail v1.1 with SL patch
*
* @package freemail
* @copyright Copyright (c) 2008 Serafim Panov
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
* @author Serafim Panov
* @contributor: Paul G. Preibisch - aka Fire centaur in Second Life
* @contributor: Edmund Edgar
*
*/

// 
require_once 'freemail_imap_message_handler.php'; 

// The following are base classes, but with static methods to load inherited classes.

// We'll need an email_processor to parse our email - for example if it looks like a Second Life snapshot, we'll want one of those.
require_once 'freemail_email_processor.php'; 

// It will then need to find something to do with the email, like import it into the blog.
require_once 'freemail_moodle_importer.php';

$email_processors = freemail_email_processor::available_email_processors();
if (!count($email_processors)) {
    print "No email processoors available, aborting.";
    exit;
}

$handler = new freemail_imap_message_handler();
if (!$mailbox = $handler->connect($CFG->freemail_mail_box_settings, $CFG->freemail_mail_user_name, $CFG->freemail_mail_user_pass)) {
    print "connection failed";
    exit;
}

if (!$msgcount = $handler->count()) {
    print "no messages";
    $handler->close();
    exit;
}

if ($msgcount > 0)  {  

    if ($msgcount > $CFG->freemail_mail_maxcheck) {
        $msgcount = $CFG->freemail_mail_maxcheck;
    }

    for ($mid = 1; $mid <= $msgcount; $mid++) {

        if (!$handler->load($mid)) {
            continue;
        }

        $htmlmsg = $handler->get_html_message();;
        $plainmsg = $handler->get_plain_message();; 
        $charset = $handler->get_charset();
        $attachments = $handler->get_attachments();
        $subject = $handler->get_subject();

        if ($handler->get_size_in_bytes() > $CFG->freemail_mail_maxsize) {
            $usermailcontent[] = array('email' => $msg->header[$mid]['from'][0], 'subject' => $msg->header[$mid]['subject'], 'error' => 'bigmailsize');
            continue;
        }

        foreach($email_processors as $processor) {

            $processor->set_subject($subject);
            $processor->set_plain_body($plainmsg);
            $processor->set_html_body($htmlmsg);
            $processor->set_charset($charset);

            // This isn't used by the sloodle processor, which this was originally developed for.
            // It's added here for other processors that might want to use it.
            // But it hasn't been tested, and may be broken.
            foreach($attachments as $attachment_filename => $attachment_body) {
                $processor->add_attachment($attachment_filename, $attachment_body);
            }

            // Couldn't make sense of it, skip
            if (!$processor->prepare()) {
                continue;
            }

            // Couldn't find anyone to process it, skip.
            if (!$processor->load_importer()) {
                continue;
            }

            // Processor can't handle this kind of email.
            if (!$processor->is_email_processable()) {
                continue;
            }

            $ok = $processor->import();
            break;

        }

        // skipping subcommand stuff
        // list($subcomm, $messcomm) = freemail_getcommands($msg->header[$mid]['subject'], $messagebody['message'], $commands);


        print "Skipping delete";
        $handler->delete();
        //imap_delete($mailbox, $mid);

        print "skipping user mail content";

    }

}

$handler->expunge();
$handler->close();

exit;
