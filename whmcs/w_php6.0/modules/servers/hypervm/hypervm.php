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
function hypervm_ConfigOptions()
{
    $configarray = array( 'Type' => array( 'Type' => 'text', 'Size' => '25', 'Description' => "Either openvz/xen" ), "Plan Name" => array( 'Type' => 'text', 'Size' => '25' ), "OS Template" => array( 'Type' => 'text', 'Size' => '25' ), 'Server' => array( 'Type' => 'text', 'Size' => '25' ), "Number Of IPs" => array( 'Type' => 'dropdown', 'Options' => '0,1,2,3,4,5,6,7,8' ), "Disable Welcome Email" => array( 'Type' => 'yesno', 'Description' => "Prevent HyperVM Welcome Mail Sending" ) );
    return $configarray;
}
function hypervm_ClientArea($params)
{
    global $_LANG;
    $form = sprintf("<form action=\"clientarea.php?action=productdetails\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"id\" value=\"%s\" />" . "<input type=\"hidden\" name=\"serveraction\" value=\"custom\" />" . "<input type=\"hidden\" name=\"a\" value=\"restart\" />" . "<input type=\"submit\" value=\"%s\" class=\"button\" />" . "</form>", (int) $params['serviceid'], $_LANG['hypervmrestart']);
    return $form;
}
function hypervm_LoginLink($params)
{
    if( $params['serversecure'] )
    {
        $protocol = 'https';
        $port = '8887';
    }
    else
    {
        $protocol = 'http';
        $port = '8888';
    }
    $form = sprintf("<a href=\"%s://%s:%s/display.php?frm_action=show&frm_o_o[0][class]=vps&frm_o_o[0][nname]=%s\" target=\"_blank\" class=\"moduleloginlink\">%s</a>", $protocol, WHMCS_Input_Sanitize::encode($params['serverip']), $port, WHMCS_Input_Sanitize::encode($params['username']), "login to control panel");
    return $form;
}
function hypervm_AdminLink($params)
{
    if( $params['serversecure'] )
    {
        $protocol = 'https';
        $port = '8887';
    }
    else
    {
        $protocol = 'http';
        $port = '8888';
    }
    $form = sprintf("<form action=\"%s://%s:%s/htmllib/phplib/\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"frm_class\" value=\"client\" />" . "<input type=\"hidden\" name=\"frm_clientname\" value=\"%s\" />" . "<input type=\"hidden\" name=\"frm_password\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\" />" . "</form>", $protocol, WHMCS_Input_Sanitize::encode($params['serverip']), $port, WHMCS_Input_Sanitize::encode($params['serverusername']), WHMCS_Input_Sanitize::encode($params['serverpassword']), 'HyperVM');
    return $form;
}
function hypervm_CreateAccount($params)
{
    if( isset($params['customfields']['Username']) )
    {
        $params['username'] = $params['customfields']['Username'];
    }
    $vhostname = '';
    if( isset($params['customfields']['Hostname']) )
    {
        if( stristr($params['domain'], $params['customfields']['Hostname'] . ".") !== false || $params['customfields']['Hostname'] == $params['domain'] )
        {
            $vhostname = $params['domain'];
        }
        else
        {
            $vhostname = $params['customfields']['Hostname'];
            if( $params['domain'] )
            {
                $vhostname .= "." . $params['domain'];
            }
            update_query('tblhosting', array( 'domain' => $vhostname ), array( 'id' => $params['serviceid'] ));
        }
        $vhostname = "&v-hostname=" . $vhostname;
    }
    if( isset($params['configoptions']["Operating System"]) )
    {
        $params['configoption3'] = $params['configoptions']["Operating System"];
    }
    if( $params['serveraccesshash'] )
    {
        $params['configoption4'] = $params['serveraccesshash'];
    }
    if( substr($params['username'], 0 - 3) != ".vm" )
    {
        $params['username'] .= ".vm";
        update_query('tblhosting', array( 'username' => $params['username'] ), array( 'id' => (int) $params['serviceid'] ));
    }
    $result = lxlabs_get_via_json($params['serversecure'], $params['serverip'], $params['serverusername'], $params['serverpassword'], '8888', "action=simplelist&resource=resourceplan");
    $list = $result->result;
    if( $list )
    {
        foreach( $list as $key => $value )
        {
            $plansarray[strtolower($value)] = $key;
        }
    }
    if( !$params['configoption6'] )
    {
        $vhostname .= "&v-send_welcome_f=on";
    }
    $result = lxlabs_get_via_json($params['serversecure'], $params['serverip'], $params['serverusername'], $params['serverpassword'], '8888', "action=add&class=vps&v-type=" . $params['configoption1'] . "&name=" . $params['username'] . "&v-num_ipaddress_f=" . $params['configoption5'] . "&v-contactemail=" . $params['clientsdetails']['email'] . "&v-password=" . $params['password'] . "&v-ostemplate=" . $params['configoption3'] . "&v-syncserver=" . $params['configoption4'] . "&v-plan_name=" . $plansarray[strtolower($params['configoption2'])] . $vhostname);
    if( lxlabs_if_error($result) )
    {
        return $result->message;
    }
    $result = lxlabs_get_via_json($params['serversecure'], $params['serverip'], $params['serverusername'], $params['serverpassword'], '8888', "action=getproperty&class=vps&name=" . $params['username'] . "&v-coma_vmipaddress_a=");
    $ipaddresses = $result->result->_obfuscated_762D636F6D615F766D6970616464726573735F61_;
    update_query('tblhosting', array( 'dedicatedip' => $ipaddresses ), array( 'id' => $params['serviceid'] ));
    return 'success';
}
function hypervm_TerminateAccount($params)
{
    $result = lxlabs_get_via_json($params['serversecure'], $params['serverip'], $params['serverusername'], $params['serverpassword'], '8888', "class=vps&name=" . $params['username'] . "&action=delete");
    if( lxlabs_if_error($result) )
    {
        return $result->message;
    }
    return 'success';
}
function hypervm_SuspendAccount($params)
{
    $result = lxlabs_get_via_json($params['serversecure'], $params['serverip'], $params['serverusername'], $params['serverpassword'], '8888', "class=vps&name=" . $params['username'] . "&action=update&subaction=disable");
    if( lxlabs_if_error($result) )
    {
        return $result->message;
    }
    return 'success';
}
function hypervm_UnsuspendAccount($params)
{
    $result = lxlabs_get_via_json($params['serversecure'], $params['serverip'], $params['serverusername'], $params['serverpassword'], '8888', "class=vps&name=" . $params['username'] . "&action=update&subaction=enable");
    if( lxlabs_if_error($result) )
    {
        return $result->message;
    }
    return 'success';
}
function hypervm_ChangePackage($params)
{
    $result = lxlabs_get_via_json($params['serversecure'], $params['serverip'], $params['serverusername'], $params['serverpassword'], '8888', "action=simplelist&resource=resourceplan");
    $list = $result->result;
    if( $list )
    {
        foreach( $list as $key => $value )
        {
            $plansarray[strtolower($value)] = $key;
        }
    }
    $result = lxlabs_get_via_json($params['serversecure'], $params['serverip'], $params['serverusername'], $params['serverpassword'], '8888', "class=vps&name=" . $params['username'] . "&action=update&subaction=change_plan&v-newresourceplan=" . $plansarray[strtolower($params['configoption2'])] . '');
    if( lxlabs_if_error($result) )
    {
        return $result->message;
    }
    return 'success';
}
function hypervm_AdminCustomButtonArray()
{
    $buttonarray = array( 'Restart' => 'restart' );
    return $buttonarray;
}
function hypervm_ClientAreaCustomButtonArray()
{
    $buttonarray = array( 'Restart' => 'restart' );
    return $buttonarray;
}
function hypervm_restart($params)
{
    $result = lxlabs_get_via_json($params['serversecure'], $params['serverip'], $params['serverusername'], $params['serverpassword'], '8888', "class=vps&name=" . $params['username'] . "&action=update&subaction=reboot");
    if( lxlabs_if_error($result) )
    {
        return $result->message;
    }
    return 'success';
}
function lxlabs_get_via_json($protocol, $server, $serverusername, $serverpassword, $port, $param)
{
    if( $protocol )
    {
        $protocol = 'https';
        $port = '8887';
    }
    else
    {
        $protocol = 'http';
        $port = '8888';
    }
    $param = "login-class=client&login-name=" . $serverusername . "&login-password=" . $serverpassword . "&output-type=json&" . $param;
    $url = $protocol . "://" . $server . ":" . $port . "/webcommand.php";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    $totalout = curl_exec($ch);
    $totalout = trim($totalout);
    if( curl_errno($ch) )
    {
        $totalout = "Curl Error: " . curl_errno($ch) . " - " . curl_error($ch);
    }
    require_once(dirname(__FILE__) . "/JSON.php");
    $json = new Services_JSON();
    $object = $json->decode($totalout);
    $friendobject = hypervm_objectToArray($object);
    logModuleCall('hypervm', '', $url . "?" . $param, $totalout, '', array( $serverusername, $serverpassword ));
    return $object;
}
function lxlabs_if_error($json)
{
    return $json->return === 'error';
}
function hypervm_objectToArray($d)
{
    if( is_object($d) )
    {
        $d = get_object_vars($d);
    }
    if( is_array($d) )
    {
        return array_map('hypervm_objectToArray', $d);
    }
    return $d;
}