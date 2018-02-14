<?php
/**
 * @file Contains \Drupal\authorize_forms\Payments
 */
 
namespace Drupal\authorize_forms;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;



/**
 * Process a payment with Authorize.Net
 */
class Payments {




    public function chargeCreditCard($amount){


        // Get vars from config
        $config = \Drupal::config('authorize_forms.settings');

        // Create a merchantAuthenticationType object with authentication details
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($config->get('api_login_id'));
        $merchantAuthentication->setTransactionKey($config->get('transaction_key'));

        // Set the transaction's refId
        $refId = 'ref' . time();

        // Create the payment object for a payment nonce
        $opaqueData = new AnetAPI\OpaqueDataType();
        $opaqueData->setDataDescriptor("COMMON.ACCEPT.INAPP.PAYMENT");
        $opaqueData->setDataValue($_POST['dataValue']);

        /*Create the payment data for a credit card
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setcardNumber($_POST['cardNumber']);
        $creditCard->setExpirationDate($_POST['expiryYear'].'-'.$_POST['expiryMonth']);
        $creditCard->setcardCode($_POST['cardCode']);*/

        // Add the payment data to a paymentType object
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setOpaqueData($opaqueData);

        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber("10101");
        $order->setDescription($_POST['description']);

        // Set the customer's Bill To address
        $customerAddress = new AnetAPI\CustomerAddressType();
        $customerAddress->setFirstName($_POST['firstName']);
        $customerAddress->setLastName($_POST['lastName']);
        $customerAddress->setAddress($_POST['address']);
        $customerAddress->setCity($_POST['city']);
        $customerAddress->setState($_POST['state']);
        $customerAddress->setZip($_POST['zip']);
        $customerAddress->setCountry("USA");
        $customerAddress->setPhoneNumber($_POST['phone']);

        // Set the customer's identifying information
        $customerData = new AnetAPI\CustomerDataType();
        $customerData->setType("individual");
        $customerData->setId($refId);
        $customerData->setEmail($_POST['emailAddress']);

        // Add values for transaction settings
        $duplicateWindowSetting = new AnetAPI\SettingType();
        $duplicateWindowSetting->setSettingName("duplicateWindow");
        $duplicateWindowSetting->setSettingValue("60");

        // Add some merchant defined fields. These fields won't be stored with the transaction, but will be echoed back in the response.

        // Loop vars and pull paymentoptions
        $purchaseOptions = [];
        foreach($_POST as $key => $value){
            if(strpos($key,'purchaseoptions-') !== false){
                $purchaseOptions[str_replace('purchaseoptions-','',$key)] = $value;
            }
        }
        // Create custom fields
        $customfields = [];
        foreach($purchaseOptions as $key => $value){
            $customfields[$key] = new AnetAPI\UserFieldType();
            $customfields[$key]->setName($key);
            $customfields[$key]->setValue($value);
        }

        // Create a TransactionRequestType object and add the previous objects to it
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($amount);
        $transactionRequestType->setOrder($order);
        $transactionRequestType->setPayment($paymentOne);
        $transactionRequestType->setBillTo($customerAddress);
        $transactionRequestType->setCustomer($customerData);
        $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
        // Add custom fields to transaction request
        foreach($customfields as $customfield){
            $transactionRequestType->addToUserFields($customfield);
        }

        // Assemble the complete transaction request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);

        // Create the controller and get the response
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);


        if ($response != null) {

            $responseText = '';

            // Check to see if the API request was successfully received and acted upon
            if ($response->getMessages()->getResultCode() == 'Ok') {
                // Since the API request was successful, look for a transaction response
                // and parse it to display the results of authorizing the card
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $responseText .= " Successfully created transaction with Transaction ID: " . $tresponse->getTransId() . "<br />";
                    $responseText .= " Transaction Response Code: " . $tresponse->getResponseCode() . "<br />";
                    $responseText .= " Message Code: " . $tresponse->getMessages()[0]->getCode() . "<br />";
                    $responseText .= " Auth Code: " . $tresponse->getAuthCode() . "<br />";
                    $responseText .= " Description: " . $tresponse->getMessages()[0]->getDescription() . "<br />";
                    $responseOutcome = 'success';
                } else {
                    $responseText .= "Transaction Failed \n";
                    if ($tresponse->getErrors() != null) {
                        $responseText .= " Error Code  : " . $tresponse->getErrors()[0]->getErrorCode() . "<br />";
                        $responseText .= " Error Message : " . $tresponse->getErrors()[0]->getErrorText() . "<br />";
                    }
                    $responseOutcome = 'fail';
                }

                return [
                    'outcome' => $responseOutcome,
                    'text' => $responseText
                ];

                // Or, print errors if the API request wasn't successful
            } else {
                $responseText .= "Transaction Failed \n";
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getErrors() != null) {
                    $responseText .= " Error Code  : " . $tresponse->getErrors()[0]->getErrorCode() . "<br />";
                    $responseText .= " Error Message : " . $tresponse->getErrors()[0]->getErrorText() . "<br />";
                } else {
                    $responseText .= " Error Code  : " . $response->getMessages()->getMessage()[0]->getCode() . "<br />";
                    $responseText .= " Error Message : " . $response->getMessages()->getMessage()[0]->getText() . "<br />";
                }

                $responseOutcome = 'fail';

                return [
                    'outcome' => $responseOutcome,
                    'text' => $responseText
                ];

            }

        } else {

            echo  "No response returned \n";

        }

        return $response;

    }


}
