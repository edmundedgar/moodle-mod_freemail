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

// Class for handling an imap message connection, and fetching and parsing emails one by one.
require_once 'freemail_imap_message_handler.php'; 

// The following are base classes, but with static methods to load inherited classes.

// We'll need an email_processor to parse our email - for example if it looks like a Second Life snapshot, we'll want one of those.
require_once 'freemail_email_processor.php'; 

// It will then need to find something to do with the email, like import it into the blog.
require_once 'freemail_moodle_importer.php';



$verbose = in_array("-v", $argv);
$daemon = in_array("-d", $argv);

if ($daemon) {

    while ($handler = freemail_read_mail($verbose, $daemon, $handler)) {
        freemail_verbose_output($verbose, "Handling run done, sleeping");
        sleep(2);
    }

} else {

    freemail_read_mail($verbose, $daemon, null);

}

function freemail_verbose_output($verbose, $msg) {

    if ($verbose) {
        print $msg."\n";
    }

}

function freemail_read_mail($verbose, $daemon, $handler = null) {

    global $CFG;

    $statuses = array(
        'result' => array(),
        'errors' => array(),
        'messages' => array(
        )
    );

    $giveup = false;
    $msgcount = 0;

    $email_processors = freemail_email_processor::available_email_processors();
    if (!count($email_processors)) {
        freemail_verbose_output($verbose, "No email processors available, aborting.");
        $statuses['errors']["-1"] = "No email processors available, aborting.";
        $giveup = true;
    }

    // In daemon mode, the handler is kept alive between calls to this function with its connection open.
    freemail_verbose_output($verbose, "Trying to get connection");
    $handler = !is_null($handler) ? $handler : new freemail_imap_message_handler();

    if (!$giveup) {
        freemail_verbose_output($verbose, "Connecting");
        if (!$handler->connect($CFG->freemail_mail_box_settings, $CFG->freemail_mail_user_name, $CFG->freemail_mail_user_pass)) {
            $statuses['errors']["-2"] = "Connection failed. Could not fetch email.";
            $giveup = true;
        }
    }

    if (!$giveup) {
        if (!$msgcount = $handler->count()) {
            // In daemon mode, keep the connection open, and return the handler object so we can reuse it.
            if ($daemon) {
                return $handler;
            }
            $handler->close();
            $statuses['result']["1"] = "No messages.";
            $giveup = true;
        }
    }

    if (!$giveup) {

        freemail_verbose_output($verbose, "Got $msgcount messages.");

        if ($msgcount > 0)  {  

            if ($msgcount > $CFG->freemail_mail_maxcheck) {
                $msgcount = $CFG->freemail_mail_maxcheck;
            }

            for ($mid = 1; $mid <= $msgcount; $mid++) {

                $statuses['messages'] = array();

                freemail_verbose_output($verbose, "Considering loading message with ID :$mid:");

                // Load the header first so that we can check what we need to know before downloading the rest. 
                if (!$handler->load_header($mid)) {
                    $statuses['messages'][] = array( 
                        'errors' => array('-101' => 'Could not load header') 
                    );
                    continue;
                }

                $subject = $handler->get_subject();
                $fromaddress = $handler->get_from_address();

                $info = array(
                    'subject' => $subject,
                    'fromaddress' => $fromaddress
                );

                $size_in_bytes = $handler->get_size_in_bytes();
                if ($size_in_bytes > $CFG->freemail_mail_maxsize) {
                    $statuses['messages'][] = array( 
                        'errors' => array('-101' => 'Could not load header.'),
                        'info' => $info
                    );
                    continue;
                }
                freemail_verbose_output($verbose, "Message size :$size_in_bytes: small enough - continuing.");

                // TODO: Separate load_header and load_body so we don't load the whole thing if it's too big.
                if (!$handler->load($mid)) {
                    $statuses['messages'][] = array( 
                        'errors' => array('-102' => 'Could not load.'),
                        'info' => $info
                    );

                    continue;
                }
                freemail_verbose_output($verbose, "Loaded message...");

                $htmlmsg = $handler->get_html_message();;
                $plainmsg = $handler->get_plain_message();; 
                $charset = $handler->get_charset();
                $attachments = $handler->get_attachments();

                foreach($email_processors as $processor) {

                    freemail_verbose_output($verbose, "Trying processor...");

                    $processor->set_subject($subject);
                    $processor->set_from_address($fromaddress);
                    $processor->set_plain_body($plainmsg);
                    $processor->set_html_body($htmlmsg);
                    $processor->set_charset($charset);

                    foreach($attachments as $attachment_filename => $attachment_body) {
                        $processor->add_attachment($attachment_filename, $attachment_body);
                    }

                    freemail_verbose_output($verbose, "Preparing message...");
                    // Couldn't make sense of it, skip
                    if (!$processor->prepare()) {

                        $statuses['messages'][] = array( 
                            'errors' => array('-103' => 'Could not prepare email.') ,
                            'info' => $info
                        );
                        freemail_verbose_output($verbose, "Could not prepare email.");
                        continue;
                    }

                    // Couldn't find anyone to process it, skip.
                    if (!$processor->load_importer()) {
                        freemail_verbose_output($verbose, "Could not load importer.");

                        $statuses['messages'][] = array( 
                            'errors' => array('-104' => 'Could not load importer.'),
                            'info' => $info
                        );
                        continue;
                    }

                    // Processor can't handle this kind of email.
                    if (!$processor->is_email_processable()) {
                        freemail_verbose_output($verbose, "Processor cannot handle this email. Will let others try.");

                        $statuses['messages'][] = array( 
                            'errors' => array('-104' => 'Could not load importer.'),
                            'info' => $info
                        );
                        continue;
                    }

                    // TODO: Get this working.
                    // Ideally we should mark messages as flagged before we start to import them
                    // ...and skip over messages that are already flagged.
                    // This should prevent multiple processes running at the same time from tripping over each other and importing the same message multiple times.
                    // Mark the message as flagged 
                    // $handler->mark_flagged($mid);

                    freemail_verbose_output($verbose, "Importing...");
                    if (!$processor->import()) {
                        freemail_verbose_output($verbose, "Importing failed.");
                        $statuses['messages'][] = array( 
                            'errors' => array('-105' => 'Importing failed.'),
                            'info' => $info
                        );
                        continue;
                    }

                    freemail_verbose_output($verbose, "Notifying user...");
                    if (!$processor->notify_user()) {
                        $statuses['messages'][] = array( 
                            'success' => array('107' => 'Imported, but could not notify user..'),
                            'errors' => array('-106' => 'Imported, but could not notify user..'),
                            'info' => $info
                        );
                        break;
                    }

                    freemail_verbose_output($verbose, "Handling of this email complete.");
                    $statuses['messages'][] = array( 
                        'success' => array('107' => 'Imported, but could not notify user..'),
                        'errors' => array('-106' => 'Imported, but could not notify user..'),
                        'info' => $info
                    );

                    break;

                }

                // skipping subcommand stuff
                // list($subcomm, $messcomm) = freemail_getcommands($msg->header[$mid]['subject'], $messagebody['message'], $commands);

                freemail_verbose_output($verbose, "Deleting message $mid.");
                if (!$handler->delete($mid)) {
                    freemail_verbose_output($verbose, "Deletion of message $mid failed.");
                }
                //imap_delete($mailbox, $mid);

                //print "skipping user mail content";

            }

        }

        $handler->expunge();

    }

$CFG->freemail_mail_emailadress = 'edmund.edgar@gmail.com';
    if ($CFG->freemail_mail_emailadress) {
        // in daemon mode, only send a report if some messages were actually processed.
        if (!$daemon || $msgcount) { 
            $subject = "Email processing report";
            $body = freemail_status_text($statuses);
            mail($CFG->freemail_mail_emailadress, $subject, $body); 
        }
    }

    // In daemon mode, keep the handler with its connection alive and return it so it can be used again next time.
    if ($daemon) {
        return $handler;
    }

    $handler->close();

    return true;

}


function freemail_status_text($statuses) {

    $str = '';

    if (count($statuses['errors'])) {
        $str .= 'Fetching email failed:'."\n";
        $str .= implode("\n", $statuses['errors'])."\n";
    }

    $str .= count($statuses['messages']).' messages processed.';

    return $str;

}
exit;
