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

// Function to check if any input exceeds 200
function checkInputLimit($requests, $head) {
    $maxLimit = 200;
    if ($head > $maxLimit) {
        return ["error" => "Head position exceeds maximum limit of 200. Please enter a value less than 200."];
    }
    foreach ($requests as $request) {
        if ($request > $maxLimit) {
            return ["error" => "One or more requests exceed maximum limit of 200. Please enter values less than 200."];
        }
    }
    return null; // Return null if all inputs are within limit
}

// Validate input data
if (!isset($data['algorithm']) || !isset($data['requests']) || !isset($data['head'])) {
    echo json_encode(["error" => "Invalid input data"]);
    exit;
}

$algorithm = $data['algorithm'];
$requests = array_map('intval', $data['requests']); // Convert requests to integers
$head = intval($data['head']); // Convert head to integer

// Check input limits before proceeding
$limitCheck = checkInputLimit($requests, $head);
if ($limitCheck !== null) {
    echo json_encode($limitCheck);
    exit;
}

// Only set direction if provided and relevant
$direction = isset($data['direction']) && in_array($algorithm, ['scan', 'cscan', 'look', 'clook']) ? $data['direction'] : null;
$diskSize = $data['diskSize'] ?? 200; // Default disk size

// Debug: Log the direction to verify what’s being received
error_log("Algorithm: $algorithm, Direction: " . ($direction ?? 'none'));

//Throughput Calculation
function calculateThroughput($requests, $seekTime) {
    return count($requests) / $seekTime; // Requests per unit of seek time
}

// Disk Scheduling Algorithms

//----------------------- FCFS Algorithm -----------------------//
function fcfs($requests, $head) {
    $seekTime = 0;
    $sequence = array_merge([$head], $requests);
    
    for ($i = 0; $i < count($requests); $i++) {
        $seekTime += abs($requests[$i] - $head);
        $head = $requests[$i];
    }

    return ["seekTime" => $seekTime,
     "sequence" => $sequence, 
     "avgSeekTime" => round($seekTime / count($requests), 2),
     "throughput" => calculateThroughput($requests, $seekTime)
    ];    // Return performance metrics
}

//----------------------- SSTF Algorithm -----------------------//
function sstf($requests, $head) {
    $seekTime = 0;
    $sequence = [$head];
    $remaining = $requests;
    
    while (!empty($remaining)) {
        usort($remaining, fn($a, $b) => abs($a - $head) - abs($b - $head));
        $next = array_shift($remaining);
        $seekTime += abs($next - $head);
        $head = $next;
        $sequence[] = $head;
   }

    return [
        "seekTime" => $seekTime, 
        "sequence" => $sequence, 
        "avgSeekTime" => round($seekTime / count($requests), 2),
        "throughput" => calculateThroughput($requests, $seekTime)
    ];     // Return performance metrics
}



//----------------------- SCAN Algorithm -----------------------//
function scan($requests, $head, $direction, $disk_size) {
    $seekTime = 0;
    $sequence = [$head];
    sort($requests);

    $tmp = 0;
    foreach ($requests as $index => $request) {
        if ($head < $request) {
            $tmp = $index;
            break;
        }
    }

    if (strtolower($direction) == "left") {   //Left direction
        for ($i = $tmp - 1; $i >= 0; $i--) {
            $sequence[] = $requests[$i];
        }
        if (end($sequence) !== 0) {
            $sequence[] = 0;
        }
        for ($i = $tmp; $i < count($requests); $i++) {
            $sequence[] = $requests[$i];
        }
        $seekTime = abs($head + end($sequence));
    } else {     //Right direction
        for ($i = $tmp; $i < count($requests); $i++) {
            $sequence[] = $requests[$i];
        }
        if (end($sequence) !== $disk_size - 1) {
            $sequence[] = $disk_size - 1;
        }
        for ($i = $tmp - 1; $i >= 0; $i--) {
            $sequence[] = $requests[$i];
        }
        $seekTime = abs(($disk_size - 1 - $head) + ($disk_size - 1 - end($sequence)));
    }

    $avgSeekTime = round($seekTime / (count($sequence) - 1), 2);

    return [
        "seekTime" => $seekTime,
        "sequence" => $sequence,
        "avgSeekTime" => $avgSeekTime,
        "throughput" => calculateThroughput($requests, $seekTime)
    ];      // Return performance metrics
}



