<?php
/*
 * Slack Randomiser Script
 * Author: marcin.calka@gmail.com
 */
session_start();

require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ( isset($_REQUEST['token']) && $_REQUEST['token'] !== getenv('SLACK_TOKEN') )  {
    exit('Invalid token.');
}

$cookie_name  = "standup-rand";
$names_value = filter_input(INPUT_GET, 'names', FILTER_SANITIZE_STRING);
// $channel_id = filter_input(INPUT_GET, 'channel_id', FILTER_SANITIZE_STRING);
// $user_name = filter_input(INPUT_GET, 'user_name', FILTER_SANITIZE_STRING);
// $command = filter_input(INPUT_GET, 'command', FILTER_SANITIZE_STRING);

$emoji_support = filter_input(INPUT_GET, 'emojis', FILTER_SANITIZE_NUMBER_INT);
$html_format = filter_input(INPUT_GET, 'html', FILTER_SANITIZE_NUMBER_INT) ?: 1;

$names_param = getopt(null,["names::"]);

if ( !isset($_COOKIE[$cookie_name]) && empty($names_value) && empty($_REQUEST['text']) && empty($names_param) ) {
    exit('For slack use add comma separated names as a parameter, commandline --names= parameter, for WWW simply add ?names= url param with comma separated names');
}
if ( !empty($_REQUEST['text']) ) {
    $stringArray = explode(',', $_REQUEST['text']);
    $html_format = false;
}
if ( !empty($names_param) ) {
    $stringArray = $names_param;
    $html_format = false;
}

if ( $html_format ) {
    header("content-type: text/html; charset=UTF-8");
    echo '<meta charset="UTF-8">';

    if (!empty($names_value)) {
        $names_value = explode(',', $names_value);
        setcookie( $cookie_name, serialize($names_value), time() + ( 86400 * 30 ), "/" );
    }
    $stringArray = isset($_COOKIE[$cookie_name]) ? unserialize($_COOKIE[$cookie_name]) : range('A', 'Z');
}

shuffle($stringArray);

$id = 600;
foreach ($stringArray as $string) {
    $randomEmojis[] = '&#x1F' . ++$id;
}
shuffle($randomEmojis);

$output = '';
foreach ($stringArray as $id => $string) {
	if ($emoji_support) {
		$output .=  '* ' . $randomEmojis[ $id ] . ' ' . $stringArray[ $id ] . " -\n";
	} else {
		$output .= '* ' . $stringArray[ $id ] . " -\n";
	}
}

$output = "```\n" . $output . '```';

if ($html_format) {
    echo nl2br($output);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        "response_type" => "in_channel",
        "text" => "Good morning, enjoy your stand-up",
        "attachments" => [
            ["text" => $output]
        ]
    ]);
}
