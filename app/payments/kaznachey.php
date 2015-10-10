<?php
use Tygh\Registry;error_reporting(E_ALL);ini_set("display_errors", 1);

if ( !defined('AREA') ) { die('Access denied'); }
	$ExternalLibPath =realpath(dirname(__FILE__)).DS.'kaznacheyLib.php';	require_once ($ExternalLibPath);

if (defined('PAYMENT_NOTIFICATION')) {
    if ($mode == 'notify') {

	$kaznachey = new kaznacheyLib();
	$order_id = isset($_GET['OrderId'])?$_GET['OrderId']:false;
	if ($_GET['Result'] == 'success'){
		$kaznachey->success_page($order_id);
		die;
	}
	if ($_GET['Result'] == 'deferred'){
		$kaznachey->deferred_page($order_id);
		die;
	}
	
	$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');	
	$hrpd = json_decode($HTTP_RAW_POST_DATA);

	if(isset($hrpd->MerchantInternalPaymentId))	{
			if($hrpd->ErrorCode == 0){
				$pp_response['order_status'] = 'P';
				$pp_response["reason_text"] = '';
				$pp_response["kaznachey"] = $pay_msg . $hrpd->MerchantInternalPaymentId.$test_msg ;
				$pp_response["transaction_id"] = $hrpd->MerchantInternalPaymentId;
				fn_finish_payment(intval($hrpd->MerchantInternalPaymentId), $pp_response);
			}
		}
	}
}else{
	$order_id = $order_info['repaid'] ? $order_id . '_' . $order_info['repaid'] : $order_id;
	$kaznachey = new kaznacheyLib($processor_data);
	$kaznachey->createOrder($order_id, $order_info);
exit;
}
?>