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

define('CLI_SCRIPT', true);
require_once "../../config.php";

$statuses = freemail_read_mail();

$verbose = true;

function freemail_verbose_output($msg) {

    $verbose = true; 
    if ($verbose) {
        print $msg."\n";
    }

}

function freemail_read_mail() {

    global $CFG;

    $statuses = array(
        'errors' => array(),
        'messages' => array(
            'successes' => array(),
            'failures' => array()
        )
    );

    // Class for handling an imap message connection, and fetching and parsing emails one by one.
    require_once 'freemail_imap_message_handler.php'; 

    // The following are base classes, but with static methods to load inherited classes.

    // We'll need an email_processor to parse our email - for example if it looks like a Second Life snapshot, we'll want one of those.
    require_once 'freemail_email_processor.php'; 

    // It will then need to find something to do with the email, like import it into the blog.
    require_once 'freemail_moodle_importer.php';

    $email_processors = freemail_email_processor::available_email_processors();
    if (!count($email_processors)) {
        $statuses['errors']["-1"] = "No email processors available, aborting.";
        return $statuses; 
    }

    $handler = new freemail_imap_message_handler();
    if (!$mailbox = $handler->connect($CFG->freemail_mail_box_settings, $CFG->freemail_mail_user_name, $CFG->freemail_mail_user_pass)) {
        $statuses['errors']["-2"] = "Connection failed. Could not fetch email.";
        return $statuses; 
    }

    if (!$msgcount = $handler->count()) {
        $handler->close();
        $statuses['result']["1"] = "No messages.";
        return $statuses; 
    }

    freemail_verbose_output("Got $msgcount messages.");

    if ($msgcount > 0)  {  

        if ($msgcount > $CFG->freemail_mail_maxcheck) {
            $msgcount = $CFG->freemail_mail_maxcheck;
        }

        for ($mid = 1; $mid <= $msgcount; $mid++) {

            freemail_verbose_output("Considering loading message with ID :$mid:");

            // Load the header first so that we can check what we need to know before downloading the rest. 
            if (!$handler->load_header($mid)) {
                $statuses['messages']['failures'][$mid] = array('error'=>'Could not load header.');
                continue;
            }

            $size_in_bytes = $handler->get_size_in_bytes();
            if ($size_in_bytes > $CFG->freemail_mail_maxsize) {
                $usermailcontent[] = array('email' => $msg->header[$mid]['from'][0], 'subject' => $msg->header[$mid]['subject'], 'error' => 'bigmailsize');
                continue;
            }
            freemail_verbose_output("Message size :$size_in_bytes: small enough - continuing.");

            // TODO: Separate load_header and load_body so we don't load the whole thing if it's too big.
            if (!$handler->load($mid)) {
                $statuses['messages']['failures'][$mid] = array('error'=>'Could not load.');
                continue;
            }

            $htmlmsg = $handler->get_html_message();;
            $plainmsg = $handler->get_plain_message();; 
            $charset = $handler->get_charset();
            $attachments = $handler->get_attachments();
            $subject = $handler->get_subject();
            $fromaddress = $handler->get_from_address();

            foreach($email_processors as $processor) {

                $processor->set_subject($subject);
                $processor->set_from_address($fromaddress);
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
                    freemail_verbose_output("Could not prepare email.");
                    continue;
                }

                // Couldn't find anyone to process it, skip.
                if (!$processor->load_importer()) {
                    freemail_verbose_output("Could not load importer.");
                    continue;
                }

                // Processor can't handle this kind of email.
                if (!$processor->is_email_processable()) {
                    freemail_verbose_output("Processor cannot handle this email. Will let others try.");
                    continue;
                }

                // TODO: Get this working.
                // Ideally we should mark messages as flagged before we start to import them
                // ...and skip over messages that are already flagged.
                // This should prevent multiple processes running at the same time from tripping over each other and importing the same message multiple times.
                // Mark the message as flagged 
                // $handler->mark_flagged($mid);

                $ok = $processor->import();

                $processor->notify_user();

                break;

            }

            // skipping subcommand stuff
            // list($subcomm, $messcomm) = freemail_getcommands($msg->header[$mid]['subject'], $messagebody['message'], $commands);

            $handler->delete();
            //imap_delete($mailbox, $mid);

            //print "skipping user mail content";

        }

    }

    $handler->expunge();
    $handler->close();

}

exit;
