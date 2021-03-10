<?php
file_put_contents("/home/ctbhub/public_html/navid/modules/mobilpay_cc/my-orderDetaile.log", "====> CRON.PHP - START <===="."\r\n", FILE_APPEND);

$_GET['fc'] = 'module';
$_GET['module'] = 'mobilpay_cc';
$_GET['controller'] = 'cron';

require_once dirname(__FILE__) . '/../../index.php';

