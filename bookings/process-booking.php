<?php
header('Content-Type: application/json');

$serverName="MSI\SQLEXPRESS";
$connectionOptions=[
"Database"=>"VNR_DATABASE",
"TrustServerCertificate"=>true,
"Authentication"=>"ActiveDirectoryIntegrated"
];

$conn=sqlsrv_connect($serverName, $connectionOptions);
if($conn===false) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// first, i kinda found out how to create a booking reference num below
function bookingReferenceGen() {
    $prefix = "VNR";
    $year = date('Y');

    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomPlaceholder = '';

    for ($i=0; $i<6; $i++) {
        $randomPlaceholder .= $chars[rand(0, strlen($chars) - 1)];
    }

    $finalref = $prefix . '-' .  $year . '-' .  $randomPlaceholder;

    return $finalref;
}

// next, we check if one of the references repeat
function bookingChecker($conn, $finalref) {
    $bchkrsql = "SELECT COUNT(*) AS REFERENCE_COUNT FROM VNR_BOOKINGS WHERE BOOKING_REFERENCE = ?";
    $bchkrparams = array($finalref);

    $bchkstmt = sqlsrv_query($conn, $bchkrsql, $bchkrparams);

    if ($bchkstmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $bchkrow = sqlsrv_fetch_array($bchkstmt, SQLSRV_FETCH_ASSOC);
    return $bchkrow['REFERENCE_COUNT'] > 0;
}

// oh this is fun, we have to make a unique reference everytime
function uniqueRefGen($conn) {
    do {
        $finalref = bookingReferenceGen();
    } while (bookingChecker($conn, $finalref));

    return $finalref;
}

// okay now we put data sia
if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    $fname = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $destination = $_POST['destination'];
    $chkin_date = $_POST['checkin'];
    $chkout_date = $_POST['checkout'];
    $guests = intval($_POST['guests']);
    $message = $_POST['message'];

    // Validate required fields
    if (empty($fname) || empty($email)) {
        echo json_encode([
            'success' => false,
            'message' => 'Name and email are required fields'
        ]);
        exit;
    }

    $book_ref = uniqueRefGen($conn);

    $datasql = "INSERT INTO VNR_BOOKINGS (BOOKING_REFERENCE, NAME, EMAIL, PASSWORD, PHONE, DESTINATION, CHECKIN_DATE, CHECKOUT_DATE, GUESTS, MESSAGE)
    VALUES ('$book_ref', '$fname', '$email', '$password', '$phone', '$destination', '$chkin_date', '$chkout_date', '$guests', '$message')";

    $confirm = sqlsrv_query($conn, $datasql);

    if ($confirm === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create booking',
            'error' => sqlsrv_errors()
        ]);
    } else {
        // Send confirmation email via SendGrid API (non-blocking)
        $emailSent = false;
        try {
            $sendgridApiKey = 'API_key_here'; // Replace with your actual API key
            $url = 'https://api.sendgrid.com/v3/mail/send';
            
            $subject = "Booking Confirmation - Voyage en Route (Reference: $book_ref)";
            
            $message_body = "Dear $fname,\n\n";
            $message_body .= "Thank you for choosing Voyage en Route! Your booking inquiry has been received successfully.\n\n";
            $message_body .= "BOOKING REFERENCE: $book_ref\n\n";
            $message_body .= "Booking Details:\n";
            $message_body .= "- Name: $fname\n";
            $message_body .= "- Email: $email\n";
            $message_body .= "- Destination: $destination\n";
            $message_body .= "- Check-in: $chkin_date\n";
            $message_body .= "- Check-out: $chkout_date\n";
            $message_body .= "- Guests: $guests\n";
            $message_body .= "- Status: Pending Review\n\n";
            $message_body .= "Our travel experts will review your booking and get back to you within 24-48 hours.\n\n";
            $message_body .= "You can view your booking status anytime by logging into your account at:\n";
            $message_body .= "http://localhost/website-proj/bookings/check.html\n\n";
            $message_body .= "Need Help?\n";
            $message_body .= "Phone: +63 (917) 123 4567\n";
            $message_body .= "Email: help@vnrtravels.com\n\n";
            $message_body .= "© 2025 Voyage en Route - Travel Guide. All rights reserved.";
            
            $data = [
                'personalizations' => [[
                    'to' => [['email' => $email, 'name' => $fname]],
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
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Log response for debugging
            error_log("SendGrid Response Code: $httpCode");
            error_log("SendGrid Response: $response");
            if ($curlError) {
                error_log("SendGrid cURL Error: $curlError");
            }
            
            $emailSent = ($httpCode >= 200 && $httpCode < 300);
        } catch (Exception $e) {
            // Email failed, but don't stop the booking process
            $emailSent = false;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully!',
            'booking_reference' => $book_ref,
            'name' => $fname,
            'email' => $email,
            'destination' => $destination
        ]);
    }
    
    sqlsrv_close($conn);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

?>