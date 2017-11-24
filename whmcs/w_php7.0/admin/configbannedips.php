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
$aInt = new WHMCS_Admin("View Banned IPs");
$aInt->title = $aInt->lang('bans', 'iptitle');
$aInt->sidebar = 'config';
$aInt->icon = 'configbans';
$aInt->helplink = "Security/Ban Control";
if( $whmcs->get_req_var('ip') )
{
    check_token("WHMCS.admin.default");
    if( defined('DEMO_MODE') )
    {
        redir("demo=1");
    }
    checkPermission("Add Banned IP");
    $expires = $year . $month . $day . $hour . $minutes . '00';
    insert_query('tblbannedips', array( 'ip' => $ip, 'reason' => $reason, 'expires' => $expires ));
    redir("success=true");
}
if( $whmcs->get_req_var('delete') )
{
    check_token("WHMCS.admin.default");
    if( defined('DEMO_MODE') )
    {
        redir("demo=1");
    }
    checkPermission("Unban Banned IP");
    delete_query('tblbannedips', array( 'id' => $id ));
    redir("deleted=true");
}
ob_start();
$infobox = '';
if( defined('DEMO_MODE') )
{
    infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
}
if( $whmcs->get_req_var('success') )
{
    infoBox($aInt->lang('bans', 'ipaddsuccess'), $aInt->lang('bans', 'ipaddsuccessinfo'));
}
if( $whmcs->get_req_var('deleted') )
{
    infoBox($aInt->lang('bans', 'ipdelsuccess'), $aInt->lang('bans', 'ipdelsuccessinfo'));
}
echo $infobox;
$aInt->deleteJSConfirm('doDelete', 'bans', 'ipdelsure', $_SERVER['PHP_SELF'] . "?delete=true&id=");
echo $aInt->Tabs(array( $aInt->lang('global', 'add'), $aInt->lang('global', 'searchfilter') ), true);
echo "\n<div id=\"tab0box\" class=\"tabbox\">\n  <div id=\"tab_content\">\n\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
$new_ban_time = mktime(date('H'), date('i'), date('s'), date('m'), date('d') + 7, date('Y'));
echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang('fields', 'ipaddress');
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ip\" size=\"20\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang('bans', 'banreason');
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"reason\" size=\"90\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang('bans', 'banexpires');
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"day\" size=\"1\" maxlength=\"2\" value=\"";
echo date('d', $new_ban_time);
echo "\">/<input type=\"text\" name=\"month\" size=\"1\" maxlength=\"2\" value=\"";
echo date('m', $new_ban_time);
echo "\">/<input type=\"text\" name=\"year\" size=\"3\" maxlength=\"4\" value=\"";
echo date('Y', $new_ban_time);
echo "\"> <input type=\"text\" name=\"hour\" size=\"1\" maxlength=\"2\" value=\"";
echo date('H', $new_ban_time);
echo "\">:<input type=\"text\" name=\"minutes\" size=\"1\" maxlength=\"2\" value=\"";
echo date('i', $new_ban_time);
echo "\"> (";
echo $aInt->lang('bans', 'format');
echo ")</td></tr>\n</table>\n\n<img src=\"images/spacer.gif\" height=\"10\" width=\"1\"><br>\n\n<div align=\"center\"><input type=\"submit\" value=\"";
echo $aInt->lang('bans', 'addbannedip');
echo "\" name=\"postreply\" class=\"button\"></div>\n\n</form>\n\n  </div>\n</div>\n<div id=\"tab1box\" class=\"tabbox\">\n  <div id=\"tab_content\">\n\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "\">\nFilter for <select name=\"filterfor\"><option";
if( $filterfor == "IP Address" )
{
    echo " selected";
}
echo ">";
echo $aInt->lang('fields', 'ipaddress');
echo "<option";
if( $filterfor == "Ban Reason" )
{
    echo " selected";
}
echo ">";
echo $aInt->lang('bans', 'banreason');
echo "</select> matching <input type=\"text\" name=\"filtertext\" size=\"40\" value=\"";
echo $filtertext;
echo "\"> <input type=\"submit\" value=\"";
echo $aInt->lang('global', 'search');
echo "\" name=\"postreply\" class=\"button\">\n</form>\n\n  </div>\n</div>\n\n<br>\n\n";
$aInt->sortableTableInit('nopagination');
$where = array(  );
if( $filterfor = $whmcs->get_req_var('filterfor') )
{
    $filtertext = $whmcs->get_req_var('filtertext');
    if( $filterfor == "IP Address" )
    {
        $where = array( 'ip' => $filtertext );
    }
    else
    {
        $where = array( 'reason' => array( 'sqltype' => 'LIKE', 'value' => $filtertext ) );
    }
}
$result = select_query('tblbannedips', '', $where, 'id', 'DESC');
while( $data = mysqli_fetch_array($result) )
{
    $id = $data['id'];
    $ip = $data['ip'];
    $reason = $data['reason'];
    $expires = $data['expires'];
    $expires = fromMySQLDate($expires, 'time');
    $tabledata[] = array( "<a href=\"http://www.geoiptool.com/en/?IP=" . $ip . "\" target=\"_blank\">" . $ip . "</a>", $reason, $expires, "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang('global', 'delete') . "\"></a>" );
}
echo $aInt->sortableTable(array( $aInt->lang('fields', 'ipaddress'), $aInt->lang('bans', 'banreason'), $aInt->lang('bans', 'banexpires'), '' ), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();