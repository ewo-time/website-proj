<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
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
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get form data
$bookingRef = $_POST['booking_reference'] ?? '';
$newStatus = $_POST['status'] ?? '';

// Validate inputs
if (empty($bookingRef) || empty($newStatus)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Validate status value
if (!in_array($newStatus, ['Confirmed', 'Cancelled'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
    ]);
    exit;
}

// Update booking status
$sql = "UPDATE VNR_BOOKINGS SET STATUS = '$newStatus' WHERE BOOKING_REFERENCE = '$bookingRef'";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status: ' . print_r(sqlsrv_errors(), true)
    ]);
    exit;
}

// Check if any rows were affected
$rowsAffected = sqlsrv_rows_affected($stmt);

if ($rowsAffected === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking not found'
    ]);
    exit;
}

// Try to send status update email via SendGrid (non-blocking)
try {
    $detailSql = "SELECT * FROM VNR_BOOKINGS WHERE BOOKING_REFERENCE = '$bookingRef'";
    $detailStmt = sqlsrv_query($conn, $detailSql);
    $booking = sqlsrv_fetch_array($detailStmt, SQLSRV_FETCH_ASSOC);

    if ($booking) {
        $sendgridApiKey = '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'; // Replace with your actual API key
        $url = 'https://api.sendgrid.com/v3/mail/send';
        
        $subject = "Booking $newStatus - Voyage en Route (Reference: $bookingRef)";
        
        $statusMessage = $newStatus === 'Confirmed' 
            ? 'Great news! Your booking has been confirmed.' 
            : 'We regret to inform you that your booking has been cancelled.';
        
        $checkinDate = $booking['CHECKIN_DATE'] ? $booking['CHECKIN_DATE']->format('F j, Y') : 'N/A';
        $checkoutDate = $booking['CHECKOUT_DATE'] ? $booking['CHECKOUT_DATE']->format('F j, Y') : 'N/A';
        
        $message_body = "Dear {$booking['NAME']},\n\n";
        $message_body .= "BOOKING STATUS UPDATE: $newStatus\n\n";
        $message_body .= "$statusMessage\n\n";
        $message_body .= "Booking Reference: $bookingRef\n\n";
        $message_body .= "Booking Details:\n";
        $message_body .= "- Destination: {$booking['DESTINATION']}\n";
        $message_body .= "- Check-in: $checkinDate\n";
        $message_body .= "- Check-out: $checkoutDate\n";
        $message_body .= "- Guests: {$booking['GUESTS']}\n\n";
        
        if ($newStatus === 'Confirmed') {
            $message_body .= "We're excited to help you plan your journey! Our team will be in touch with you shortly with further details.\n\n";
        } else {
            $message_body .= "If you have any questions or would like to discuss alternative options, please don't hesitate to contact us.\n\n";
        }
        
        $message_body .= "You can view your booking at:\n";
        $message_body .= "http://localhost/website-proj/bookings/check.html\n\n";
        $message_body .= "Need Help?\n";
        $message_body .= "Phone: +63 (917) 123 4567\n";
        $message_body .= "Email: help@vnrtravels.com\n\n";
        $message_body .= "© 2025 Voyage en Route - Travel Guide. All rights reserved.";
        
        $data = [
            'personalizations' => [[
                'to' => [['email' => $booking['EMAIL'], 'name' => $booking['NAME']]],
                'subject' => $subject
            ]],
            'from' => [
                'email' => 'diamondthekidrs44@gmail.com',
                'name' => 'Voyage en Route'
            ],
            'content' => [[
                'type' => 'text/plain',
                'value' => $message_body
            ]]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $sendgridApiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_exec($ch);
        curl_close($ch);
    }
} catch (Exception $e) {
    // Email failed, but don't stop the update process
}

echo json_encode([
    'success' => true,
    'message' => 'Booking status updated successfully',
    'booking_reference' => $bookingRef,
    'new_status' => $newStatus
]);

sqlsrv_close($conn);
?>