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

			$paymentStatus = intval(Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action)));
							

		/**/
		if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 1 || 1)
			{
			if(isset($_POST['env_key']) && isset($_POST['data']) || 1)
			{
				#calea catre cheia privata
				#cheia privata este generata de mobilpay, accesibil in Admin -> Conturi de comerciant -> Detalii -> Setari securitate
				$privateKeyFilePath = dirname(__FILE__).'/../../Mobilpay/certificates/private.key';
				try{
					$objPmReq = Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($_POST['env_key'], $_POST['data'], $privateKeyFilePath);

					switch($objPmReq->objPmNotify->action)
						{
						#orice action este insotit de un cod de eroare si de un mesaj de eroare. Acestea pot fi citite folosind $cod_eroare = $objPmReq->objPmNotify->errorCode; respectiv $mesaj_eroare = $objPmReq->objPmNotify->errorMessage;
						#pentru a identifica ID-ul comenzii pentru care primim rezultatul platii folosim $id_comanda = $objPmReq->orderId;
						case 'confirmed':
							#cand action este confirmed avem certitudinea ca banii au plecat din contul posesorului de card si facem update al starii comenzii si livrarea produsului
							$errorMessage = $objPmReq->objPmNotify->getCrc();
							break;
						case 'confirmed_pending':
							#cand action este confirmed_pending inseamna ca tranzactia este in curs de verificare antifrauda. Nu facem livrare/expediere. In urma trecerii de aceasta verificare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
							$errorMessage = $objPmReq->objPmNotify->getCrc();
							break;
						case 'paid_pending':
							#cand action este paid_pending inseamna ca tranzactia este in curs de verificare. Nu facem livrare/expediere. In urma trecerii de aceasta verificare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
							$errorMessage = $objPmReq->objPmNotify->getCrc();
							break;
						case 'paid':
							#cand action este paid inseamna ca tranzactia este in curs de procesare. Nu facem livrare/expediere. In urma trecerii de aceasta procesare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
							$errorMessage = $objPmReq->objPmNotify->getCrc();
							break;
						case 'canceled':
							#cand action este canceled inseamna ca tranzactia este anulata. Nu facem livrare/expediere.
							$errorMessage = $objPmReq->objPmNotify->getCrc();
							break;
						case 'credit':
							#cand action este credit inseamna ca banii sunt returnati posesorului de card. Daca s-a facut deja livrare, aceasta trebuie oprita sau facut un reverse.
							$errorMessage = $objPmReq->objPmNotify->getCrc();
							break;
						default:
							$errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
							$errorCode 		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_ACTION;
							$errorMessage 	= 'mobilpay_refference_action paramaters is invalid';
							break;
						}
				} catch (Exception $e) {
					$this->errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY;
					$this->errorCode		= $e->getCode();
					$this->errorMessage 	= $e->getMessage();
				}
		
			}	else {
				$this->errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
				$this->errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS;
				$this->errorMessage 	= 'mobilpay.ro posted invalid parameters';
			}
		} else {
			$this->errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
			$this->errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_METHOD;
			$this->errorMessage 	= 'invalid request metod for payment confirmation';
		}

		

		
		if(!empty($objPmReq->orderId) && $objPmReq->objPmNotify->errorCode == 0) {
			$IpnOrderIdParts = explode('#', $objPmReq->orderId);
			$realOrderId = intval($IpnOrderIdParts[0]);
			$cart = new Cart($realOrderId);
			$customer = new Customer((int)$cart->id_customer);

			//real order id
			$order_id = Order::getOrderByCartId($realOrderId);

			if(intval($order_id)>0) {
				$order = new Order(intval($order_id));

				$history = new OrderHistory();
				$history->id_order = $order_id;

				$history->id_employee = 1;
				$carrier = new Carrier(intval($order->id_carrier), intval($order->id_lang));
				$templateVars = array('{followup}' => ($history->id_order_state == _PS_OS_SHIPPING_ AND $order->shipping_number) ? str_replace('@', $order->shipping_number, $carrier->url) : '');
				$history->addWithemail(true, $templateVars);

			}else{
				/**
				 * Add Order
				 */
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
			}
			  

		}

		

		/**
		 * Make XML as respunse
		 */
		
		header('Content-type: application/xml'); 
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>"; 
		if($this->errorCode == 0)
			{
			echo "<crc>{$this->errorMessage}</crc>";
			}
		else
			{
			echo "<crc error_type=\"{$this->errorType}\" error_code=\"{$this->errorCode}\">{$this->errorMessage}</crc>";
			}

		
		/*
		$this->context->smarty->assign([
            'errorType' => $this->errorType,
            'errorCode' => $this->errorCode,
            'errorMessage' => $this->errorMessage
        ]);
		$this->setTemplate('module:mobilpay_cc/views/templates/front/alfavalidation.tpl');
		
		// /**
		//  * Temporary
		//  */
		// echo $this->errorType;
		// echo $this->errorCode;
		// echo $this->errorMessage;
		// return true;
	}
}