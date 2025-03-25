<?php
date_default_timezone_set('America/New_York');
$con = mysqli_connect("localhost","root","","nutrition");
if (mysqli_connect_errno())
{
echo "Failed to connect to MySQL: " . mysqli_connect_error();
}else{
echo "";
}
?>
