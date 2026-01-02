<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Profile</title>
<link href="../assets/css/main.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<div class="flex min-h-screen bg-gray-100">

    <!-- Sticky Sidebar -->
    <aside class="sticky top-0 h-screen overflow-y-auto">
        <?php include 'sidebar.php'; ?>
    </aside>

    <!-- Scrollable Main content -->
    <main class="flex-1 p-8 overflow-y-auto h-screen">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Staff Dashboard</h1>
        <p class="text-gray-500 mt-1">Overview of clinic statistics and charts</p>
    </div>

    <!-- Filter Section (Matches Reports Design) -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
        <form id="filterForm" class="flex flex-wrap gap-4 items-end" onsubmit="event.preventDefault();">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Start Date</label>
                <input type="date" id="startDate" class="border px-3 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" onchange="loadDashboard()">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">End Date</label>
                <input type="date" id="endDate" class="border px-3 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" onchange="loadDashboard()">
            </div>
        </form>
    </div>

        <!-- Top Cards: Total Patients, None, Senior -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 mb-8" id="dashboardCards">
            <!-- Cards will be dynamically generated here -->
        </div>

        <!-- Category Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8 mb-8" id="categoryCards"></div>

        <!-- Gender Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-8" id="genderCards"></div>

        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-8 rounded-xl shadow">
                <h2 class="text-2xl font-bold mb-6 text-center">Patients by Category</h2>
                <canvas id="categoryChart" width="400" height="250"></canvas>
            </div>
            <div class="bg-white p-8 rounded-xl shadow">
                <h2 class="text-2xl font-bold mb-6 text-center">Patients by Gender</h2>
                <canvas id="genderChart" width="400" height="250"></canvas>
            </div>
        </div>

        <!-- NEW: Total Income Chart -->
        <div class="bg-white p-8 rounded-xl shadow mb-8">
            <h2 class="text-2xl font-bold mb-6 text-center">Total Income Overview</h2>
            <canvas id="incomeChart" width="800" height="300"></canvas>
        </div>
    </main>
</div>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let categoryChart = null;
let genderChart = null;
let incomeChart = null;

function loadDashboard() {
    let startDate = document.getElementById('startDate').value;
    let endDate = document.getElementById('endDate').value;

    const today = new Date().toISOString().split('T')[0];

    // Auto-fill dates logic
    if (startDate && !endDate) {
        endDate = today;
        document.getElementById('endDate').value = endDate;
    } else if (!startDate && endDate) {
        startDate = today; 
        document.getElementById('startDate').value = startDate;
    }

    fetch(`get_patient_counts.php?start_date=${startDate}&end_date=${endDate}`)
    .then(res => res.json())
    .then(data => {
        // --- Top Dashboard Cards (Total, None, Senior) ---
        // --- Top Dashboard Cards ---
        const dashboardCards = document.getElementById('dashboardCards');
        dashboardCards.innerHTML = '';
        const dashboardColors = ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6']; 
        
        // Map 'None' to 'Regular' for display
        const regularCount = data.categories?.None || 0;
        const seniorCount = data.categories?.Senior || 0;
        const pwdCount = data.categories?.PWD || 0;

        const cardsData = [
            { title: 'Total Patients', count: data.totalPatients || 0 },
            { title: 'Regular', count: regularCount },
            { title: 'Senior', count: seniorCount },
            { title: 'PWD', count: pwdCount }
        ];

        // Update grid cols to 4 or flex
        dashboardCards.className = "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8";

        cardsData.forEach(({ title, count }, index) => {
            const card = document.createElement('div');
            card.className = `shadow-lg rounded-xl p-6 text-center flex flex-col justify-center transform hover:scale-105 transition duration-300`;
            card.style.backgroundColor = dashboardColors[index];
            card.innerHTML = `
                <h3 class="text-xl font-bold text-white">${title}</h3>
                <p class="text-4xl font-semibold mt-4 text-white">${count}</p>
            `;
            dashboardCards.appendChild(card);
        });

        // --- Category Cards (Remaining) ---
        const categoryCards = document.getElementById('categoryCards');
        categoryCards.innerHTML = '';
        const categoryColors = ['#f59e0b', '#10b981', '#3b82f6', '#ef4444'];
        let catIndex = 0;
        for (const category in data.categories) {
            if (category === 'None' || category === 'Senior' || category === 'PWD') continue; 
            const card = document.createElement('div');
            card.className = "shadow-lg rounded-xl p-6 text-center bg-white border-l-4";
            card.style.borderColor = categoryColors[catIndex % categoryColors.length];
            card.innerHTML = `
                <h3 class="text-lg font-bold text-gray-700">${category}</h3>
                <p class="text-3xl font-semibold mt-2 text-gray-900">${data.categories[category]}</p>
            `;
            categoryCards.appendChild(card);
            catIndex++;
        }

        // --- Gender Cards ---
        const genderCards = document.getElementById('genderCards');
        genderCards.innerHTML = '';
        const genderColors = ['#3b82f6', '#ec4899'];
        let genderIndex = 0;
        
        // Show Gender Counts explicitly
        const allGenders = new Set(['Male', 'Female']);
        Object.keys(data.genders).forEach(g => allGenders.add(g));

        allGenders.forEach(gender => {
            const count = data.genders[gender] || 0;
            // Skip 'Not Specified' (empty string) only if count is 0
            if (gender === '' && count === 0) return;

            const card = document.createElement('div');
            card.className = "shadow-md rounded-xl p-6 text-center bg-white border border-gray-100";
             
            let color = '#9ca3af'; // Default gray
            if (gender === 'Male') color = '#3b82f6';
            if (gender === 'Female') color = '#ec4899';

            const genderLabel = gender ? gender : 'Not Specified';

            card.innerHTML = `
                <h3 class="text-lg font-bold" style="color: ${color}">${genderLabel}</h3>
                <p class="text-3xl font-semibold mt-2 text-gray-800">${count}</p>
            `;
            genderCards.appendChild(card);
        });

        // --- Charts ---
        const ctxCat = document.getElementById('categoryChart').getContext('2d');
        const ctxGen = document.getElementById('genderChart').getContext('2d');

        if (categoryChart) categoryChart.destroy();
        if (genderChart) genderChart.destroy();

        categoryChart = new Chart(ctxCat, {
            type: 'pie',
            data: {
                labels: Object.keys(data.categories),
                datasets: [{
                    label: 'Patients by Category',
                    data: Object.values(data.categories),
                    backgroundColor: categoryColors,
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        genderChart = new Chart(ctxGen, {
            type: 'pie',
            data: {
                labels: Object.keys(data.genders),
                datasets: [{
                    label: 'Patients by Gender',
                    data: Object.values(data.genders),
                    backgroundColor: genderColors,
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

    })
    .catch(err => console.error(err));

    // --- Fetch Income Data (Reusable from Admin) ---
    fetch(`../admin/get_income_data.php?start_date=${startDate}&end_date=${endDate}`)
    .then(res => res.json())
    .then(data => {
        const ctxIncome = document.getElementById('incomeChart').getContext('2d');
        if (incomeChart) incomeChart.destroy();

        incomeChart = new Chart(ctxIncome, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Total Income',
                    data: data.values,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(value) { return 'P' + value; } } }
                }
            }
        });
    })
    .catch(err => console.error("Income fetch error:", err));
}
// Initial using current logic would be empty/all
loadDashboard();
