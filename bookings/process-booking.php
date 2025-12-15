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