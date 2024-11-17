<?php
header("Access-Control-Allow-Origin: *");

$company = $_POST['company'];

require_once '../config.php';

$core = 'jobs';

// Ensure the company name is provided
if (empty($company)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Company name is required', 'code' => 400]);
    exit;
}

// Step 1: Get the count of jobs for the given company
$countCommand = '/select';
$countQS = '?q=' . rawurlencode('hiringOrganization.name:"' . $company . '"') . '&wt=json&rows=0';

$countUrl = 'http://' . $server . '/solr/' . $core . $countCommand . $countQS;

try {
    $countJson = @file_get_contents($countUrl);
    if ($countJson === FALSE) {
        list($version, $status, $msg) = explode(' ', $http_response_header[0], 3);
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Failed to query Solr for count, HTTP status: ' . $status, $status);
    }

    $countResponse = json_decode($countJson, true);
    $jobCount = $countResponse['response']['numFound'];

    if ($jobCount === 0) {
        echo json_encode(['message' => 'No jobs found for the specified company', 'jobCount' => 0]);
        exit;
    }

    // Step 2: Delete the jobs

    $qs = '?commit=true&wt=json';
    $deleteUrl = 'http://' . $server . '/solr/' . $core . $deleteCommand . $qs;

    $deleteData = json_encode(['delete' => ['query' => 'hiringOrganization.name:"' . $company . '"']]);

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $deleteData
        )
    );

    $context = stream_context_create($options);
    $deleteJson = @file_get_contents($deleteUrl, false, $context);

    if ($deleteJson === FALSE) {
        list($version, $status, $msg) = explode(' ', $http_response_header[0], 3);
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Failed to delete jobs from Solr, HTTP status: ' . $status, $status);
    }

    echo json_encode(['message' => 'Jobs deleted successfully', 'jobsDeleted' => $jobCount]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
