<?php
session_start();
require_once '../db_connect.php';

// Ensure staff is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle new slot submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['available_date'], $_POST['start_time'], $_POST['end_time'])) {
    $available_date = $_POST['available_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if ($available_date && $start_time && $end_time) {
        $stmt = $conn->prepare("INSERT INTO dentist_calendar (user_id, available_date, start_time, end_time) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $available_date, $start_time, $end_time);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Slot added successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add slot.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    }
    exit;
}

// Fetch existing slots
$slots = [];
$result = $conn->prepare("SELECT id, available_date, start_time, end_time FROM dentist_calendar WHERE user_id = ? ORDER BY available_date, start_time");
$result->bind_param("i", $user_id);
$result->execute();
$res = $result->get_result();

function formatTime12($time) {
    return date("g:i A", strtotime($time));
}

while ($row = $res->fetch_assoc()) {
    $slots[] = [
        'title' => formatTime12($row['start_time']) . ' - ' . formatTime12($row['end_time']),
        'start' => $row['available_date'] . 'T' . $row['start_time'],
        'end' => $row['available_date'] . 'T' . $row['end_time']
    ];
}
$result->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dentist Availability Calendar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<style>
  body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; margin:0; padding:0; }
  .wrapper { display: flex; min-height: 100vh; }
  aside { flex: 0 0 250px; background: #343a40; color: #fff; }
  main { flex: 1; padding: 20px; }
  #calendar { max-width: 900px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
</style>
</head>
<body>
<div class="wrapper">
    <aside>
        <?php include 'admin_sidebar.php'; ?>
    </aside>
    <main>
        <div class="container-fluid">
            <h2 class="text-center mb-4">My Calendar</h2>
            <div id="calendar"></div>
        </div>
    </main>
</div>

<!-- Modal for Adding Slot -->
<div class="modal fade" id="slotModal" tabindex="-1" aria-labelledby="slotModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="slotForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="slotModalLabel">Add Availability Slot</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="available_date" class="form-label">Date</label>
            <input type="date" class="form-control" id="available_date" name="available_date" required>
          </div>
          <div class="mb-3">
            <label for="start_time" class="form-label">Start Time</label>
            <input type="time" class="form-control" id="start_time" name="start_time" required>
          </div>
          <div class="mb-3">
            <label for="end_time" class="form-label">End Time</label>
            <input type="time" class="form-control" id="end_time" name="end_time" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Slot</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    function formatTime12(time) {
        let [h, m] = time.split(':');
        h = parseInt(h);
        let ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        selectable: true,
        selectMirror: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: <?= json_encode($slots); ?>,
        eventColor: '#0d6efd',
        eventDisplay: 'block',
        displayEventTime: false,
        slotMinTime: "06:00:00",
        slotMaxTime: "20:00:00",
        select: function(info) {
            // Auto-fill modal with selected time
            let date = info.start.toISOString().substring(0,10);
            let start = info.start.toTimeString().substring(0,5);
            let end = info.end.toTimeString().substring(0,5);

            document.getElementById('available_date').value = date;
            document.getElementById('start_time').value = start;
            document.getElementById('end_time').value = end;

            var slotModal = new bootstrap.Modal(document.getElementById('slotModal'));
            slotModal.show();
        }
    });

    calendar.render();

    // Handle form submission
    $('#slotForm').submit(function(e){
        e.preventDefault();
        let formData = $(this).serialize();

        $.post('<?= $_SERVER['PHP_SELF']; ?>', formData, function(data){
            alert(data.message);
            if(data.status === 'success') {
                let start12 = formatTime12($('#start_time').val());
                let end12 = formatTime12($('#end_time').val());

                calendar.addEvent({
                    title: start12 + ' - ' + end12,
                    start: $('#available_date').val() + 'T' + $('#start_time').val(),
                    end: $('#available_date').val() + 'T' + $('#end_time').val(),
                    color: '#0d6efd'
                });
                var slotModalEl = document.getElementById('slotModal');
                var modal = bootstrap.Modal.getInstance(slotModalEl);
                modal.hide();
            }
        }, 'json');
    });
});
</script>

</body>
</html>
