<?php
class Mobilpay_CcCronModuleFrontController extends ModuleFrontController
{
    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /** @var bool */
    public $ajax;

    public function display()
    {
        $this->ajax = 1;

        if (php_sapi_name() !== 'cli') {
            $this->ajaxDie('Forbidden call.');
        }

        // Additional token checks

        // ...
        file_put_contents("/home/ctbhub/public_html/navid/modules/mobilpay_cc/my-orderDetaile.log", "====> CRON. CONTROLLER  <===="."\r\n", FILE_APPEND);
        $this->ajaxDie("hello\n");
    }
}