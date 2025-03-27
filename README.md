# Advanced Disk Scheduling Simulator

# Live Demo: http://advanceddiskschedulingsimulator.free.nf/

# Overview
The Advanced Disk Scheduling Simulator is an interactive web-based project that simulates various disk scheduling algorithms. It provides detailed visualization and performance metrics, including Total Seek Time, Execution Sequence, and Average Seek Time. The project is built using PHP for backend processing, JavaScript (with Chart.js) for visualization, and Tailwind CSS for a modern, responsive user interface.

# Features
- Multiple Disk Scheduling Algorithms:  
  - FCFS (First Come, First Serve)
  - SSTF (Shortest Seek Time First)
  - SCAN (Elevator Algorithm)
  - C-SCAN (Circular SCAN)
  - LOOK 
  - C-LOOK

- Dynamic Visualization:  
  - Real-time disk head movement visualized via Chart.js.
  - Interactive graph updates upon each simulation run.

- Performance Metrics:  
  - Total Seek Time.
  - Execution Sequence.
  - Average Seek Time.
  - (Throughput is available if extended in the future.)

- Modern UI:  
  - Responsive and visually appealing interface built with Tailwind CSS.
  - User-friendly input fields for disk requests, head position, and algorithm selection.
  - Option to select the direction (left/right) for applicable algorithms.

- Modular Architecture:  
  - Frontend Module: Contains HTML, CSS, and JavaScript code for user interaction and visualization.
  - Backend Module: Contains PHP code implementing disk scheduling logic.

# Project Structure
AdvancedDiskSchedulingSimulator/
├── ui_module/
│   ├── index.html        # Main user interface and simulation page
│   ├── compare.html      # Comaprison page
├── simulated-engine/
│   ├── backend.php       # PHP code implementing disk scheduling algorithms (FCFS, SSTF, SCAN, C-SCAN, LOOK, C-LOOK)
├── visualization/
    ├── script.js         # JavaScript handling frontend logic and visualization
├── README.md             # This is readme file



# Prerequisites
- XAMPP (or any PHP server):  
  Install and start Apache to run PHP scripts.
- Modern Web Browser:  
  For accessing the simulation UI and viewing the visualization.
- Internet Connection:  
  To load external libraries (Chart.js, Tailwind CSS via CDN).

# Steps to Run the Project
1. Clone the Repository:
   ```bash
   git clone https://github.com/your-username/AdvancedDiskSchedulingSimulator.git
   ```
2. Place Files in XAMPP Directory:
   Copy the repository folder to your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\DiskSchedulingSimulator`).

3. Start Apache Server:
   Open XAMPP Control Panel and start Apache.

4. Access the Project in Browser:
   Open your browser and navigate to:
   ```
   http://localhost/DiskSchedulingSimulator/ui_module/index.html
   ```
5. Interact with the Simulator:
   - Select a disk scheduling algorithm.
   - Input a comma-separated list of disk requests.
   - Enter the initial head position.
   - (If applicable, select a direction—left or right.)
   - Click the "Simulate" button to view the results and visualization.


# Frontend (UI Module)
- User Input:  
  The HTML form collects disk requests, head position, and algorithm choice.
  
- Data Transmission:  
  JavaScript (in `script.js`) sends the input as a JSON payload to `backend/backend.php` using the Fetch API.

- Visualization:  
  Upon receiving results from the backend, the JavaScript function `visualizeDiskMovement()` uses Chart.js to render a line graph depicting the disk head movements.

# Visualization (Graph Module)
- Data Processing:  
  The seek sequence and movement data received from the backend are parsed in JavaScript.
  
- Graph Rendering:
  Chart.js is used to plot the disk scheduling execution path, with the x-axis representing request order and the y-axis representing track positions.

- Animation & UI Enhancements:
  Transitions and color coding are added to improve clarity between different algorithms.

# Backend (PHP Module)
- Input Processing:  
  The PHP script (`backend.php`) decodes the JSON input and extracts the algorithm, requests, head position, and direction.

- Algorithm Implementation:  
  Each disk scheduling algorithm (FCFS, SSTF, SCAN, C-SCAN, LOOK, C-LOOK) is implemented as a separate function.  
  For example, SCAN:
  - Left Direction: Processes requests to the left of the head (in descending order), moves to track 0, then processes right requests.
  - Right Direction: Processes requests to the right of the head, moves to the maximum track, then processes left requests in reverse order.
  
- Output:  
  The results (total seek time, execution sequence, and average seek time) are encoded in JSON and sent back to the frontend.

- Comments explain how user input is collected, transmitted, and visualized.

# Algorithm Functions (in `backend.php`)
Each algorithm function is documented with inline comments explaining:
- How input is processed.
- The logical steps to compute the seek time and execution sequence.
- How the average seek time is calculated.

# Conclusion
This project demonstrates a comprehensive disk scheduling simulator that:
- Implements multiple algorithms.
- Provides real-time visualization.
- Presents detailed performance metrics.
- Has a modular structure with well-documented code.
