<?php
/*
 * Payment Processor class for Click and Pledge
 */

class CRM_Core_Payment_ClickandPledge extends CRM_Core_Payment {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Mode of operation: live or test.
   *
   * @var object
   */
  protected $_mode = NULL;

  /**
   * Constructor
   *
   * @param string $mode
   * The mode of operation: live or test.
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode             = $mode;
    $this->_islive           = ($mode == 'live' ? 1 : 0);
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName    = ts('Click and Pledge');
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *  The error message if any.
   *
   * @public
   */
  function checkConfig() {
    $config = CRM_Core_Config::singleton();
    $error  = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('The "Account ID" is not set in the Click and Pledge Payment Processor settings.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('The "API Account GUID" is not set in the Click and Pledge Payment Processor settings.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * Helper log function.
   *
   * @param string $op
   * @param Exception $exception
   */
  function logException($op, $exception) {
    $body = print_r($exception->getJsonBody(), TRUE);
    CRM_Core_Error::debug_log_message("ClickandPledge_Error {$op}:  <pre> {$body} </pre>");
  }

  /**
   * @param  $erroreReturn
   * @return bool
   *
   */
  function isErrorReturn($erroreReturn) {
      if (is_object($erroreReturn) && get_class($erroreReturn) == 'CRM_Core_Error') {
        return true;
      }
      return false;
  }

  /**
 
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   The result in a nice formatted array (or an error object).
   *
   * @public
   */
  function doDirectPayment(&$params) {
	$cookedParams = $params; //Transalated params
	CRM_Utils_Hook::alterPaymentProcessorParams( $this, $params, $cookedParams );
    $allowed_currencies = array('USD','EUR','CAD','GBP');
    $package_path       = explode('/CRM',dirname(__FILE__));
    if(!isset($params['description'])) {
			$params['description'] = 'Contribution:Contribution';
	 } 
	 if(isset($params['credit_card_number']) && $params['credit_card_number'] == "") {
			return self::errorExit(2105, "Error: [Credit Card number is not valid]");	
	 } 
	 if(isset($params['cvv2']) && $params['cvv2'] == "") {
			return self::errorExit(2106, "Error: [Security Code is blank]");	
	 } 
	 if(( isset( $params['cvv2'] ) && strlen( $params['cvv2'] ) > 4 ))
	 {
		  return self::errorExit(9099, "Error: [Security Code length error (Max 4 characters & Min 3 characters)]");	
	 }
	  
	 if(isset( $params['is_pledge'] ) && $params['is_pledge'] == TRUE)
	 {
		return self::errorExit(9099, "Error: [We are not supporting Pledge Contributions. Please contact administrator]");	
	 }
	 if (isset( $params['is_recur'] ) && $params['is_recur'] == TRUE) 
	 {
		 if (isset( $params['frequency_interval'] ) && $params['frequency_interval'] < 1 )
		   {
			return self::errorExit(9099, "Error: [We are not supporting recurring intervals or recurring interval should be one. Please contact administrator]");	
		   }
			 
		 if (( isset( $params['installments'] ) && $params['installments'] <= 1 && $params['installments'] != '' ) || 
		    ( isset( $params['installments'] ) && $params['installments'] >= 1000 && $params['installments'] != ''  ))
	        {
			  return self::errorExit(9099, "Error: [Number of installments should be between 2 to 999. Please check the installments]");
		    }
		    $Periodicity = '';
			$Periodicity = $this->fetch_periodicity($params['frequency_unit'],$params['frequency_interval']);
	    if (!in_array( $Periodicity, array('Week','2 weeks','2 Weeks','Month','2 Months','Quarter','6 Months','Year') ))
	  	 {
			return self::errorExit(9099, "Error: [Periodicity should be 'Week','2 Weeks','Month','2 Months','Quarter','6 Months','Year'. Please contact administrator.]");	
		 }
	  
	  }
	  if (!in_array($params['currencyID'],$allowed_currencies))
	  {
			return self::errorExit(9099, 'It appears that selected currency is not supported with this payment processor. If you continue to have problems please contact the site administrator.');
	 }
	 if (( isset( $params['billing_first_name'] ) && strlen( $params['billing_first_name'] ) > 40 ))
	 {
	   return self::errorExit(9099, "Error: [Billing First Name should be less than 40 chanracters]");
	 }
	  
	if (!preg_match("/^([a-zA-Z0-9\.\,\#\&\-\ \']{2,40})+$/", $params['billing_first_name'])) 
	{
	   return self::errorExit(9099, "Error: [In valid Billing First Name.]");	
	}
	  
	if (( isset( $params['billing_middle_name'] ) && strlen( $params['billing_middle_name'] ) > 1 ))
	{
	  return self::errorExit(9099, "Error: [Billing Middle Name should be 1 chanracter only]");	
	}
	  
   if (( isset( $params['billing_last_name'] ) && strlen( $params['billing_last_name'] ) > 50 ))
   {
	  return self::errorExit(9099, "Error: [Billing Last Name should be less than 50 characters]");	
   }
   if (!preg_match("/^([a-zA-Z0-9\.\,\#\&\-\ \']{0,50})+$/", $params['billing_last_name'])) 
   {
	  return self::errorExit(9099, "Error: [In valid Billing Last Name.]");	
   }
	  
   if (( isset( $params['billing_city-5'] ) && strlen( $params['billing_city-5'] ) > 40 ))
   {
	  return self::errorExit(9099, "Error: [Billing City Name should be less than 40 chanracters]");	
   }
	  
   if (( isset( $params['billing_city-5'] ) && strlen( $params['billing_city-5'] ) > 40 ))
   {
	  return self::errorExit(9099, "Error: [Billing City Name should be less than 40 chanracters]");	
   }
	  
  if (( isset( $params['billing_postal_code-5'] ) && strlen( $params['billing_postal_code-5'] ) > 20 ))
  {
   return self::errorExit(9099, "Error: [Billing Postal should be less than 20 chanracters]");	
  }
	  
  $item_parts = explode(':',$params['description']);
  if(trim( $item_parts[0] ) == 'Online Event Registration' ) {
		 
			//for CiviCRM 4.3
			$query = "SELECT civicrm_event.id as event_id,civicrm_event.title as title, civicrm_financial_type.is_deductible,
					  civicrm_financial_type.name,civicrm_event.is_email_confirm,civicrm_event.confirm_email_text  FROM  civicrm_event  
					  inner join civicrm_financial_type ON civicrm_event.financial_type_id = civicrm_financial_type.id
					  WHERE  civicrm_event.title = %0  and civicrm_event.registration_end_date >= %2"; 
			
			$sql_params  = array(
			  0 => array(trim($item_parts[1]), 'String'),
			  1 => array($params['payment_processor_id'], 'Integer'),
			  2 => array(date('Y-m-d'), 'String')
			);
			$dao  = CRM_Core_DAO::executeQuery($query, $sql_params);
			$rows = 0;
			if ($dao->fetch()) { $rows = $dao->N; }
			if( $rows > 1 )
			{
				return self::errorExit(9099, "Error: [Event Name should be unique. Please contact administrator.]");
			}
	    }
		if( trim( $item_parts[0] ) == 'Online Contribution' )
	    {
		 
			//for CiviCRM 4.3
			$query       = "SELECT *  FROM  civicrm_contribution_page  WHERE  id = %0 and (end_date >= %1 or end_date IS NULL)";
            $sql_params  = array(0 => array($params['contributionPageID'], 'Integer'),
			                    1 => array(date('Y-m-d'), 'String'));
				
						 
			$dao  = CRM_Core_DAO::executeQuery($query, $sql_params);
			$rows = 0;
			if ($dao->fetch()) { $rows = $dao->N; }
			if( $rows == 0 )
			{
				return self::errorExit(9099, "Error: [Contribution Page is Expired. Please contact administrator.]");
			}
	    }
		if( trim( $item_parts[0] ) == 'Online Event Registration' )
	    {
		$query = "SELECT civicrm_event.id as event_id,civicrm_event.title as title, civicrm_financial_type.is_deductible,
					  civicrm_financial_type.name,civicrm_event.is_email_confirm,civicrm_event.is_online_registration  FROM  civicrm_event  
					  inner join civicrm_financial_type on civicrm_event.financial_type_id=civicrm_financial_type.id
					  WHERE  civicrm_event.title = %0  and civicrm_event.registration_end_date >= %2"; 
			
			$sql_params = array(
				     0 => array(trim($item_parts[1]), 'String'),
			         1 => array($params['payment_processor_id'], 'Integer'),
			         2 => array(date('Y-m-d H:i:s'), 'String')
			);
			
			$dao  = CRM_Core_DAO::executeQuery($query, $sql_params);
		 
		  if ($dao->fetch())
			 { 
				if( $dao->is_online_registration == 1)
				{
		
			//for CiviCRM 4.3
			$query = "SELECT civicrm_event.id as event_id,civicrm_event.title as title, civicrm_financial_type.is_deductible,
					  civicrm_financial_type.name,civicrm_event.is_email_confirm,civicrm_event.confirm_email_text  FROM  civicrm_event  
					  inner join civicrm_financial_type on civicrm_event.financial_type_id=civicrm_financial_type.id
					  WHERE  civicrm_event.title = %0  and (civicrm_event.registration_end_date >= %1 OR civicrm_event.registration_end_date IS NULL) 
					  and (civicrm_event.end_date >= %2 OR civicrm_event.end_date IS NULL)"; 
			
			$sql_params = array(
			         0 => array(trim($item_parts[1]), 'String'),
			         1 => array(date('Y-m-d H:i:s'), 'String'),
			         2 => array(date('Y-m-d H:i:s'), 'String')
			);
			
			$errrmsg = "Registration is closed for this event";
			
		}
		else
		{
		  $query = "SELECT civicrm_event.id as event_id,civicrm_event.title as title, civicrm_financial_type.is_deductible,
					  civicrm_financial_type.name,civicrm_event.is_email_confirm,civicrm_event.confirm_email_text  FROM  civicrm_event  
					  inner join civicrm_financial_type on civicrm_event.financial_type_id=civicrm_financial_type.id
					  WHERE  civicrm_event.title = %0  and (civicrm_event.end_date >= %1 OR civicrm_event.end_date IS NULL)"; 
			
			$sql_params = array(
			 		  0 => array(trim($item_parts[1]), 'String'),
			          1 => array(date('Y-m-d H:i:s'), 'String')
			);
			$errrmsg = "Registration is closed for this event";
		}	
			$dao  = CRM_Core_DAO::executeQuery($query, $sql_params);
			$rows = 0;
			if ($dao->fetch()) { $rows = $dao->N; }
			if( $rows == 0 )
			{
				return self::errorExit(9099, "Error: [".$errrmsg." . Please contact administrator.]");
			}			 
		}	
	    }
	     $strParam    =  $this->buildXML( $params, $package_path[0] );
	     $connect     =  array('soap_version' => SOAP_1_1, 'trace' => 1, 'exceptions' => 0);
		 $client      =  new SoapClient('https://paas.cloud.clickandpledge.com/paymentservice.svc?wsdl', $connect);
		 $soapParams  =  array('instruction'=>$strParam);
		 $response    =  $client->Operation($soapParams);
	
		// NOTE: You will not necessarily get a string back, if the request failed for
		// any reason, the return value will be the boolean false.
		//print_r($response);exit;
		if (($response === FALSE))
		 {
		    return self::errorExit(9006, "Error: Connection to payment gateway failed - no data returned.");
		 }
		$ResultCode        = $response->OperationResult->ResultCode;
		$ResultData        = $response->OperationResult->ResultData;
	    $transation_number = $response->OperationResult->VaultGUID;
		
		if($ResultCode  ==  '0')
		{
			$response_message = $response->OperationResult->ResultData;
			//Success
			$params['trxn_id'] = $transation_number;
			$params['trxn_result_code'] = $response_message;
			return $params;
		}
		else
		{
			if( in_array( $ResultCode, array( 2051,2052,2053 ) ) )
			{
				$AdditionalInfo       = $response->OperationResult->AdditionalInfo;
			}
			else
			{
				if( isset( $ResultCode ) )
				{
					$AdditionalInfo   = $ResultData;
				}
				else
				{
					$AdditionalInfo   = 'Unknown error';
				}
			}
				
				$response_message  = $ResultCode.':'.$AdditionalInfo;
			
			return self::errorExit(9099, "Error: [" . $response_message . "]");			     
		}
  
  
   }
   
   function _checkDupe($invoiceId) {
   
    require_once $civicrm_root .'CRM/Contribute/DAO/Contribution.php';
    $contribution             = new CRM_Contribute_DAO_Contribution();
    $contribution->invoice_id = $invoiceId;
	return $contribution->find();
	
   }
  
    function safeString( $str,  $length=1, $start=0 )
	{
		return substr(  $str , $start, $length );
	}
	
	function safeStringSplChar( $str,  $length=1, $start=0 )
	{
		return substr( htmlspecialchars( ( $str ) ), $start, $length );
	}
	
	function safeStringReplace( $str )
	{
		$str = str_replace( '&', '&amp;', trim( $str ) );
		return substr( $str, 0, 500 );
	}
	
	function fetch_periodicity($cycle_period, $cycle_number)
	{
	
			$Periodicity = '';
			switch(ucfirst($cycle_period))
			{
				case 'Day':
					if(in_array($cycle_number, array(7,14,30,61,91,183,365))) {
						if($cycle_number == 7) {
						$Periodicity = 'Week';
						}
						elseif($cycle_number == 14) {
						$Periodicity = '2 Weeks';
						}
						elseif($cycle_number == 30) {
						$Periodicity = 'Month';
						}
						elseif($cycle_number == 61) {
						$Periodicity = '2 Months';
						}
						elseif($cycle_number == 91) {
						$Periodicity = 'Quarter';
						}elseif($cycle_number == 183) {
						$Periodicity = '6 Months';
						} else {
						$Periodicity = 'Year';
						}
					}
					else
					{
						$Periodicity = $cycle_number . " Days";
					}
				break;
				case 'Week':
					 $days = $cycle_number; //This will convert week into days
					if(in_array($days, array(1,2,4,8,12,24,52))) {
						if($cycle_number == 1) {
						$Periodicity = 'Week';
						}
						elseif($cycle_number == 2) {
						$Periodicity = '2 Weeks';
						}
						elseif($cycle_number == 4) {
						$Periodicity = 'Month';
						}
						elseif($cycle_number == 8) {
						$Periodicity = '2 Months';
						}
						elseif($cycle_number == 12) {
						$Periodicity = 'Quarter';
						}
						elseif($cycle_number == 24) {
						$Periodicity = '6 Months';
						}
						elseif($cycle_number == 52) {
						$Periodicity = 'Year';
						}
					}
					else
					{
						$Periodicity = $cycle_number . " Weeks";
					}
				break;
				case 'Month':
					if(in_array($cycle_number, array(1,2,3,6,12))) {
						if($cycle_number == 1) {
						$Periodicity = 'Month';
						}elseif($cycle_number == 2) {
						$Periodicity = '2 Months';
						}elseif($cycle_number == 3) {
						$Periodicity = 'Quarter';
						}elseif($cycle_number == 6) {
						$Periodicity = '6 Months';
						} else {
						$Periodicity = 'Year';
						}
					}
					else
					{
						$Periodicity = $cycle_number . " Months";
					}
				break;
				case 'Year':
					if(in_array($cycle_number, array(1))) {
						$Periodicity = 'Year';
					}
					else
					{
						$Periodicity = $cycle_number . " Years";
					}
				break;
				
			}
			
			return $Periodicity;
		}
		
		
   function buildXML( $params, $xmlpath )
  {

	    $configValues = $this->_paymentProcessor;
		$cnpVersion   = "3.000.000/WP:v".get_bloginfo('version')."/CiviCRM:v".CRM_Utils_System::version();
		$dom          = new DOMDocument('1.0', 'UTF-8');
		
        $root = $dom->createElement('CnPAPI', '');
        $root->setAttribute("xmlns","urn:APISchema.xsd");
        $root = $dom->appendChild($root);
			  
		$version=$dom->createElement("Version","1.5");
    	$version=$root->appendChild($version);
		 
		$engine = $dom->createElement('Engine', '');
        $engine = $root->appendChild($engine);
			 
		$application = $dom->createElement('Application','');
		$application = $engine->appendChild($application);
    
		$applicationid=$dom->createElement('ID','CnP_CiviCRM_WordPress'); 
		$applicationid=$application->appendChild($applicationid);
			
		$applicationname=$dom->createElement('Name','CnP_CiviCRM_WordPress'); //CnP_CiviCRM_WordPress#CnP_CiviCRM_Joomla#CnP_CiviCRM_Drupal
		$applicationid=$application->appendChild($applicationname);
			
		$applicationversion=$dom->createElement('Version',$cnpVersion);  
		$applicationversion=$application->appendChild($applicationversion);
    
    	$request = $dom->createElement('Request', '');
    	$request = $engine->appendChild($request);
    
    	$operation=$dom->createElement('Operation','');
    	$operation=$request->appendChild( $operation );
			 
		$operationtype=$dom->createElement('OperationType','Transaction');
    	$operationtype=$operation->appendChild($operationtype);
    
    	$ipaddress=$dom->createElement('IPAddress',$params['ip_address']);
    	$ipaddress=$operation->appendChild($ipaddress);
		
        $httpreferrer=$dom->createElement('UrlReferrer',$_SERVER['HTTP_REFERER']);
		$httpreferrer=$operation->appendChild($httpreferrer);
		
		$authentication=$dom->createElement('Authentication','');
    	$authentication=$request->appendChild($authentication);
		
    	$accounttype=$dom->createElement('AccountGuid',$configValues['password'] ); 
    	$accounttype=$authentication->appendChild($accounttype);
    
	    $accountid=$dom->createElement('AccountID',$configValues['user_name'] );
    	$accountid=$authentication->appendChild($accountid);
		
		
			 
		$order=$dom->createElement('Order','');
    	$order=$request->appendChild($order);
			
		if( $this->_mode == 'live' )
		{
			$orderMode = 'Production';
		}else{
			$orderMode = 'Test';
		}
    	$ordermode=$dom->createElement('OrderMode',$orderMode);
    	$ordermode=$order->appendChild($ordermode);
		
		// Check if it is Personal Campaign Page
		
		if ( (isset( $params['contributionPageID'] ) && $params['contributionPageID'] != '') || 
		     (isset( $params['campaign_id'] ) && $params['campaign_id'] != '') || 
		     (isset( $params['pcp_made_through_id'] ) && $params['pcp_made_through_id'] != '' ) )
				{
					//Check if it is any Campaign Pages attached to this contribution
					if ( isset( $params['pcp_made_through_id'] ) && $params['pcp_made_through_id'] != '' )
					{
						$query      = "SELECT *  FROM  civicrm_pcp  WHERE id = %0";
                        $params_app = array(0 => array($params['pcp_made_through_id'], 'Integer'));
						$dao        = CRM_Core_DAO::executeQuery($query, $params_app);
						if ($dao->fetch()) 
						{
							$Campaign=$dom->createElement('Campaign',$this->safeStringSplChar(trim($dao->title), 73).'-p-'.$params['pcp_made_through_id']);
							$Campaign=$order->appendChild($Campaign);
						}
					}
					elseif((isset( $params['contributionPageID'] ) && $params['contributionPageID'] != ''))
					{
						$query      = "SELECT *  FROM  civicrm_contribution_page  WHERE  id = %0";
                        $params_app = array(0 => array($params['contributionPageID'], 'Integer'));
						$dao        = CRM_Core_DAO::executeQuery($query, $params_app);
						if ($dao->fetch())
						 {
							if( $dao->campaign_id != '' )
							{
								$query_campaign = "SELECT *  FROM  civicrm_campaign  WHERE  id = %0";
                				$params_app     = array(0 => array($dao->campaign_id, 'Integer'));
								$dao_campaign   = CRM_Core_DAO::executeQuery($query_campaign, $params_app);
								if ($dao_campaign->fetch()) 
								{
									$Campaign=$dom->createElement('Campaign',$this->safeStringSplChar(trim($dao_campaign->title), 73).'-a-'.$dao->campaign_id);
									$Campaign=$order->appendChild($Campaign);
								}
							}
						}
						
					}
					elseif((isset( $params['campaign_id'] ) && $params['campaign_id'] != '') && (!isset( $params['contributionPageID'] )))
					{
					
						$query_campaign  = "SELECT *  FROM  civicrm_campaign  WHERE  id = %0";
                        $params_app      = array(0 => array($params['campaign_id'], 'Integer'));
						$dao_campaign    = CRM_Core_DAO::executeQuery($query_campaign, $params_app);
						if ($dao_campaign->fetch()) 
						{
							$Campaign=$dom->createElement('Campaign',$this->safeStringSplChar(trim($dao_campaign->title), 73).'-a-'.$params['campaign_id']);
							$Campaign=$order->appendChild($Campaign);
						}
							
					}
				}
		$ConnectCampaignAlias=$dom->createElement('ConnectCampaignAlias',$configValues['signature'] );
    	$ConnectCampaignAlias=$order->appendChild($ConnectCampaignAlias);
				
    	$cardholder=$dom->createElement('CardHolder','');
    	$cardholder=$order->appendChild($cardholder);
			 
		$billinginfo=$dom->createElement('BillingInformation','');
    	$billinginfo=$cardholder->appendChild($billinginfo);

		
		$billfirst_name=$dom->createElement('BillingFirstName','');
		$billfirst_name=$billinginfo->appendChild($billfirst_name);
		$billfirst_name->appendChild($dom->createCDATASection( $this->safeString($params['first_name'],50) ));
			
		if( isset( $params['middle_name'] ) && $params['middle_name'] != '' )
		 {
			
			$billmiddle_name=$dom->createElement('BillingMI',$this->safeStringSplChar($params['middle_name'],1));
			$billmiddle_name=$billinginfo->appendChild($billmiddle_name);
		 }
			
		$billlast_name=$dom->createElement('BillingLastName','');
		$billlast_name=$billinginfo->appendChild($billlast_name);
		$billlast_name->appendChild($dom->createCDATASection( $this->safeString($params['billing_last_name'],50) ));
		
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
		
			$keys        = array_keys( $params );
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
			
			 $billingaddress  = $dom->createElement('BillingAddress','');
			 $billingaddress  = $cardholder->appendChild($billingaddress);
		   
			 $billingaddress1 = $dom->createElement('BillingAddress1','');
			 $billingaddress1 = $billingaddress->appendChild($billingaddress1);
			 $billingaddress1->appendChild($dom->createCDATASection( $this->safeString($params['street_address'],100) ));
			
			 $billing_city    = $dom->createElement('BillingCity','');
			 $billing_city    = $billingaddress->appendChild($billing_city);
			 $billing_city->appendChild($dom->createCDATASection( $this->safeString($params['city'],50) ));
			
			
			 $billing_state   = $dom->createElement('BillingStateProvince','');
			 $billing_state   = $billingaddress->appendChild($billing_state);
			 $billing_state->appendChild($dom->createCDATASection( $this->safeString($params['state_province'],50) ));
			
			 $billing_zip     = $dom->createElement('BillingPostalCode',$this->safeStringSplChar( $params['postal_code'],20 ));
			 $billing_zip     = $billingaddress->appendChild($billing_zip);
			   
			 $countries       = simplexml_load_file( $xmlpath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'ClickandPledge'.DIRECTORY_SEPARATOR.'Countries.xml' );
			 foreach( $countries as $country ){
				if( $country->attributes()->Abbrev == $params['country'] ){
				$billing_country_id = $country->attributes()->Code;
				} 
			 }
			 $billing_country = $dom->createElement('BillingCountryCode',str_pad($billing_country_id, 3, "0", STR_PAD_LEFT));
			 $billing_country = $billingaddress->appendChild($billing_country);
			 
			 
			 $customfieldlist = $dom->createElement('CustomFieldList','');
             $customfieldlist = $cardholder->appendChild($customfieldlist);
	

			 if(  isset( $params['participant_role_id'] ) && $params['participant_role_id'] != '' )
			 {
				 $query = "SELECT v.label as label FROM civicrm_option_value v, civicrm_option_group g WHERE v.option_group_id = g.id AND 
				           g.name = 'participant_role' AND g.is_active = 1 AND v.is_active = 1 and v.value = '".$params['participant_role_id']."'  ORDER BY v.weight";
				 
				 $participant_role = CRM_Core_Dao::singleValueQuery( $query );
				 if( $participant_role != '' )
				 {
				 $customfield1  = $dom->createElement('CustomField','');
				 $customfield1  = $customfieldlist->appendChild($customfield1);
				
				
				 $fieldname1   = $dom->createElement('FieldName','Participant Role');
				 $fieldname1   = $customfield1->appendChild($fieldname1);
				 
				 $fieldvalue1  = $dom->createElement('FieldValue',$this->safeStringSplChar( $participant_role,50 ));
				 $fieldvalue1  = $customfield1->appendChild($fieldvalue1);
				 }
			 }
			 
			 if(  isset( $params['contributionType_name'] ) && $params['contributionType_name'] != '' )
			 {
				 $customfield1 = $dom->createElement('CustomField','');
				 $customfield1 = $customfieldlist->appendChild($customfield1);
						
				 $fieldname1   = $dom->createElement('FieldName','Contribution Type');
				 $fieldname1   = $customfield1->appendChild($fieldname1);
				 
				 $fieldvalue1  = $dom->createElement('FieldValue',$this->safeStringSplChar( $params['contributionType_name'],50 ));
				 $fieldvalue1  = $customfield1->appendChild($fieldvalue1);
			 }
			 

			 $customfield4 = $dom->createElement('CustomField','');
			 $customfield4 = $customfieldlist->appendChild($customfield4);
			 
			 $fieldname4   = $dom->createElement('FieldName','Description');
			 $fieldname4   = $customfield4->appendChild($fieldname4);
	
			 $fieldvalue4  = $dom->createElement('FieldValue','');
			 $fieldvalue4  = $customfield4->appendChild($fieldvalue4);
			 $fieldvalue4->appendChild($dom->createCDATASection( $this->safeString($params['description'],50) ));
			 
			 if( isset( $params['additional_participants'] ) && $params['additional_participants'] != '' )
			 {
				 $customfield5  = $dom->createElement('CustomField','');
				 $customfield5  = $customfieldlist->appendChild($customfield5);
				 
				 $fieldname5    = $dom->createElement('FieldName','Total Participants');
				 $fieldname5    = $customfield5->appendChild($fieldname5);
		
				 $fieldvalue5   = $dom->createElement('FieldValue',($params['additional_participants']+1));
				 $fieldvalue5   = $customfield5->appendChild($fieldvalue5);
			 }
			 
			  $query     = "SELECT version FROM   civicrm_domain";
			  $dbVersion = CRM_Core_DAO::singleValueQuery($query);
			
			if($dbVersion < '4.5.0')
			 {
				 if( isset( $params['honor_type_id'] ) && $params['honor_type_id'] != '' )
				 {
					 $query       = "SELECT v.label as label FROM civicrm_option_value v, civicrm_option_group g WHERE v.option_group_id = g.id AND 
					                 g.name = 'honor_type' AND g.is_active = 1 AND v.is_active = 1 
									 and v.value = '".$params['honor_type_id']."'  ORDER BY v.weight";				 
					$honor_type  = CRM_Core_Dao::singleValueQuery( $query );
					 
					$customfield6 = $dom->createElement('CustomField','');
					$customfield6 = $customfieldlist->appendChild($customfield6);
					 
					$fieldname6   = $dom->createElement('FieldName',$this->safeString($honor_type,200));
					$fieldname6   = $customfield6->appendChild($fieldname6);
					
					$query        = "SELECT v.label as label FROM civicrm_option_value v, civicrm_option_group g WHERE v.option_group_id = g.id AND 
					                 g.name = 'individual_prefix' AND g.is_active = 1 AND v.is_active = 1 and 
									 v.value = '".$params['honor_prefix_id']."'  ORDER BY v.weight";				 
					$honor_prefix = CRM_Core_Dao::singleValueQuery( $query );
					
					 $honor_name  = $honor_prefix . ' ' . $params['honor_first_name'];
					 if(isset($params['honor_last_name']) && $params['honor_last_name'] != '')
					 $honor_name .= ' ' . $params['honor_last_name'];
					 if(isset($params['honor_email']) && $params['honor_email'] != '')
					 $honor_name .= ' (' . $params['honor_email'] . ')';
					 $fieldvalue6 = $dom->createElement('FieldValue',$this->safeString($honor_name, 500));
					 $fieldvalue6 = $customfield6->appendChild($fieldvalue6);
				 }
			 } else {
				if( isset( $params['honor'] ) && $params['soft_credit_type_id'] != '' ) {
					$query      = "SELECT v.label as label FROM civicrm_option_value v, civicrm_option_group g WHERE v.option_group_id = g.id AND 
					               g.name = 'soft_credit_type' AND g.is_active = 1 AND v.is_active = 1 and
							       v.value = '".$params['soft_credit_type_id']."'  ORDER BY v.weight";				 
					$honor_type = CRM_Core_Dao::singleValueQuery( $query );
					
					 $customfield6 = $dom->createElement('CustomField','');
					 $customfield6 = $customfieldlist->appendChild($customfield6);
					 
					 $fieldname6 = $dom->createElement('FieldName',$this->safeString($honor_type,200));
					 $fieldname6 = $customfield6->appendChild($fieldname6);
					
					$query        = "SELECT v.label as label FROM civicrm_option_value v, civicrm_option_group g WHERE v.option_group_id = g.id AND 
					                g.name = 'individual_prefix' AND g.is_active = 1 AND v.is_active = 1 and
					                v.value = '".$params['honor']['prefix_id']."'  ORDER BY v.weight";				 
					$honor_prefix = CRM_Core_Dao::singleValueQuery( $query );
					
					 $honor_name  = $honor_prefix . ' ' . $params['honor']['first_name'];
					 if(isset($params['honor']['last_name']) && $params['honor']['last_name'] != '')
					 $honor_name .= ' ' . $params['honor']['last_name'];
					 if(isset($params['honor']['email-1']) && $params['honor']['email-1'] != '')
					 $honor_name .= ' (' . $params['honor']['email-1'] . ')';
					 if(isset($params['honor']['email-Primary']) && $params['honor']['email-Primary'] != '')
					 $honor_name .= ' (' . $params['honor']['email-Primary'] . ')';
					 $fieldvalue6 = $dom->createElement('FieldValue',$this->safeString($honor_name, 500));
					 $fieldvalue6 = $customfield6->appendChild($fieldvalue6);
				}
			 }
			 $paymentmethod   = $dom->createElement('PaymentMethod','');
			 $paymentmethod   = $cardholder->appendChild($paymentmethod);
			
			 $payment_type    = $dom->createElement('PaymentType','CreditCard');
			 $payment_type    = $paymentmethod->appendChild($payment_type);
			
			 $creditcard      = $dom->createElement('CreditCard','');
			 $creditcard      = $paymentmethod->appendChild($creditcard);
			
			if (isset($params['cardholder_name'])) {
				$credit_card_name = $params['cardholder_name'];
			}
			else
			 {
				$credit_card_name = $params['first_name'] . " ";
				if (isset($params['middle_name']) && !empty($params['middle_name'])) {
					$credit_card_name .= $params['middle_name'] . " ";
			}
				$credit_card_name .= $params['last_name'];
			}
	
			 $credit_name  = $dom->createElement('NameOnCard','');
			 $credit_name  = $creditcard->appendChild($credit_name);
			 $credit_name->appendChild($dom->createCDATASection( $this->safeString( $credit_card_name,50) ));
			
			 $credit_number = $dom->createElement('CardNumber',$this->safeStringSplChar( str_replace(' ', '', $params['credit_card_number']), 17));
			 $credit_number = $creditcard->appendChild($credit_number);
			
			 $credit_cvv    = $dom->createElement('Cvv2',$params['cvv2']);
			 $credit_cvv    = $creditcard->appendChild($credit_cvv);
		
			 $credit_expdate=$dom->createElement('ExpirationDate',str_pad($params['credit_card_exp_date']['M'],2,'0',STR_PAD_LEFT) ."/" .substr($params['credit_card_exp_date']['Y'],2,2));
			 $credit_expdate=$creditcard->appendChild($credit_expdate);
			
			 $orderitemlist = $dom->createElement('OrderItemList','');
             $orderitemlist = $order->appendChild($orderitemlist);			
			
			$orderitem     = $dom->createElement('OrderItem','');
			$orderitem     = $orderitemlist->appendChild($orderitem);
				 
			$ItemNUM       =  101401;	//as updated on 22062016
			$itemid        =  $dom->createElement('ItemID',$ItemNUM++);
			$itemid        =  $orderitem->appendChild($itemid);
				
			$item_parts    = explode(':',$params['description']);
			$itemname      = $dom->createElement('ItemName','');
			$itemname      = $orderitem->appendChild($itemname);
			$itemname->appendChild($dom->createCDATASection( $this->safeString(trim($item_parts[1]),50) ));
				
			$quntity       = $dom->createElement('Quantity','1');
			$quntity       = $orderitem->appendChild($quntity);
				 
			$unitprice     = $dom->createElement('UnitPrice',CRM_Utils_Rule::cleanMoney($params['amount'])*100);
			$unitprice     = $orderitem->appendChild($unitprice);
				
			$is_email_confirm = FALSE;
			$confirm_email_text = '';
			if( isset( $params['contributionTypeID'] ) && $params['contributionTypeID'] != '' )
			 {
				
				//for CiviCRM 4.3
				    $query           = "SELECT *  FROM  civicrm_financial_type  WHERE  id = %0";
					$params_app      = array(0 => array($params['contributionTypeID'], 'Integer'));
					 $dao            = CRM_Core_DAO::executeQuery($query, $params_app);
							if ($dao->fetch()) { //echo  $dao->is_deductible; exit;
								if( $dao->is_deductible == '1' )
								{
									$unit_deduct = $dom->createElement('UnitDeductible',CRM_Utils_Rule::cleanMoney($params['amount'])*100);
									$unit_deduct = $orderitem->appendChild($unit_deduct);	
								}
								
							}
			}
			elseif( isset( $params['contributionType_name'] ) && $params['contributionType_name'] != '' )
			{
				
				//for CiviCRM 4.3
					$query      = "SELECT *  FROM  civicrm_financial_type  WHERE  id = %0";
				    $params_app = array(0 => array($params['contributionType_name'], 'String'));
					$dao        = CRM_Core_DAO::executeQuery($query, $params_app);
							if ($dao->fetch()) {
								if( $dao->is_deductible == '1' )
								{
									$unit_deduct=$dom->createElement('UnitDeductible',CRM_Utils_Rule::cleanMoney($params['amount'])*100);
									$unit_deduct=$orderitem->appendChild($unit_deduct);	
								}
								
							}
			}
			elseif( trim( $item_parts[0] ) == 'Online Event Registration' )
			{
						
			  //For CiviCRM 4.3
			   $query 		= "SELECT civicrm_event.id as event_id,civicrm_event.title as title, civicrm_financial_type.is_deductible,
					     	   civicrm_financial_type.name,civicrm_event.is_email_confirm,civicrm_event.confirm_email_text  FROM  civicrm_event  
			                   inner join civicrm_financial_type on civicrm_event.financial_type_id=civicrm_financial_type.id
			                   WHERE  civicrm_event.title = %0 "; //and payment_processor = %1 and civicrm_event.registration_end_date >= %2 1  => array($params['payment_processor_id'], 'String'),2  => array(date('Y-m-d'), 'String')
			  $params_app = array(
						0  => array(trim($item_parts[1]), 'String')
				       
			          );
					
			         $dao        = CRM_Core_DAO::executeQuery($query, $params_app);
						
						 if ($dao->fetch()) 
						 { //echo $dao->is_email_confirm; exit;
							if( $dao->is_email_confirm == 1 ){
								$is_email_confirm = TRUE;
								$confirm_email_text = $dao->confirm_email_text;
							}
							if( $dao->is_deductible == '1' )
							{
								$unit_deduct = $dom->createElement('UnitDeductible',CRM_Utils_Rule::cleanMoney($params['amount'])*100);
								$unit_deduct = $orderitem->appendChild($unit_deduct);	
							}
							
						}
			}
			
			if ( isset( $params['selectProduct'] ) && $params['selectProduct'] != 'no_thanks' && $params['selectProduct'] != '' )
			{
				$orderitem2 = $dom->createElement('OrderItem','');
				$orderitem2 = $orderitemlist->appendChild($orderitem2);
				$itemid     = $dom->createElement('ItemID',$ItemNUM++);
				$itemid     = $orderitem2->appendChild($itemid);
					
				$query      = "SELECT *  FROM  civicrm_product  WHERE  id = %0";
                $params_app = array(0 => array($params['selectProduct'], 'Integer'));
				$dao        = CRM_Core_DAO::executeQuery($query, $params_app);
				if ($dao->fetch())
				 {
						$itemname=$dom->createElement('ItemName',$this->safeStringSplChar(trim($dao->name), 50));
						$itemname=$orderitem2->appendChild($itemname);
				 }
										
				$quntity   = $dom->createElement('Quantity','1');
				$quntity   = $orderitem2->appendChild($quntity);
					 
				$unitprice = $dom->createElement('UnitPrice',0);
				$unitprice = $orderitem2->appendChild($unitprice);
					
				$sku       = $dom->createElement('SKU',$dao->sku);
				$sku       = $orderitem2->appendChild($sku);
				}
						
			 $receipt   = $dom->createElement('Receipt','');
			 $receipt   = $order->appendChild($receipt);
			
			 
			if ( isset( $params['contributionPageID'] ) && $params['contributionPageID'] != '' )
			{
				//$is_email_confirm = FALSE;
				$query      = "SELECT * FROM civicrm_contribution_page  WHERE  id = %0";
                $params_app = array(0 => array($params['contributionPageID'], 'Integer'));
				$dao        = CRM_Core_DAO::executeQuery($query, $params_app);
					
					if ($dao->fetch()) 
					{
					
						if(  $dao->is_email_receipt == 1 )  //$dao->receipt_text != '' && Commented By Lakshmi
						{   
							$is_email_confirm = TRUE;
							$recipt_thanks=$dom->createElement('ThankYouMessage',$this->safeStringReplace(trim($dao->receipt_text), 50));
							//$recipt_thanks=$receipt->appendChild($recipt_thanks);
						}
						
					}
					else
					{
						if( $confirm_email_text != '' )
						{
							$recipt_thanks=$dom->createElement('ThankYouMessage',$this->safeStringReplace($confirm_email_text ));
							//$recipt_thanks=$receipt->appendChild($recipt_thanks);
						}
					}
					
			}
			else
			{			
			  
              if( $confirm_email_text != '' )
			  {$is_email_confirm = TRUE;
					$recipt_thanks=$dom->createElement('ThankYouMessage',$this->safeStringReplace ($confirm_email_text ));
					
			  }
			}
			
			if( $is_email_confirm )
			{
			
			   $email_sendreceipt =$dom->createElement('SendReceipt',"true");
			   $email_sendreceipt=$receipt->appendChild($email_sendreceipt);
			}
			else
			{
			  $email_sendreceipt=$dom->createElement('SendReceipt',"false");
			  $email_sendreceipt=$receipt->appendChild($email_sendreceipt);		
	        }
			
			 $recipt_lang = $dom->createElement('Language','ENG');
			 $recipt_lang = $receipt->appendChild($recipt_lang);
			 if(isset($recipt_thanks)){
			// $recipt_thanks=$receipt->appendChild($recipt_thanks);
				} 
			
		  $transation = $dom->createElement('Transaction','');
	      $transation = $order->appendChild($transation);

	      $trans_type = $dom->createElement('TransactionType','Payment');
	      $trans_type = $transation->appendChild($trans_type);
	        
		  $trans_desc = $dom->createElement('DynamicDescriptor','DynamicDescriptor');
		  $trans_desc = $transation->appendChild($trans_desc); 
			 
		  if ( isset( $params['is_recur'] ) && $params['is_recur'] == TRUE) 
		   {
				$Periodicity = '';
				$Periodicity = $this->fetch_periodicity($params['frequency_unit'],$params['frequency_interval']);
				
				$trans_recurr = $dom->createElement('Recurring','');
				$trans_recurr = $transation->appendChild($trans_recurr);
							
				if( isset($params['installments']) && $params['installments'] != '' )
				{		
					$total_installment = $dom->createElement('Installment',$params['installments']);
					$total_installment = $trans_recurr->appendChild($total_installment);
				}
				else
				{
					$total_installment = $dom->createElement('Installment','999');
					$total_installment = $trans_recurr->appendChild($total_installment);
				}
				
				
				$total_periodicity    = $dom->createElement('Periodicity',$Periodicity);
				$total_periodicity    = $trans_recurr->appendChild($total_periodicity);
				
				if( isset($params['installments']) && $params['installments'] != '' )
				{		
					$RecurringMethod  = $dom->createElement('RecurringMethod','Subscription');
					$RecurringMethod  = $trans_recurr->appendChild($RecurringMethod);
				}
				else
				{
					$RecurringMethod  = $dom->createElement('RecurringMethod','Subscription');
					$RecurringMethod  = $trans_recurr->appendChild($RecurringMethod);
				}
			}
			
			 	   $trans_totals      = $dom->createElement('CurrentTotals','');
			       $trans_totals      = $transation->appendChild($trans_totals);
			//No need of doing this as we sending unitdeductible above. But for clean coding we need to do this
			if( isset( $params['contributionTypeID'] ) && $params['contributionTypeID'] != '' )
			{
			
      			$query = "SELECT *  FROM  civicrm_financial_type  WHERE  id = '" . $params['contributionTypeID'] . "'";
				$dao   = CRM_Core_DAO::executeQuery($query);
				if ($dao->fetch()) {
					if( $dao->is_deductible == '1' )
					{
						//$recipt_deduct = $dom->createElement('Deductible','1');
					//	$recipt_deduct = $receipt->appendChild($recipt_deduct);
						
						$total_deduct  = $dom->createElement('TotalDeductible',CRM_Utils_Rule::cleanMoney($params['amount'])*100);
						$total_deduct  = $trans_totals->appendChild($total_deduct);
					}
					
				}
			}
			elseif( isset( $params['contributionType_name'] ) && $params['contributionType_name'] != '' )
			{
			
      			$query      = "SELECT *  FROM  civicrm_financial_type  WHERE  name = %0";
        		$params_app = array(0 => array($params['contributionType_name'], 'String'));
				$dao        = CRM_Core_DAO::executeQuery($query, $params_app);
				if ($dao->fetch()) {
					if( $dao->is_deductible == '1' )
					{
						//$recipt_deduct = $dom->createElement('Deductible','1');
					//	$recipt_deduct = $receipt->appendChild($recipt_deduct);
						
					    $total_deduct  = $dom->createElement('TotalDeductible',CRM_Utils_Rule::cleanMoney($params['amount'])*100);
						$total_deduct  = $trans_totals->appendChild($total_deduct);
					}
					
				}
			}
			elseif( trim( $item_parts[0] ) == 'Online Event Registration' )
			{
				
				
				$query = "SELECT civicrm_event.id as event_id,civicrm_event.title as title, civicrm_financial_type.is_deductible,
							civicrm_financial_type.name,civicrm_event.is_email_confirm,civicrm_event.confirm_email_text  FROM  civicrm_event  
							inner join civicrm_financial_type on civicrm_event.financial_type_id=civicrm_financial_type.id
							WHERE  civicrm_event.title = %0 and payment_processor = %1 and civicrm_event.registration_end_date >= %2";
				$params_app = array(
					       0 => array(trim($item_parts[1]), 'String'),
					       1 => array($params['payment_processor_id'], 'String'),
					       2 => array(date('Y-m-d'), 'String')
					      );
				 $dao      = CRM_Core_DAO::executeQuery($query, $params_app);
				
				if ($dao->fetch()) {
					if( $dao->is_email_confirm == 1 ){
							$is_email_confirm  = TRUE;
						}
					if( $dao->is_deductible == '1' )
					{
						//$recipt_deduct = $dom->createElement('Deductible','1');
						//$recipt_deduct = $receipt->appendChild($recipt_deduct);
						
						//$total_deduct  = $dom->createElement('TotalDeductible',CRM_Utils_Rule::cleanMoney($params['amount'])*100);
						//$total_deduct  = $trans_totals->appendChild($total_deduct);
					}
					
				}
			}
			 
			if( $is_email_confirm )
			{
				$recipt_email  = $dom->createElement('EmailNotificationList','');
				$recipt_email  = $receipt->appendChild($recipt_email);

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

				$email_note = $dom->createElement('NotificationEmail',""); //$email_notification
				$email_note = $recipt_email->appendChild($email_note);
			} 
			 $total_amount = $dom->createElement('Total',CRM_Utils_Rule::cleanMoney($params['amount'])*100);
			 $total_amount = $trans_totals->appendChild($total_amount);
			 
	         $strParam     =  $dom->saveXML();
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
   * Submit a recurring payment using Click & Pledge's PHP API:
   *
   * @param  array $params assoc array of input parameters for this transaction

   * @return array the result in a nice formatted array (or an error object)
   * @public
   */
  function doRecurPayment(&$params, $amount, $customer) {
    
  
  }

  /**
   * Transfer method not in use.
   *
   * @param array $params
   *   Name value pair of contribution data.
   *
   * @return void
   *
   * @access public
   *
   */
  function doTransferCheckout(&$params, $component) {
    CRM_Core_Error::fatal(ts('Use direct billing instead of Transfer method.'));
  }
}
