<?php
header('Content-Type:text/plain');
$urls=['https://api.ipify.org','https://ifconfig.me/ip','https://icanhazip.com'];
foreach($urls as $u){$ip=@trim(file_get_contents($u));
 if(filter_var($ip,FILTER_VALIDATE_IP)){echo $ip; exit;}}
echo 'Could not detect IP';
