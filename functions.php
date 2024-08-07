<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class request{
    private $system_url="https://dash.sparrowhost.in/api/";
    private $username, $password;
    public function __construct($params){
        $this->username=$params["email"];
        $this->password=$params["apikey"];
    }
    public function call($path, $params) {
        $username = $this->username;
        $password = $this->password;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->system_url . $path);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password"); // Basic authentication
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        return json_decode($response, true);
    }
    public function tld(){
        $username = $this->username;
        $password = $this->password;
        $baseUri= $this->system_url.'domain/order';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        
        $response = curl_exec($ch);
         curl_close($ch);
        return json_decode($response,true);
        
    }

}