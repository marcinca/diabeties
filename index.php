<?php
/*
 * Slack Randomiser Script
 * Author: marcin.calka@gmail.com
 */
$stringArray = ['Ishani','Tom','Tony','Marcin','Nick','Andy','Andrew','Chris','Ian'];

$cookie_name  = "standup-rand";
$names_value = filter_input(INPUT_GET, 'names', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
$emoji_support = filter_input(INPUT_GET, 'emojis', FILTER_SANITIZE_NUMBER_INT);
$html_format = filter_input(INPUT_GET, 'html', FILTER_SANITIZE_NUMBER_INT) ?: 1;

if ($html_format) {
    header("content-type: text/html; charset=UTF-8");
    echo '<meta charset="UTF-8">';
}

if ($names_value && is_array($names_value)) {
	setcookie( $cookie_name, serialize($names_value), time() + ( 86400 * 30 ), "/" );
}

$stringArray = isset($_COOKIE[$cookie_name]) ? unserialize($_COOKIE[$cookie_name]) : range('A', 'Z');
shuffle($stringArray);

$id = 600;
foreach ($stringArray as $string) {
    $randomEmojis[] = '&#x1F' . ++$id;
}
shuffle($randomEmojis);

foreach ($stringArray as $id => $string) {
	if ($emoji_support) {
		$output =  '* ' . $randomEmojis[ $id ] . ' ' . $stringArray[ $id ] . " -\n";
	} else {
		$output = '* ' . $stringArray[ $id ] . " -\n";
	}

	if ($html_format) {
		$output = nl2br($output);
	}

	echo $output;
}


