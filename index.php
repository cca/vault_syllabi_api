<?php
// Guzzle v3 — our PHP is too old to do latest version
// https://guzzle3.readthedocs.org/docs.html
require 'vendor/autoload.php';
use Guzzle\Http\Client;

// constants
define('VAULT_URL', 'https://vault.cca.edu');
define('SEARCH_API', '/api/search');

// disable SSL validation since our cert causes an error
$client = new Client(VAULT_URL, Array(
    'ssl.certificate_authority' => false
));
// OAuth token for this specific app, client credentials grant from "eric1"
// that account has privileges to see all items in Syllabus Collection
$token = getenv("SYLLABI_API_TOKEN");
$client->setDefaultOption('headers/X-Authorization', 'access_token=' . $token);

// construct query string for EQUELLA search API
// parse_str parses a query string into variables inside $arr
if (isset($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $options);
} else {
    $options = array();
}
$options['info'] = 'metadata,basic,attachment';
// search only the Syllabus Collection (this is its UUID)
$options['collections'] = '9ec74523-e018-4e01-ab4e-be4dd06cdd68';

// translate "semester" parameter into XML where query
if (isset($options['semester'])) {
    $semester = $options['semester'];
    unset($options['semester']);
    $options['where'] = "/xml/local/courseInfo/semester = '" . $semester . "'";
}
// same for "section" parameter
if (isset($options['section'])) {
    $section = $options['section'];
    unset($options['section']);
    $section_where_string = "/xml/local/courseInfo/section = '" . $section . "'";
    // if we already have a "where" clause, append to it, otherwise initialize it
    $options['where'] = (isset($options['where']) ? $options['where'] . ' AND ' . $section_where_string : $section_where_string);
}

// ignore "debug" which will return EQUELLA API response
if (isset($options['debug'])) {
    $debug = true;
    unset($options['debug']);
} else {
    $debug = false;
}

$query_string = http_build_query($options);

// request URL
$request = $client->get(SEARCH_API . '?' . $query_string);

// get JSON from EQUELLA API
$response = $request->send();
$data = $response->json();
$output = Array(
    // useful debugging information
    'vault_api_url' => $request->getUrl(),
    'results' => Array()
);

// iterate over item metadata XML, parsing out
foreach ($data['results'] as $item) {
    // basic info contained in API response
    $output_item = Array(
        'name' => $item['name'],
        'link' => $item['links']['view'],
        'attachments' => $item['attachments']
    );

    // grab information from metadata
    $metadata = simplexml_load_string($item['metadata']);
    // remove noisy local/courseInfo/courseinfo node, taxonomy string
    unset($metadata->local->courseInfo->courseinfo);
    // merge two data sources & append to our ouput
    // cast $metadata SimpleXMLElement to array
    $output['results'][] = array_merge($output_item, (array) $metadata->local->courseInfo);
}

// contruct response
// headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
// enable CORS
header('Access-Control-Allow-Origin: *');
// no reason to broadcast our PHP version
header_remove('X-Powered-By');

if ($debug) {
    // send the raw EQUELLA response
    echo $response->getBody();
} else {
    // our manicured subset of EQUELLA's API response with course metadata
    echo json_encode($output);
}
