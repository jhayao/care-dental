<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Profile</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: {
          poppins: ['Poppins', 'sans-serif'],
        }
      }
    }
  }
</script>
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
        <h1 class="text-3xl font-bold text-gray-800">Patient Dashboard</h1>
        <p class="text-gray-500 mt-1">Overview of patient statistics and charts</p>
    </div>

    <!-- Filter Section (Matches Reports Design) -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
        <form id="filterForm" class="flex flex-wrap gap-4 items-end" onsubmit="event.preventDefault(); loadDashboard();">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Start Date</label>
                <input type="date" id="startDate" class="border px-3 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">End Date</label>
                <input type="date" id="endDate" class="border px-3 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                Generate
            </button>
            <button type="button" onclick="clearFilters()" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition font-medium">
                Clear
            </button>
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-xl shadow">
                <h2 class="text-2xl font-bold mb-6 text-center">Patients by Category</h2>
                <canvas id="categoryChart" width="400" height="250"></canvas>
            </div>
            <div class="bg-white p-8 rounded-xl shadow">
                <h2 class="text-2xl font-bold mb-6 text-center">Patients by Gender</h2>
                <canvas id="genderChart" width="400" height="250"></canvas>
            </div>
        </div>
    </main>
</div>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let categoryChart = null;
let genderChart = null;

function loadDashboard() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    fetch(`get_patient_counts.php?start_date=${startDate}&end_date=${endDate}`)
    .then(res => res.json())
    .then(data => {
        // --- Top Dashboard Cards (Total, None, Senior) ---
        const dashboardCards = document.getElementById('dashboardCards');
        dashboardCards.innerHTML = '';

        const dashboardColors = ['#f59e0b', '#10b981', '#3b82f6']; // assign colors
        const cardsData = [
            { title: 'Total Patients', count: data.totalPatients || 0 },
            { title: 'None', count: data.categories?.None || 0 },
            { title: 'Senior', count: data.categories?.Senior || 0 }
        ];

        cardsData.forEach(({ title, count }, index) => {
            const card = document.createElement('div');
            card.className = `shadow-lg rounded-xl p-8 text-center flex flex-col justify-center`;
            card.style.backgroundColor = dashboardColors[index]; // set card color
            card.innerHTML = `
                <h3 class="text-xl font-bold text-white">${title}</h3>
                <p class="text-4xl font-semibold mt-4 text-white">${count}</p>
            `;
            dashboardCards.appendChild(card);
        });

        // --- Category Cards ---
        const categoryCards = document.getElementById('categoryCards');
        categoryCards.innerHTML = '';

        const categoryColors = ['#f59e0b', '#10b981', '#3b82f6', '#ef4444']; // same as chart
        let catIndex = 0;
        for (const category in data.categories) {
            if (category === 'None' || category === 'Senior') continue;
            const card = document.createElement('div');
            card.className = "shadow-lg rounded-xl p-6 text-center";
            card.style.backgroundColor = categoryColors[catIndex % categoryColors.length];
            card.innerHTML = `
                <h3 class="text-lg font-bold text-white">${category}</h3>
                <p class="text-3xl font-semibold mt-2 text-white">${data.categories[category]}</p>
            `;
            categoryCards.appendChild(card);
            catIndex++;
        }

        // --- Gender Cards ---
        const genderCards = document.getElementById('genderCards');
        genderCards.innerHTML = '';

        const genderColors = ['#3b82f6', '#ec4899']; // same as gender chart
        let genderIndex = 0;
        for (const gender in data.genders) {
            const card = document.createElement('div');
            card.className = "shadow-lg rounded-xl p-6 text-center";
            card.style.backgroundColor = genderColors[genderIndex % genderColors.length];
            card.innerHTML = `
                <h3 class="text-lg font-bold text-white">${gender}</h3>
                <p class="text-3xl font-semibold mt-2 text-white">${data.genders[gender]}</p>
            `;
            genderCards.appendChild(card);
            genderIndex++;
        }

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
}

function clearFilters() {
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    loadDashboard();
}

// Initial Load
loadDashboard();
</script>
