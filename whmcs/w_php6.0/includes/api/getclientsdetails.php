<?php //00e57
// *************************************************************************
// *                                                                       *
// * WHMCS - The Complete Client Management, Billing & Support Solution    *
// * Copyright (c) WHMCS Ltd. All Rights Reserved,                         *
// * Version: 5.3.14 (5.3.14-release.1)                                    *
// * BuildId: 0866bd1.62                                                   *
// * Build Date: 28 May 2015                                               *
// *                                                                       *
// *************************************************************************
// *                                                                       *
// * Email: info@whmcs.com                                                 *
// * Website: http://www.whmcs.com                                         *
// *                                                                       *
// *************************************************************************
// *                                                                       *
// * This software is furnished under a license and may be used and copied *
// * only  in  accordance  with  the  terms  of such  license and with the *
// * inclusion of the above copyright notice.  This software  or any other *
// * copies thereof may not be provided or otherwise made available to any *
// * other person.  No title to and  ownership of the  software is  hereby *
// * transferred.                                                          *
// *                                                                       *
// * You may not reverse  engineer, decompile, defeat  license  encryption *
// * mechanisms, or  disassemble this software product or software product *
// * license.  WHMCompleteSolution may terminate this license if you don't *
// * comply with any of the terms and conditions set forth in our end user *
// * license agreement (EULA).  In such event,  licensee  agrees to return *
// * licensor  or destroy  all copies of software  upon termination of the *
// * license.                                                              *
// *                                                                       *
// * Please see the EULA file for the full End User License Agreement.     *
// *                                                                       *
// *************************************************************************
if( !defined('WHMCS') )
{
    exit( "This file cannot be accessed directly" );
}
if( !function_exists('getClientsDetails') )
{
    require(ROOTDIR . "/includes/clientfunctions.php");
}
$where = array(  );
if( $clientid )
{
    $where['id'] = $clientid;
}
else
{
    if( $email )
    {
        $where['email'] = $email;
    }
    else
    {
        $apiresults = array( 'result' => 'error', 'message' => "Either clientid Or email Is Required" );
        return NULL;
    }
}
$result = select_query('tblclients', 'id', $where);
$data = mysql_fetch_array($result);
$clientid = $data['id'];
if( !$clientid )
{
    $apiresults = array( 'result' => 'error', 'message' => "Client Not Found" );
}
else
{
    $clientsdetails = getClientsDetails($clientid);
    $currency_result = full_query("SELECT code FROM tblcurrencies WHERE id=" . (int) $clientsdetails['currency']);
    $currency = mysql_fetch_assoc($currency_result);
    $clientsdetails['currency_code'] = $currency['code'];
    if( $responsetype == 'xml' )
    {
        $apiresults = array( 'result' => 'success', 'client' => $clientsdetails );
    }
    else
    {
        $apiresults = array_merge(array( 'result' => 'success' ), $clientsdetails);
    }
    if( $stats || $responsetype == 'xml' )
    {
        $apiresults = array( 'result' => 'success', 'client' => $clientsdetails, 'stats' => getClientsStats($clientid) );
    }
}