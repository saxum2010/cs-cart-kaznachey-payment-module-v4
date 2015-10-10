<?php
use Tygh\Storage;

class kaznacheyLib
{
	public	$urlGetMerchantInfo = 'http://payment.kaznachey.net/api/PaymentInterface/CreatePayment';
	public	$urlGetClientMerchantInfo = 'http://payment.kaznachey.net/api/PaymentInterface/GetMerchatInformation';
	public	$merchantGuid;
	public	$merchnatSecretKey;
	
	public function __construct($processor_data=false)
	{
		if($processor_data['processor_params']['kaznachey_merchantGuid']){
			$this->merchantGuid = $processor_data['processor_params']['kaznachey_merchantGuid'];
		}
		if($processor_data['processor_params']['kaznachey_merchnatSecretKey']){
			$this->merchnatSecretKey = $processor_data['processor_params']['kaznachey_merchnatSecretKey'];
		}
	}  
  
   function createOrder($order_id, $order_info, $selectedPaySystemId = false)
   {
	$success_url = fn_url("payment_notification.notify?payment=kaznachey&order_id=$order_id&transmode=success", AREA, 'http');			
	$result_url = fn_url("payment_notification.notify?payment=kaznachey&order_id=$order_id&transmode=done", AREA, 'http');			
		
	$currency = 'UAH';

	$i = 0;
	$amount = 0;
	$product_count =  0;
  	foreach($order_info['products'] as $k=>$pr_item){

		$products[$i]['ImageUrl'] =  $this->getProductImage($pr_item['product_id']);
		$products[$i]['ProductItemsNum'] = number_format($pr_item['amount'], 2, '.', '');
		$products[$i]['ProductName'] = $pr_item['product'];
		
		$products[$i]['ProductPrice'] = number_format($this->getCurrenciesPrice($pr_item['price'])/$pr_item['amount'], 2, '.', '');
		$amount += $this->getCurrenciesPrice($pr_item['price']);
	
		$products[$i]['ProductId'] = $pr_item['product_id'];
		$product_count += $products[$i]['ProductItemsNum'];
		$i++; 
	}

    $paymentDetails = Array(
       "MerchantInternalPaymentId"=>$order_id,
       "MerchantInternalUserId"=>$order_info['user_id'],
       "EMail"=>$order_info['email'],
       "PhoneNumber"=>$order_info['phone'],
       "CustomMerchantInfo"=>'',
       "StatusUrl"=>"$result_url",
       "ReturnUrl"=>"$success_url",
       "BuyerCountry"=>$order_info['b_country'],
       "BuyerFirstname"=>$order_info['b_firstname'],
       "BuyerPatronymic"=>'',
       "BuyerLastname"=>$order_info['b_lastname'],
       "BuyerStreet"=>$order_info['b_address'],
       "BuyerZone"=>'',
       "BuyerZip"=>$order_info['b_zipcode'],
       "BuyerCity"=>$order_info['b_city'],
       "DeliveryFirstname"=>'',
       "DeliveryLastname"=>"",
       "DeliveryZip"=>"", 
       "DeliveryCountry"=>'',
       "DeliveryPatronymic"=>"",
       "DeliveryStreet"=>'',
       "DeliveryCity"=>'',
       "DeliveryZone"=>"",
    );

	$product_count = number_format($product_count, 2, '.', '');	
	$amount = number_format($amount, 2, '.', '');	
		
	$selectedPaySystemId = (isset($selectedPaySystemId) && ($selectedPaySystemId>0)) ? $selectedPaySystemId : $this->GetMerchnatInfo(false, 1);

	$signature = md5(
		$this->merchantGuid.
		"$amount".
		"$product_count".
		$paymentDetails["MerchantInternalUserId"].
		$paymentDetails["MerchantInternalPaymentId"].
		$selectedPaySystemId.
		$this->merchnatSecretKey
	);
	
	$request = Array(
        "SelectedPaySystemId"=>$selectedPaySystemId,
        "Products"=>$products,
        "PaymentDetails"=>$paymentDetails,
        "Signature"=>$signature,
        "MerchantGuid"=>$this->merchantGuid,
		"Currency"=> $currency
    );
	$res = $this->sendRequestKaznachey($this->urlGetMerchantInfo, json_encode($request));
	$result = json_decode($res,true);

	if($result['ErrorCode'] != 0){
		return false;
	}
	
		echo base64_decode($result["ExternalForm"]);
		
	}
	
	function getCurrenciesPrice($value) {
		$cscart_currencies = db_get_row("SELECT * FROM `cscart_currencies` WHERE `currency_code` = 'UAH'");
		if(isset($cscart_currencies['coefficient']) && $cscart_currencies['coefficient'] > 0)
		{
			return $value * $cscart_currencies['coefficient'];
		}else{
			return $value;
		}
	}
	
	function getProductImage($product_id) {
		$image_path = db_get_row("SELECT image_path FROM cscart_images WHERE image_id = (SELECT detailed_id FROM cscart_images_links WHERE object_type='product' AND type='M' AND object_id='$product_id')");
		
		//return Storage::instance('images')->getAbsolutePath($image_path['image_path']);
		
		if(file_exists($_SERVER["DOCUMENT_ROOT"].'/images/detailed/0/' . $image_path['image_path']))
		{
			return 'http://'.$_SERVER['SERVER_NAME'] . '/images/detailed/0/' . $image_path['image_path'];
		}
		return 'http://'.$_SERVER['SERVER_NAME'] . '/images/detailed/1/' . $image_path['image_path'];
	}
	
