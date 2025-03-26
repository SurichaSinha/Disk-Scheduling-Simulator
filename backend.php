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
if (!isset($data['algorithm']) || !isset($data['requests']) || !isset($data['head'] )) {
    echo json_encode(["error" => "Invalid input data"]);
    exit;
}

$algorithm = $data['algorithm'];
$requests = array_map('intval', $data['requests']); // Convert requests to integers
$head = intval($data['head']); // Convert head to integer
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
     
    
    /* FCFS: Processes requests in the order they appear.*/
    
    $seekTime = 0;
    
    
    // Created a sequence starting with the head position, then all requests.
    $sequence = array_merge([$head], $requests);

    
    // For each request, added the absolute difference from current head, then update head.
    for ($i = 0; $i < count($requests); $i++) {
        $seekTime += abs($requests[$i] - $head);
        $head = $requests[$i];
    }

    return ["seekTime" => $seekTime, "sequence" => $sequence, "avgSeekTime" => round($seekTime / count($requests), 2),"throughput" => calculateThroughput($requests, $seekTime)];
}



//----------------------- SSTF Algorithm -----------------------//
function sstf($requests, $head) {

    /* SSTF: Always picks the request with the shortest seek time from the current head.*/
    
    $seekTime = 0;
    $sequence = [$head];
    $remaining = $requests;

    
    // Loop until all requests have been processed.
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
    ];
}



//----------------------- SCAN Algorithm -----------------------//
function scan($requests, $head, $direction, $disk_size) {

    /* SCAN (Elevator Algorithm): The disk arm moves in one direction,
     servicing all requests until it reaches the end (0 or disk_size-1), then reverses.*/
    

    $seekTime = 0;
    $sequence = [];
    
    // Sort requests in ascending order
    sort($requests);
    
    // Separate requests into left (less than head) and right (greater than head)
    $left = array_values(array_filter($requests, fn($r) => $r < $head));
    $right = array_values(array_filter($requests, fn($r) => $r > $head));
    
    // For left direction, process left requests in descending order then go to track 0, then process right requests (ascending)

    if ($direction == "left") {
        rsort($left); // Descending order for left side
        // Sequence: start at head, then left side, then 0, then right side
        $sequence = array_merge([$head], $left, [0], $right);
    } else {
        // For right direction, process right requests in ascending order, then go to max track, then process left requests in descending order
        $sequence = array_merge([$head], $right, [$disk_size - 1], array_reverse($left));
    }
    
    // Compute total seek time as sum of absolute differences between consecutive tracks
    for ($i = 1; $i < count($sequence); $i++) {
        $seekTime += abs($sequence[$i] - $sequence[$i - 1]);
    }
    
    // Compute average seek time as total seek time divided by (number of moves)
    $avgSeekTime = round($seekTime / (count($sequence) - 1), 2);
    
    return [
        "seekTime" => $seekTime,
        "sequence" => $sequence,
        "avgSeekTime" => $avgSeekTime,
        "throughput" => calculateThroughput($requests, $seekTime)
    ];
}






//----------------------- CSCAN Algorithm -----------------------//
function cscan($requests, $head, $direction, $disk_size) {

    /* CSCAN (Circular SCAN): The disk arm moves in one direction, servicing requests,
     and upon reaching the end, it jumps to the opposite end (without servicing in between)
     and continues servicing.*/

    $seekTime = 0;
    $sequence = [];
    sort($requests); // Sort the requests in ascending order

    // Separate requests into left and right of the head
    $left = array_values(array_filter($requests, fn($r) => $r < $head));
    $right = array_values(array_filter($requests, fn($r) => $r > $head));

    if ($direction == "left") {

	// Head, then left side (in descending order), then track 0, then disk's maximum track, then right side in descending order.
        
        $sequence = array_merge([$head], array_reverse($left), [0], [$disk_size - 1], array_reverse($right));
        $seekTime = ($head - min($left))  
                  + (min($left) - 0)      
                  + (($disk_size - 1) - 0)  
                  + (($disk_size - 1) - min($right));
        $avgSeekTime = round($seekTime / (count($sequence) - 1), 2);
    } else {
	
	 // For right direction:
        $sequence = array_merge([$head], $right, [$disk_size - 1], [0], $left);
        $seekTime = ($disk_size - 1 - $head)
                  + (($disk_size - 1) - 0)
                  + (end($left) - 0);
        
        $avgSeekTime = round($seekTime / (count($sequence) - 1), 2);
    }

    return [
        "seekTime" => $seekTime,
        "sequence" => $sequence,
        "avgSeekTime" => $avgSeekTime,
        "throughput" => calculateThroughput($requests, $seekTime)
    ];
}




//----------------------- LOOK Algorithm -----------------------//
function look($requests, $head, $direction) {

    /* LOOK: Similar to SCAN but the disk arm only goes as far as the last request in each direction.*/
    
    $seekTime = 0;
    $sequence = [];
    sort($requests);

    $left = array_filter($requests, fn($r) => $r <= $head);
    $right = array_filter($requests, fn($r) => $r > $head);
    rsort($left);

    if ($direction == "left") {
        $sequence = array_merge([$head], $left, $right);
    } else {
        $sequence = array_merge([$head], $right, $left);
    }

    for ($i = 1; $i < count($sequence); $i++) {
        $seekTime += abs($sequence[$i] - $sequence[$i - 1]);
    }

    return ["seekTime" => $seekTime, "sequence" => $sequence, "avgSeekTime" => round($seekTime / count($requests), 2),"throughput" => calculateThroughput($requests, $seekTime)];
}



//----------------------- CLOOK Algorithm -----------------------//
function clook($requests, $head, $direction) {

    /* CLOOK: Similar to CSCAN but the arm only goes as far as the last request,
     then jumps back to the first request.*/
    $seekTime = 0;
    $sequence = [];
    sort($requests);

    $left = array_filter($requests, fn($r) => $r <= $head);
    $right = array_filter($requests, fn($r) => $r > $head);

    if ($direction == "left") {
        $sequence = array_merge([$head], array_reverse($left), array_reverse($right));
    } else {
        $sequence = array_merge([$head], $right, $left);
    }

    for ($i = 1; $i < count($sequence); $i++) {
        $seekTime += abs($sequence[$i] - $sequence[$i - 1]);
    }

    return ["seekTime" => $seekTime, "sequence" => $sequence, "avgSeekTime" => round($seekTime / count($requests), 2),"throughput" => calculateThroughput($requests, $seekTime)];
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
