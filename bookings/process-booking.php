<?php
$serverName="MSI\SQLEXPRESS";
$connectionOptions=[
"Database"=>"VNR_DATABASE",
"TrustServerCertificate"=>true,
"Authentication"=>"ActiveDirectoryIntegrated"
];

$conn=sqlsrv_connect($serverName, $connectionOptions);
if($conn===false)
die(print_r(sqlsrv_errors(),true));
else echo 'Connection Success';



?>