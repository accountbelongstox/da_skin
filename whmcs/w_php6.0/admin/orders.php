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
define('ADMINAREA', true);
require("../init.php");
$action = $whmcs->get_req_var('action');
if( $action == 'view' )
{
    $reqperm = "View Order Details";
}
else
{
    $reqperm = "View Orders";
}
$aInt = new WHMCS_Admin($reqperm);
$aInt->title = $aInt->lang('orders', 'manage');
$aInt->sidebar = 'orders';
$aInt->icon = 'orders';
$aInt->helplink = "Order Management";
$aInt->requiredFiles(array( 'gatewayfunctions', 'orderfunctions', 'modulefunctions', 'domainfunctions', 'invoicefunctions', 'processinvoices', 'clientfunctions', 'ccfunctions', 'registrarfunctions' ));
list($massSuccesses, $massFailures) = explode(',', $whmcs->get_req_var('massstatus'));
$massSuccesses = (int) $massSuccesses;
$massFailures = (int) $massFailures;
if( $whmcs->get_req_var('masssuccess') == 1 )
{
    infoBox($aInt->lang('orders', 'statusmassaccept'), $massSuccesses . " " . $aInt->lang('orders', 'statusmassacceptmsg'), 'success');
}
else
{
    if( 0 < $massFailures )
    {
        $massErrors = explode(',', $whmcs->get_req_var('masserror'));
        foreach( $massErrors as $key => $value )
        {
            $massErrors[$key] = (int) $value;
        }
        $massErrors = implode(", ", $massErrors);
        infoBox($aInt->lang('orders', 'statusmassfailures'), sprintf($aInt->lang('orders', 'statusmassfailuresmsg'), $massSuccesses, $massFailures, $massErrors) . "  <a href=\"systemactivitylog.php\">" . $aInt->lang('system', 'activitylog') . "</a>", 'error');
    }
}
if( $whmcs->get_req_var('noDelete') )
{
    infoBox($aInt->lang('global', 'error'), $aInt->lang('orders', 'noDelete'), 'error');
    $action = 'view';
}
if( $whmcs->get_req_var('massDeleteError') )
{
    infoBox($aInt->lang('global', 'error'), $aInt->lang('orders', 'massDeleteError'), 'error');
}
if( $whmcs->get_req_var('rerunfraudcheck') )
{
    check_token("WHMCS.admin.default");
    $result = select_query('tblorders', 'id,userid,ipaddress', array( 'id' => $orderid ));
    $data = mysql_fetch_array($result);
    $orderid = $data['id'];
    $userid = $data['userid'];
    $ipaddress = $data['ipaddress'];
    $fraudmodule = 'maxmind';
    $fraud = new WHMCS_Module_Fraud();
    if( $fraud->load($fraudmodule) )
    {
        $results = $fraud->doFraudCheck($orderid, $userid, $ipaddress);
        $fraudresults = $fraud->processResultsForDisplay($orderid, $results['fraudoutput']);
        if( is_array($fraudresults) )
        {
            echo "<div id=\"fraudresults\"><table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\"><tr>";
            $i = 0;
            foreach( $fraudresults as $key => $value )
            {
                $i++;
                echo "<td class=\"fieldlabel\" width=\"30%\">" . $key . "</td><td class=\"fieldarea\"";
                if( $key == 'Explanation' )
                {
                    echo " colspan=\"3\"";
                    $i = 2;
                }
                else
                {
                    echo " width=\"20%\"";
                }
                echo ">" . $value . "</td>";
                if( $i == '2' )
                {
                    echo "</tr><tr>";
                    $i = 0;
                }
            }
            echo "</td></table></div>";
        }
    }
    exit();
}
if( $action == 'affassign' )
{
    if( $orderid && $affid )
    {
        check_token("WHMCS.admin.default");
        $result = select_query('tblhosting', 'id', array( 'orderid' => $orderid ));
        while( $data = mysql_fetch_array($result) )
        {
            $serviceid = $data['id'];
            insert_query('tblaffiliatesaccounts', array( 'affiliateid' => $affid, 'relid' => $serviceid ));
        }
        exit();
    }
    echo $aInt->lang('orders', 'chooseaffiliate') . "<br /><select name=\"affid\" id=\"affid\" style=\"width:270px;\">";
    $result = select_query('tblaffiliates', "tblaffiliates.id,tblclients.firstname,tblclients.lastname", '', 'firstname', 'ASC', '', "tblclients ON tblclients.id=tblaffiliates.clientid");
    while( $data = mysql_fetch_array($result) )
    {
        $aff_id = $data['id'];
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        echo "<option value=\"" . $aff_id . "\">" . $firstname . " " . $lastname . "</option>";
    }
    echo "</select>";
    exit();
}
if( $action == 'ajaxchangeorderstatus' )
{
    check_token("WHMCS.admin.default");
    $id = get_query_val('tblorders', 'id', array( 'id' => $id ));
    $result = select_query('tblorderstatuses', 'title', '', 'sortorder', 'ASC');
    while( $data = mysql_fetch_array($result) )
    {
        $statusesarr[] = $data['title'];
    }
    if( in_array($status, $statusesarr) && $id )
    {
        update_query('tblorders', array( 'status' => $status ), array( 'id' => $id ));
        echo $id;
    }
    else
    {
        echo 0;
    }
    exit();
}
if( $action == 'ajaxCanOrderBeDeleted' )
{
    check_token("WHMCS.admin.default");
    $id = get_query_val('tblorders', 'id', array( 'id' => $id ));
    echo canOrderBeDeleted($id);
    exit();
}
$filters = new WHMCS_Filter();
if( $action == 'delete' && $id )
{
    check_token("WHMCS.admin.default");
    checkPermission("Delete Order");
    if( canOrderBeDeleted($id) )
    {
        deleteOrder($id);
        $filters->redir();
    }
    else
    {
        $filters->redir("noDelete=true&id=" . $id);
    }
}
if( $action == 'cancel' && $id )
{
    check_token("WHMCS.admin.default");
    checkPermission("View Order Details");
    changeOrderStatus($id, 'Cancelled');
    $filters->redir();
}
if( $action == 'cancelDelete' && $id )
{
    check_token("WHMCS.admin.default");
    checkPermission("View Order Details");
    changeOrderStatus($id, 'Cancelled');
    checkPermission("Delete Order");
    if( canOrderBeDeleted($id) )
    {
        deleteOrder($id);
        $filters->redir();
    }
    else
    {
        $filters->redir("noDelete=true&id=" . $id);
    }
}
if( $whmcs->get_req_var('massaccept') )
{
    check_token("WHMCS.admin.default");
    checkPermission("View Order Details");
    $acceptErrors = array(  );
    $successes = $failures = 0;
    if( is_array($selectedorders) )
    {
        foreach( $selectedorders as $orderid )
        {
            $errors = acceptOrder($orderid);
            if( empty($errors) )
            {
                $successes++;
            }
            else
            {
                $acceptErrors[] = $orderid;
                $failures++;
            }
        }
    }
    if( empty($acceptErrors) )
    {
        $massStatus = "&masssuccess=1";
    }
    else
    {
        $massStatus = "&masserror=" . implode(',', $acceptErrors);
    }
    $filters->redir("massstatus=" . $successes . ',' . $failures . $massStatus);
}
if( $whmcs->get_req_var('masscancel') )
{
    check_token("WHMCS.admin.default");
    checkPermission("View Order Details");
    if( is_array($selectedorders) )
    {
        foreach( $selectedorders as $orderid )
        {
            changeOrderStatus($orderid, 'Cancelled');
        }
    }
    $filters->redir();
}
if( $whmcs->get_req_var('massdelete') )
{
    check_token("WHMCS.admin.default");
    checkPermission("Delete Order");
    $deleteError = '';
    if( is_array($selectedorders) )
    {
        foreach( $selectedorders as $orderid )
        {
            if( canOrderBeDeleted($orderid) )
            {
                deleteOrder($orderid);
            }
            else
            {
                $deleteError = "massDeleteError=true";
            }
        }
    }
    $filters->redir($deleteError);
}
if( $whmcs->get_req_var('sendmessage') )
{
    check_token("WHMCS.admin.default");
    $clientslist = '';
    $result = select_query('tblorders', "DISTINCT userid", "id IN (" . db_build_in_array($selectedorders) . ")");
    while( $data = mysql_fetch_array($result) )
    {
        $clientslist .= "selectedclients[]=" . $data['userid'] . "&";
    }
    redir("type=general&multiple=true&" . substr($clientslist, 0, 0 - 1), "sendmessage.php");
}
ob_start();
if( !$action )
{
    echo $infobox;
    WHMCS_Session::release();
    echo $aInt->Tabs(array( $aInt->lang('global', 'searchfilter') ), true);
    $client = $filters->get('client');
    $clientid = $filters->get('clientid');
    if( !$clientid && $client )
    {
        $clientid = $client;
    }
    $clientname = $filters->get('clientname');
    echo "\n<div id=\"tab0box\" class=\"tabbox\">\n  <div id=\"tab_content\">\n\n<form action=\"";
    echo $whmcs->getPhpSelf();
    echo "\" method=\"post\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
    echo $aInt->lang('fields', 'orderid');
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"orderid\" size=\"8\" value=\"";
    echo $orderid = $filters->get('orderid');
    echo "\"></td><td width=\"15%\" class=\"fieldlabel\">";
    echo $aInt->lang('fields', 'client');
    echo "</td><td class=\"fieldarea\">";
    echo $aInt->clientsDropDown($clientid, '', 'clientid', true);
    echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang('fields', 'ordernum');
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ordernum\" size=\"20\" value=\"";
    echo $ordernum = $filters->get('ordernum');
    echo "\"></td><td class=\"fieldlabel\">";
    echo $aInt->lang('fields', 'paymentstatus');
    echo "</td><td class=\"fieldarea\"><select name=\"paymentstatus\">\n<option value=\"\">";
    echo $aInt->lang('global', 'any');
    echo "</option>\n<option value=\"Paid\"";
    $paymentstatus = $filters->get('paymentstatus');
    if( $paymentstatus == 'Paid' )
    {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang('status', 'paid');
    echo "</option>\n<option value=\"Unpaid\"";
    if( $paymentstatus == 'Unpaid' )
    {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang('status', 'unpaid');
    echo "</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang('fields', 'date');
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"orderdate\" value=\"";
    echo $orderdate = $filters->get('orderdate');
    echo "\" class=\"datepick\"></td><td class=\"fieldlabel\">";
    echo $aInt->lang('fields', 'status');
    echo "</td><td class=\"fieldarea\"><select name=\"status\">\n<option value=\"\">";
    echo $aInt->lang('global', 'any');
    echo "</option>\n";
    $status = $filters->get('status');
    $result = select_query('tblorderstatuses', '', '', 'sortorder', 'ASC');
    while( $data = mysql_fetch_array($result) )
    {
        echo "<option value=\"" . $data['title'] . "\" style=\"color:" . $data['color'] . "\"";
        if( $status == $data['title'] )
        {
            echo " selected";
        }
        echo ">" . ($aInt->lang('status', strtolower($data['title'])) ? $aInt->lang('status', strtolower($data['title'])) : $data['title']) . "</option>";
    }
    echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang('fields', 'amount');
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"amount\" value=\"";
    echo $amount = $filters->get('amount');
    echo "\" size=\"10\"></td><td class=\"fieldlabel\">";
    echo $aInt->lang('fields', 'ipaddress');
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"orderip\" value=\"";
    echo $orderip = $filters->get('orderip');
    echo "\" size=\"20\"></td></tr>\n</table>\n\n<img src=\"images/spacer.gif\" height=\"8\" width=\"1\"><br>\n<div align=\"center\"><input type=\"submit\" value=\"";
    echo $aInt->lang('global', 'search');
    echo "\" class=\"button\"></div>\n\n</form>\n\n  </div>\n</div>\n\n<br>\n\n";
    $aInt->deleteJSConfirm('doDelete', 'orders', 'confirmdelete', "orders.php?action=delete&id=");
    $aInt->deleteJSConfirm('doCancelDelete', 'orders', 'confirmCancelDelete', "orders.php?action=cancelDelete&id=");
    $name = 'orders';
    $orderby = 'id';
    $sort = 'DESC';
    $pageObj = new WHMCS_Pagination($name, $orderby, $sort);
    $pageObj->digestCookieData();
    $filters->store();
    $tbl = new WHMCS_ListTable($pageObj);
    $tbl->setColumns(array( 'checkall', array( 'id', $aInt->lang('fields', 'id') ), array( 'ordernum', $aInt->lang('fields', 'ordernum') ), array( 'date', $aInt->lang('fields', 'date') ), $aInt->lang('fields', 'clientname'), array( 'paymentmethod', $aInt->lang('fields', 'paymentmethod') ), array( 'amount', $aInt->lang('fields', 'total') ), $aInt->lang('fields', 'paymentstatus'), array( 'status', $aInt->lang('fields', 'status') ), '' ));
    $criteria = array( 'clientid' => $clientid, 'amount' => $amount, 'orderid' => $orderid, 'ordernum' => $ordernum, 'orderip' => $orderip, 'orderdate' => $orderdate, 'clientname' => $clientname, 'paymentstatus' => $paymentstatus, 'status' => $status );
    $ordersModel = new WHMCS_Orders($pageObj);
    $ordersModel->execute($criteria);
    $numresults = $pageObj->getNumResults();
    if( $filters->isActive() && $numresults == 1 )
    {
        $order = $pageObj->getOne();
        redir("action=view&id=" . $order['id']);
    }
    else
    {
        $orderlist = $pageObj->getData();
        foreach( $orderlist as $order )
        {
            if( canOrderBeDeleted($order['id']) )
            {
                $function = 'doDelete';
                $alt = $aInt->lang('global', 'delete');
            }
            else
            {
                $function = 'doCancelDelete';
                $alt = $aInt->lang('global', 'cancelAndDelete');
            }
            $deleteIcon = "<a href=\"#\" onClick=\"" . $function . "('" . $order['id'] . "');return false\">" . "<img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $alt . "\"></a>";
            $tbl->addRow(array( "<input type='checkbox' name='selectedorders[]' value='" . $order['id'] . "' class='checkall'>", "<a href='?action=view&id=" . $order['id'] . "'><b>" . $order['id'] . "</b></a>", $order['ordernum'], $order['date'], $order['clientname'], $order['paymentmethod'], $order['amount'], $order['paymentstatusformatted'], $order['statusformatted'], $deleteIcon ));
        }
        $tbl->setMassActionBtns("<input type=\"submit\" name=\"massaccept\" value=\"" . $aInt->lang('orders', 'accept') . "\" class=\"btn-success\" onclick=\"return confirm('" . $aInt->lang('orders', 'acceptconfirm', '1') . "')\" /> <input type=\"submit\" name=\"masscancel\" value=\"" . $aInt->lang('orders', 'cancel') . "\" onclick=\"return confirm('" . $aInt->lang('orders', 'cancelconfirm', '1') . "')\" /> <input type=\"submit\" name=\"massdelete\" value=\"" . $aInt->lang('orders', 'delete') . "\" class=\"btn-danger\" onclick=\"return confirm('" . $aInt->lang('orders', 'deleteconfirm', '1') . "')\" /> <input type=\"submit\" name=\"sendmessage\" value=\"" . $aInt->lang('global', 'sendmessage') . "\" />");
        echo $tbl->output();
        unset($orderlist);
        unset($ordersModel);
    }
}
else
{
    if( $action == 'view' )
    {
        if( $whmcs->get_req_var('activate') )
        {
            check_token("WHMCS.admin.default");
            $errors = acceptOrder($id, $vars);
            WHMCS_Cookie::set('OrderAccept', $errors);
            redir("action=view&id=" . $id . "&activated=true");
        }
        if( $whmcs->get_req_var('cancel') )
        {
            check_token("WHMCS.admin.default");
            changeOrderStatus($id, 'Cancelled');
            redir("action=view&id=" . $id . "&cancelled=true");
        }
        if( $whmcs->get_req_var('fraud') )
        {
            check_token("WHMCS.admin.default");
            changeOrderStatus($id, 'Fraud');
            redir("action=view&id=" . $id . "&frauded=true");
        }
        if( $whmcs->get_req_var('pending') )
        {
            check_token("WHMCS.admin.default");
            changeOrderStatus($id, 'Pending');
            redir("action=view&id=" . $id . "&backpending=true");
        }
        if( $whmcs->get_req_var('cancelrefund') )
        {
            check_token("WHMCS.admin.default");
            checkPermission("Refund Invoice Payments");
            $error = cancelRefundOrder($id);
            redir("action=view&id=" . $id . "&cancelledrefunded=true&error=" . $error);
        }
        if( $whmcs->get_req_var('activated') && isset($_COOKIE['WHMCSOrderAccept']) )
        {
            $errors = WHMCS_Cookie::get('OrderAccept', 1);
            WHMCS_Cookie::delete('OrderAccept');
            if( count($errors) )
            {
                infoBox($aInt->lang('orders', 'statusaccepterror'), implode("<br>", $errors), 'error');
            }
            else
            {
                infoBox($aInt->lang('orders', 'statusaccept'), $aInt->lang('orders', 'statusacceptmsg'), 'success');
            }
        }
        if( $whmcs->get_req_var('cancelled') )
        {
            infoBox($aInt->lang('orders', 'statuscancelled'), $aInt->lang('orders', 'statuschangemsg'));
        }
        if( $whmcs->get_req_var('frauded') )
        {
            infoBox($aInt->lang('orders', 'statusfraud'), $aInt->lang('orders', 'statuschangemsg'));
        }
        if( $whmcs->get_req_var('backpending') )
        {
            infoBox($aInt->lang('orders', 'statuspending'), $aInt->lang('orders', 'statuschangemsg'));
        }
        if( $whmcs->get_req_var('cancelledrefunded') )
        {
            $error = $whmcs->get_req_var('error');
            if( $error == 'noinvoice' )
            {
                infoBox($aInt->lang('orders', 'statusrefundfailed'), $aInt->lang('orders', 'statusrefundnoinvoice'), 'error');
            }
            else
            {
                if( $error == 'notpaid' )
                {
                    infoBox($aInt->lang('orders', 'statusrefundfailed'), $aInt->lang('orders', 'statusrefundnotpaid'), 'error');
                }
                else
                {
                    if( $error == 'alreadyrefunded' )
                    {
                        infoBox($aInt->lang('orders', 'statusrefundfailed'), $aInt->lang('orders', 'statusrefundalready'), 'error');
                    }
                    else
                    {
                        if( $error == 'refundfailed' )
                        {
                            infoBox($aInt->lang('orders', 'statusrefundfailed'), $aInt->lang('orders', 'statusrefundfailedmsg'), 'error');
                        }
                        else
                        {
                            if( $error == 'manual' )
                            {
                                infoBox($aInt->lang('orders', 'statusrefundfailed'), $aInt->lang('orders', 'statusrefundnoauto'), 'error');
                            }
                            else
                            {
                                infoBox($aInt->lang('orders', 'statusrefundsuccess'), $aInt->lang('orders', 'statusrefundsuccessmsg'), 'success');
                            }
                        }
                    }
                }
            }
        }
        if( $whmcs->get_req_var('updatenotes') )
        {
            check_token("WHMCS.admin.default");
            update_query('tblorders', array( 'notes' => $notes ), array( 'id' => $id ));
            exit();
        }
        echo $infobox;
        $gatewaysarray = getGatewaysArray();
        require(ROOTDIR . "/includes/countries.php");
        $result = select_query('tblorders', "tblorders.*,tblclients.firstname,tblclients.lastname,tblclients.email,tblclients.companyname,tblclients.address1,tblclients.address2,tblclients.city,tblclients.state,tblclients.postcode,tblclients.country,tblclients.groupid,(SELECT status FROM tblinvoices WHERE id=tblorders.invoiceid) AS invoicestatus", array( "tblorders.id" => $id ), '', '', '', "tblclients ON tblclients.id=tblorders.userid");
        $data = mysql_fetch_array($result);
        $id = $data['id'];
        if( !$id )
        {
            exit( "Order not found... Exiting..." );
        }
        $ordernum = $data['ordernum'];
        $userid = $data['userid'];
        $aInt->assertClientBoundary($userid);
        $date = $data['date'];
        $amount = $data['amount'];
        $paymentmethod = $data['paymentmethod'];
        $paymentmethod = $gatewaysarray[$paymentmethod];
        $orderstatus = $data['status'];
        $showpending = get_query_val('tblorderstatuses', 'showpending', array( 'title' => $orderstatus ));
        $amount = $data['amount'];
        $client = $aInt->outputClientLink($userid, $data['firstname'], $data['lastname'], $data['companyname'], $data['groupid']);
        $address = $data['address1'];
        if( $data['address2'] )
        {
            $address .= ", " . $data['address2'];
        }
        $address .= "<br />" . $data['city'] . ", " . $data['state'] . ", " . $data['postcode'] . "<br />" . $countries[$data['country']];
        $ipaddress = $data['ipaddress'];
        $clientemail = $data['email'];
        $invoiceid = $data['invoiceid'];
        $nameservers = $data['nameservers'];
        $nameservers = explode(',', $nameservers);
        $transfersecret = $data['transfersecret'];
        $transfersecret = $transfersecret ? unserialize($transfersecret) : array(  );
        $renewals = $data['renewals'];
        $promocode = $data['promocode'];
        $promotype = $data['promotype'];
        $promovalue = $data['promovalue'];
        $orderdata = $data['orderdata'];
        $fraudmodule = $data['fraudmodule'];
        $fraudoutput = $data['fraudoutput'];
        $notes = $data['notes'];
        $contactid = $data['contactid'];
        $invoicestatus = $data['invoicestatus'];
        $date = fromMySQLDate($date, 'time');
        $jscode = "function cancelOrder() {\n    if (confirm(\"" . $aInt->lang('orders', 'confirmcancel') . "\"))\n        window.location=\"" . $_SERVER['PHP_SELF'] . "?action=view&id=" . $id . "&cancel=true" . generate_token('link') . "\";\n}\nfunction cancelRefundOrder() {\n    if (confirm(\"" . $aInt->lang('orders', 'confirmcancelrefund') . "\"))\n        window.location=\"" . $_SERVER['PHP_SELF'] . "?action=view&id=" . $id . "&cancelrefund=true" . generate_token('link') . "\";\n}\nfunction fraudOrder() {\n    if (confirm(\"" . $aInt->lang('orders', 'confirmfraud') . "\"))\n        window.location=\"" . $_SERVER['PHP_SELF'] . "?action=view&id=" . $id . "&fraud=true" . generate_token('link') . "\";\n}\nfunction pendingOrder() {\n    if (confirm(\"" . $aInt->lang('orders', 'confirmpending') . "\"))\n        window.location=\"" . $_SERVER['PHP_SELF'] . "?action=view&id=" . $id . "&pending=true" . generate_token('link') . "\";\n}\nfunction deleteOrder() {\n    \$.post(\n        \"" . $_SERVER['PHP_SELF'] . "?action=ajaxCanOrderBeDeleted&id=" . $id . "\",\n            { token: \"" . generate_token('plain') . "\" },\n           function (data) {\n                if (data == 1) {\n                    if (confirm(\"" . $aInt->lang('orders', 'confirmdelete') . "\")) {\n                        window.location=\"" . $_SERVER['PHP_SELF'] . "?action=delete&id=" . $id . generate_token('link') . "\";\n                    }\n                } else {\n                    alert(\"" . $aInt->lang('orders', 'noDelete') . "\");\n                }\n           }\n    )\n}\n";
        $currency = getCurrency($userid);
        $amount = formatCurrency($amount);
        $jquerycode = "\$(\"#ajaxchangeorderstatus\").change(function() {\n    var newstatus = \$(\"#ajaxchangeorderstatus\").val();\n    \$.post(\"" . $_SERVER['PHP_SELF'] . "?action=ajaxchangeorderstatus&id=" . $id . "\",\n    { status: newstatus, token: \"" . generate_token('plain') . "\" },\n   function(data) {\n     if(data == " . $id . "){\n         \$(\"#orderstatusupdated\").fadeIn().fadeOut(5000);\n     }\n   });\n});";
        $statusoptions = "<select id=\"ajaxchangeorderstatus\" style=\"font-size:14px;\">";
        $result = select_query('tblorderstatuses', '', '', 'sortorder', 'ASC');
        while( $data = mysql_fetch_array($result) )
        {
            $statusoptions .= "<option style=\"color:" . $data['color'] . "\"";
            if( $orderstatus == $data['title'] )
            {
                $statusoptions .= " selected";
            }
            $statusoptions .= ">" . ($aInt->lang('status', strtolower($data['title'])) ? $aInt->lang('status', strtolower($data['title'])) : $data['title']) . "</option>";
        }
        $statusoptions .= "</select>&nbsp;<span id=\"orderstatusupdated\" style=\"display:none;padding-top:14px;\"><img src=\"images/icons/tick.png\" /></span>";
        $orderdata = unserialize($orderdata);
        if( $invoiceid == '0' )
        {
            $paymentstatus = "<span class=\"textgreen\">" . $aInt->lang('orders', 'noinvoicedue') . "</span>";
        }
        else
        {
            if( !$invoicestatus )
            {
                $paymentstatus = "<span class=\"textred\">Invoice Deleted</span>";
            }
            else
            {
                if( $invoicestatus == 'Paid' )
                {
                    $paymentstatus = "<span class=\"textgreen\">" . $aInt->lang('status', 'complete') . "</span>";
                }
                else
                {
                    if( $invoicestatus == 'Unpaid' )
                    {
                        $paymentstatus = "<span class=\"textred\">" . $aInt->lang('status', 'incomplete') . "</span>";
                    }
                    else
                    {
                        $paymentstatus = getInvoiceStatusColour($invoicestatus);
                    }
                }
            }
        }
        run_hook('ViewOrderDetailsPage', array( 'orderid' => $id, 'ordernum' => $ordernum, 'userid' => $userid, 'amount' => $amount, 'paymentmethod' => $paymentmethod, 'invoiceid' => $invoiceid, 'status' => $orderstatus ));
        $clientnotes = array(  );
        $result = select_query('tblnotes', "tblnotes.*,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE tbladmins.id=tblnotes.adminid) AS adminuser", array( 'userid' => $userid, 'sticky' => '1' ), 'modified', 'DESC');
        while( $data = mysql_fetch_assoc($result) )
        {
            $data['created'] = fromMySQLDate($data['created'], 1);
            $data['modified'] = fromMySQLDate($data['modified'], 1);
            $data['note'] = autoHyperLink(nl2br($data['note']));
            $clientnotes[] = $data;
        }
        if( count($clientnotes) )
        {
            echo "<div id=\"clientsimportantnotes\">\n";
            foreach( $clientnotes as $note )
            {
                echo "<div class=\"ticketstaffnotes\">\n    <table class=\"ticketstaffnotestable\">\n        <tr>\n            <td>" . $note['adminuser'] . "</td>\n            <td align=\"right\">" . $note['modified'] . "</td>\n        </tr>\n    </table>\n    <div>\n        " . $note['note'] . "\n        <div style=\"float:right;\"><a href=\"clientsnotes.php?userid=" . $userid . "&action=edit&id=" . $note['id'] . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" align=\"absmiddle\" /></a></div>\n    </div>\n</div>\n";
            }
            echo "</div>";
        }
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang('fields', 'date');
        echo "</td><td class=\"fieldarea\">";
        echo $date;
        echo "</td><td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang('fields', 'paymentmethod');
        echo "</td><td class=\"fieldarea\">";
        echo $paymentmethod;
        echo "</td></tr>\n<tr><td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang('fields', 'ordernum');
        echo "</td><td class=\"fieldarea\">";
        echo $ordernum . " (ID: " . $id . ")";
        echo "</td><td class=\"fieldlabel\">";
        echo $aInt->lang('fields', 'amount');
        echo "</td><td class=\"fieldarea\">";
        echo $amount;
        echo "</td></tr>\n<tr><td class=\"fieldlabel\" rowspan=\"3\" valign=\"top\">";
        echo $aInt->lang('fields', 'client');
        echo "</td><td class=\"fieldarea\" rowspan=\"3\" valign=\"top\"><a href=\"clientssummary.php?userid=";
        echo $userid;
        echo "\">";
        echo $client;
        echo "</a><br />";
        echo $address;
        echo "</td><td class=\"fieldlabel\">";
        echo $aInt->lang('fields', 'invoicenum');
        echo "</td><td class=\"fieldarea\">";
        if( $invoiceid )
        {
            echo "<a href=\"invoices.php?action=edit&id=" . $invoiceid . "\">" . $invoiceid . "</a>";
        }
        else
        {
            echo "No Invoice";
        }
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang('fields', 'status');
        echo "</td><td class=\"fieldarea\">";
        echo $statusoptions;
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang('fields', 'ipaddress');
        echo "</td><td class=\"fieldarea\">";
        echo $ipaddress;
        echo " - <a href=\"http://www.geoiptool.com/en/?IP=";
        echo $ipaddress;
        echo "\" target=\"_blank\">";
        echo $aInt->lang('orders', 'iplookup');
        echo "</a> | <a href=\"orders.php?orderip=";
        echo $ipaddress;
        echo "\">";
        echo $aInt->lang('gatewaytranslog', 'filter');
        echo "</a> | <a href=\"configbannedips.php?ip=";
        echo $ipaddress;
        echo "&reason=Banned due to Orders&year=2020&month=12&day=31&hour=23&minutes=59";
        echo generate_token('link');
        echo "\">";
        echo $aInt->lang('orders', 'ipban');
        echo "</a></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang('fields', 'promocode');
        echo "</td><td class=\"fieldarea\">";
        if( $promocode )
        {
            if( strpos($promotype, 'Percentage') )
            {
                echo $promocode . " - " . $promovalue . "% " . str_replace('Percentage', '', $promotype);
            }
            else
            {
                echo $promocode . " - " . formatCurrency($promovalue) . " " . str_replace("Fixed Amount", '', $promotype);
            }
            echo "<br />";
        }
        if( is_array($orderdata) )
        {
            if( array_key_exists('bundleids', $orderdata) && is_array($orderdata['bundleids']) )
            {
                foreach( $orderdata['bundleids'] as $bid )
                {
                    $bundlename = get_query_val('tblbundles', 'name', array( 'id' => $bid ));
                    if( !$bundlename )
                    {
                        $bundlename = "Bundle Has Been Deleted";
                    }
                    echo "Bundle ID " . $bid . " - " . $bundlename . "<br />";
                }
            }
        }
        else
        {
            if( !$promocode )
            {
                echo 'None';
            }
        }
        echo "</td><td class=\"fieldlabel\">";
        echo $aInt->lang('fields', 'affiliate');
        echo "</td><td class=\"fieldarea\" id=\"affiliatefield\">";
        $affid = get_query_val('tblaffiliatesaccounts', 'affiliateid', array( "tblhosting.orderid" => $id ), '', '', '1', "tblhosting on tblhosting.id = tblaffiliatesaccounts.relid");
        if( $affid )
        {
            $result = select_query('tblaffiliates', "tblaffiliates.id,firstname,lastname", array( "tblaffiliates.id" => $affid ), '', '', '', "tblclients ON tblclients.id=tblaffiliates.clientid");
            $data = mysql_fetch_array($result);
            $affid = $data['id'];
            $afffirstname = $data['firstname'];
            $afflastname = $data['lastname'];
            echo "<a href=\"affiliates.php?action=edit&id=" . $affid . "\">" . $afffirstname . " " . $afflastname . "</a>";
        }
        else
        {
            echo $aInt->lang('orders', 'affnone') . " - <a href=\"#\" id=\"showaffassign\">" . $aInt->lang('orders', 'affmanualassign') . "</a>";
        }
        echo "</td></tr>\n</table>\n\n<div id=\"togglenotesbtnholder\" style=\"float:right;margin:10px;\"><input type=\"button\" value=\"";
        echo $notes ? 'Hide' : 'Add';
        echo " Notes\" id=\"togglenotesbtn\" /></div>\n\n<p><b>";
        echo $aInt->lang('orders', 'items');
        echo "</b></p>\n\n<form method=\"post\" action=\"whois.php\" target=\"_blank\" id=\"frmWhois\">\n<input type=\"hidden\" name=\"domain\" value=\"\" id=\"frmWhoisDomain\" />\n</form>\n\n<form method=\"post\" action=\"";
        echo $_SERVER['PHP_SELF'];
        echo "?action=view&id=";
        echo $id;
        echo "&activate=true\">\n\n<div class=\"tablebg\">\n<table class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n<tr><th>";
        echo $aInt->lang('fields', 'item');
        echo "</th><th>";
        echo $aInt->lang('fields', 'description');
        echo "</th><th>";
        echo $aInt->lang('fields', 'billingcycle');
        echo "</th><th>";
        echo $aInt->lang('fields', 'amount');
        echo "</th><th>";
        echo $aInt->lang('fields', 'status');
        echo "</th><th>";
        echo $aInt->lang('fields', 'paymentstatus');
        echo "</th></tr>\n";
        $result = select_query('tblhosting', '', array( 'orderid' => $id ));
        while( $data = mysql_fetch_array($result) )
        {
            $hostingid = $data['id'];
            $domain = $data['domain'];
            $billingcycle = $data['billingcycle'];
            $hostingstatus = $data['domainstatus'];
            $firstpaymentamount = formatCurrency($data['firstpaymentamount']);
            $recurringamount = $data['amount'];
            $packageid = $data['packageid'];
            $server = $data['server'];
            $regdate = $data['regdate'];
            $nextduedate = $data['nextduedate'];
            $serverusername = $data['username'];
            $serverpassword = decrypt($data['password']);
            if( !$serverusername )
            {
                $serverusername = createServerUsername($domain);
            }
            if( !$serverpassword )
            {
                $serverpassword = createServerPassword();
            }
            $result2 = select_query('tblproducts', "tblproducts.name,tblproducts.type,tblproducts.welcomeemail,tblproducts.autosetup,tblproducts.servertype,tblproductgroups.name AS groupname", array( "tblproducts.id" => $packageid ), '', '', '', "tblproductgroups ON tblproducts.gid=tblproductgroups.id");
            $data = mysql_fetch_array($result2);
            $groupname = $data['groupname'];
            $productname = $data['name'];
            $producttype = $data['type'];
            $welcomeemail = $data['welcomeemail'];
            $autosetup = $data['autosetup'];
            $servertype = $data['servertype'];
            if( $domain && $producttype != 'other' )
            {
                $domain .= "<br />(<a href=\"http://" . $domain . "\" target=\"_blank\" style=\"color:#cc0000\">www</a> <a href=\"#\" onclick=\"\$('#frmWhoisDomain').val('" . addslashes($domain) . "');\$('#frmWhois').submit();return false\">" . $aInt->lang('domains', 'whois') . "</a> <a href=\"http://www.intodns.com/" . $domain . "\" target=\"_blank\" style=\"color:#006633\">intoDNS</a>)";
            }
            echo "<tr><td align=\"center\"><a href=\"clientsservices.php?userid=" . $userid . "&id=" . $hostingid . "\"><b>";
            if( $producttype == 'hostingaccount' )
            {
                echo $aInt->lang('orders', 'sharedhosting');
            }
            else
            {
                if( $producttype == 'reselleraccount' )
                {
                    echo $aInt->lang('orders', 'resellerhosting');
                }
                else
                {
                    if( $producttype == 'server' )
                    {
                        echo $aInt->lang('orders', 'server');
                    }
                    else
                    {
                        if( $producttype == 'other' )
                        {
                            echo $aInt->lang('orders', 'other');
                        }
                    }
                }
            }
            echo "</b></a></td><td>" . $groupname . " - " . $productname . "<br>" . $domain . "</td><td>" . $aInt->lang('billingcycles', str_replace(array( '-', 'account', " " ), '', strtolower($billingcycle))) . "</td><td>" . $firstpaymentamount . "</td><td>" . $aInt->lang('status', strtolower($hostingstatus)) . "</td><td><b>" . $paymentstatus . "</td></tr>";
            if( $showpending && $hostingstatus == 'Pending' )
            {
                echo "<tr><td style=\"background-color:#EFF2F9;text-align:center;\" colspan=\"6\">";
                if( $servertype )
                {
                    echo $aInt->lang('fields', 'username') . ": <input type=\"text\" name=\"vars[products][" . $hostingid . "]" . "[username]\" size=\"12\" value=\"" . $serverusername . "\"> " . $aInt->lang('fields', 'password') . ": <input type=\"text\" name=\"vars[products][" . $hostingid . "]" . "[password]\" size=\"12\" value=\"" . $serverpassword . "\"> " . $aInt->lang('fields', 'server') . ": <select name=\"vars[products][" . $hostingid . "]" . "[server]\" style=\"width:150px;\"><option value=\"\">None</option>";
                    $result2 = select_query('tblservers', '', array( 'type' => $servertype ), 'name', 'ASC');
                    while( $data2 = mysql_fetch_array($result2) )
                    {
                        $serverid = $data2['id'];
                        $servername = $data2['name'];
                        $servermaxaccounts = $data2['maxaccounts'];
                        $result3 = select_query('tblhosting', '', "server='" . $serverid . "' AND (domainstatus='Active' OR domainstatus='Suspended')");
                        $servernumaccounts = mysql_num_rows($result3);
                        echo "<option value=\"" . $serverid . "\"";
                        if( $serverid == $server )
                        {
                            echo " selected";
                        }
                        echo ">" . $servername . " (" . $servernumaccounts . '/' . $servermaxaccounts . ")";
                    }
                    echo "</select> <label><input type=\"checkbox\" name=\"vars[products][" . $hostingid . "]" . "[runcreate]\"";
                    if( $hostingstatus == 'Pending' && $autosetup )
                    {
                        echo " checked";
                    }
                    echo "> " . $aInt->lang('orders', 'runmodule') . "</label> ";
                }
                echo "<label><input type=\"checkbox\" name=\"vars[products][" . $hostingid . "]" . "[sendwelcome]\"";
                if( $hostingstatus == 'Pending' && $welcomeemail )
                {
                    echo " checked";
                }
                echo "> " . $aInt->lang('orders', 'sendwelcome') . "</label></td></tr>";
            }
        }
        $predefinedaddons = array(  );
        $result = select_query('tbladdons', '', '');
        while( $data = mysql_fetch_array($result) )
        {
            $addon_id = $data['id'];
            $addon_name = $data['name'];
            $addon_welcomeemail = $data['welcomeemail'];
            $predefinedaddons[$addon_id] = array( 'name' => $addon_name, 'welcomeemail' => $addon_welcomeemail );
        }
        $result = select_query('tblhostingaddons', '', array( 'orderid' => $id ));
        while( $data = mysql_fetch_array($result) )
        {
            $aid = $data['id'];
            $hostingid = $data['hostingid'];
            $addonid = $data['addonid'];
            $name = $data['name'];
            $billingcycle2 = $data['billingcycle'];
            $addonamount = $data['recurring'] + $data['setupfee'];
            $addonstatus = $data['status'];
            $regdate = $data['regdate'];
            $nextduedate = $data['nextduedate'];
            $addonamount = formatCurrency($addonamount);
            if( !$name )
            {
                $name = $predefinedaddons[$addonid]['name'];
            }
            echo "<tr><td align=\"center\"><a href=\"clientsservices.php?userid=" . $userid . "&id=" . $hostingid . "\"><b>" . $aInt->lang('orders', 'addon') . "</b></a></td><td>" . $name . "</td><td>" . $aInt->lang('billingcycles', str_replace(array( '-', 'account', " " ), '', strtolower($billingcycle2))) . "</td><td>" . $addonamount . "</td><td>" . $aInt->lang('status', strtolower($addonstatus)) . "</td><td><b>" . $paymentstatus . "</td></tr>";
            if( $addonstatus == 'Pending' && $predefinedaddons[$addonid]['welcomeemail'] )
            {
                echo "<tr><td style=\"background-color:#EFF2F9;text-align:center;\" colspan=\"6\"><label><input type=\"checkbox\" name=\"vars[addons][" . $aid . "]" . "[sendwelcome]\" checked> " . $aInt->lang('orders', 'sendwelcome') . "</label></td></tr>";
            }
        }
        $result = select_query('tbldomains', '', array( 'orderid' => $id ));
        while( $data = mysql_fetch_array($result) )
        {
            $domainid = $data['id'];
            $type = $data['type'];
            $domain = $data['domain'];
            $registrationperiod = $data['registrationperiod'];
            $status = $data['status'];
            $regdate = $data['registrationdate'];
            $nextduedate = $data['nextduedate'];
            $domainamount = formatCurrency($data['firstpaymentamount']);
            $domainregistrar = $data['registrar'];
            $dnsmanagement = $data['dnsmanagement'];
            $emailforwarding = $data['emailforwarding'];
            $idprotection = $data['idprotection'];
            $type = $aInt->lang('domains', strtolower($type));
            echo "<tr><td align=\"center\"><a href=\"clientsdomains.php?userid=" . $userid . "&domainid=" . $domainid . "\"><b>" . $aInt->lang('fields', 'domain') . "</b></a></td><td>" . $type . " - " . $domain . "<br>";
            if( $contactid )
            {
                $result2 = select_query('tblcontacts', 'firstname,lastname', array( 'id' => $contactid ));
                $data = mysql_fetch_array($result2);
                echo $aInt->lang('domains', 'registrant') . ": <a href=\"clientscontacts.php?userid=" . $userid . "&contactid=" . $contactid . "\">" . $data['firstname'] . " " . $data['lastname'] . " (" . $contactid . ")</a><br>";
            }
            if( $dnsmanagement )
            {
                echo " + " . $aInt->lang('domains', 'dnsmanagement') . "<br>";
            }
            if( $emailforwarding )
            {
                echo " + " . $aInt->lang('domains', 'emailforwarding') . "<br>";
            }
            if( $idprotection )
            {
                echo " + " . $aInt->lang('domains', 'idprotection') . "<br>";
            }
            if( $transfersecret[$domain] )
            {
                echo $aInt->lang('domains', 'eppcode') . ": " . WHMCS_Input_Sanitize::makesafeforoutput($transfersecret[$domain]);
            }
            $regperiods = 1 < $registrationperiod ? 's' : '';
            echo "</td><td>" . $registrationperiod . " " . $aInt->lang('domains', 'year' . $regperiods) . "</td><td>" . $domainamount . "</td><td>" . $aInt->lang('status', strtolower(str_replace(" ", '', $status))) . "</td><td><b>" . $paymentstatus . "</td></tr>";
            if( $showpending && $status == 'Pending' )
            {
                echo "<tr><td style=\"background-color:#EFF2F9;text-align:center;\" colspan=\"6\">" . $aInt->lang('fields', 'registrar') . ": " . getRegistrarsDropdownMenu('', "vars[domains][" . $domainid . "][registrar]") . " <label><input type=\"checkbox\" name=\"vars[domains][" . $domainid . "]" . "[sendregistrar]\" checked> " . $aInt->lang('orders', 'sendtoregistrar') . "</label> <label><input type=\"checkbox\" name=\"vars[domains][" . $domainid . "]" . "[sendemail]\" checked> " . $aInt->lang('orders', 'sendconfirmation') . "</label></td></tr>";
            }
        }
        if( $renewals )
        {
            $renewals = explode(',', $renewals);
            foreach( $renewals as $renewal )
            {
                $renewal = explode("=", $renewal);
                $domainid = $renewal[0];
                $registrationperiod = $renewal[1];
                $result = select_query('tbldomains', '', array( 'id' => $domainid ));
                $data = mysql_fetch_array($result);
                $domainid = $data['id'];
                $type = $data['type'];
                $domain = $data['domain'];
                $registrar = $data['registrar'];
                $status = $data['status'];
                $regdate = $data['registrationdate'];
                $nextduedate = $data['nextduedate'];
                $domainamount = formatCurrency($data['recurringamount']);
                $domainregistrar = $data['registrar'];
                $dnsmanagement = $data['dnsmanagement'];
                $emailforwarding = $data['emailforwarding'];
                $idprotection = $data['idprotection'];
                echo "<tr><td><a href=\"clientsdomains.php?userid=" . $userid . "&domainid=" . $domainid . "\"><b>" . $aInt->lang('fields', 'domain') . "</b></a></td><td>" . $aInt->lang('domains', 'renewal') . " - " . $domain . "<br>";
                if( $dnsmanagement )
                {
                    echo " + " . $aInt->lang('domains', 'dnsmanagement') . "<br>";
                }
                if( $emailforwarding )
                {
                    echo " + " . $aInt->lang('domains', 'emailforwarding') . "<br>";
                }
                if( $idprotection )
                {
                    echo " + " . $aInt->lang('domains', 'idprotection') . "<br>";
                }
                $regperiods = 1 < $registrationperiod ? 's' : '';
                echo "</td><td>" . $registrationperiod . " " . $aInt->lang('domains', 'year' . $regperiods) . "</td><td>" . $domainamount . "</td><td>" . $aInt->lang('status', strtolower($status)) . "</td><td><b>" . $paymentstatus . "</td></tr>";
                if( $showpending )
                {
                    $checkstatus = $registrar && !$CONFIG['AutoRenewDomainsonPayment'] ? " checked" : " disabled";
                    echo "<tr><td style=\"background-color:#EFF2F9\" colspan=\"6\"><label><input type=\"checkbox\" name=\"vars[renewals][" . $domainid . "]" . "[sendregistrar]\"" . $checkstatus . " /> Send to Registrar</label> <label><input type=\"checkbox\" name=\"vars[renewals][" . $domainid . "]" . "[sendemail]\"" . $checkstatus . " /> Send Confirmation Email</label></td></tr>";
                }
            }
        }
        if( substr($promovalue, 0, 2) == 'DR' )
        {
            $domainid = substr($promovalue, 2);
            $result = select_query('tbldomains', '', array( 'id' => $domainid ));
            $data = mysql_fetch_array($result);
            $domainid = $data['id'];
            $type = $data['type'];
            $domain = $data['domain'];
            $registrar = $data['registrar'];
            $registrationperiod = $data['registrationperiod'];
            $status = $data['status'];
            $regdate = $data['registrationdate'];
            $nextduedate = $data['nextduedate'];
            $domainamount = formatCurrency($data['firstpaymentamount']);
            $domainregistrar = $data['registrar'];
            $dnsmanagement = $data['dnsmanagement'];
            $emailforwarding = $data['emailforwarding'];
            $idprotection = $data['idprotection'];
            echo "<tr><td><a href=\"clientsdomains.php?userid=" . $userid . "&domainid=" . $domainid . "\"><b>" . $aInt->lang('fields', 'domain') . "</b></a></td><td>" . $aInt->lang('domains', 'renewal') . " - " . $domain . "<br>";
            if( $dnsmanagement )
            {
                echo " + " . $aInt->lang('domains', 'dnsmanagement') . "<br>";
            }
            if( $emailforwarding )
            {
                echo " + " . $aInt->lang('domains', 'emailforwarding') . "<br>";
            }
            if( $idprotection )
            {
                echo " + " . $aInt->lang('domains', 'idprotection') . "<br>";
            }
            $regperiods = 1 < $registrationperiod ? 's' : '';
            echo "</td><td>" . $registrationperiod . " " . $aInt->lang('domains', 'year' . $regperiods) . "</td><td>" . $domainamount . "</td><td>" . $aInt->lang('status', strtolower($status)) . "</td><td><b>" . $paymentstatus . "</td></tr>";
            if( $showpending )
            {
                echo "<tr><td style=\"background-color:#EFF2F9\" colspan=\"6\"><label><input type=\"checkbox\" name=\"vars[domains][" . $domainid . "]" . "[sendregistrar]\"";
                if( $registrar && !$CONFIG['AutoRenewDomainsonPayment'] )
                {
                    echo " checked";
                }
                else
                {
                    echo " disabled";
                }
                echo "> Send to Registrar</label> <label><input type=\"checkbox\" name=\"vars[domains][" . $domainid . "]" . "[sendemail]\"";
                if( $registrar )
                {
                    echo " checked";
                }
                else
                {
                    echo " disabled";
                }
                echo "> Send Confirmation Email</label></td></tr>";
            }
        }
        $result = select_query('tblupgrades', '', array( 'orderid' => $id ));
        while( $data = mysql_fetch_array($result) )
        {
            $upgradeid = $data['id'];
            $type = $data['type'];
            $relid = $data['relid'];
            $originalvalue = $data['originalvalue'];
            $newvalue = $data['newvalue'];
            $upgradeamount = formatCurrency($data['amount']);
            $newrecurringamount = $data['newrecurringamount'];
            $status = $data['status'];
            $paid = $data['paid'];
            $result2 = select_query('tblhosting', "tblproducts.name AS productname,domain", array( "tblhosting.id" => $relid ), '', '', '', "tblproducts ON tblproducts.id=tblhosting.packageid");
            $data = mysql_fetch_array($result2);
            $productname = $data['productname'];
            $domain = $data['domain'];
            if( $type == 'package' )
            {
                $result2 = select_query('tblproducts', 'name', array( 'id' => $originalvalue ));
                $data = mysql_fetch_array($result2);
                $oldpackagename = $data['name'];
                $newvalue = explode(',', $newvalue);
                $newpackageid = $newvalue[0];
                $result2 = select_query('tblproducts', 'name', array( 'id' => $newpackageid ));
                $data = mysql_fetch_array($result2);
                $newpackagename = $data['name'];
                $newbillingcycle = $newvalue[1];
                $details = "<a href=\"clientshosting.php?userid=" . $userid . "&id=" . $relid . "\">" . $oldpackagename . " => " . $newpackagename . "</a><br />";
                if( $domain )
                {
                    $details .= $domain;
                }
                echo "<tr><td align=\"center\"><a href=\"clientshosting.php?userid=" . $userid . "&id=" . $relid . "\"><b>Product Upgrade</b></a></td><td>" . $details . "</td><td>" . $aInt->lang('billingcycles', $newbillingcycle) . "</td><td>" . $upgradeamount . "</td><td>" . $aInt->lang('status', strtolower($status)) . "</td><td><b>" . $paymentstatus . "</td></tr>";
            }
            else
            {
                if( $type == 'configoptions' )
                {
                    $tempvalue = explode("=>", $originalvalue);
                    $configid = $tempvalue[0];
                    $oldoptionid = $tempvalue[1];
                    $result2 = select_query('tblproductconfigoptions', '', array( 'id' => $configid ));
                    $data = mysql_fetch_array($result2);
                    $configname = $data['optionname'];
                    $optiontype = $data['optiontype'];
                    if( $optiontype == 1 || $optiontype == 2 )
                    {
                        $result2 = select_query('tblproductconfigoptionssub', '', array( 'id' => $oldoptionid ));
                        $data = mysql_fetch_array($result2);
                        $oldoptionname = $data['optionname'];
                        $result2 = select_query('tblproductconfigoptionssub', '', array( 'id' => $newvalue ));
                        $data = mysql_fetch_array($result2);
                        $newoptionname = $data['optionname'];
                    }
                    else
                    {
                        if( $optiontype == 3 )
                        {
                            if( $oldoptionid )
                            {
                                $oldoptionname = 'Yes';
                                $newoptionname = 'No';
                            }
                            else
                            {
                                $oldoptionname = 'No';
                                $newoptionname = 'Yes';
                            }
                        }
                        else
                        {
                            if( $optiontype == 4 )
                            {
                                $result2 = select_query('tblproductconfigoptionssub', '', array( 'configid' => $configid ));
                                $data = mysql_fetch_array($result2);
                                $optionname = $data['optionname'];
                                $oldoptionname = $oldoptionid;
                                $newoptionname = $newvalue . " x " . $optionname;
                            }
                        }
                    }
                    $details = "<a href=\"clientshosting.php?userid=" . $userid . "&id=" . $relid . "\">" . $productname;
                    $details .= " - " . $domain;
                    $details .= "</a><br />" . $configname . ": " . $oldoptionname . " => " . $newoptionname . "<br>";
                    echo "<tr><td align=\"center\"><a href=\"clientshosting.php?userid=" . $userid . "&id=" . $relid . "\"><b>Options Upgrade</b></a></td><td colspan=\"2\">" . $details . "</td><td>" . $upgradeamount . "</td><td>" . $aInt->lang('status', strtolower($status)) . "</td><td><b>" . $paymentstatus . "</td></tr>";
                }
            }
        }
        echo "<tr><th colspan=\"3\" style=\"text-align:right;\">";
        echo $aInt->lang('fields', 'totaldue');
        echo ":&nbsp;</th><th>";
        echo $amount;
        echo "</th><th colspan=\"2\"></th></tr>\n</table>\n</div>\n\n<br />\n\n<table align=\"center\"><tr>\n<td><input type=\"submit\" value=\"";
        echo $aInt->lang('orders', 'accept');
        echo "\" class=\"btn";
        if( !$showpending )
        {
            echo " disabled\" disabled";
        }
        else
        {
            echo " btn-success\"";
        }
        echo " /></td>\n<td><input type=\"button\" value=\"";
        echo $aInt->lang('orders', 'cancel');
        echo "\" onClick=\"cancelOrder()\" class=\"btn";
        if( $orderstatus == 'Cancelled' )
        {
            echo " disabled\" disabled";
        }
        else
        {
            echo "\"";
        }
        echo " /></td>\n<td><input type=\"button\" value=\"";
        echo $aInt->lang('orders', 'cancelrefund');
        echo "\" onClick=\"cancelRefundOrder()\" class=\"btn";
        if( !$invoiceid || $invoicestatus == 'Refunded' )
        {
            echo " disabled\" disabled";
        }
        else
        {
            echo "\"";
        }
        echo " /></td>\n<td><input type=\"button\" value=\"";
        echo $aInt->lang('orders', 'fraud');
        echo "\" onClick=\"fraudOrder()\" class=\"btn";
        if( $orderstatus == 'Fraud' )
        {
            echo " disabled\" disabled";
        }
        else
        {
            echo "\"";
        }
        echo " /></td>\n<td><input type=\"button\" value=\"";
        echo $aInt->lang('orders', 'pending');
        echo "\" onClick=\"pendingOrder()\" class=\"btn\" /></td>\n<td><input type=\"button\" value=\"";
        echo $aInt->lang('orders', 'delete');
        echo "\" onClick=\"deleteOrder()\" class=\"btn\" style=\"color:#cc0000;\" /></td>\n</tr></table>\n\n";
        if( trim($nameservers[0]) )
        {
            echo "<p><b>" . $aInt->lang('orders', 'nameservers') . "</b></p><p>";
            foreach( $nameservers as $key => $ns )
            {
                if( trim($ns) )
                {
                    echo $aInt->lang('domains', 'nameserver') . " " . ($key + 1) . ": " . $ns . "<br />";
                }
            }
            echo "</p>";
        }
        echo "<div id=\"notesholder\"" . ($notes ? '' : " style=\"display:none\"") . "><p><b>" . $aInt->lang('orders', 'notes') . "</b></p><p align=\"center\"><table align=\"center\" cellspacing=\"0\" cellpadding=\"0\"><tr><td><textarea rows=\"4\" cols=\"100\" id=\"notes\">" . $notes . "</textarea></td><td>&nbsp;&nbsp; <input type=\"button\" value=\"Update/Save\" id=\"savenotesbtn\" /></td></tr></table></p></div>";
        if( $fraudmodule )
        {
            $fraud = new WHMCS_Module_Fraud();
            if( $fraud->load($fraudmodule) )
            {
                $fraudresults = $fraud->processResultsForDisplay($id, $fraudoutput);
                if( is_array($fraudresults) )
                {
                    if( $fraudmodule == 'maxmind' )
                    {
                        echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td><p><b>" . $aInt->lang('orders', 'fraudcheckresults') . "</b></p></td><td align=\"right\"><div id=\"rerunfraud\"><a href=\"#\">" . $aInt->lang('orders', 'fraudcheckrerun') . "</a></div></td></tr></table><br />";
                    }
                    else
                    {
                        $aInt->lang('orders', 'fraudcheckresults');
                        "<p><b>" . $aInt->lang('orders', 'fraudcheckresults') . "</b></p>";
                    }
                    echo "<div id=\"fraudresults\"><table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\"><tr>";
                    $i = 0;
                    foreach( $fraudresults as $key => $value )
                    {
                        $i++;
                        echo "<td class=\"fieldlabel\" width=\"30%\">" . $key . "</td><td class=\"fieldarea\"";
                        if( $key == 'Explanation' )
                        {
                            echo " colspan=\"3\"";
                            $i = 2;
                        }
                        else
                        {
                            echo " width=\"20%\"";
                        }
                        echo ">" . $value . "</td>";
                        if( $i == '2' )
                        {
                            echo "</tr><tr>";
                            $i = 0;
                        }
                    }
                    echo "</tr></table></div>";
                    $jquerycode .= "\$(\"#rerunfraud\").click(function () {\n        \$(\"#rerunfraud\").html(\"<img src=\\\"../images/spinner.gif\\\" align=\\\"absmiddle\\\" /> Performing Check...\");\n        \$.post(\"orders.php\", { action: \"view\", rerunfraudcheck: \"true\", orderid: " . $id . ", token: \"" . generate_token('plain') . "\" },\n        function(data){\n            \$(\"#fraudresults\").html(data);\n            \$(\"#rerunfraud\").html(\"Update Completed\");\n        });\n        return false;\n    });";
                }
            }
        }
        echo "\n</form>\n\n";
        echo $aInt->jqueryDialog('affassign', $aInt->lang('orders', 'affassign'), $aInt->lang('global', 'loading'), array( $aInt->lang('global', 'savechanges') => "\$('#affiliatefield').html(\$('#affid option:selected').text());\$(this).dialog('close');\$.post('orders.php', { action: 'affassign', orderid: " . $id . ", affid: \$('#affid').val(), token: \"" . generate_token('plain') . "\" });", $aInt->lang('global', 'cancelchanges') => '' ));
        $jquerycode .= "\$(\"#showaffassign\").click(\n    function() {\n        \$(\"#affassign\").dialog(\"open\");\n        \$(\"#affassign\").load(\"orders.php?action=affassign\");\n        return false;\n    }\n);\n\$(\"#togglenotesbtn\").click(function() {\n    \$(\"#notesholder\").slideToggle(\"slow\", function() {\n        toggletext = \$(\"#togglenotesbtn\").attr(\"value\");\n        if(toggletext == \"Add Notes\") { \$(\"#togglenotesbtn\").fadeOut(\"fast\",function(){ \$(\"#togglenotesbtn\").attr(\"value\",\"Hide Notes\"); \$(\"#togglenotesbtn\").fadeIn(); }); }\n        if(toggletext == \"Hide Notes\") { \$(\"#togglenotesbtn\").fadeOut(\"fast\",function(){ \$(\"#togglenotesbtn\").attr(\"value\",\"Add Notes\"); \$(\"#togglenotesbtn\").fadeIn(); }); }\n        \$(\"#shownotesbtnholder\").slideToggle(); \n    }); \n    return false;\n});\n\$(\"#savenotesbtn\").click(function() {\n    \$.post(\"?action=view&id=" . $id . "\", { updatenotes: true, notes: \$('#notes').val(), token: \"" . generate_token('plain') . "\" });\n    \$(\"#savenotesbtn\").attr(\"value\",\"Saved\");\n    return false;\n});\n\$(\"#notes\").keyup(function() {\n    \$(\"#savenotesbtn\").attr(\"value\",\"Save Notes\");\n});";
        $aInt->jquerycode = $jquerycode;
        $aInt->jscode = $jscode;
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();