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
$aInt = new WHMCS_Admin("List Addons");
$aInt->title = $aInt->lang('services', 'listaddons');
$aInt->sidebar = 'clients';
$aInt->icon = 'productaddons';
$aInt->requiredFiles(array( 'gatewayfunctions' ));
ob_start();
$predefinedaddons = array(  );
$result = select_query('tbladdons', '', '');
while( $data = mysql_fetch_array($result) )
{
    $addon_id = $data['id'];
    $addon_name = $data['name'];
    $predefinedaddons[$addon_id] = $addon_name;
}
$aInt->sortableTableInit('id', 'DESC');
$query = "FROM tblhostingaddons INNER JOIN tblhosting ON tblhosting.id=tblhostingaddons.hostingid INNER JOIN tblclients ON tblclients.id=tblhosting.userid INNER JOIN tblproducts ON tblhosting.packageid=tblproducts.id WHERE tblhostingaddons.id!='' ";
if( $clientname )
{
    $query .= "AND concat(firstname,' ',lastname) LIKE '%" . db_escape_string($clientname) . "%' ";
}
if( $addon )
{
    $query .= is_numeric($addon) ? "AND tblhostingaddons.addonid='" . $addon . "'" : "AND tblhostingaddons.name='" . db_escape_string($addon) . "' ";
}
if( $type != '' )
{
    $query .= "AND tblproducts.type='" . db_escape_string($type) . "' ";
}
if( $package != '' )
{
    $query .= "AND tblproducts.id='" . db_escape_string($package) . "' ";
}
if( $billingcycle != '' )
{
    $query .= "AND tblhostingaddons.billingcycle='" . db_escape_string($billingcycle) . "' ";
}
if( $server != '' )
{
    $query .= "AND tblhosting.server='" . db_escape_string($server) . "' ";
}
if( $paymentmethod != '' )
{
    $query .= "AND tblhostingaddons.paymentmethod='" . db_escape_string($paymentmethod) . "' ";
}
if( $status != '' )
{
    $query .= "AND tblhostingaddons.status='" . db_escape_string($status) . "' ";
}
if( $domain != '' )
{
    $query .= "AND tblhosting.domain LIKE '%" . db_escape_string($domain) . "%' ";
}
$result = full_query("SELECT COUNT(tblhostingaddons.id) " . $query);
$data = mysql_fetch_array($result);
$numrows = $data[0];
echo $aInt->Tabs(array( $aInt->lang('global', 'searchfilter') ), true);
echo "\n<div id=\"tab0box\" class=\"tabbox\">\n  <div id=\"tab_content\">\n\n<form action=\"";
echo $whmcs->getPhpSelf();
echo "\" method=\"post\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang('fields', 'addon');
echo "</td><td class=\"fieldarea\"><select name=\"addon\">\n<option value=\"\">";
echo $aInt->lang('global', 'any');
echo "</option>\n";
$result = select_query('tbladdons', 'id,name', '', 'name', 'ASC');
while( $data = mysql_fetch_array($result) )
{
    $addon_id = $data['id'];
    $addon_name = $data['name'];
    $predefinedaddons[$addon_id] = $addon_name;
    echo "<option value=\"" . $addon_id . "\"";
    if( $addon == $addon_id )
    {
        echo " selected";
    }
    echo ">" . $addon_name . "</option>";
}
$query2 = "SELECT DISTINCT name FROM tblhostingaddons WHERE name!='' ORDER BY name ASC";
$result2 = full_query($query2);
while( $data = mysql_fetch_array($result2) )
{
    $addon_name = $data['name'];
    echo "<option";
    if( $addon == $addon_name )
    {
        echo " selected";
    }
    echo ">" . $addon_name . "</option>";
}
echo "</select></td><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang('fields', 'producttype');
echo "</td><td class=\"fieldarea\"><select name=\"type\">\n<option value=\"\">";
echo $aInt->lang('global', 'any');
echo "</option>\n<option value=\"hostingaccount\"";
if( $type == 'hostingaccount' )
{
    echo " selected";
}
echo ">";
echo $aInt->lang('orders', 'sharedhosting');
echo "</option>\n<option value=\"reselleraccount\"";
if( $type == 'reselleraccount' )
{
    echo " selected";
}
echo ">";
echo $aInt->lang('orders', 'resellerhosting');
echo "</option>\n<option value=\"server\"";
if( $type == 'server' )
{
    echo " selected";
}
echo ">";
echo $aInt->lang('orders', 'server');
echo "</option>\n<option value=\"other\"";
if( $type == 'other' )
{
    echo " selected";
}
echo ">";
echo $aInt->lang('orders', 'other');
echo "</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang('fields', 'product');
echo "</td><td class=\"fieldarea\"><select name=\"package\">";
echo $aInt->productDropDown($package, 0, true);
echo "</select></td><td class=\"fieldlabel\">";
echo $aInt->lang('fields', 'paymentmethod');
echo "</td><td class=\"fieldarea\">";
echo paymentMethodsSelection($aInt->lang('global', 'any'));
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang('fields', 'billingcycle');
echo "</td><td class=\"fieldarea\">";
echo $aInt->cyclesDropDown($billingcycle, true);
echo "</td><td class=\"fieldlabel\">";
echo $aInt->lang('fields', 'status');
echo "</td><td class=\"fieldarea\">";
echo $aInt->productStatusDropDown($status, true);
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang('fields', 'domain');
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domain\" size=\"35\" value=\"";
echo $domain;
echo "\"></td><td class=\"fieldlabel\">";
echo $aInt->lang('fields', 'clientname');
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"clientname\" size=\"25\" value=\"";
echo $clientname;
echo "\"></td></tr>\n</table>\n\n<img src=\"images/spacer.gif\" height=\"10\" width=\"1\"><br>\n<DIV ALIGN=\"center\"><input type=\"submit\" value=\"";
echo $aInt->lang('global', 'search');
echo "\" class=\"button\"></DIV>\n\n</form>\n\n  </div>\n</div>\n\n<br>\n\n";
$query .= "ORDER BY ";
if( $orderby == 'addon' )
{
    $query .= "tblhostingaddons.name";
}
else
{
    if( $orderby == 'product' )
    {
        $query .= "tblproducts.name";
    }
    else
    {
        if( $orderby == 'amount' )
        {
            $query .= 'recurring';
        }
        else
        {
            if( $orderby == 'clientname' )
            {
                $query .= "tblclients.firstname " . db_escape_string($order) . ",tblclients.lastname";
            }
            else
            {
                $query .= db_escape_string($orderby);
            }
        }
    }
}
$query .= " " . db_escape_string($order);
$query = "SELECT tblhostingaddons.*,tblhostingaddons.name AS addonname,tblhosting.domain,tblhosting.userid,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblclients.groupid,tblclients.currency,tblproducts.name,tblproducts.type " . $query . " LIMIT " . (int) ($page * $limit) . ',' . (int) $limit;
$result = full_query($query);
while( $data = mysql_fetch_array($result) )
{
    $aid = $data['id'];
    $id = $data['hostingid'];
    $addonid = $data['addonid'];
    $userid = $data['userid'];
    $addonname = $data['addonname'];
    $domain = $data['domain'];
    $dtype = $data['type'];
    $dpackage = $data['name'];
    $upgrades = $data['upgrades'];
    $dpaymentmethod = $data['paymentmethod'];
    $amount = $data['recurring'];
    $billingcycle = $data['billingcycle'];
    $nextduedate = $data['nextduedate'];
    $status = $data['status'];
    if( !$addonname )
    {
        $addonname = $predefinedaddons[$addonid];
    }
    $regdate = fromMySQLDate($regdate);
    $nextduedate = fromMySQLDate($nextduedate);
    $firstname = $data['firstname'];
    $lastname = $data['lastname'];
    $companyname = $data['companyname'];
    $groupid = $data['groupid'];
    $currency = $data['currency'];
    if( !$domain )
    {
        $domain = "(" . $aInt->lang('addons', 'nodomain') . ")";
    }
    $currency = getCurrency('', $currency);
    $amount = formatCurrency($amount);
    if( $billingcycle == "One Time" || $billingcycle == "Free Account" || $billingcycle == 'Free' )
    {
        $nextduedate = '-';
    }
    $billingcycle = $aInt->lang('billingcycles', str_replace(array( '-', 'account', " " ), '', strtolower($billingcycle)));
    $tabledata[] = array( "<input type=\"checkbox\" name=\"selectedclients[]\" value=\"" . $id . "\" class=\"checkall\" />", "<a href=\"clientsservices.php?userid=" . $userid . "&id=" . $id . "&aid=" . $aid . "\">" . $aid . "</a>", $addonname . " <span class=\"label " . strtolower($status) . "\">" . $status . "</span>", "<a href=\"clientsservices.php?userid=" . $userid . "&id=" . $id . "\">" . $dpackage . "</a>", $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid), $billingcycle, $amount, $nextduedate );
}
$tableformurl = "sendmessage.php?type=product&multiple=true";
$tableformbuttons = "<input type=\"submit\" value=\"" . $aInt->lang('global', 'sendmessage') . "\" class=\"button\">";
echo $aInt->sortableTable(array( 'checkall', array( 'id', $aInt->lang('fields', 'id') ), array( 'addon', $aInt->lang('fields', 'addon') ), array( 'product', $aInt->lang('fields', 'product') ), array( 'clientname', $aInt->lang('fields', 'clientname') ), array( 'billingcycle', $aInt->lang('fields', 'billingcycle') ), array( 'amount', $aInt->lang('fields', 'price') ), array( 'nextduedate', $aInt->lang('fields', 'nextduedate') ) ), $tabledata, $tableformurl, $tableformbuttons);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();