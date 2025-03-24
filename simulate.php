<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set response headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Ensure the request is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Invalid request method"]);
    exit;
}

// Read input JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input data
if (!isset($data['algorithm']) || !isset($data['requests']) || !isset($data['head'])) {
    echo json_encode(["error" => "Invalid input data"]);
    exit;
}

$algorithm = $data['algorithm'];
$requests = array_map('intval', $data['requests']); // Convert requests to integers
$head = intval($data['head']); // Convert head to integer
$diskSize = $data['diskSize'] ?? 200; // Default disk size

//Throughput Calculation
function calculateThroughput($requests, $seekTime) {
    return count($requests) / $seekTime; // Requests per unit of seek time
}

// Disk Scheduling Algorithms

function fcfs($requests, $head) {
    $seekTime = 0;
    $sequence = array_merge([$head], $requests);

    for ($i = 1; $i < count($sequence); $i++) {
        $seekTime += abs($sequence[$i] - $sequence[$i - 1]);
    }

    return ["sequence" => $sequence, "seekTime" => $seekTime,
    "avgSeekTime" => $seekTime / count($requests),
    "throughput" => calculateThroughput($requests, $seekTime)];
}

function sstf($requests, $head) {
    $seekTime = 0;
    $sequence = [$head];
    $pending = $requests;

    while (!empty($pending)) {
        usort($pending, fn($a, $b) => abs($a - $head) - abs($b - $head));
        $next = array_shift($pending);
        $seekTime += abs($head - $next);
        $head = $next;
        $sequence[] = $head;
    }

    return ["sequence" => $sequence, "seekTime" => $seekTime, "avgSeekTime" => $seekTime / count($requests),"throughput" => calculateThroughput($requests, $seekTime)];
}


// Select Algorithm
$result = [];
switch (strtolower($algorithm)) {
    case "fcfs":
        $result = fcfs($requests, $head);
        break;
    case "sstf":
        $result = sstf($requests, $head);
        break;
    case "scan":
        $result = scan($requests, $head, $diskSize);
        break;
    case "cscan":
        $result = cscan($requests, $head, $diskSize);
        break;
    case "look":
        $result = look($requests, $head);
        break;
    case "clook":
        $result = clook($requests, $head);
        break;
    default:
        $result = ["error" => "Invalid algorithm"];
        break;
}

// Return result as JSON
echo json_encode($result);
?>
