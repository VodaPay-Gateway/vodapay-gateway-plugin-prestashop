<?php
/*
 *
 *                     Copyright (c) 2011 TradeRoot (Pty) Ltd
 *
 *         This source file contains confidential, proprietary information
 *                and is a trade secret of TradeRoot (Pty) Ltd.
 *
 *         All use, disclosure, and/or reproduction is prohibited unless
 *                        expressly authorize in writing.
 *
 *                             All rights reserved.
 *
 * $Revision: 1.2 $
 *
 */

class ResponseCodeConstants {

	/* Begin - Good response codes */
	const RESPONSE_CODE_OK = "00";
	const RESPONSE_CODE_HONOR_WITH_IDENTIFIACTION = "08";
	const RESPONSE_CODE_APPROVED_PARTIAL = "10";
	const RESPONSE_CODE_APPROVED_VIP = "11";
	const RESPONSE_CODE_APPROVED_UPDATE_TRK3 = "16";
	/* End - Good response codes */

	public static function getGoodResponseCodeList() {
		$result = array(self::RESPONSE_CODE_OK,
		self::RESPONSE_CODE_HONOR_WITH_IDENTIFIACTION,
		self::RESPONSE_CODE_APPROVED_PARTIAL,
		self::RESPONSE_CODE_APPROVED_VIP,
		self::RESPONSE_CODE_APPROVED_UPDATE_TRK3);
		return $result;
	}

	/* Begin - Bad response codes */
	const RESPONSE_CODE_INVALID_MERCHANT = "03";
	const RESPONSE_CODE_PICKUP_CARD = "04";
	const RESPONSE_CODE_DO_NOT_HONOR = "05";
	const RESPONSE_CODE_ERROR = "06";
	const RESPONSE_CODE_CUSTOMER_CANCELLATION = "17";
	const RESPONSE_CODE_DUPLICATE_RECORD = "26";
	const RESPONSE_CODE_INSUFFICIENT_FUNDS = "51";
	const RESPONSE_CODE_EXPIRED_CARD = "54";
	const RESPONSE_CODE_NO_CARD_RECORD = "56";
	const RESPONSE_CODE_RESPONSE_RECEIVED_TOO_LATE = "68";
	const RESPONSE_CODE_NO_RESPONSE = "69";
	const RESPONSE_CODE_ISSUER_OR_SWITCH_INOPERATIVE = "91";
	const RESPONSE_CODE_ROUTING_ERROR = "92";
	const RESPONSE_CODE_SYSTEM_MALFUNCTION = "96";
	const RESPONSE_CODE_3DS_FAIL = "99";
	const RESPONSE_CODE_INVALID_SERVICE_LEVEL = "D1";
	const RESPONSE_CODE_INVALID_TRANSACTION_PARAMETERS_USAGE = "D2";
	const RESPONSE_CODE_REPEAT_PARAMETER_MISMATCH = "D3";
	const RESPONSE_CODE_TRANSACTION_IN_PROGRESS = "D4";
	/* End - Bad response codes */

	const UNKNOWN_RESPONSE_CODE_TEXT = "Unknown";

	public static function getBadResponseCodeList() {
		$result = array(self::RESPONSE_CODE_INVALID_MERCHANT,
		self::RESPONSE_CODE_PICKUP_CARD,
		self::RESPONSE_CODE_DO_NOT_HONOR,
		self::RESPONSE_CODE_ERROR,
		self::RESPONSE_CODE_CUSTOMER_CANCELLATION,
		self::RESPONSE_CODE_DUPLICATE_RECORD,
		self::RESPONSE_CODE_INSUFFICIENT_FUNDS,
		self::RESPONSE_CODE_EXPIRED_CARD,
		self::RESPONSE_CODE_NO_CARD_RECORD,
		self::RESPONSE_CODE_RESPONSE_RECEIVED_TOO_LATE,
		self::RESPONSE_CODE_NO_RESPONSE,
		self::RESPONSE_CODE_ISSUER_OR_SWITCH_INOPERATIVE,
		self::RESPONSE_CODE_ROUTING_ERROR,
		self::RESPONSE_CODE_SYSTEM_MALFUNCTION,
		self::RESPONSE_CODE_3DS_FAIL,
		self::RESPONSE_CODE_INVALID_SERVICE_LEVEL,
		self::RESPONSE_CODE_INVALID_TRANSACTION_PARAMETERS_USAGE,
		self::RESPONSE_CODE_REPEAT_PARAMETER_MISMATCH,
		self::RESPONSE_CODE_TRANSACTION_IN_PROGRESS);
		return $result;
	}

