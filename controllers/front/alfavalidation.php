<?php
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Request/Abstract.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Request/Card.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Request/Notify.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Invoice.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Address.php';

class Mobilpay_CcAlfavalidationModuleFrontController extends ModuleFrontController
{
	public $errorCode 		= 0;
	public $errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
	public $errorMessage	= '';
	//public global $kernel;

	public function postProcess()
	{

		file_put_contents("/home/ctbhub/public_html/navid/modules/mobilpay_cc/my-orderDetaile.log", "====> HELOO ALFA <===="."\r\n", FILE_APPEND);

		/*
         * If the module is not active anymore, no need to process anything.
         */
        if ($this->module->active == false
            || !$this->context->cart->id_address_delivery
            || !$this->context->cart->id_address_invoice)
            {
                Tools::redirect($this->context->link->getPageLink('order'));
            }

			$cart = $this->context->cart;

			if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
				Tools::redirect('index.php?controller=order&step=1');
	
				return;
			}

			// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
			$authorized = false;
			foreach (Module::getPaymentModules() as $module) {
				if ($module['name'] == 'mobilpay_cc') {
					$authorized = true;
					break;
				}
			}

			if (!$authorized) {
				die($this->trans('This payment method is not available.', [], 'Modules.Mobilpay_cc.Shop'));
			}

			$customer = new Customer($cart->id_customer);

			if (!Validate::isLoadedObject($customer)) {
				Tools::redirect('index.php?controller=order&step=1');
	
				return;
			}

			$currency = $this->context->currency;
			$total = (float) $cart->getOrderTotal(true, Cart::BOTH);
			
			$mailVars = array();


			switch($paymentAction){
				case 1:
					$paymentStatus = 1;//intval(Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action)));
				break;
				case 2:
					$paymentStatus = 2;//intval(Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action)));
				break;
				default:
					$paymentStatus = intval(Configuration::get('MPCC_OS_CONFIRMED'));
			}
			

			$this->module->validateOrder(
				(int) $cart->id,
				(int) $paymentStatus,
				$total,
				$this->module->displayName,
				null,
				$mailVars,
				(int) $currency->id,
				false,
				$customer->secure_key
			);

		
		// /**
		//  * Temporary
		//  */
		// echo $this->errorType;
		// echo $this->errorCode;
		// echo $this->errorMessage;
		// return true;
	}
}