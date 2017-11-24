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
if( !isset($_REQUEST['projectid']) )
{
    $apiresults = array( 'result' => 'error', 'message' => "Project ID Not Set" );
}
else
{
    if( isset($_REQUEST['projectid']) )
    {
        $result = select_query('mod_project', '', array( 'id' => (int) $projectid ));
        $data = simulate_fetch_assoc($result);
        $projectid = $data['id'];
        if( !$projectid )
        {
            $apiresults = array( 'result' => 'error', 'message' => "Project ID Not Found" );
            return NULL;
        }
    }
    if( isset($_REQUEST['userid']) )
    {
        $result_userid = select_query('tblclients', 'id', array( 'id' => $_REQUEST['userid'] ));
        $data_userid = mysqli_fetch_array($result_userid);
        if( !$data_userid['id'] )
        {
            $apiresults = array( 'result' => 'error', 'message' => "Client ID Not Found" );
            return NULL;
        }
    }
    if( isset($_REQUEST['adminid']) )
    {
        $result_adminid = select_query('tbladmins', 'id', array( 'id' => $_REQUEST['adminid'] ));
        $data_adminid = mysqli_fetch_array($result_adminid);
        if( !$data_adminid['id'] )
        {
            $apiresults = array( 'result' => 'error', 'message' => "Admin ID Not Found" );
            return NULL;
        }
    }
    if( !isset($_REQUEST['task']) )
    {
        $apiresults = array( 'result' => 'error', 'message' => "A task description must be specified" );
    }
    else
    {
        $ordervalue = get_query_val('mod_projecttasks', "`order`", array( 'projectid' => $projectid ), 'order', 'DESC');
        $projectid = $_REQUEST['projectid'];
        $adminid = isset($_REQUEST['adminid']) ? $data_adminid['id'] : 0;
        $task = $_REQUEST['task'];
        $notes = $_REQUEST['notes'];
        $duedate = $_REQUEST['duedate'];
        $completed = isset($_REQUEST['completed']) ? 1 : 0;
        $billed = isset($_REQUEST['billed']) ? 1 : 0;
        $created = "now()";
        $ordervalue++;
        $apply = insert_query('mod_projecttasks', array( 'projectid' => $projectid, 'adminid' => $adminid, 'task' => $task, 'notes' => $notes, 'completed' => $completed, 'created' => $created, 'duedate' => $duedate, 'billed' => $billed, 'order' => $ordervalue ));
        $apiresults = array( 'result' => 'success', 'message' => "Task has been added" );
    }
}