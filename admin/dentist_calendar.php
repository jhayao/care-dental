<?php
session_start();
require_once '../db_connect.php';

// Ensure staff is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Actions (Add, Update, Delete) via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add' && isset($_POST['from_date'], $_POST['to_date'], $_POST['start_time'], $_POST['end_time'])) {
        // ADD LOGIC (Existing)
        $from_date = $_POST['from_date'];
        $to_date = $_POST['to_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        if ($from_date && $to_date && $start_time && $end_time) {
            $begin = new DateTime($from_date);
            $end = new DateTime($to_date);
            $end->modify('+1 day'); 

            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);

            $stmt = $conn->prepare("INSERT INTO dentist_calendar (user_id, available_date, start_time, end_time) VALUES (?, ?, ?, ?)");
            
            if ($stmt) {
                $successCount = 0;
                foreach ($period as $dt) {
                    $current_date = $dt->format("Y-m-d");
                    $stmt->bind_param("isss", $user_id, $current_date, $start_time, $end_time);
                    try {
                        if ($stmt->execute()) $successCount++;
                    } catch (mysqli_sql_exception $e) {
                         // Handle FK error specifically if needed, primarily just ignore dupes or specific errors here for bulk add
                         if ($e->getCode() == 1452) { // FK Error
                             echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login.', 'redirect' => '../login.php']);
                             exit;
                         }
                    }
                }
                $stmt->close();
                echo json_encode(['status' => 'success', 'message' => "Added availability for $successCount days."]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'DB Prepare Error']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Input']);
        }
    } 
    elseif ($action === 'update' && isset($_POST['slot_id'], $_POST['start_time'], $_POST['end_time'])) {
        // UPDATE LOGIC
        $slot_id = $_POST['slot_id']; // comes as 'slot_123', need to strip 'slot_'
        $id = str_replace('slot_', '', $slot_id);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $stmt = $conn->prepare("UPDATE dentist_calendar SET start_time = ?, end_time = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $start_time, $end_time, $id, $user_id);
        
        if ($stmt->execute()) {
             echo json_encode(['status' => 'success', 'message' => 'Slot updated successfully.']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Update failed.']);
        }
        $stmt->close();
    }
    elseif ($action === 'delete' && isset($_POST['slot_id'])) {
        // DELETE LOGIC
        $slot_id = $_POST['slot_id'];
        $id = str_replace('slot_', '', $slot_id);

        $stmt = $conn->prepare("DELETE FROM dentist_calendar WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        
        if ($stmt->execute()) {
             echo json_encode(['status' => 'success', 'message' => 'Slot deleted successfully.']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Delete failed.']);
        }
        $stmt->close();
    }
    exit;
}

// --- FETCH DATA FOR CALENDAR ---
$events = [];

// 1. Fetch Availability Slots
$stmt = $conn->prepare("SELECT id, available_date, start_time, end_time FROM dentist_calendar WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $events[] = [
        'id' => 'slot_' . $row['id'],
        'title' => 'Open',
        'start' => $row['available_date'] . 'T' . $row['start_time'],
        'end' => $row['available_date'] . 'T' . $row['end_time'],
        'color' => '#6c757d', // Gray
        'extendedProps' => [
            'type' => 'slot',
            'start_raw' => $row['start_time'], // Needed for editing inputs
            'end_raw' => $row['end_time'],
            'start_fmt' => date("h:i A", strtotime($row['start_time'])),
            'end_fmt' => date("h:i A", strtotime($row['end_time']))
        ]
    ];
}
$stmt->close();

// 2. Fetch Bookings (Existing Logic)
$query = "
    SELECT 
        b.id, 
        b.appointment_date, 
        b.appointment_time, 
        b.duration_minutes, 
        b.status,
        u.first_name, 
        u.last_name 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.status != 'cancelled' 
";
$res = $conn->query($query);
while ($row = $res->fetch_assoc()) {
    $start = $row['appointment_date'] . 'T' . $row['appointment_time'];
    $startTimeObj = new DateTime($start);
    $endTimeObj = clone $startTimeObj;
    $endTimeObj->modify('+' . $row['duration_minutes'] . ' minutes');
    
    $color = '#ffc107'; 
    if ($row['status'] === 'confirmed') $color = '#198754'; 
    elseif ($row['status'] === 'completed') $color = '#0d6efd'; 
    elseif ($row['status'] === 'cancelled') $color = '#dc3545'; 

    $events[] = [
        'id' => 'booking_' . $row['id'],
        'title' => $row['first_name'] . ' ' . $row['last_name'],
        'start' => $start,
        'end' => $endTimeObj->format('Y-m-d\TH:i:s'),
        'color' => $color,
        'extendedProps' => [
            'type' => 'booking',
            'status' => ucfirst($row['status']),
            'patient' => $row['first_name'] . ' ' . $row['last_name'],
            'start_fmt' => date("h:i A", strtotime($start)),
            'end_fmt' => $endTimeObj->format("h:i A")
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Calendar</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">

<!-- FullCalendar -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

<style>
    .fc-event { cursor: pointer; }
    .fc-toolbar-title { font-size: 1.5rem !important; font-weight: 600; }
    .fc-button-primary { background-color: #2563eb !important; border-color: #2563eb !important; }
    .fc-button-primary:hover { background-color: #1d4ed8 !important; border-color: #1d4ed8 !important; }
</style>
</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">

<?php include 'admin_sidebar.php'; ?>

<div class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-calendar-alt mr-2 text-blue-600"></i> Schedule & Availability
        </h1>
        <button onclick="openSlotModal()" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 flex items-center shadow-lg transition">
            <i class="fas fa-plus-circle mr-2"></i> Add Availability
        </button>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap gap-4 mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-yellow-400 mr-2"></span> <span class="text-sm font-medium">Pending Booking</span></div>
        <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-green-600 mr-2"></span> <span class="text-sm font-medium">Confirmed Booking</span></div>
        <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-blue-600 mr-2"></span> <span class="text-sm font-medium">Completed Booking</span></div>
        <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-gray-500 mr-2 opacity-50"></span> <span class="text-sm font-medium">Available Slot</span></div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-lg h-[calc(100vh-220px)] overflow-hidden">
        <div id='calendar' class="h-full"></div>
    </div>
</div>

<!-- Add Availability Modal -->
<div id="slotModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-full max-w-md p-6 relative shadow-2xl">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h2 class="text-xl font-bold text-gray-800">Add Availability</h2>
            <button onclick="closeSlotModal()" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="slotForm">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block mb-1 font-semibold text-gray-700">From Date</label>
                    <input type="date" id="from_date" name="from_date" class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>
                 <div>
                    <label class="block mb-1 font-semibold text-gray-700">To Date</label>
                    <input type="date" id="to_date" name="to_date" class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block mb-1 font-semibold text-gray-700">Start Time</label>
                    <input type="time" id="start_time" name="start_time" class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block mb-1 font-semibold text-gray-700">End Time</label>
                    <input type="time" id="end_time" name="end_time" class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>
            </div>
            <div class="mb-4 text-xs text-gray-500">
                <i class="fas fa-info-circle mr-1"></i> Slots will be created for <b>each day</b> in the selected range.
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeSlotModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-medium transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium transition shadow-md">Save Availability</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit/View Slot Modal -->
<div id="viewSlotModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-full max-w-sm p-6 relative shadow-2xl">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h2 class="text-xl font-bold text-gray-800">Edit Availability</h2>
            <button onclick="closeViewSlotModal()" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="editSlotForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="slot_id" id="edit_slot_id">
            
            <div class="mb-4">
                <p class="text-sm text-gray-500 mb-1">Date</p>
                <p class="font-semibold text-lg text-gray-800" id="view_slot_date">-</p>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="text-sm text-gray-500 mb-1 block">Start Time</label>
                    <input type="time" name="start_time" id="edit_slot_start" class="w-full border px-2 py-1 rounded" required>
                </div>
                <div>
                    <label class="text-sm text-gray-500 mb-1 block">End Time</label>
                    <input type="time" name="end_time" id="edit_slot_end" class="w-full border px-2 py-1 rounded" required>
                </div>
            </div>

            <div class="flex justify-between items-center pt-2">
                <button type="button" id="btnDeleteSlot" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                    <i class="fas fa-trash-alt mr-1"></i> Delete Slot
                </button>
                <div class="flex gap-2">
                     <button type="button" onclick="closeViewSlotModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-medium transition">Cancel</button>
                     <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium transition">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Past Date Warning Modal -->
<div id="pastDateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-full max-w-sm p-6 relative shadow-2xl text-center">
        <div class="mb-4">
             <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Invalid Date</h3>
        <p class="text-gray-600 mb-6">You cannot add availability to past dates. Please select a future date.</p>
        <button onclick="closePastDateModal()" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">OK</button>
    </div>
</div>


<!-- Booking Details Modal -->
<div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-full max-w-sm p-6 relative shadow-2xl">
        <div class="border-b pb-2 mb-4">
            <h2 class="text-xl font-bold text-gray-800">Booking Details</h2>
        </div>
        <div class="mb-6 space-y-2">
            <div>
                <span class="text-xs text-gray-500 uppercase font-semibold">Patient</span>
                <p id="booking_patient" class="text-gray-800 font-medium text-lg"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase font-semibold">Status</span>
                <p id="booking_status" class="text-gray-800"></p>
            </div>
        </div>
        <div class="flex justify-end">
            <button onclick="closeBookingModal()" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Close</button>
        </div>
    </div>
</div>

<!-- Generic Alert Modal (Success/Error) -->
<div id="alertModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-full max-w-sm p-6 relative shadow-2xl text-center">
        <div class="mb-4" id="alertIcon">
             <!-- Icon injected via JS -->
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2" id="alertTitle">Notification</h3>
        <p class="text-gray-600 mb-6" id="alertMessage"></p>
        <button onclick="closeAlertModal()" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">OK</button>
    </div>
</div>

<script>
    const slotModal = document.getElementById('slotModal');
    const viewSlotModal = document.getElementById('viewSlotModal');
    const pastDateModal = document.getElementById('pastDateModal');
    const bookingModal = document.getElementById('bookingModal');
    const alertModal = document.getElementById('alertModal');

    function openSlotModal() {
        document.getElementById('from_date').value = '';
        document.getElementById('to_date').value = '';
        document.getElementById('start_time').value = '';
        document.getElementById('end_time').value = '';
        slotModal.classList.remove('hidden');
        slotModal.classList.add('flex');
    }
    function closeSlotModal() { slotModal.classList.add('hidden'); slotModal.classList.remove('flex'); }

    // Edit Modal Functions
    function openViewSlotModal(id, date, startRaw, endRaw) {
        document.getElementById('edit_slot_id').value = id;
        document.getElementById('view_slot_date').textContent = date;
        document.getElementById('edit_slot_start').value = startRaw; // set time input
        document.getElementById('edit_slot_end').value = endRaw;     // set time input
        
        viewSlotModal.classList.remove('hidden');
        viewSlotModal.classList.add('flex');
    }
    function closeViewSlotModal() { viewSlotModal.classList.add('hidden'); viewSlotModal.classList.remove('flex'); }

    // Past Date Modal
    function showPastDateModal() { pastDateModal.classList.remove('hidden'); pastDateModal.classList.add('flex'); }
    function closePastDateModal() { pastDateModal.classList.add('hidden'); pastDateModal.classList.remove('flex'); }

    // Booking Details Modal
    function showBookingModal(title, status) {
        document.getElementById('booking_patient').textContent = title;
        document.getElementById('booking_status').textContent = status;
        // Colorize status
        const statusEl = document.getElementById('booking_status');
        statusEl.className = 'font-semibold px-2 py-1 rounded text-sm w-fit ' + (
            status === 'Confirmed' ? 'bg-green-100 text-green-800' : 
            status === 'Completed' ? 'bg-blue-100 text-blue-800' : 
            status === 'Cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'
        );

        bookingModal.classList.remove('hidden');
        bookingModal.classList.add('flex');
    }
    function closeBookingModal() { bookingModal.classList.add('hidden'); bookingModal.classList.remove('flex'); }

    // Custom Alert Modal
    function showAlert(message, type = 'info') {
        const titleEl = document.getElementById('alertTitle');
        const msgEl = document.getElementById('alertMessage');
        const iconEl = document.getElementById('alertIcon');

        msgEl.textContent = message;
        
        if (type === 'success') {
            titleEl.textContent = 'Success';
            iconEl.innerHTML = '<i class="fas fa-check-circle text-green-500 text-4xl"></i>';
        } else if (type === 'error') {
             titleEl.textContent = 'Error';
             iconEl.innerHTML = '<i class="fas fa-times-circle text-red-500 text-4xl"></i>';
        } else {
             titleEl.textContent = 'Notification';
             iconEl.innerHTML = '<i class="fas fa-info-circle text-blue-500 text-4xl"></i>';
        }

        alertModal.classList.remove('hidden');
        alertModal.classList.add('flex');
    }
    function closeAlertModal() { alertModal.classList.add('hidden'); alertModal.classList.remove('flex'); }


    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'multiMonthYear',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'multiMonthYear,dayGridMonth,timeGridWeek'
            },
            events: <?= json_encode($events); ?>,
            slotMinTime: "07:00:00",
            slotMaxTime: "20:00:00",
            allDaySlot: false,
            nowIndicator: true,
            height: '100%',
            selectable: true,
            // Gray out past dates
            dayCellClassNames: function(arg) {
                if (arg.date < new Date(new Date().setHours(0,0,0,0))) {
                    return ['bg-gray-100', 'cursor-not-allowed', 'opacity-50'];
                }
                return [];
            },
            select: function(info) {
                // Prevent past dates
                const selectedDate = new Date(info.startStr);
                const today = new Date();
                today.setHours(0,0,0,0);
                
                if (selectedDate < today) {
                    showPastDateModal(); // USE MODAL INSTEAD OF ALERT
                    calendar.unselect();
                    return;
                }

                // Handle Date Range Selection
                // FullCalendar 'end' is exclusive (next day), so we subtract 1 day for the 'To Date'
                let endDate = new Date(info.endStr);
                endDate.setDate(endDate.getDate() - 1); // Subtract 1 day
                
                const fromDateStr = info.startStr.split('T')[0];
                const toDateStr = endDate.toISOString().split('T')[0];

                document.getElementById('from_date').value = fromDateStr;
                document.getElementById('to_date').value = toDateStr;
                
                if (info.startStr.includes('T')) {
                    // TimeGrid selection (updates time too)
                    document.getElementById('start_time').value = info.startStr.split('T')[1].substring(0,5);
                    document.getElementById('end_time').value = info.endStr.split('T')[1].substring(0,5);
                } else {
                    // Month/Year view selection (defaults)
                    document.getElementById('start_time').value = '09:00';
                    document.getElementById('end_time').value = '17:00';
                }
                
                slotModal.classList.remove('hidden');
                slotModal.classList.add('flex');
            },
            eventClick: function(info) {
                if (info.event.extendedProps.type === 'booking') {
                    showBookingModal(info.event.title, info.event.extendedProps.status);
                } else if (info.event.extendedProps.type === 'slot') {
                    const id = info.event.id;
                    const date = info.event.start.toLocaleDateString();
                    // Use raw 24hr format for input fields
                    const startRaw = info.event.extendedProps.start_raw; 
                    const endRaw = info.event.extendedProps.end_raw;
                    openViewSlotModal(id, date, startRaw, endRaw);
                }
            }
        });
        calendar.render();

        // ADD Slot Form
        $('#slotForm').submit(function(e){
            e.preventDefault();
            let formData = $(this).serialize();
            $.post('<?= $_SERVER['PHP_SELF']; ?>', formData, function(data){
                if(data.status === 'success') {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 2000); 
                } else {
                    showAlert(data.message, 'error');
                    if (data.redirect) window.location.href = data.redirect;
                }
            }, 'json');
        });

        // UPDATE Slot Form
        $('#editSlotForm').submit(function(e){
            e.preventDefault();
            let formData = $(this).serialize();
            $.post('<?= $_SERVER['PHP_SELF']; ?>', formData, function(data){
                if(data.status === 'success') {
                    showAlert(data.message, 'success');
                     setTimeout(() => location.reload(), 2000); 
                } else {
                    showAlert(data.message, 'error');
                    if (data.redirect) window.location.href = data.redirect;
                }
            }, 'json');
        });

        // DELETE Slot Action
        $('#btnDeleteSlot').click(function(){
            if(confirm('Are you sure you want to delete this availability slot?')) {
                const id = $('#edit_slot_id').val();
                $.post('<?= $_SERVER['PHP_SELF']; ?>', {action: 'delete', slot_id: id}, function(data){
                    if(data.status === 'success') {
                        showAlert(data.message, 'success');
                         setTimeout(() => location.reload(), 2000); 
                    } else {
                        showAlert(data.message, 'error');
                        if (data.redirect) window.location.href = data.redirect;
                    }
                }, 'json');
            }
        });
    });
</script>

</body>
</html>
