<?php
use WHMCS\Database\Capsule;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Results\ResultsList;
require_once('functions.php');

function sparrowhost_MetaData() {
    return [
        'DisplayName' => 'Sparrowhost Registrar Module',
        'APIVersion' => '1.1', // Use API version 1.1
    ];
}
function sparrowhost_GetConfigArray()
{
    return array(
        "Description" => array("Type" => "System", "Value" => "Don't have a The PowerHost Account yet? Get one here: " . "<a href=\"https://thepowerhost.in/my/register.php\" target=\"_blank\">" . "https://thepowerhost.in</a>"),
        "email" => array("FriendlyName" => "Email Address:", "Type" => "text", "Size" => "25", "Default" => "", "Description" => "Enter your email address which you registered in our system."),        		
        "apikey" => array("FriendlyName" => "API Key:", "Type" => "text", "Size" => "25", "Default" => "", "Description" => "Enter your API Key."),
    );
}
$system_url="https://dash.sparrowhost.in/api/";
class DomainDetails {
    private $response;

    public function __construct($params) {
        $username = $params["email"];
        $password = $params["apikey"];
        $domain = $params["domain"];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://dash.sparrowhost.in/api/domain/name/' . $domain);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        $response = curl_exec($ch);
        if(curl_errno($ch)) {
            throw new Exception('cURL Error: ' . curl_error($ch));
            logActivity('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);
        $this->response = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON Decode Error: ' . json_last_error_msg());
        }
    }
    public function get_nameserver() {
        // Check if 'domains' key exists and is an array
        if (isset($this->response['domains']) && is_array($this->response['domains'])) {
            foreach ($this->response['domains'] as $detail) {
                if (isset($detail['status']) && $detail['status'] == "Active") {
                    return $detail['nameservers'];
                }
            }
            if (count($this->response['domains']) > 0) {
                return $this->response['domains'][0]['nameservers'];
            }
        }
        return [];
    }
    public function get_id(){
        if (isset($this->response['domains']) && is_array($this->response['domains'])) {
            foreach ($this->response['domains'] as $detail) {
                if (isset($detail['status']) && $detail['status'] == "Active") {
                    return $detail['id'];
                }
            }
            if (count($this->response['domains']) > 0) {
                return $this->response['domains'][0]['id'];
            }
        }
    }
}

function sparrowhost_GetNameservers($params) {
    $res = new DomainDetails($params);
    $val=$res->get_nameserver();
    $n=1;
    foreach($val as $nameserver){
        $resp["ns$n"]=$nameserver;
     $n++;   
    }
    return $resp;
}

function sparrowhost_SaveNameservers($params){
    $res = new DomainDetails($params);
    $id=$res->get_id();
    $update= new request($params);
    $nameservers = [];
    if (!empty($params['ns1'])) $nameservers[] = $params['ns1'];
    if (!empty($params['ns2'])) $nameservers[] = $params['ns2'];
    if (!empty($params['ns3'])) $nameservers[] = $params['ns3'];
    if (!empty($params['ns4'])) $nameservers[] = $params['ns4'];
    
    $data = [
        'nameservers' => $nameservers
    ];
    $val = $update->call('domain/'.$id.'/ns',$data);
    if($val['success']==true){
        return true;
    }else{
         return  array('error' =>  $val);
    }
}

function sparrowhost_RegisterDomain($params)
{
    $domainslug= new request($params);
    $domainslug = $domainslug->tld();
    $tlds = $domainslug['tlds'];
    foreach($tlds as $tld){
        if($tld['tld'] == '.'.$params['tld']){
            $tld_id=$tld['id'];
            break;
        }
    }
    $domain=$params["sld"] . "." . $params["tld"];
    $year ='1';
     $nameservers = [];
    if (!empty($params['ns1'])) $nameservers[] = $params['ns1'];
    if (!empty($params['ns2'])) $nameservers[] = $params['ns2'];
    if (!empty($params['ns3'])) $nameservers[] = $params['ns3'];
    if (!empty($params['ns4'])) $nameservers[] = $params['ns4'];
    $postData = [
        "name" => $domain,
        "years" => $year,
        "action" => "register",
        "tld_id" => $tld_id,
        "pay_method" => "11",
        "nameservers" =>  $nameservers,
    ];
    $resp = $domainslug->call('domain/order',$postData);
}

function sparrowhost_RenewDomain($params){
    $res = new DomainDetails($params);
    $id=$res->get_id();
    $update= new request($params);
    $postData = [
        "id" => $id,
        "years" => $params['regperiod'],
        "pay_method" => "11",
    ];
    $resp = $domainslug->call('domain/'.$id.'/renew',$postData);
    return $resp;
}

function sparrowhost_TransferDomain($params){
    $domainslug= new request($params);
    $domainslug = $domainslug->tld();
    $tlds = $domainslug['tlds'];
    foreach($tlds as $tld){
        if($tld['tld'] == '.'.$params['tld']){
            $tld_id=$tld['id'];
            break;
        }
    }
    $domain=$params["sld"] . "." . $params["tld"];
    $year ='1';
     $nameservers = [];
    if (!empty($params['ns1'])) $nameservers[] = $params['ns1'];
    if (!empty($params['ns2'])) $nameservers[] = $params['ns2'];
    if (!empty($params['ns3'])) $nameservers[] = $params['ns3'];
    if (!empty($params['ns4'])) $nameservers[] = $params['ns4'];
    $postData = [
        "name" => $domain,
        "years" => $year,
        "action" => "transfer",
        "epp" => $params["transfersecret"],
        "tld_id" => $tld_id,
        "pay_method" => "11",
        "nameservers" =>  $nameservers,
    ];
    $resp = $domainslug->call('domain/order',$postData);
    return $resp;
}