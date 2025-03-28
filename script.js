let chartInstance = null;

function runAlgorithm() {
    const requestsInput = document.getElementById('requests').value.trim();
    const headInput = document.getElementById('head').value.trim();

    // Check if requests or head is empty
    if (!requestsInput || !headInput) {
        alert("Error: Please fill in both requests and head position fields.");
        return;
    }

    const requests = requestsInput.split(',').map(num => Number(num.trim()));
    const head = parseInt(headInput);

    // Get algorithm from URL parameter instead of innerText and convert to lowercase
    const urlParams = new URLSearchParams(window.location.search);
    const algorithm = urlParams.get('algo').toLowerCase(); // Convert to lowercase to match backend

    const directionContainer = document.getElementById('direction-container');
    let direction = "";

    // Only include direction if the direction dropdown is visible (for scan, cscan, look, clook)
    if (!directionContainer.classList.contains('hidden')) {
        direction = document.getElementById('direction').value;
    }

    fetch('backend.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ algorithm, requests, head, direction })
    })
    .then(response => response.json())
    .then(data => {
        // Check if data contains an error
        if (data.error) {
            alert("Error from backend: " + data.error);
            return;
        }
        document.getElementById('sequence').innerText = "Sequence: " + data.sequence.join(" → ");
        document.getElementById('seekTime').innerText = "Total Seek Time: " + data.seekTime;
        document.getElementById('avgSeekTime').innerText = "Average Seek Time: " + data.avgSeekTime;
        document.getElementById('throughput').innerText = "Throughput: " + (data.throughput || "N/A");
        
        visualizeDiskMovement(data.sequence);
    })
    .catch(error => console.error('Error:', error));
}


//Chart for visualisation
function visualizeDiskMovement(sequence) {
    const ctx = document.getElementById('chart').getContext('2d');
    
    if (chartInstance) {  
        chartInstance.destroy();
    }

    // Create an array of indices for the x-axis positions
    const indices = Array.from({ length: sequence.length }, (_, i) => i);

    

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: sequence, 
            datasets: [{
                label: 'Disk Head Movement',
                data: sequence, 
                borderColor:' #c562ae', 
                borderWidth: 2, 
                fill: false,
                pointRadius: 3, 
                pointBackgroundColor: '#c562ae', 
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#000000',
                        font: {
                            size: 16,
                            weight: 'bold',
                        },
                    },
                },
            },
            layout: {
                padding: {
                    left: 10,
                    right: 10,
                    top: 10,
                    bottom: 10,
                },
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Cylinder Number',
                        color: '#000000',
                        font: {
                            size: 16,
                            weight: 'bold',
                        },
                    },
                    ticks: {
                        color: '#000000',
                        font: {
                            size: 14,
                            weight: 'bold',
                        },
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                    },
                },
                y: {
                    title: {
                        display: true,
                        text: 'Time',
                        color: '#000000',
                        font: {
                            size: 16,
                            weight: 'bold',
                        },
                    },
                    ticks: {
                        color: '#000000',
                        font: {
                            size: 14,
                            weight: 'bold',
                        },
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                    },
                },
            },
            // chart canvas
            plugins: {
                beforeDraw: (chart) => {
                    const ctx = chart.ctx;
                    ctx.save();
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, chart.width, chart.height);
                    ctx.restore();
                },
            },
        }
    });
}