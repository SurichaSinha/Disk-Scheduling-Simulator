let chartInstance = null;  // Declare a global variable to store the chart instance.

function runAlgorithm() {
    const requestsInput = document.getElementById('requests').value;
    const head = document.getElementById('head').value;
    const algorithm = document.getElementById('algorithm').value;
    const directionContainer = document.getElementById('direction-container');
    let direction = '';

    // Check if requests or head is empty
    if (!requestsInput.trim() || !head.trim()) {
        alert("Error: Please fill in both requests and head position fields.");
        return; // Exit the function if validation fails
    }

    const requests = requestsInput.split(',').map(Number);
    const headParsed = parseInt(head);

    // Only include direction if the direction dropdown is visible (i.e., for scan, cscan, look, clook)
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
        document.getElementById('sequence').innerText = "Sequence: " + data.sequence.join(" → ");
        document.getElementById('seekTime').innerText = "Total Seek Time: " + data.seekTime;
        document.getElementById('avgSeekTime').innerText = "Average Seek Time: " + data.avgSeekTime;
        document.getElementById('throughput').innerText = "Throughput: " + data.throughput;

        visualizeDiskMovement(data.sequence);
    })
    .catch(error => console.error('Error:', error));
}



//Chart for visualisation
function visualizeDiskMovement(sequence) {
    const ctx = document.getElementById('chart').getContext('2d');
    
    if (chartInstance) {  
        chartInstance.destroy();  // Destroys the previous chart instance before creating a new one to prevent overlapping issues.
    }

    const indices = Array.from({ length: sequence.length }, (_, i) => i);

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: sequence, 
            datasets: [{
                label: 'Disk Head Movement',
                data: sequence, 
                borderColor: '#c562ae', 
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
            // chart canvas background 
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