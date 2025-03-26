let chartInstance = null;  // Declare a global variable to store the chart instance.

function runAlgorithm() {
    const requestsInput = document.getElementById('requests').value.trim();
    const headInput = document.getElementById('head').value.trim();

    // Check if requests or head is empty
    if (!requestsInput || !headInput) {
        alert("Error: Please fill in both requests and head position fields.");
        return; // Exit the function if validation fails
    }

    const requests = requestsInput.split(',').map(num=> Number(num.trim()));
    const head = parseInt(headInput);


    const algorithm = document.getElementById('algoDisplay').innerText.split(" ")[0];
    const directionContainer = document.getElementById('direction-container');
    let direction = "";

    // Only include direction if the direction dropdown is visible (i.e., for scan, cscan, look, clook)
    if (!directionContainer.classList.contains('hidden')) {
        direction = document.getElementById('direction').value;
    }

    
    // const requests = document.getElementById('requests').value.split(',').map(Number);
    // const head = parseInt(document.getElementById('head').value);
    // const algorithm = document.getElementById('algorithm').value;
    // const directionContainer = document.getElementById('direction-container');
    // let direction = '';

    
    
    fetch('backend.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ algorithm, requests, head, direction })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('sequence').innerText = "Sequence: " + data.sequence.join(" → ");
        document.getElementById('seekTime').innerText = "Total Seek Time: " + data.seekTime;
        document.getElementById('avgSeekTime').innerText = "Average Seek Time: " + data.avgSeekTime;
        document.getElementById('throughput').innerText = "Throughput: " + (data.throughput || "N/A");
        

        visualizeDiskMovement(data.sequence);
    })
    .catch(error => console.error('Error:', error));
}

function visualizeDiskMovement(sequence) {
    const ctx = document.getElementById('chart').getContext('2d');
 
    
    if (chartInstance) {  
        chartInstance.destroy();  // Destroys the previous chart instance before creating a new one to prevent overlapping issues.**
    }

    chartInstance=new Chart(ctx, {
        type: 'line',
        data: {
            labels: sequence,
            datasets: [{
                label: 'Disk Head Movement',
                data: sequence,
                borderColor: 'white',
                borderWidth: 2,
                fill: false,
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: { title: { display: true, text: 'Cylinder Number' } },
                y: { title: { display: true, text: 'Time' } }
            }
        }
    });
}