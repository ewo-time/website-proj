<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Database connection
$serverName = "MSI\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "VNR_DATABASE",
    "TrustServerCertificate" => true,
    "Authentication" => "ActiveDirectoryIntegrated"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Database connection failed: " . print_r(sqlsrv_errors(), true));
}

// Get all bookings
$sql = "SELECT * FROM VNR_BOOKINGS ORDER BY DATE_OF_CREATION DESC";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

$bookings = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $bookings[] = $row;
}

// Count statistics
$totalBookings = count($bookings);
$pendingCount = 0;
$confirmedCount = 0;
$cancelledCount = 0;

foreach ($bookings as $booking) {
    if ($booking['STATUS'] === 'Pending') $pendingCount++;
    elseif ($booking['STATUS'] === 'Confirmed') $confirmedCount++;
    elseif ($booking['STATUS'] === 'Cancelled') $cancelledCount++;
}

sqlsrv_close($conn);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard — Voyage en Route</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    
    <style>
      .admin-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
      }
      
      .stat-card {
        border-left: 4px solid;
        transition: transform 0.2s;
      }
      
      .stat-card:hover {
        transform: translateY(-5px);
      }
      
      .table-actions button {
        min-width: 90px;
      }
    </style>
  </head>
  <body>
    <!-- Admin Navigation -->
    <nav class="navbar navbar-dark sticky-top" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
      <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
          <i class="fas fa-user-shield me-2"></i>Admin Dashboard
        </a>
        <div class="d-flex align-items-center">
          <span class="text-white me-3">
            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['admin_username']); ?>
          </span>
          <a href="logout.php" class="btn btn-light btn-sm">
            <i class="fas fa-sign-out-alt me-1"></i>Logout
          </a>
        </div>
      </div>
    </nav>

    <!-- Page Header -->
    <header class="admin-header py-4">
      <div class="container-fluid">
        <h1 class="display-6 fw-bold mb-0">
          <i class="fas fa-tachometer-alt me-2"></i>Booking Management
        </h1>
        <p class="mb-0 opacity-75">Manage all customer bookings and inquiries</p>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container-fluid my-4">
      
      <!-- Statistics Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-3">
          <div class="card stat-card shadow-sm h-100" style="border-left-color: #3b82f6;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-muted mb-1">Total Bookings</h6>
                  <h2 class="fw-bold mb-0"><?php echo $totalBookings; ?></h2>
                </div>
                <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                  <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card stat-card shadow-sm h-100" style="border-left-color: #f59e0b;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-muted mb-1">Pending</h6>
                  <h2 class="fw-bold mb-0"><?php echo $pendingCount; ?></h2>
                </div>
                <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                  <i class="fas fa-clock fa-2x text-warning"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card stat-card shadow-sm h-100" style="border-left-color: #10b981;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-muted mb-1">Confirmed</h6>
                  <h2 class="fw-bold mb-0"><?php echo $confirmedCount; ?></h2>
                </div>
                <div class="bg-success bg-opacity-10 rounded-circle p-3">
                  <i class="fas fa-check-circle fa-2x text-success"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card stat-card shadow-sm h-100" style="border-left-color: #ef4444;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-muted mb-1">Cancelled</h6>
                  <h2 class="fw-bold mb-0"><?php echo $cancelledCount; ?></h2>
                </div>
                <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                  <i class="fas fa-times-circle fa-2x text-danger"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bookings Table -->
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Bookings</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Reference</th>
                  <th>Customer Name</th>
                  <th>Email</th>
                  <th>Destination</th>
                  <th>Check-in</th>
                  <th>Guests</th>
                  <th>Status</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($bookings)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                    No bookings found
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($bookings as $booking): ?>
                  <tr>
                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($booking['BOOKING_REFERENCE']); ?></td>
                    <td><?php echo htmlspecialchars($booking['NAME']); ?></td>
                    <td><?php echo htmlspecialchars($booking['EMAIL']); ?></td>
                    <td><?php echo $booking['DESTINATION'] ? htmlspecialchars($booking['DESTINATION']) : '<span class="text-muted">Not specified</span>'; ?></td>
                    <td><?php echo $booking['CHECKIN_DATE'] ? $booking['CHECKIN_DATE']->format('M j, Y') : '<span class="text-muted">N/A</span>'; ?></td>
                    <td><?php echo $booking['GUESTS']; ?></td>
                    <td>
                      <?php
                      $statusClass = 'warning';
                      if ($booking['STATUS'] === 'Confirmed') $statusClass = 'success';
                      elseif ($booking['STATUS'] === 'Cancelled') $statusClass = 'danger';
                      ?>
                      <span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($booking['STATUS']); ?></span>
                    </td>
                    <td class="text-center table-actions">
                      <?php 
                      // Trim and check status (case-insensitive)
                      $currentStatus = trim($booking['STATUS']);
                      if (strcasecmp($currentStatus, 'Pending') === 0): 
                      ?>
                        <button class="btn btn-sm btn-success me-1" onclick="updateStatus('<?php echo htmlspecialchars($booking['BOOKING_REFERENCE']); ?>', 'Confirmed')">
                          <i class="fas fa-check me-1"></i>Confirm
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="updateStatus('<?php echo htmlspecialchars($booking['BOOKING_REFERENCE']); ?>', 'Cancelled')">
                          <i class="fas fa-times me-1"></i>Cancel
                        </button>
                      <?php else: ?>
                        <span class="text-muted small">No actions available</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
      function updateStatus(bookingRef, newStatus) {
        const action = newStatus === 'Confirmed' ? 'confirm' : 'cancel';
        
        if (!confirm(`Are you sure you want to ${action} booking ${bookingRef}?`)) {
          return;
        }
        
        // Send update request
        fetch('update-status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `booking_reference=${encodeURIComponent(bookingRef)}&status=${encodeURIComponent(newStatus)}`
        })
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            alert(`Booking ${action}ed successfully!`);
            location.reload();
          } else {
            alert(`Error: ${result.message}`);
          }
        })
        .catch(error => {
          alert('Connection error. Please try again.');
        });
      }
    </script>
  </body>
</html>