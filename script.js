function runAlgorithm() {
    const requests = document.getElementById('requests').value.split(',').map(Number);
    const head = parseInt(document.getElementById('head').value);
    const algorithm = document.getElementById('algorithm').value;
    
    fetch('backend.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ algorithm, requests, head })
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

function visualizeDiskMovement(sequence) {
    const ctx = document.getElementById('chart').getContext('2d');
    new Chart(ctx, {
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
