#!/usr/bin/env php
<?php
/**
 * This will run a bash script and if the exit code is not successful (0) then it will send an email
 */

//cd into this directory
chdir(__DIR__);

if (count($argv) == 1) {
    echo showUsage();
    die;
}

$script_to_run = $argv[1];

$extra_args='';

if (count($argv) > 2) {
    foreach ($argv as $k => $v) {
        if ($k > 1) {
            $extra_args .= " $v ";
        }
    }
}


$output = array();

$exit_code = 0;

$grep_already_running = "ps waux | grep '[.]/$script_to_run'";
var_dump($grep_already_running);

$ps_waux = shell_exec($grep_already_running);
var_dump($ps_waux);

if (!empty($ps_waux)) {
    $output = 'FOUND ALREADY RUNNING INSTANCE - ABORTING';
    $output .= "\n$ps_waux";
    $exit_code = 'ABORTED';
} else {
    exec('/usr/bin/php  ' . $script_to_run .' '. $extra_args . '2>&1 ', $output, $exit_code);
    $output = implode("\n", $output);
    $exit_code = intval($exit_code);
    var_dump('/usr/bin/php  ' . $script_to_run .' '. $extra_args . '2>&1 ', $output, $exit_code);
}

echo "\nOutput:\n-----------------------------------\n";
echo $output;

echo "\nExit Code:\n-----------------------------------\n";
var_dump($exit_code);

file_put_contents($script_to_run . '.log', $output, FILE_APPEND);

$pattern = '%<h[0-9] style="color: red;">(.+?)</h[0-9]>|FATAL|Exception %';
$matches = array();
if ((0 !== $exit_code) || preg_match_all($pattern, $output, $matches)) {
    $msg = "ERROR running Cron $script_to_run \n\n ";
    if (isset($matches[0])) {
        $msg .= "Error messages detected:\n\n";
        foreach ($matches[1] as $m) {
            $msg .= "\n * $m \n";
        }
    } else {
        $msg .= '<h3>Error Matches:</h3><pre>' . var_export($matches, true) . '</pre>';
    }
    $output = "<pre>$output</pre>";
    $msg .= "\n\n Exit Code: $exit_code <br><br> \n\n $output";
    $subject = "Error Running $script_to_run, Exit Code: $exit_code";
    echo "\nEmailing Error Report";
    send_email('test@test.com', $msg, $subject);
    echo "\nDone";
} else {
    echo "\nNo email sent";
}

function showUsage() {
    return <<<USAGE
Usage:  php -f cronRunner.php [options]

USAGE;
}


function send_email($to_email, $email, $subject)
{
    $our_email_name = 'Cron Errors';
    $our_email = 'NO_REPLY@'.$_SERVER['HOSTNAME'];
    $email = '<font face="arial">' . $email . '</font>';

    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= "From: $our_email_name<$our_email>" . "\r\n";
    $headers .= "Reply-To: $our_email" . "\n";
    $headers .= "Return-Path: " . $our_email . "\r\n"; // these two to set reply address
    $headers .= "Message-ID: <" . time() . "@" . $_SERVER['HOSTNAME'] . ">" . "\r\n";
    $headers .= "X-Mailer: cronRunner" . "\r\n"; // These two to help avoid spam-filters
    $headers .= "Date: " . date("r") . "\r\n";
    mail($to_email, $subject, $email, $headers, '-f ' . $our_email) or die('<h3 style="color: red;">Mail Failed</h3>');
}

echo "\n\n";
