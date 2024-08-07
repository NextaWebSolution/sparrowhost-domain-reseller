<?php
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\View\Menu\Item as MenuItem;

add_hook('AdminHomeWidgets', 1, function () {
    return new sparrowhostAdminWidget();
});
class sparrowhostAdminWidget extends \WHMCS\Module\AbstractWidget
{
    protected $title = 'Sparrow Host';
    protected $description = '';
    protected $weight = 150;
    protected $columns = 1;
    protected $cache = false;
    protected $cacheExpiry = 60;
    protected $requiredPermission = '';
        public function getData()
    {
        return true;
    }
    public function getamount(){
         $username = decrypt(Capsule::table('tblregistrars')
            ->where('registrar', '=', 'sparrowhost')
            ->where('setting', '=', 'email')
            ->value('value'));
        $password = decrypt(Capsule::table('tblregistrars')
            ->where('registrar', '=', 'sparrowhost')
            ->where('setting', '=', 'apikey')
            ->value('value'));	
        $system_url="https://dash.sparrowhost.in/api/balance";
        $ch = curl_init($system_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        $response = curl_exec($ch);
        curl_close($ch);
        $response=json_decode($response,true);
        if($response['success']==true){
         $amount=$response['details']['acc_credit']." ".$response['details']['currency'];  
         return $amount;
        }
        return 0;
    }
    public function generateOutput($data)
    {
        $amt= $this->getamount();
        return '<div style="margin:10px;padding:10px;text-align:center;font-size:16px;color:#000;">Credits: <b>' . $amt . '</b></div>';
    }
}
