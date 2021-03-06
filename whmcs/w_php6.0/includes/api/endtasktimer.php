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
if( !function_exists('saveCustomFields') )
{
    require(ROOTDIR . "/includes/customfieldfunctions.php");
}
if( isset($_REQUEST['projectid']) )
{
    $result = select_query('mod_project', '', array( 'id' => (int) $projectid ));
    $data = mysql_fetch_assoc($result);
    $projectid = $data['id'];
    if( !$projectid )
    {
        $apiresults = array( 'result' => 'error', 'message' => "Project ID Not Found" );
        return NULL;
    }
}
if( !isset($_REQUEST['timerid']) )
{
    $apiresults = array( 'result' => 'error', 'message' => "Timer ID Not Set" );
}
else
{
    if( isset($_REQUEST['timerid']) )
    {
        $result = select_query('mod_projecttimes', '', array( 'id' => $_REQUEST['timerid'] ));
        $data_timerid = mysql_fetch_assoc($result);
        $timerid = $data_timerid['id'];
        if( !$timerid )
        {
            $apiresults = array( 'result' => 'error', 'message' => "Timer ID Not Found" );
            return NULL;
        }
    }
    $timerid = $data_timerid['id'];
    if( isset($_REQUEST['adminid']) )
    {
        $result_adminid = select_query('tbladmins', 'id', array( 'id' => $_REQUEST['adminid'] ));
        $data_adminid = mysql_fetch_array($result_adminid);
        if( !$data_adminid['id'] )
        {
            $apiresults = array( 'result' => 'error', 'message' => "Admin ID Not Found" );
            return NULL;
        }
    }
    $projectid = $_REQUEST['projectid'];
    $adminid = $_REQUEST['adminid'];
    $endtime = isset($_REQUEST['end_time']) ? $_REQUEST['end_time'] : time();
    $updateqry = array(  );
    if( $projectid )
    {
        $updateqry['projectid'] = $projectid;
    }
    if( $adminid )
    {
        $updateqry['adminid'] = $adminid;
    }
    if( $timerid )
    {
        $updateqry['end'] = $endtime;
    }
    update_query('mod_projecttimes', $updateqry, array( 'id' => $timerid ));
    $apiresults = array( 'result' => 'success', 'message' => "Timer Has Ended" );
}