//----------------------- C-SCAN Algorithm -----------------------//
function cscan($requests, $head, $direction, $disk_size) {
    $seekTime = 0;
    $sequence = [$head];
    rsort($requests);

    $tmp = 0;
    foreach ($requests as $index => $request) {
        if ($head > $request) {
            $tmp = $index;
            break;
        }
    }

    if (strtolower($direction) == "right") {    //Right direction
        for ($i = $tmp - 1; $i >= 0; $i--) {
            $sequence[] = $requests[$i];
        }
        if (end($sequence) !== $disk_size - 1) {
            $sequence[] = $disk_size - 1;
        }
        for ($i = count($requests) - 1; $i >= $tmp; $i--) {
            if ($i === count($requests) - 1 && $requests[$i] !== 0) {
                $sequence[] = 0;
            }
            $sequence[] = $requests[$i];
        }
        $seekTime = abs(($disk_size - 1) - $head + ($disk_size - 1) + end($sequence));
    } else {    //Left direction
        for ($i = $tmp; $i <= count($requests) - 1; $i++) {
            $sequence[] = $requests[$i];
        }
        if (end($sequence) !== 0) {
            $sequence[] = 0;
        }
        for ($i = 0; $i < $tmp; $i++) {
            if ($i === 0 && $requests[$i] !== $disk_size - 1) {
                $sequence[] = $disk_size - 1;
            }
            $sequence[] = $requests[$i];
        }
        $seekTime = abs($head + ($disk_size - 1) + (($disk_size - 1) - end($sequence)));
    }

    $avgSeekTime = round($seekTime / (count($sequence) - 1), 2);

    return [
        "seekTime" => $seekTime,
        "sequence" => $sequence,
        "avgSeekTime" => $avgSeekTime,
        "throughput" => calculateThroughput($requests, $seekTime)
    ];    // Return performance metrics
}



//----------------------- LOOK Algorithm -----------------------//
function look($requests, $head, $direction) {
    $seekTime = 0;
    $sequence = [$head];
    sort($requests);

    $tmp = 0;
    foreach ($requests as $index => $request) {
        if ($request > $head) {
            $tmp = $index;
            break;
        }
    }

    if (strtolower($direction) == "right") {     //Right direction
        for ($i = $tmp; $i < count($requests); $i++) {
            $sequence[] = $requests[$i];
        }
        for ($i = $tmp - 1; $i >= 0; $i--) {
            $sequence[] = $requests[$i];
        }
        $largest = !empty($requests) ? $requests[count($requests) - 1] : $head;
        $smallest = !empty($requests) ? $requests[0] : $head;
        $seekTime = abs($largest - $head) + abs($largest - $smallest);
    } else {      //Left direction
        for ($i = $tmp - 1; $i >= 0; $i--) {
            $sequence[] = $requests[$i];
        }
        for ($i = $tmp; $i < count($requests); $i++) {
            $sequence[] = $requests[$i];
        }
        $largest = !empty($requests) ? $requests[count($requests) - 1] : $head;
        $smallest = !empty($requests) ? $requests[0] : $head;
        $seekTime = abs($head - $smallest) + abs($largest - $smallest);
    }

    return [
        "seekTime" => $seekTime,
        "sequence" => $sequence,
        "avgSeekTime" => round($seekTime / count($requests), 2),
        "throughput" => calculateThroughput($requests, $seekTime)
    ];    // Return performance metrics
}



//----------------------- C-LOOK Algorithm -----------------------//
function clook($requests, $head, $direction) {
    $seekTime = 0;
    $sequence = [$head];
    sort($requests);
    $sortedRequests = $requests;
    
    $tmp = 0;
    for ($i = 0; $i < count($sortedRequests); $i++) {
        if ($sortedRequests[$i] > $head) {
            $tmp = $i;
            break;
        }
    }

    if ($direction == "right") {     //Right direction
        for ($i = $tmp; $i < count($sortedRequests); $i++) {
            $sequence[] = $sortedRequests[$i];
        }
        for ($i = 0; $i < $tmp; $i++) {
            $sequence[] = $sortedRequests[$i];
        }
        $seekTime = abs($sortedRequests[count($sortedRequests) - 1] - $head) +
                    abs($sortedRequests[count($sortedRequests) - 1] - $sortedRequests[0]) +
                    abs(end($sequence) - $sortedRequests[0]);
    } else {     //Left direction
        for ($i = $tmp - 1; $i >= 0; $i--) {
            $sequence[] = $sortedRequests[$i];
        }
        for ($i = count($sortedRequests) - 1; $i >= $tmp; $i--) {
            $sequence[] = $sortedRequests[$i];
        }
        $seekTime = abs($head - $sortedRequests[0]) +
                    abs($sortedRequests[count($sortedRequests) - 1] - $sortedRequests[0]) +
                    abs($sortedRequests[count($sortedRequests) - 1] - $sortedRequests[$tmp]);
    }

    return [
        "seekTime" => $seekTime,
        "sequence" => $sequence,
        "avgSeekTime" => round($seekTime / count($requests), 2),
        "throughput" => calculateThroughput($requests, $seekTime)
    ];   // Return performance metrics
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
        $result = scan($requests, $head, $direction, $diskSize);
        break;
    case "cscan":
        $result = cscan($requests, $head, $direction, $diskSize);
        break;
    case "look":
        $result = look($requests, $head, $direction);
        break;
    case "clook":
        $result = clook($requests, $head, $direction);
        break;   
    default:
        $result = ["error" => "Invalid algorithm"];
        break;
}

// Return result as JSON
echo json_encode($result);

?>