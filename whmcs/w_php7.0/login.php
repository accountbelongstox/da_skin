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
    header("Location: clientarea.php");
    exit( dirname(__FILE__) . " | line".__LINE__ );
}
$_SESSION['loginurlredirect'] = html_entity_decode($_SERVER['REQUEST_URI']);
if( WHMCS_Session::get('2faverifyc') )
{
    $templatefile = 'logintwofa';
    if( WHMCS_Session::get('2fabackupcodenew') )
    {
        $smartyvalues['newbackupcode'] = true;
    }
    else
    {
        if( $whmcs->get_req_var('incorrect') )
        {
            $smartyvalues['incorrect'] = true;
        }
    }
    $twofa = new WHMCS_2FA();
    if( $twofa->setClientID(WHMCS_Session::get('2faclientid')) )
    {
        if( !$twofa->isActiveClients() || !$twofa->isEnabled() )
        {
            WHMCS_Session::destroy();
            redir();
        }
        if( $whmcs->get_req_var('backupcode') )
        {
            $smartyvalues['backupcode'] = true;
        }
        else
        {
            $challenge = $twofa->moduleCall('challenge');
            if( $challenge )
            {
                $smartyvalues['challenge'] = $challenge;
            }
            else
            {
                $smartyvalues['error'] = "Bad 2 Factor Auth Module. Please contact support.";
            }
        }
    }
    else
    {
        $smartyvalues['error'] = "An error occurred. Please try again.";
    }
}
else
{
    $templatefile = 'login';
    $smartyvalues['loginpage'] = true;
    $smartyvalues['formaction'] = "dologin.php";
    if( $whmcs->get_req_var('incorrect') )
    {
        $smartyvalues['incorrect'] = true;
    }
}
outputClientArea($templatefile);
exit( dirname(__FILE__) . " | line".__LINE__ );