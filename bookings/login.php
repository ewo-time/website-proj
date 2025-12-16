<?php
header('Content-Type: application/json');

// Database connection
$serverName = "MSI\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "VNR_DATABASE",
    "TrustServerCertificate" => true,
    "Authentication" => "ActiveDirectoryIntegrated"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

// okay, we're checking if we got the account in here.
if ($conn === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . print_r(sqlsrv_errors(), true)
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$email = $_POST['email'];
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide both email and password'
    ]);
    exit;
}
   
$loginsql = "SELECT * FROM VNR_BOOKINGS WHERE EMAIL = '$email'";
$logincf = sqlsrv_query($conn, $loginsql);

if ($logincf === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Query execution failed: ' . print_r(sqlsrv_errors(), true)
    ]);
    exit;
}

$usercf = sqlsrv_fetch_array($logincf, SQLSRV_FETCH_ASSOC);

if (!$usercf) {
    echo json_encode([
        'success' => false,
        'message' => 'No account found with that email'
    ]);
    exit;
}

if ($password !== $usercf['PASSWORD']) {
    echo json_encode([
        'success' => false,
        'message' => 'Incorrect password'
    ]);
    exit;
}

// then we try to login or smt
session_start();
$_SESSION['customer_logged_in'] = true;
$_SESSION['customer_email'] = $usercf['EMAIL'];
$_SESSION['customer_name'] = $usercf['NAME'];
$_SESSION['booking_reference'] = $usercf['BOOKING_REFERENCE'];


echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'name' => $usercf['NAME'],
    'redirect' => 'dashboard.php'
]);

sqlsrv_close($conn);

?>
