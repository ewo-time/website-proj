<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: check.html');
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

// Get customer's booking
$email = $_SESSION['customer_email'];
$sql = "SELECT * FROM VNR_BOOKINGS WHERE EMAIL = '$email'";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

$booking = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$booking) {
    die("Booking not found.");
}

// Format dates for display
$checkinDate = $booking['CHECKIN_DATE'] ? $booking['CHECKIN_DATE']->format('F j, Y') : 'Not specified';
$checkoutDate = $booking['CHECKOUT_DATE'] ? $booking['CHECKOUT_DATE']->format('F j, Y') : 'Not specified';
$createdAt = $booking['DATE_OF_CREATION'] ? $booking['DATE_OF_CREATION']->format('F j, Y g:i A') : 'Unknown';

// Status badge color
$statusClass = 'warning';
if ($booking['STATUS'] === 'Confirmed') {
    $statusClass = 'success';
} elseif ($booking['STATUS'] === 'Cancelled') {
    $statusClass = 'danger';
}

sqlsrv_close($conn);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Booking — Voyage en Route</title>
    
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
  </head>
  <body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
      <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="../home.html">
          <i class="fas fa-plane-departure text-primary me-2"></i>Voyage en Route
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-1"></i>My Dashboard
              </a>
            </li>
            <li class="nav-item">
              <span class="nav-link text-primary fw-bold">
                <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($booking['NAME']); ?>
              </span>
            </li>
            <li class="nav-item">
              <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Page Header -->
    <header class="bg-light py-5">
      <div class="container">
        <h1 class="display-5 fw-bold mb-2">My Booking</h1>
        <p class="lead text-muted">View and manage your travel booking</p>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container my-5">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          
          <!-- Booking Reference Card -->
          <div class="card shadow-sm mb-4 border-primary">
            <div class="card-body text-center py-4" style="background: linear-gradient(135deg, #f5f1e8 0%, #e8e0d5 100%);">
              <h5 class="text-muted mb-2">Your Booking Reference</h5>
              <h2 class="text-primary fw-bold mb-0"><?php echo htmlspecialchars($booking['BOOKING_REFERENCE']); ?></h2>
            </div>
          </div>

          <!-- Status Card -->
          <div class="card shadow-sm mb-4">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="card-title mb-1">Booking Status</h5>
                  <p class="text-muted small mb-0">Last updated: <?php echo $createdAt; ?></p>
                </div>
                <span class="badge bg-<?php echo $statusClass; ?> fs-5 px-4 py-2">
                  <?php echo htmlspecialchars($booking['STATUS']); ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Personal Information -->
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
              <h5 class="mb-0"><i class="fas fa-user me-2 text-primary"></i>Personal Information</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="small text-muted">Full Name</label>
                  <p class="mb-0 fw-bold"><?php echo htmlspecialchars($booking['NAME']); ?></p>
                </div>
                <div class="col-md-6">
                  <label class="small text-muted">Email Address</label>
                  <p class="mb-0 fw-bold"><?php echo htmlspecialchars($booking['EMAIL']); ?></p>
                </div>
                <div class="col-md-6">
                  <label class="small text-muted">Phone Number</label>
                  <p class="mb-0 fw-bold"><?php echo $booking['PHONE'] ? htmlspecialchars($booking['PHONE']) : 'Not provided'; ?></p>
                </div>
                <div class="col-md-6">
                  <label class="small text-muted">Number of Travelers</label>
                  <p class="mb-0 fw-bold"><?php echo $booking['GUESTS']; ?> guest(s)</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Travel Details -->
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
              <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Travel Details</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="small text-muted">Destination</label>
                  <p class="mb-0 fw-bold fs-5"><?php echo $booking['DESTINATION'] ? htmlspecialchars($booking['DESTINATION']) : 'Not specified'; ?></p>
                </div>
                <div class="col-md-6">
                  <label class="small text-muted">Check-in Date</label>
                  <p class="mb-0 fw-bold"><?php echo $checkinDate; ?></p>
                </div>
                <div class="col-md-6">
                  <label class="small text-muted">Check-out Date</label>
                  <p class="mb-0 fw-bold"><?php echo $checkoutDate; ?></p>
                </div>
              </div>
            </div>
          </div>

          <!-- Additional Information -->
          <?php if ($booking['MESSAGE']): ?>
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
              <h5 class="mb-0"><i class="fas fa-comment me-2 text-primary"></i>Special Requests</h5>
            </div>
            <div class="card-body">
              <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['MESSAGE'])); ?></p>
            </div>
          </div>
          <?php endif; ?>

          <!-- Contact Support -->
          <div class="card shadow-sm bg-light">
            <div class="card-body text-center">
              <h5 class="card-title">Need Help?</h5>
              <p class="card-text text-muted">Our travel experts are available 24/7 to assist you.</p>
              <div class="row">
                <div class="col-md-4">
                  <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                  <p class="small mb-0">+63 (917) 123 4567</p>
                </div>
                <div class="col-md-4">
                  <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                  <p class="small mb-0">help@vnrtravels.com</p>
                </div>
                <div class="col-md-4">
                  <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                  <p class="small mb-0">24/7 Support</p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
      <div class="container">
        <div class="text-center small text-white-50">
          &copy; 2025 Voyage en Route — Travel Guide. All rights reserved.
        </div>
      </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>