<?php
// Be sure to fill in your public Dropbox ID!
// You can get this from the UpShot preferences pane.
define(DROPBOX_URL, "https://dl.dropboxusercontent.com/u/[ Your Public Dropbox ID ]/Screenshots/");
define("ANALYTICS", true);
define("ANALYTICS_JSON", "dashboard/analytics.json");

// Treat all warnings as exceptions.
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

// Check to make sure the request is actually for an image file.
$file_extension = strtolower(substr($_GET["file"], -4));
if (($file_extension != ".png" && $file_extension != ".jpg") || strlen($_GET["file"]) != 8) {
    http_response_code(400);
    die("Not a valid image.");
}

// Let's get that screenshot!
$url = DROPBOX_URL.$_GET["file"];

if (ANALYTICS) {
    $analytics_json = json_decode(file_get_contents(ANALYTICS_JSON), true);
    if (!isset($analytics_json[$_GET["file"]])) {
        $analytics_json[$_GET["file"]] = 0;
    }
    $analytics_json[$_GET["file"]]++;
    file_put_contents(ANALYTICS_JSON, json_encode($analytics_json));
}

try {
    return_image($url);
} catch (Exception $e) {
    // Failed to find the image.  Try again.
    sleep(3);
    try {
        return_image($url);
    } catch (Exception $e) {
        http_response_code(404);
        die("Image not found.");
    }
}

function return_image($url) {
    $imginfo = getimagesize($url);
    header("Content-type: ".$imginfo['mime']);
    readfile($url);
}
