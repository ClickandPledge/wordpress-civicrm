<?php
/**
 *
 * @package CRM
 * @author Kamran Razvan <Kamran@clickandpledge.com>
 * $Id: clickandpledge.php
 * Written & Contributed by http://clickandpledge.com
 */
 
/*
 * Payment Processor class for Click & Pledge
 */
class com_clickandpledge_payment_clickandpledge extends CRM_Core_Payment {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = null;

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  static protected $_mode = null;
  
  var $responsecodes = array(2055=>'AccountGuid is not valid',2056=>'AccountId is not valid',2057=>'Username is not valid',2058=>'Password is not valid',2059=>'Invalid recurring parameters',2060=>'Account is disabled',2101=>'Cardholder information is null',2102=>'Cardholder information is null',2103=>'Cardholder information is null',2104=>'Invalid billing country',2105=>'Credit Card number is not valid',2106=>'Cvv2 is blank',2107=>'Cvv2 length error',2108=>'Invalid currency code',2109=>'CreditCard object is null',2110=>'Invalid card type ',2111=>'Card type not currently accepted',2112=>'Card type not currently accepted',2210=>'Order item list is empty',2212=>'CurrentTotals is null',2213=>'CurrentTotals is invalid',2214=>'TicketList lenght is not equal to quantity',2215=>'NameBadge lenght is not equal to quantity',2216=>'Invalid textonticketbody',2217=>'Invalid textonticketsidebar',2218=>'Invalid NameBadgeFooter',2304=>'Shipping CountryCode is invalid',2401=>'IP address is null',2402=>'Invalid operation',2501=>'WID is invalid',2502=>'Production transaction is not allowed. Contact support for activation.',2601=>'Invalid character in a Base-64 string',2701=>'ReferenceTransaction Information Cannot be NULL',2702=>'Invalid Refrence Transaction Information',2703=>'Expired credit card',2805=>'eCheck Account number is invalid',2807=>'Invalid payment method',2809=>'Invalid payment method',2811=>'eCheck payment type is currently not accepted',2812=>'Invalid check number',5001=>'Declined (general)',5002=>'Declined (lost or stolen card)',5003=>'Declined (fraud)',5004=>'Declined (Card expired)',5005=>'Declined (Cvv2 is not valid)',5006=>'Declined (Insufficient fund)',5007=>'Declined (Invalid credit card number)');

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode             = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName    = ts('ClickandPledge');
  }

  /**
   * Singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor) {
      $processorName = $paymentProcessor['name'];
      if (self::$_singleton[$processorName] === NULL ) {
          self::$_singleton[$processorName] = new self($mode, $paymentProcessor);
      }
      return self::$_singleton[$processorName];
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $config = CRM_Core_Config::singleton();
    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('The "Account ID" is not set in the ClickandPledge Payment Processor settings.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('The "API Account GUID" is not set in the ClickandPledge Payment Processor settings.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in a nice formatted array (or an error object)
   * @public
   */
  function doDirectPayment(&$params) {
	$cookedParams = $params; //Transalated params
    CRM_Utils_Hook::alterPaymentProcessorParams( $this, $params, $cookedParams );
	
	$allowed_currencies = array('USD','EUR','CAD','GBP');
	$package_path = explode('CRM',dirname(__FILE__));
	
		  
	  if(!file_exists($package_path[0].DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'ClickandPledge'.DIRECTORY_SEPARATOR.'Countries.xml')){
	return self::errorExit(9003, 'It appears that there is no countries XML file. If you continue to have problems please contact the site administrator.');
	}
	
	if(!in_array($params['currencyID'],$allowed_currencies)){
	return self::errorExit(9099, 'It appears that selected currency is not supported with this payment processor. If you continue to have problems please contact the site administrator.');
	}
	
	if (isset($params['is_recurr']) && $params['is_recur'] == TRUE) {
	
			  
		if( isset( $params['frequency_interval'] ) && $params['frequency_interval'] > 1 )
	  {
		return self::errorExit(9099, "Error: [We are not supporting recurring intervals or recurring interval should be one. Please contact administrator]");	
	  }
	 	 
	if( ( isset( $params['installments'] ) && $params['installments'] <= 1 && $params['installments'] != '' ) || ( isset( $params['installments'] ) && $params['installments'] >= 1000 && $params['installments'] != ''  ) )
	  {
		return self::errorExit(9099, "Error: [Number of installments should be between 2 to 999. Please check the installments]");	
	  }
	  
	  if(!in_array( ucfirst( $params['frequency_unit'] ), array('Week','2 weeks','2 Weeks','Month','2 Months','Quarter','6 Months','Year') ))
	  {
		return self::errorExit(9099, "Error: [Periodicity should be 'Week','2 Weeks','Month','2 Months','Quarter','6 Months','Year'. Please contact administrator.]");	
	  }
	  
	 // $strParam = $this->doRecurPayment( $params, $package_path[0]);
	  }
	  
	  if(   ( isset( $params['billing_first_name'] ) && strlen( $params['billing_first_name'] ) > 40 ) )
	  {
		return self::errorExit(9099, "Error: [Billing First Name should be less than 40 chanracters]");	
	  }
	  
	  if (!preg_match("/^([a-zA-Z0-9\.\,\#\&\-\ \']{2,40})+$/", $params['billing_first_name'])) 
	  {
		return self::errorExit(9099, "Error: [In valid Billing First Name.]");	
	  }
	  
	  if(   ( isset( $params['billing_middle_name'] ) && strlen( $params['billing_middle_name'] ) > 1 ) )
	  {
		return self::errorExit(9099, "Error: [Billing Middle Name should be 1 chanracter only]");	
	  }
	  
	  if(   ( isset( $params['billing_last_name'] ) && strlen( $params['billing_last_name'] ) > 50 ) )
	  {
		return self::errorExit(9099, "Error: [Billing Last Name should be less than 50 characters]");	
	  }
	  if (!preg_match("/^([a-zA-Z0-9\.\,\#\&\-\ \']{0,50})+$/", $params['billing_last_name'])) 
	  {
		return self::errorExit(9099, "Error: [In valid Billing Last Name.]");	
	  }
	  
	  if(   ( isset( $params['billing_city-5'] ) && strlen( $params['billing_city-5'] ) > 40 ) )
	  {
		return self::errorExit(9099, "Error: [Billing City Name should be less than 40 chanracters]");	
	  }
	  
	  if(   ( isset( $params['billing_city-5'] ) && strlen( $params['billing_city-5'] ) > 40 ) )
	  {
		return self::errorExit(9099, "Error: [Billing City Name should be less than 40 chanracters]");	
	  }
	  
	  if(   ( isset( $params['billing_postal_code-5'] ) && strlen( $params['billing_postal_code-5'] ) > 20 ) )
	  {
		return self::errorExit(9099, "Error: [Billing Postal should be less than 20 chanracters]");	
	  }
	  
	$item_parts = explode(':',$params['description']);
	if( trim( $item_parts[0] ) == 'Online Event Registration' )
	{
		 $query = "SELECT civicrm_event.id as event_id,civicrm_event.title as title, civicrm_contribution_type.is_deductible, civicrm_contribution_type.name,civicrm_event.is_email_confirm,civicrm_event.confirm_email_text  FROM  civicrm_event  
		inner join civicrm_contribution_type on civicrm_event.contribution_type_id=civicrm_contribution_type.id
		WHERE  civicrm_event.title = '" . trim( $item_parts[1] ) . "' and payment_processor = '".$params['payment_processor']."' and civicrm_event.registration_end_date >= '".date('Y-m-d')."'";
		$dao = CRM_Core_DAO::executeQuery($query);
		$rows = 0;
		if ($dao->fetch()) {
			$rows = $dao->N;
		}
	
		if( $rows > 1 )
		{
			return self::errorExit(9099, "Error: [Event Name should be unique. Please contact administrator.]");
		}
	}
		
	 $strParam =  $this->buildXML( $params, $package_path[0] );
	
	  $connect = array('soap_version' => SOAP_1_1, 'trace' => 1, 'exceptions' => 0);
		 $client = new SoapClient('https://paas.cloud.clickandpledge.com/paymentservice.svc?wsdl', $connect);
		 $soapParams = array('instruction'=>$strParam);
		 
		 $response = $client->Operation($soapParams);
	
		// NOTE: You will not necessarily get a string back, if the request failed for
		//       any reason, the return value will be the boolean false.
		if (($response === FALSE)) {
		  return self::errorExit(9006, "Error: Connection to payment gateway failed - no data returned.");
		}
	
		$ResultCode=$response->OperationResult->ResultCode;
		$transation_number=$response->OperationResult->TransactionNumber;
		 
		
		if($ResultCode=='0')
		{
			$response_message=$response->OperationResult->ResultData;
			//Success
			$params['trxn_id'] = $transation_number;
			$params['trxn_result_code'] = $response_message;
			return $params;
		 }
		 else
		{
			if( in_array( $ResultCode, array( 2051,2052,2053 ) ) )
			{
				$AdditionalInfo = $response->OperationResult->AdditionalInfo;
			}
			else
			{
				if( isset( $this->responsecodes[$ResultCode] ) )
				{
					$AdditionalInfo = $this->responsecodes[$ResultCode];
				}
				else
				{
					$AdditionalInfo = 'Unknown error';
				}
			}
				
				$response_message = $ResultCode.':'.$AdditionalInfo;
			   return self::errorExit(9099, "Error: [" . $response_message . "]");			     
		}
     	
	
	

	
   
		 
  }

 
 /**
   * Submit a recurring payment using Click & Pledge's PHP API:
   *
   * @param  array $params assoc array of input parameters for this transaction

   * @return array the result in a nice formatted array (or an error object)
   * @public
   */
  function doRecurPayment( &$params, $package_path) {

	return $strParam = $this->buildXML( $params, $package_path );

		}
  
	
  /**
   * Checks to see if invoice_id already exists in db
   *
   * @param  int     $invoiceId   The ID to check
   *
   * @return bool                  True if ID exists, else false
   */
  function _checkDupe($invoiceId) {
    require_once 'CRM/Contribute/DAO/Contribution.php';
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->invoice_id = $invoiceId;
    return $contribution->find();
  }
  
  function safeString( $str,  $length=1, $start=0 )
	{
		return substr( htmlentities( $str ), $start, $length );
	}
	
	function safeStringReplace( $str )
	{
		$str = str_replace( '&', '&amp;', trim( $str ) );
		return substr( $str, 0, 500 );
	}
	
  function buildXML( $params, $xmlpath )
  {
	
	$configValues = $this->_paymentProcessor;

		$dom = new DOMDocument('1.0', 'UTF-8');
             $root = $dom->createElement('CnPAPI', '');
             $root->setAttribute("xmlns","urn:APISchema.xsd");
             $root = $dom->appendChild($root);
			  
			 $version=$dom->createElement("Version","1.5");
    		 $version=$root->appendChild($version);
			 
			 $engine = $dom->createElement('Engine', '');
             $engine = $root->appendChild($engine);
			 
			 $application = $dom->createElement('Application','');
			 $application = $engine->appendChild($application);
    
			 $applicationid=$dom->createElement('ID','CnP_CiviCRM_WordPress'); //
			 $applicationid=$application->appendChild($applicationid);
			
			 $applicationname=$dom->createElement('Name','CnP_CiviCRM_WordPress'); //CnP_CiviCRM_WordPress#CnP_CiviCRM_Joomla#CnP_CiviCRM_Drupal
			 $applicationid=$application->appendChild($applicationname);
			
			 $applicationversion=$dom->createElement('Version','2.001.000.000.20130304');  //2.000.000.000.20130103 Version-Minor change-Bug Fix-Internal Release Number -Release Date
			 $applicationversion=$application->appendChild($applicationversion);
    
    		 $request = $dom->createElement('Request', '');
    		 $request = $engine->appendChild($request);
    
    		 $operation=$dom->createElement('Operation','');
    		 $operation=$request->appendChild( $operation );
			 
			 $operationtype=$dom->createElement('OperationType','Transaction');
    		 $operationtype=$operation->appendChild($operationtype);
    
    		 $ipaddress=$dom->createElement('IPAddress',$params['ip_address']);
    		 $ipaddress=$operation->appendChild($ipaddress);
    
			 $authentication=$dom->createElement('Authentication','');
    		 $authentication=$request->appendChild($authentication);
			
    		 $accounttype=$dom->createElement('AccountGuid',$configValues['password'] ); 
    		 $accounttype=$authentication->appendChild($accounttype);
    
    		 $accountid=$dom->createElement('AccountID',$configValues['user_name'] );
    		 $accountid=$authentication->appendChild($accountid);
			 
			 $order=$dom->createElement('Order','');
    		 $order=$request->appendChild($order);
			
			if( $this->_mode == 'live' ){
				$orderMode = 'Production';
			}else{
				$orderMode = 'Test';
			}
    		 $ordermode=$dom->createElement('OrderMode',$orderMode);
    		 $ordermode=$order->appendChild($ordermode);
			
				//Check if it is Personal Campaign Page
				if ( (isset( $params['contributionPageID'] ) && $params['contributionPageID'] != '') || (isset( $params['campaign_id'] ) && $params['campaign_id'] != '') || ( isset( $params['pcp_made_through_id'] ) && $params['pcp_made_through_id'] != '' ) )
				{
					//Check if it is any Campaign Pages attached to this contribution
					if ( isset( $params['pcp_made_through_id'] ) && $params['pcp_made_through_id'] != '' )
					{
						$query = "SELECT *  FROM  civicrm_pcp  WHERE id = '" . $params['pcp_made_through_id'] . "'";
						$dao = CRM_Core_DAO::executeQuery($query);
						if ($dao->fetch()) {
							$Campaign=$dom->createElement('Campaign',$this->safeString(trim($dao->title), 73).'-p-'.$params['pcp_made_through_id']);
							$Campaign=$order->appendChild($Campaign);
						}
					}
					elseif((isset( $params['contributionPageID'] ) && $params['contributionPageID'] != ''))
					{
						$query = "SELECT *  FROM  civicrm_contribution_page  WHERE  id = '" . $params['contributionPageID'] . "'";
						$dao = CRM_Core_DAO::executeQuery($query);
						if ($dao->fetch()) {
							if( $dao->campaign_id != '' )
							{
								$query_campaign = "SELECT *  FROM  civicrm_campaign  WHERE  id = '" . $dao->campaign_id . "'";
								$dao_campaign = CRM_Core_DAO::executeQuery($query_campaign);
								if ($dao_campaign->fetch()) 
								{
									$Campaign=$dom->createElement('Campaign',$this->safeString(trim($dao_campaign->title), 73).'-a-'.$dao->campaign_id);
									$Campaign=$order->appendChild($Campaign);
								}
							}
						}
						
					}
					elseif((isset( $params['campaign_id'] ) && $params['campaign_id'] != '') && (!isset( $params['contributionPageID'] )))
					{
					
						$query_campaign = "SELECT *  FROM  civicrm_campaign  WHERE  id = '" . $params['campaign_id'] . "'";
						$dao_campaign = CRM_Core_DAO::executeQuery($query_campaign);
						if ($dao_campaign->fetch()) 
						{
							$Campaign=$dom->createElement('Campaign',$this->safeString(trim($dao_campaign->title), 73).'-a-'.$params['campaign_id']);
							$Campaign=$order->appendChild($Campaign);
						}
							
					}
				}
				
    		 $cardholder=$dom->createElement('CardHolder','');
    		 $cardholder=$order->appendChild($cardholder);
			 
			 $billinginfo=$dom->createElement('BillingInformation','');
    		 $billinginfo=$cardholder->appendChild($billinginfo);

			 $billfirst_name=$dom->createElement('BillingFirstName',$this->safeString($params['first_name'],50));
			 $billfirst_name=$billinginfo->appendChild($billfirst_name);
				//echo $this->safeString($params['first_name'],50);
				//die();
			 if( isset( $params['middle_name'] ) && $params['middle_name'] != '' )
			 {
			 $billmiddle_name=$dom->createElement('BillingMI',$this->safeString($params['middle_name'],1));
			 $billmiddle_name=$billinginfo->appendChild($billmiddle_name);
			 }
			 
			 $billlast_name=$dom->createElement('BillingLastName',$this->safeString($params['billing_last_name'],50));
			 $billlast_name=$billinginfo->appendChild($billlast_name);
		
			if (isset($params['email']) && $params['email'] != '') {
				$email = $params['email'];
			}
			elseif( array_key_exists('email-5',$params ))
			 {
			 	$email = $params['email-5'];
			}
			elseif( isset( $params['email-Primary'] ) && $params['email-Primary'] != '' )
			{
				$email = $params['email-Primary'];
			}
			else
			{
				$email = '';
			}
				
			if( $email != '' )
			{
			 $bill_email=$dom->createElement('BillingEmail',$email);
			 $bill_email=$billinginfo->appendChild($bill_email);
			}
			
			$keys = array_keys( $params );
			$phone_field = '';
			
			for( $i = 0; $i < count( $keys ); $i++ )
			{
				if( strpos( $keys[$i],'phone' )  !== false )
				{
					$phone_field = $keys[$i];
					break;
				}
			}
			if( $phone_field != '' )
			{
				if( $params[$phone_field] != '' )
				{
				$bill_phone=$dom->createElement('BillingPhone',$params[$phone_field]);
				$bill_phone=$billinginfo->appendChild($bill_phone);
				}
			}
			
			 $billingaddress=$dom->createElement('BillingAddress','');
			 $billingaddress=$cardholder->appendChild($billingaddress);
		
			 $billingaddress1=$dom->createElement('BillingAddress1',$this->safeString($params['street_address'],100));
			 $billingaddress1=$billingaddress->appendChild($billingaddress1);
			
			 $billing_city=$dom->createElement('BillingCity',$this->safeString($params['city'],50));
			 $billing_city=$billingaddress->appendChild($billing_city);
			
			 $billing_state=$dom->createElement('BillingStateProvince',$this->safeString($params['state_province'],50));
			 $billing_state=$billingaddress->appendChild($billing_state);
			
			 $billing_zip=$dom->createElement('BillingPostalCode',$this->safeString( $params['postal_code'],20 ));
			 $billing_zip=$billingaddress->appendChild($billing_zip);
			 //echo $xmlpath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'ClickandPledge'.DIRECTORY_SEPARATOR.'Countries.xml';
			 //die();
			 $countries = simplexml_load_file( $xmlpath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'ClickandPledge'.DIRECTORY_SEPARATOR.'Countries.xml' );
			 foreach( $countries as $country ){
				if( $country->attributes()->Abbrev == $params['country'] ){
				$billing_country_id = $country->attributes()->Code;
				} 
			 }
			 $billing_country=$dom->createElement('BillingCountryCode',str_pad($billing_country_id, 3, "0", STR_PAD_LEFT));
			 $billing_country=$billingaddress->appendChild($billing_country);
			 
			 
			 $customfieldlist = $dom->createElement('CustomFieldList','');
             $customfieldlist = $cardholder->appendChild($customfieldlist);
	

			 if(  isset( $params['participant_role_id'] ) && $params['participant_role_id'] != '' )
			 {
				 $query = "SELECT v.label as label FROM civicrm_option_value v, civicrm_option_group g WHERE v.option_group_id = g.id AND g.name = 'participant_role' AND g.is_active = 1 AND v.is_active = 1 and v.value = '".$params['participant_role_id']."'  ORDER BY v.weight";
				 
				 $participant_role = CRM_Core_Dao::singleValueQuery( $query );
				 if( $participant_role != '' )
				 {
				 $customfield1 = $dom->createElement('CustomField','');
				 $customfield1 = $customfieldlist->appendChild($customfield1);
				
				
				 $fieldname1 = $dom->createElement('FieldName','Participant Role');
				 $fieldname1 = $customfield1->appendChild($fieldname1);
				 
				 $fieldvalue1 = $dom->createElement('FieldValue',$this->safeString( $participant_role,50 ));
				 $fieldvalue1 = $customfield1->appendChild($fieldvalue1);
				 }
			 }
			 
			 if(  isset( $params['contributionType_name'] ) && $params['contributionType_name'] != '' )
			 {
				 $customfield1 = $dom->createElement('CustomField','');
				 $customfield1 = $customfieldlist->appendChild($customfield1);
				
				
				 $fieldname1 = $dom->createElement('FieldName','Contribution Type');
				 $fieldname1 = $customfield1->appendChild($fieldname1);
				 
				 $fieldvalue1 = $dom->createElement('FieldValue',$this->safeString( $params['contributionType_name'],50 ));
				 $fieldvalue1 = $customfield1->appendChild($fieldvalue1);
			 }
			 

			 $customfield4 = $dom->createElement('CustomField','');
			 $customfield4 = $customfieldlist->appendChild($customfield4);
			 
			 $fieldname4 = $dom->createElement('FieldName','Description');
			 $fieldname4 = $customfield4->appendChild($fieldname4);
	
			 $fieldvalue4 = $dom->createElement('FieldValue',$this->safeString ($params['description'], 50));
			 $fieldvalue4 = $customfield4->appendChild($fieldvalue4);
			 
			 if( isset( $params['additional_participants'] ) && $params['additional_participants'] != '' )
			 {
				 $customfield5 = $dom->createElement('CustomField','');
				 $customfield5 = $customfieldlist->appendChild($customfield5);
				 
				 $fieldname5 = $dom->createElement('FieldName','Total Participants');
				 $fieldname5 = $customfield5->appendChild($fieldname5);
		
				 $fieldvalue5 = $dom->createElement('FieldValue',($params['additional_participants']+1));
				 $fieldvalue5 = $customfield5->appendChild($fieldvalue5);
			 }
			 
			 $paymentmethod=$dom->createElement('PaymentMethod','');
			 $paymentmethod=$cardholder->appendChild($paymentmethod);
			
			 $payment_type=$dom->createElement('PaymentType','CreditCard');
			 $payment_type=$paymentmethod->appendChild($payment_type);
			
			 $creditcard=$dom->createElement('CreditCard','');
			 $creditcard=$paymentmethod->appendChild($creditcard);
			
			if (isset($params['cardholder_name'])) {
				$credit_card_name = $params['cardholder_name'];
			}
			else {
				$credit_card_name = $params['first_name'] . " ";
				if (isset($params['middle_name']) && !empty($params['middle_name'])) {
					$credit_card_name .= $params['middle_name'] . " ";
				}
				$credit_card_name .= $params['last_name'];
			}
	
			 $credit_name=$dom->createElement('NameOnCard',$this->safeString( $credit_card_name, 50));
			 $credit_name=$creditcard->appendChild($credit_name);
			
			 $credit_number=$dom->createElement('CardNumber',$this->safeString( str_replace(' ', '', $params['credit_card_number']), 17));
			 $credit_number=$creditcard->appendChild($credit_number);
			
			 $credit_cvv=$dom->createElement('Cvv2',$params['cvv2']);
			 $credit_cvv=$creditcard->appendChild($credit_cvv);
		
			 $credit_expdate=$dom->createElement('ExpirationDate',str_pad($params['credit_card_exp_date']['M'],2,'0',STR_PAD_LEFT) ."/" .substr($params['credit_card_exp_date']['Y'],2,2));
			 $credit_expdate=$creditcard->appendChild($credit_expdate);
			
			 $orderitemlist=$dom->createElement('OrderItemList','');
             $orderitemlist=$order->appendChild($orderitemlist);			
			
			
				$orderitem=$dom->createElement('OrderItem','');
				$orderitem=$orderitemlist->appendChild($orderitem);
				 
				//$itemid=$dom->createElement('ItemID',$params['contact_id']);
				$itemid=$dom->createElement('ItemID','1');
				$itemid=$orderitem->appendChild($itemid);
				
				$item_parts = explode(':',$params['description']);
				$itemname=$dom->createElement('ItemName',$this->safeString(trim($item_parts[1]), 50));
				$itemname=$orderitem->appendChild($itemname);
				
				$quntity=$dom->createElement('Quantity','1');
				$quntity=$orderitem->appendChild($quntity);
				 
				$unitprice=$dom->createElement('UnitPrice',($params['amount']*100));
				$unitprice=$orderitem->appendChild($unitprice);
				
				
				$is_email_confirm = FALSE;
				$confirm_email_text = '';
				if( isset( $params['contributionTypeID'] ) && $params['contributionTypeID'] != '' )
				{
				$query = "SELECT *  FROM  civicrm_contribution_type  WHERE  id = '" . $params['contributionTypeID'] . "'";
					$dao = CRM_Core_DAO::executeQuery($query);
					if ($dao->fetch()) {
						if( $dao->is_deductible == '1' )
						{
							$unit_deduct=$dom->createElement('UnitDeductible',($params['amount']*100));
							$unit_deduct=$orderitem->appendChild($unit_deduct);	
						}
						
					}
				}
				elseif( isset( $params['contributionType_name'] ) && $params['contributionType_name'] != '' )
				{
				$query = "SELECT *  FROM  civicrm_contribution_type  WHERE  name = '" . $params['contributionType_name'] . "'";
					$dao = CRM_Core_DAO::executeQuery($query);
					if ($dao->fetch()) {
						if( $dao->is_deductible == '1' )
						{
							$unit_deduct=$dom->createElement('UnitDeductible',($params['amount']*100));
							$unit_deduct=$orderitem->appendChild($unit_deduct);	
						}
						
					}
				}
				elseif( trim( $item_parts[0] ) == 'Online Event Registration' )
				{
					$query = "SELECT civicrm_event.id as event_id,civicrm_event.title as title, civicrm_contribution_type.is_deductible, civicrm_contribution_type.name,civicrm_event.is_email_confirm,civicrm_event.confirm_email_text  FROM  civicrm_event  
					inner join civicrm_contribution_type on civicrm_event.contribution_type_id=civicrm_contribution_type.id
					WHERE  civicrm_event.title = '" . trim( $item_parts[1] ) . "' and payment_processor = '".$params['payment_processor']."' and civicrm_event.registration_end_date >= '".date('Y-m-d')."'";
					$dao = CRM_Core_DAO::executeQuery($query);
					
					if ($dao->fetch()) {
						if( $dao->is_email_confirm == 1 ){
							$is_email_confirm = TRUE;
							$confirm_email_text = $dao->confirm_email_text;
						}
						if( $dao->is_deductible == '1' )
						{
							$unit_deduct=$dom->createElement('UnitDeductible',($params['amount']*100));
							$unit_deduct=$orderitem->appendChild($unit_deduct);	
						}
						
					}
				}
				
				if ( isset( $params['selectProduct'] ) && $params['selectProduct'] != 'no_thanks' && $params['selectProduct'] != '' )
				{
					$orderitem2=$dom->createElement('OrderItem','');
					$orderitem2=$orderitemlist->appendChild($orderitem2);
					$itemid=$dom->createElement('ItemID','2');
					$itemid=$orderitem2->appendChild($itemid);
					
					$query = "SELECT *  FROM  civicrm_product  WHERE  id = '" . $params['selectProduct'] . "'";
					$dao = CRM_Core_DAO::executeQuery($query);
					if ($dao->fetch()) {
						$itemname=$dom->createElement('ItemName',$this->safeString(trim($dao->name), 50));
						$itemname=$orderitem2->appendChild($itemname);
					}
										
					$quntity=$dom->createElement('Quantity','1');
					$quntity=$orderitem2->appendChild($quntity);
					 
					$unitprice=$dom->createElement('UnitPrice',0);
					$unitprice=$orderitem2->appendChild($unitprice);
					
					$sku=$dom->createElement('SKU',$dao->sku);
					$sku=$orderitem2->appendChild($sku);
				}
						
			 $receipt=$dom->createElement('Receipt','');
			 $receipt=$order->appendChild($receipt);
			
			 $recipt_lang=$dom->createElement('Language','ENG');
			 $recipt_lang=$receipt->appendChild($recipt_lang);
			
			 //$recipt_org=$dom->createElement('OrganizationInformation','ClickandPledge');
			// $recipt_org=$receipt->appendChild($recipt_org);
			
			if ( isset( $params['contributionPageID'] ) && $params['contributionPageID'] != '' )
			{
				
				$query = "SELECT *  FROM  civicrm_contribution_page  WHERE  id = '" . $params['contributionPageID'] . "'";
					$dao = CRM_Core_DAO::executeQuery($query);
					
					if ($dao->fetch()) {
					
						if( $dao->receipt_text != '' && $dao->is_email_receipt == 1 )
						{
							$is_email_confirm = TRUE;
							$recipt_thanks=$dom->createElement('ThankYouMessage',$this->safeStringReplace(trim($dao->receipt_text), 50));
							$recipt_thanks=$receipt->appendChild($recipt_thanks);
						}
						
					}
					else
					{
						if( $confirm_email_text != '' ){
							$recipt_thanks=$dom->createElement('ThankYouMessage',$this->safeStringReplace( $confirm_email_text ));
							$recipt_thanks=$receipt->appendChild($recipt_thanks);
						}
					}
					
			}
			else
			{			
				if( $confirm_email_text != '' ){
					$recipt_thanks=$dom->createElement('ThankYouMessage',$this->safeStringReplace ($confirm_email_text ));
					$recipt_thanks=$receipt->appendChild($recipt_thanks);
				}
			}
			
			 //$recipt_terms=$dom->createElement('TermsCondition','<![CDATA[All donations are tax deductible.]]>');
			// $recipt_terms=$receipt->appendChild($recipt_terms);
			
			 $transation=$dom->createElement('Transaction','');
	         $transation=$order->appendChild($transation);

	         $trans_type=$dom->createElement('TransactionType','Payment');
	         $trans_type=$transation->appendChild($trans_type);
	        
			 
	
			 $trans_desc=$dom->createElement('DynamicDescriptor','DynamicDescriptor');
			 $trans_desc=$transation->appendChild($trans_desc); 
			 
			 
			if ( isset( $params['is_recur'] ) && $params['is_recur'] == TRUE) 
			{
			
				$trans_recurr=$dom->createElement('Recurring','');
				$trans_recurr=$transation->appendChild($trans_recurr);

							
				if( $params['installments'] != '' )
				{		
					$total_installment=$dom->createElement('Installment',$params['installments']);
					$total_installment=$trans_recurr->appendChild($total_installment);
				}
				else
				{
					$total_installment=$dom->createElement('Installment','999');
					$total_installment=$trans_recurr->appendChild($total_installment);
				}
				
				
				$total_periodicity=$dom->createElement('Periodicity',ucfirst($params['frequency_unit']));
				$total_periodicity=$trans_recurr->appendChild($total_periodicity);
				
				if( $params['installments'] != '' )
				{		
					$RecurringMethod=$dom->createElement('RecurringMethod','Subscription');
					$RecurringMethod=$trans_recurr->appendChild($RecurringMethod);
				}
				else
				{
					$RecurringMethod=$dom->createElement('RecurringMethod','Subscription');
					$RecurringMethod=$trans_recurr->appendChild($RecurringMethod);
				}
			}
			
			 $trans_totals=$dom->createElement('CurrentTotals','');
			 $trans_totals=$transation->appendChild($trans_totals);
			//No need of doing this as we sending unitdeductible above. But for clean coding we need to do this
			if( isset( $params['contributionTypeID'] ) && $params['contributionTypeID'] != '' )
			{
			$query = "SELECT *  FROM  civicrm_contribution_type  WHERE  id = '" . $params['contributionTypeID'] . "'";
				$dao = CRM_Core_DAO::executeQuery($query);
				if ($dao->fetch()) {
					if( $dao->is_deductible == '1' )
					{
						$recipt_deduct=$dom->createElement('Deductible','1');
						$recipt_deduct=$receipt->appendChild($recipt_deduct);
						
						$total_deduct=$dom->createElement('TotalDeductible',($params['amount']*100));
						$total_deduct=$trans_totals->appendChild($total_deduct);
					}
					
				}
			}
			elseif( isset( $params['contributionType_name'] ) && $params['contributionType_name'] != '' )
			{
			$query = "SELECT *  FROM  civicrm_contribution_type  WHERE  name = '" . $params['contributionType_name'] . "'";
				$dao = CRM_Core_DAO::executeQuery($query);
				if ($dao->fetch()) {
					if( $dao->is_deductible == '1' )
					{
						$recipt_deduct=$dom->createElement('Deductible','1');
						$recipt_deduct=$receipt->appendChild($recipt_deduct);
						
						$total_deduct=$dom->createElement('TotalDeductible',($params['amount']*100));
						$total_deduct=$trans_totals->appendChild($total_deduct);
					}
					
				}
			}
			elseif( trim( $item_parts[0] ) == 'Online Event Registration' )
			{
				$query = "SELECT civicrm_event.id as event_id,civicrm_event.title as title, civicrm_contribution_type.is_deductible, civicrm_contribution_type.name,civicrm_event.is_email_confirm  FROM  civicrm_event  
				inner join civicrm_contribution_type on civicrm_event.contribution_type_id=civicrm_contribution_type.id
				WHERE  civicrm_event.title = '" . trim( $item_parts[1] ) . "' and payment_processor = '".$params['payment_processor']."' and civicrm_event.registration_end_date >= '".date('Y-m-d')."'";
								
				$dao = CRM_Core_DAO::executeQuery($query);
				
				if ($dao->fetch()) {
					if( $dao->is_email_confirm == 1 ){
							$is_email_confirm = TRUE;
						}
					if( $dao->is_deductible == '1' )
					{
						$recipt_deduct=$dom->createElement('Deductible','1');
						$recipt_deduct=$receipt->appendChild($recipt_deduct);
						
						$total_deduct=$dom->createElement('TotalDeductible',($params['amount']*100));
						$total_deduct=$trans_totals->appendChild($total_deduct);
					}
					
				}
			}
			 
			if( $is_email_confirm )
			{
				$recipt_email=$dom->createElement('EmailNotificationList','');
				$recipt_email=$receipt->appendChild($recipt_email);

				if (isset($params['email']) && $params['email'] != '') {
				$email_notification = $params['email'];
				}
				elseif( array_key_exists('email-5',$params )){
				$email_notification = $params['email-5'];
				}
				elseif( isset( $params['email-Primary'] ) && $params['email-Primary'] != '' ){
				$email_notification = $params['email-Primary'];
				}
				else{
				$email_notification = '';
				}

				$email_note=$dom->createElement('NotificationEmail',$email_notification);
				$email_note=$recipt_email->appendChild($email_note);
			} 
			 $total_amount=$dom->createElement('Total',($params['amount']*100));
			 $total_amount=$trans_totals->appendChild($total_amount);
			 
	         $strParam =$dom->saveXML();
			// die();
			 return $strParam;
  }

    /**************************************************
   * Produces error message and returns from class
   **************************************************/
  function &errorExit($errorCode = NULL, $errorMessage = NULL) {
    $e = CRM_Core_Error::singleton();

    if ($errorCode) {
      $e->push($errorCode, 0, NULL, $errorMessage);
    }
    else {
      $e->push(9000, 0, NULL, 'Unknown System Error.');
    }
    return $e;
  }
  /**
   * Transfer method not in use
   *
   * @param array $params  name value pair of contribution data
   *
   * @return void
   * @access public
   *
   */
  function doTransferCheckout(&$params, $component) {
    CRM_Core_Error::fatal(ts('Use direct billing instead of Transfer method.'));
  }
}