<?php
/*
Plugin Name: wp-pasargad-pay-channels
Plugin URI:
Description: wordpress pasargad payment channel
Version: 1.0.0
Author: Saeed Torabi
Author URI: https://saeedtrb.com
License: GPLv2 or later
*/

/**
 * @param ../wp-pay-channels/wp-pay-channels.php PayChannelTransaction $transaction
 * @return ../wp-pay-channels/wp-pay-channels.php PayChannelTransaction $transaction
 */
function filter_pasargad_pay_channel_pay_request($transaction){
    $merchantCode 		= "0000000";										// Merchant Code
    $terminalCode 		= "0000000"; 										// Terminal Code
    $privateKey 		= "";
    $action             = '1003';


    date_default_timezone_set('Asia/Tehran');
    $transaction->setPayDate(new DateTime());

    #region (Encrypt Data)
    #TODO: Download libraries File's Here : https://vrl.ir/pep
    if (!class_exists('RSAProcessor')) { require_once("libraries/RSAProcessor.class.php"); }
    $processor 			= new RSAProcessor($privateKey,RSAKeyType::XMLString);

    $data 				= "#". $merchantCode ."#". $terminalCode ."#". $transaction->getId() ."#". $transaction->getInvoiceDate()->format("Y/m/d H:i:s") ."#". $transaction->getAmount() ."#". $transaction->getCallbackUrl() ."#". $action ."#". $transaction->getInvoiceDate()->format("Y/m/d H:i:s") ."#";
    $data 				= sha1($data,true);
    $data 				= $processor->sign($data);
    $sign 			    = base64_encode($data);
    #endregion

    #region (Store params in transaction for PayForm);
    $transaction->setData([
        'InvoiceNumber' => $transaction->getId(),
        'invoiceDate' => $transaction->getInvoiceDate()->format("Y/m/d H:i:s"),
        'amount' => $transaction->getAmount(),
        'terminalCode' => $terminalCode,
        'merchantCode' => $merchantCode,
        'redirectAddress' => $transaction->getCallbackUrl(),
        'timeStamp' => $transaction->getInvoiceDate()->format("Y/m/d H:i:s"),
        'action' => $action,
        'sign' => $sign

    ]);
    #endregion

    return $transaction;
}
/**
 * @param ../wp-pay-channels/wp-pay-channels.php PayChannelTransaction $transaction
 * @param ../wp-pay-channels/wp-pay-channels.php PayChannelPayForm $payForm
 * @return ../wp-pay-channels/wp-pay-channels.php PayChannelPayForm $payForm
 */
function filter_pasargad_pay_channel_pay_form($payForm, $transaction){

    $payForm->setAction('https://pep.shaparak.ir/gateway.aspx');
    $payForm->setMethod('post');
    $payForm->setBody($transaction->getData());

    return $payForm;
}

function filter_pasargad_pay_channel_pay_answer($transaction){
    return $transaction;
}

function filter_pasargad_pay_channel_pay_refunds($transaction){
    #TODO: impalement pasargad refunds transaction
    return $transaction;
}

function filter_pay_channels_pasargad_options($channels){
	$channels[] = [
		'name' => 'pasargad',
		'label' => 'پاسارگاد',
		'image' => 'http://s.ir/pasargad.jpg'
	];
	return $channels;
}

function init_pasargad_pay_channel(){

    add_filter('wp_pay_channels_channels_options','filter_pay_channels_pasargad_options');
    add_filter('pasargad_pay_channel_pay_request','filter_pasargad_pay_channel_pay_request');
    add_filter('pasargad_pay_channel_pay_form','filter_pasargad_pay_channel_pay_form');
    add_filter('pasargad_pay_channel_pay_answer','filter_pasargad_pay_channel_pay_answer');
    add_filter('pasargad_pay_channel_pay_refunds','filter_pasargad_pay_channel_pay_refunds');

}
add_action('init','init_pasargad_pay_channel');

?>