	public static function getResponseText() {
		$result = array(self::RESPONSE_CODE_OK => "Approved or completed successfully",
		self::RESPONSE_CODE_HONOR_WITH_IDENTIFIACTION => "Honor with identification",
		self::RESPONSE_CODE_APPROVED_PARTIAL => "Approved, partial",
		self::RESPONSE_CODE_APPROVED_VIP => "Approved, VIP",
		self::RESPONSE_CODE_APPROVED_UPDATE_TRK3 => "Approved, update track 3",
		self::RESPONSE_CODE_INVALID_MERCHANT => "Invalid merchant",
		self::RESPONSE_CODE_PICKUP_CARD => "Pick-up card",
		self::RESPONSE_CODE_DO_NOT_HONOR => "Do not honor",
		self::RESPONSE_CODE_ERROR => "Error",
		self::RESPONSE_CODE_CUSTOMER_CANCELLATION => "Customer cancellation",
		self::RESPONSE_CODE_DUPLICATE_RECORD => "Duplicate record",
		self::RESPONSE_CODE_INSUFFICIENT_FUNDS => "Insufficient funds",
		self::RESPONSE_CODE_EXPIRED_CARD => "Expired card",
		self::RESPONSE_CODE_NO_CARD_RECORD => "No card record",
		self::RESPONSE_CODE_RESPONSE_RECEIVED_TOO_LATE => "Response received too late",
		self::RESPONSE_CODE_NO_RESPONSE => "No Response",
		self::RESPONSE_CODE_ISSUER_OR_SWITCH_INOPERATIVE => "Issuer or switch inoperative",
		self::RESPONSE_CODE_ROUTING_ERROR => "Routing error",
		self::RESPONSE_CODE_SYSTEM_MALFUNCTION => "System malfunction",
		self::RESPONSE_CODE_3DS_FAIL => "3DSecure Fail",
		self::RESPONSE_CODE_INVALID_SERVICE_LEVEL => "Invalid Service Level or level exceeded.",
		self::RESPONSE_CODE_REPEAT_PARAMETER_MISMATCH => "Repeat attempted, but certain parameters do not match.",
		self::RESPONSE_CODE_TRANSACTION_IN_PROGRESS => "A transaction with the same parameters is already in progress.");
		return $result;
	}
	
	// Build HTML Breaked Response Fields
	public static function buildResponseFields($transactionContext) {
		$responseCode = $transactionContext->getResponseCode();
		$response = self::buildField('Transaction amount', $transactionContext->getAmount());
		$response .= self::buildField('Response Code',  $responseCode);
		$response .= self::buildField('Response Code Text', ResponseCodeConstants::lookUpResponseText($responseCode));
		$response .= self::buildField('Pan',  $transactionContext->getPan());
		$response .= self::buildField('Authorization Id',  $transactionContext->getAuthorizationId());
		$response .= self::buildField('Retrieval Reference',  $transactionContext->getRetrievalReferenceNumber());
		$response .= self::buildField('SessionId',  $transactionContext->getSessionId());
		$response .= self::buildField('Account Type',  $transactionContext->getAccountType());
		$response .= self::buildField('Transaction Type',  $transactionContext->getTransactionType());
		$response .= self::buildField('Card Acceptor Id Code',  $transactionContext->getCardAcceptorId());
		$response .= self::buildField('Card Acceptor Terminal Id',  $transactionContext->getCardAcceptorTerminalId());
		$response .= self::buildField('Currency Code',  $transactionContext->getCurrencyCode());
		$response .= self::buildField('Additional Data',  $transactionContext->getAdditionalData());
		$response .= self::buildField('Acquiring Institution Id Code',  $transactionContext->getAcquiringInstitutionIdCode());
		$response .= self::buildField('Receiving Institution Id Code',  $transactionContext->getReceivingInstitutionIdCode());
		$response .= self::buildField('Replacement Card Acceptor Id Code',  $transactionContext->getReplacementCardAcceptorIdCode());
		$response .= self::buildField('Replacement Card Acceptor Terminal Id',  $transactionContext->getReplacementCardAcceptorTerminalId());
		$response .= self::buildField('Uuid',  $transactionContext->getUuid());
		$response .= self::buildField('Cardholder Message',  $transactionContext->getCardholderMessage());
		return $response;
	}
	
	// Build HTML Breaked Response Field
	public static function buildField($label, $value) {
		$result='<b>' . $label . ':</b> ' . $value . '<br/>';
		return $result;
	}

	public static function isApproved($responseCode) {
		$responseList = self::getGoodResponseCodeList();
		//return isset($responseCode) && array_key_exists($responseCode, $responseList);
    		return isset($responseCode) && in_array($responseCode, $responseList);
	}

	public static function lookUpResponseText($responseCode) {
		$responseList = self::getResponseText();
		//if (isset($responseCode) && array_key_exists($responseCode, $responseList)) {
    		if (isset($responseCode) && in_array($responseCode, $responseList)) {
			$result = $responseList[$responseCode];
		} else {
			$result = self::UNKNOWN_RESPONSE_CODE_TEXT;
		}
		return $result;
	}
}
