<?php
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Request/Abstract.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Request/Card.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Request/Notify.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Invoice.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Address.php';

class Mobilpay_CcBetavalidationModuleFrontController extends ModuleFrontController
{
	public $errorCode 		= 0;
	public $errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
	public $errorMessage	= '';
	public $beta = 0;
	//public global $kernel;

	public function initContent() {
		parent::initContent();
		$this->setTemplate('module:mobilpay_cc/views/templates/front/alfavalidation.tpl');
	}

	public function postProcess()
	{
		if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') == 0)
			{

			if(isset($_POST['env_key']) && isset($_POST['data']))
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
					$result = $this->module->validateOrder(
						(int) $cart->id,
						(int) Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action)),
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

		$this->context->smarty->assign([
            'errorType' => $this->errorType,
            'errorCode' => $this->errorCode,
            'errorMessage' => $this->errorMessage
        ]);
	}
}