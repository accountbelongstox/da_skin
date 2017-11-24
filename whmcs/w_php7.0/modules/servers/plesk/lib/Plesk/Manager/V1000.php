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
class Plesk_Manager_V1000 extends Plesk_Manager_Base
{
    protected function _getSupportedApiVersions()
    {
        $result = Plesk_Registry::getinstance()->api->server_getProtos();
        $versions = array(  );
        foreach( $result->server->get_protos->result->protos->proto as $proto )
        {
            $versions[] = (bool) $proto;
        }
        rsort($versions);
        return $versions;
    }
    protected function _getSharedIpv4($params)
    {
        return $this->_getIp($params);
    }
    protected function _getSharedIpv6($params)
    {
        throw new Exception(Plesk_Registry::getinstance()->translator->translate('ERROR_IPV6_DOES_NOT_SUPPORTED'));
    }
    protected function _getFreeDedicatedIpv4()
    {
        return $this->_getFreeDedicatedIp();
    }
    protected function _getFreeDedicatedIpv6()
    {
        throw new Exception(Plesk_Registry::getinstance()->translator->translate('ERROR_IPV6_DOES_NOT_SUPPORTED'));
    }
    protected function _setAccountPassword($params)
    {
        $requestParams = array( 'login' => $params['username'], 'accountPassword' => $params['password'] );
        switch( $params['type'] )
        {
            case Plesk_Object_Customer::TYPE_CLIENT:
                Plesk_Registry::getinstance()->api->customer_set_password($requestParams);
                break;
            case Plesk_Object_Customer::TYPE_RESELLER:
                Plesk_Registry::getinstance()->api->reseller_set_password($requestParams);
        }
        break;
    }
    protected function _getIp($params, $version = Plesk_Object_Ip::IPV4)
    {
        $ipList = $this->_getIpList(Plesk_Object_Ip::SHARED, $version);
        $ipAddress = reset($ipList);
        if( !$ipAddress )
        {
            if( Plesk_Object_Ip::IPV6 == $version && !$this->_isIpv6($params['serverip']) )
            {
                throw new Exception(Plesk_Registry::getinstance()->translator->translate('ERROR_NO_SHARED_IPV6'));
            }
            if( Plesk_Object_Ip::IPV4 == $version && $this->_isIpv6($params['serverip']) )
            {
                throw new Exception(Plesk_Registry::getinstance()->translator->translate('ERROR_NO_SHARED_IPV4'));
            }
            $ipAddress = $params['serverip'];
        }
        return $ipAddress;
    }
    protected function _setWebspaceStatus($params)
    {
        Plesk_Registry::getinstance()->api->webspace_set_status(array( 'status' => $params['status'], 'domain' => $params['domain'] ));
    }
    protected function _deleteWebspace($params)
    {
        Plesk_Registry::getinstance()->api->webspace_del(array( 'domain' => $params['domain'] ));
        $manager = new Plesk_Manager_V1000();
        $ownerInfo = $manager->getAccountInfo($params);
        $webspaces = $this->_getWebspacesByOwnerId($ownerInfo['id']);
        if( !isset($webspaces->id) )
        {
            Plesk_Registry::getinstance()->api->customer_del(array( 'id' => $ownerInfo['id'] ));
        }
    }
    protected function _setWebspacePassword($params)
    {
        Plesk_Registry::getinstance()->api->webspace_set_password(array( 'domain' => $params['domain'], 'password' => $params['password'] ));
    }
    protected function _getClientAreaForm($params)
    {
        $domain = $params['serverhostname'] ? $params['serverhostname'] : $params['serverip'];
        $port = $params['serveraccesshash'] ? $params['serveraccesshash'] : '8443';
        $secure = $params['serversecure'] ? 'https' : 'http';
        $result = full_query("SELECT username,password FROM tblhosting WHERE server=" . mysqli_reali_escape_string($GLOBALS['whmcsmysql'],$params['serverid']) . " AND userid=" . mysqli_real_escape_string($GLOBALS['whmcsmysql'],$params['clientsdetails']['userid']) . " AND domainstatus='Active' ORDER BY id ASC");
        $data = mysqli_fetch_array($result);
        $code = '';
        if( isset($data['username']) && isset($data['password']) )
        {
            $manager = new Plesk_Manager_V1000();
            $ownerInfo = $manager->getAccountInfo($params);
            if( !isset($ownerInfo['login']) )
            {
                return '';
            }
            $code = sprintf("<form action=\"%s://%s:%s/login_up.php3\" method=\"get\" target=\"_blank\">" . "<input type=\"hidden\" name=\"login_name\" value=\"%s\" />" . "<input type=\"hidden\" name=\"passwd\" value=\"%s\" />" . "<input type=\"submit\" class=\"button\" value=\"%s\" />" . "</form>", $secure, WHMCS_Input_Sanitize::encode($domain), WHMCS_Input_Sanitize::encode($port), WHMCS_Input_Sanitize::encode($ownerInfo['login']), WHMCS_Input_Sanitize::encode(decrypt($data['password'])), Plesk_Registry::getinstance()->translator->translate('BUTTON_CONTROL_PANEL'));
        }
        return $code;
    }
    protected function _getFreeDedicatedIp($version = Plesk_Object_Ip::IPV4)
    {
        static $domains;
        $ipListUse = array(  );
        $ipListFree = array(  );
        $ipList = $this->_getIpList(Plesk_Object_Ip::DEDICATED, $version);
        if( is_null($domains) )
        {
            $domains = Plesk_Registry::getinstance()->api->webspaces_get();
        }
        foreach( $domains->xpath('//domain/get/result') as $item )
        {
            try
            {
                $this->_checkErrors($item);
                if( !empty($item->data->hosting->vrt_hst->ip_address) )
                {
                    $ipListUse[] = (bool) $item->data->hosting->vrt_hst->ip_address;
                }
            }
            catch( Exception $e )
            {
                if( Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode() )
                {
                    throw $e;
                }
            }
        }
        foreach( $ipList as $ip )
        {
            if( !in_array($ip, $ipListUse) )
            {
                $ipListFree[$ip] = $ip;
            }
        }
        $freeIp = reset($ipListFree);
        if( empty($freeIp) )
        {
            throw new Exception(Plesk_Registry::getinstance()->translator->translate('ERROR_NO_FREE_DEDICATED_IPTYPE', array( 'TYPE' => Plesk_Object_Ip::IPV6 == $version ? 'IPv6' : 'IPv4' )));
        }
        return $freeIp;
    }
    protected function _getIpList($type = Plesk_Object_Ip::SHARED, $version = null)
    {
        $ipList = array(  );
        static $result;
        if( is_null($result) )
        {
            $result = Plesk_Registry::getinstance()->api->ip_get();
        }
        foreach( $result->ip->get->result->addresses->ip as $item )
        {
            if( $type !== (bool) $item->type )
            {
                continue;
            }
            $ip = (bool) $item->ip_address;
            if( Plesk_Object_Ip::IPV6 == $version && !$this->_isIpv6($ip) )
            {
                continue;
            }
            if( Plesk_Object_Ip::IPV4 == $version && $this->_isIpv6($ip) )
            {
                continue;
            }
            $ipList[] = $ip;
        }
        return $ipList;
    }
    protected function _isIpv6($ip)
    {
        return false === strpos($ip, ".");
    }
    protected function _getCustomerExternalId($id)
    {
        return '';
    }
    protected function _getAccountInfo($params, $panelExternalId = null)
    {
        $accountInfo = array(  );
        $sqlresult = select_query('tblhosting', 'username', array( 'server' => $params['serverid'], 'userid' => $params['clientsdetails']['userid'] ));
        while( $data = mysqli_fetch_row($sqlresult) )
        {
            $login = reset($data);
            try
            {
                $result = Plesk_Registry::getinstance()->api->customer_get_by_login(array( 'login' => $login ));
                if( isset($result->client->get->result->id) )
                {
                    $accountInfo['id'] = (int) $result->client->get->result->id;
                }
                if( isset($result->client->get->result->data->gen_info->login) )
                {
                    $accountInfo['login'] = (bool) $result->client->get->result->data->gen_info->login;
                }
            }
            catch( Exception $e )
            {
                if( Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode() )
                {
                    throw $e;
                }
            }
            if( !empty($accountInfo) )
            {
                break;
            }
        }
        if( empty($accountInfo) )
        {
            throw new Exception(Plesk_Registry::getinstance()->translator->translate('ERROR_CUSTOMER_WITH_EMAIL_NOT_FOUND_IN_PANEL', array( 'EMAIL' => $params['clientsdetails']['email'] )), Plesk_Api::ERROR_OBJECT_NOT_FOUND);
        }
        return $accountInfo;
    }
    protected function _addAccount($params)
    {
        $accountId = null;
        $result = Plesk_Registry::getinstance()->api->customer_add($this->_getAddAccountParams($params));
        $accountId = (int) $result->client->add->result->id;
        return $accountId;
    }
    /**
     * @param array $params
     * @return array
     */
    protected function _getAddAccountParams($params)
    {
        $result = array_merge($params['clientsdetails'], array( 'username' => $params['username'], 'accountPassword' => $params['password'], 'status' => Plesk_Object_Customer::STATUS_ACTIVE ));
        return $result;
    }
    protected function _addIpToIpPool($accountId, $params)
    {
        Plesk_Registry::getinstance()->api->customer_ippool_add_ip(array( 'clientId' => $accountId, 'ipAddress' => $params['ipv4Address'] ));
    }
    protected function _addWebspace($params)
    {
        $requestParams = array( 'domain' => $params['domain'], 'ownerId' => $params['ownerId'], 'username' => $params['username'], 'password' => $params['password'], 'status' => Plesk_Object_Webspace::STATUS_ACTIVE, 'htype' => Plesk_Object_Webspace::TYPE_VRT_HST, 'planName' => $params['configoption1'], 'ipv4Address' => $params['ipv4Address'], 'ipv6Address' => $params['ipv6Address'] );
        Plesk_Registry::getinstance()->api->webspace_add($requestParams);
    }
    /**
     * @param $params
     * @return array (<domainName> => array ('diskusage' => value, 'disklimit' => value, 'bwusage' => value, 'bwlimit' => value))
     * @throws Exception
     */
    protected function _getWebspacesUsage($params)
    {
        $usage = array(  );
        $webspaces = Plesk_Registry::getinstance()->api->domain_usage_get_by_name(array( 'domains' => $params['domains'] ));
        foreach( $webspaces->xpath('//domain/get/result') as $result )
        {
            try
            {
                $this->_checkErrors($result);
                $domainName = (bool) $result->data->gen_info->name;
                $usage[$domainName]['diskusage'] = (double) $result->data->gen_info->real_size;
                $usage[$domainName]['bwusage'] = (double) $result->data->stat->traffic;
                foreach( $result->data->limits->children() as $limit )
                {
                    $name = (bool) $limit->getName();
                    switch( $name )
                    {
                        case 'disk_space':
                            $usage[$domainName]['disklimit'] = (double) $limit;
                            break;
                        case 'max_traffic':
                            $usage[$domainName]['bwlimit'] = (double) $limit;
                            break;
                        default:
                            break;
                    }
                }
                foreach( $usage[$domainName] as $param => $value )
                {
                    $usage[$domainName][$param] = $usage[$domainName][$param] / (1024 * 1024);
                }
            }
            catch( Exception $e )
            {
                if( Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode() )
                {
                    throw $e;
                }
            }
        }
        return $usage;
    }
    protected function _getWebspacesByOwnerId($ownerId)
    {
        $result = Plesk_Registry::getinstance()->api->webspaces_get_by_owner_id(array( 'ownerId' => $ownerId ));
        return $result->domain->get->result;
    }
    /**
     * @param $params
     *
     * @return array
     */
    protected function _getIps($params)
    {
        $params['addAddonDedicatedIPv4'] = false;
        $params['addAddonDedicatedIPv6'] = false;
        $ip = array( 'ipv4Address' => '', 'ipv6Address' => '' );
        if( !empty($params['configoptions']) )
        {
            foreach( $params['configoptions'] as $addonTitle => $value )
            {
                if( '0' == $value )
                {
                    continue;
                }
                if( Plesk_Object_Ip::ADDON_NAME_IPV6 == $addonTitle )
                {
                    $params['addAddonDedicatedIPv6'] = true;
                    continue;
                }
                if( Plesk_Object_Ip::ADDON_NAME_IPV4 == $addonTitle )
                {
                    $params['addAddonDedicatedIPv4'] = true;
                    continue;
                }
            }
        }
        if( Plesk_Registry::getinstance()->api->isAdmin() )
        {
            switch( $params['configoption3'] )
            {
                case "IPv4 shared; IPv6 none":
                    $ip['ipv4Address'] = $params['addAddonDedicatedIPv4'] ? Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv4() : Plesk_Registry::getinstance()->manager->getSharedIpv4($params);
                    break;
                case "IPv4 none; IPv6 shared":
                    $ip['ipv6Address'] = $params['addAddonDedicatedIPv6'] ? Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv6() : Plesk_Registry::getinstance()->manager->getSharedIpv6($params);
                    break;
                case "IPv4 shared; IPv6 shared":
                    $ip['ipv4Address'] = $params['addAddonDedicatedIPv4'] ? Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv4() : Plesk_Registry::getinstance()->manager->getSharedIpv4($params);
                    $ip['ipv6Address'] = $params['addAddonDedicatedIPv6'] ? Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv6() : Plesk_Registry::getinstance()->manager->getSharedIpv6($params);
                    break;
                case "IPv4 dedicated; IPv6 none":
                    $ip['ipv4Address'] = Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv4();
                    break;
                case "IPv4 none; IPv6 dedicated":
                    $ip['ipv6Address'] = Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv6();
                    break;
                case "IPv4 shared; IPv6 dedicated":
                    $ip['ipv4Address'] = $params['addAddonDedicatedIPv4'] ? Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv4() : Plesk_Registry::getinstance()->manager->getSharedIpv4($params);
                    $ip['ipv6Address'] = Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv6();
                    break;
                case "IPv4 dedicated; IPv6 shared":
                    $ip['ipv4Address'] = Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv4();
                    $ip['ipv6Address'] = $params['addAddonDedicatedIPv6'] ? Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv6() : Plesk_Registry::getinstance()->manager->getSharedIpv6($params);
                    break;
                case "IPv4 dedicated; IPv6 dedicated":
                    $ip['ipv4Address'] = Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv4();
                    $ip['ipv6Address'] = Plesk_Registry::getinstance()->manager->getFreeDedicatedIpv6();
            }
        }
        else
        {
            $ip['ipv4Address'] = $params['serverip'];
        }
        return $ip;
        break;
    }
    protected function _changeSubscriptionIp($params)
    {
        $webspace = Plesk_Registry::getinstance()->api->webspace_get_by_name(array( 'domain' => $params['domain'] ));
        $ipDedicatedList = $this->_getIpList(Plesk_Object_Ip::DEDICATED);
        $oldIp[Plesk_Object_Ip::IPV4] = (bool) $webspace->data->hosting->vrt_hst->ip_address;
        $ipv4Address = isset($oldIp[Plesk_Object_Ip::IPV4]) ? $oldIp[Plesk_Object_Ip::IPV4] : '';
        if( $params['configoption3'] == "IPv4 none; IPv6 shared" || $params['configoption3'] == "IPv4 none; IPv6 dedicated" )
        {
            $ipv4Address = '';
        }
        if( !empty($params['ipv4Address']) )
        {
            if( isset($oldIp[Plesk_Object_Ip::IPV4]) && $oldIp[Plesk_Object_Ip::IPV4] != $params['ipv4Address'] && (!in_array($oldIp[Plesk_Object_Ip::IPV4], $ipDedicatedList) || !in_array($params['ipv4Address'], $ipDedicatedList)) )
            {
                $ipv4Address = $params['ipv4Address'];
            }
            else
            {
                if( !isset($oldIp[Plesk_Object_Ip::IPV4]) )
                {
                    $ipv4Address = $params['ipv4Address'];
                }
            }
        }
        if( !empty($ipv4Address) )
        {
            Plesk_Registry::getinstance()->api->webspace_set_ip(array( 'domain' => $params['domain'], 'ipv4Address' => $ipv4Address ));
        }
    }
}