	function base64_url_encode($input) {
		return strtr(base64_encode($input), '+/=', '-_,');
	}

	function base64_url_decode($input) {
		return base64_decode(strtr($input, '-_,', '+/='));
	}
   		
	function sendRequestKaznachey($url,$data)
	{
		$curl =curl_init();
		if (!$curl)
			return false;

		curl_setopt($curl, CURLOPT_URL,$url );
		curl_setopt($curl, CURLOPT_POST,true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, 
				array("Expect: ","Content-Type: application/json; charset=UTF-8",'Content-Length: ' 
					. strlen($data)));
		curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,True);
		$res =  curl_exec($curl);
		curl_close($curl);

		return $res;
	}

		function GetMerchnatInfo($id = false, $def = false)
		{
			$requestMerchantInfo = Array(
				"MerchantGuid"=>$this->merchantGuid,
				"Signature"=>md5($this->merchantGuid.$this->merchnatSecretKey)
			);

			$resMerchantInfo = json_decode($this->sendRequestKaznachey($this->urlGetClientMerchantInfo , json_encode($requestMerchantInfo)),true); 
			if($id)
			{
				foreach ($resMerchantInfo["PaySystems"] as $key=>$paysystem)
				{
					if($paysystem['Id'] == $id)
					{
						return $paysystem;
					}
				}
			}elseif($def)
			{
				foreach ($resMerchantInfo["PaySystems"] as $key=>$paysystem)
				{
					return $paysystem['Id'];
				}
			}else{
				return $resMerchantInfo;
			}
		}

		function GetTermToUse()
		{
			$requestMerchantInfo = Array(
				"MerchantGuid"=>$this->merchantGuid,
				"Signature"=>md5($this->merchantGuid.$this->merchnatSecretKey)
			);

			$resMerchantInfo = json_decode($this->sendRequestKaznachey($this->urlGetClientMerchantInfo , json_encode($requestMerchantInfo)),true); 

			return $resMerchantInfo["TermToUse"];

		}

		function getPaySystems()
		{
			$cc_types = $this->GetMerchnatInfo();
			if(isset($cc_types["PaySystems"]))
			{
				$box = '
				<div id="kznd"><label for="cc_types">Выберите способ оплаты</label><select name="cc_types" id="cc_types" >';
				$term_url = $this->GetTermToUse();
				foreach ($cc_types["PaySystems"] as $paysystem)
				{
					$box .= "<option value='$paysystem[Id]'>$paysystem[PaySystemName]</option>";
				}
				$box .= '</select><br><input type="checkbox" checked="checked" value="1" name="cc_agreed" id="cc_agreed"><label for="cc_agreed"><a href="'.$term_url.'" >Согласен с условиями использования</a></label>
				</div>';
				$box .= "<script type=\"text/javascript\">
				(function(){ 
				var cc_a = jQuery('#cc_agreed'),
					ds = jQuery('#ds');
					 cc_a.on('click', function(){
						if(cc_a.is(':checked')){	
							jQuery('#kznd').find('.error').text('');
							ds.attr('disabled', false);
						}else{
							cc_a.next().after('<span class=\"error\" style=\"color:red\">Примите условие!</span>');
							ds.attr('disabled', true);
						}
					 });
				})(); 
				</script> ";
				
				print iconv("UTF-8","CP1251",$box);

			}
		}

		function setSession($data)
		{
			foreach($data as $key=>$item)
			{
				$_SESSION[$key] = $item;
			}
		}

		function home_url(){
			header("Location: ".'http://'.$_SERVER['SERVER_NAME']);
		}

		public function success_page($order_id = false) {
			print "<style>
			body{background-color: #527496; font: normal 13px Verdana,sans-serif;}
			.message_container{background-color: #fff; width: 50%; text-align:center; margin: auto; margin-top: 100px; padding: 50px;}
			.valid {color: green;}
			.invalid {color: red;}
			</style>
			<div class='message_container'> <h4><p class='invalid'>Ваш заказ №$order_id Спасибо за Ваш заказ №$order_id! Ваш заказ оплачен</p></h4> 
				<input type='button' value=' Закрыть ' onCLick=\"location='http://".$_SERVER['HTTP_HOST']."';\">
			</div>";
			unset($_SESSION['cart']);
		}

		public function deferred_page($order_id = false) {
			print "<style>
			body{background-color: #527496; font: normal 13px Verdana,sans-serif;}
			.message_container{background-color: #fff; width: 50%; text-align:center; margin: auto; margin-top: 100px; padding: 50px;}
			.valid {color: green;}
			.invalid {color: red;}
			</style>
			<div class='message_container'> <h4><p class='invalid'>Ваш заказ №$order_id Спасибо за Ваш заказ №$order_id! Вы сможете оплатить его после проверки менеджером. Ссылка на оплату будет выслана Вам по электронной почте.</p></h4> 
				<input type='button' value=' Закрыть ' onCLick=\"location='http://".$_SERVER['HTTP_HOST']."';\">
			</div>";
		}
  
}
?>
