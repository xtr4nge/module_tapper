<? 
/*
	Copyright (C) 2013-2015 xtr4nge [_AT_] gmail.com

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/ 
?>
<?

include "../../../config/config.php";
include "../_info_.php";
include "../../../login_check.php";
include "../../../functions.php";

include "options_config.php";

// Checking POST & GET variables...
if ($regex == 1) {
	regex_standard($_POST['type'], "../../../msg.php", $regex_extra);
}

$type = $_POST['type'];

$tap_mode = $_POST["tap_mode"];
$ap_mode = $_POST["ap_mode"];

$tap1_iface = $_POST["tap1_iface"];
$tap1_set = $_POST["tap1_set"];
$tap1_ip = $_POST["tap1_ip"];
$tap1_mask = $_POST["tap1_mask"];
$tap1_gw = $_POST["tap1_gw"];
$tap1_iface_route = $_POST["tap1_iface_route"];

$tap2_iface = $_POST["tap2_iface"];
$tap2_set = $_POST["tap2_set"];
$tap2_ip = $_POST["tap2_ip"];
$tap2_mask = $_POST["tap2_mask"];
$tap2_gw = $_POST["tap2_gw"];

// ------------ IN | OUT (START) -------------
if(isset($_POST["tap_mode"])){
    $exec = "/bin/sed -i 's/tap_mode=.*/tap_mode=\\\"".$_POST["tap_mode"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

if(isset($_POST["ap_mode"])){
    $exec = "/bin/sed -i 's/ap_mode=.*/ap_mode=\\\"".$_POST["ap_mode"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
    
    if($_POST["ap_mode"] == "2") {
        $exec = "/bin/sed -i 's/io_action=.*/io_action=\\\"at0\\\";/g' options_config.php";
		exec_fruitywifi($exec);
        
        $exec = "/bin/sed -i 's/interface=.*/interface=at0/g' $mod_path/includes/conf/dnsmasq.conf";
		exec_fruitywifi($exec);
    } else {
        $exec = "/bin/sed -i 's/io_action=.*/io_action=\\\"$tap1_iface\\\";/g' options_config.php";
		exec_fruitywifi($exec);
        
        $exec = "/bin/sed -i 's/interface=.*/interface=$tap1_iface/g' $mod_path/includes/conf/dnsmasq.conf";
		exec_fruitywifi($exec);
    }
}

if(isset($_POST["tap1_iface"])){
    $exec = "/bin/sed -i 's/tap1_iface=.*/tap1_iface=\\\"".$_POST["tap1_iface"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
	
    // replace interface in hostapd.conf and hostapd-secure.conf
    $exec = "/bin/sed -i 's/^interface=.*/interface=".$_POST["tap1_iface"]."/g' $mod_path/includes/conf/hostapd.conf";
    exec_fruitywifi($exec);
    $exec = "/bin/sed -i 's/^interface=.*/interface=".$_POST["tap1_iface"]."/g' $mod_path/includes/conf/hostapd-secure.conf";
    exec_fruitywifi($exec);
    
    $exec = "/bin/sed -i 's/interface=.*/interface=".$_POST["tap1_iface"]."/g' $mod_path/includes/conf/dnsmasq.conf";
	//$exec = "/bin/sed -i 's/interface=.*/interface=bridge0/g' $mod_path/includes/conf/dnsmasq.conf";
    exec_fruitywifi($exec);
    
    //EXTRACT MACADDRESS
    $exec = "/sbin/ifconfig -a ".$_POST["tap1_iface"]." |grep HWaddr";
    $output = exec_fruitywifi($exec);
    $output = preg_replace('/\s+/', ' ',$output);
    $output = explode(" ",$output);
    
    $exec = "/bin/sed -i 's/^bssid=.*/bssid=".$output[4]."/g' $mod_path/includes/conf/hostapd.conf";
    exec_fruitywifi($exec);
    $exec = "/bin/sed -i 's/^bssid=.*/bssid=".$output[4]."/g' $mod_path/includes/conf/hostapd-secure.conf";
    exec_fruitywifi($exec);
    
    // IF AP_MODE IS AIRMON-NG KEEPS AT0 IN DNSMASQ    
    if($ap_mode == "2") {
        $exec = "/bin/sed -i 's/io_action=.*/io_action=\\\"at0\\\";/g' options_config.php";
		exec_fruitywifi($exec);
        
        $exec = "/bin/sed -i 's/interface=.*/interface=at0/g' $mod_path/includes/conf/dnsmasq.conf";
		exec_fruitywifi($exec);
    } else {
        $exec = "/bin/sed -i 's/io_action=.*/io_action=\\\"".$_POST["tap1_iface"]."\\\";/g' options_config.php";
		exec_fruitywifi($exec);
        
        $exec = "/bin/sed -i 's/interface=.*/interface=".$_POST["tap1_iface"]."/g' $mod_path/includes/conf/dnsmasq.conf";
		exec_fruitywifi($exec);
    }
	
}

if(isset($_POST["tap1_set"])){
    $exec = "/bin/sed -i 's/tap1_set=.*/tap1_set=\\\"".$_POST["tap1_set"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

if(isset($_POST["tap1_ip"])){
    $exec = "/bin/sed -i 's/tap1_ip=.*/tap1_ip=\\\"".$_POST["tap1_ip"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
	
    // DNSMASQ (replace ip)
    $exec = "/bin/sed -i 's/server=.*/server=\/\#\/".$_POST["tap1_ip"]."/g' $mod_path/includes/conf/dnsmasq.conf";
    exec_fruitywifi($exec);
	
    $exec = "/bin/sed -i 's/listen-address=.*/listen-address=".$_POST["tap1_ip"]."/g' $mod_path/includes/conf/dnsmasq.conf";
    exec_fruitywifi($exec);
	
    $ip = explode(".",$_POST["tap1_ip"]);
    $sub = $ip[0] . "." . $ip[1] . "." . $ip[2];
    
    $exec = "/bin/sed -i 's/dhcp-range=.*/dhcp-range=".$sub.".50,".$sub.".100,12h/g' $mod_path/includes/conf/dnsmasq.conf";
    exec_fruitywifi($exec);
}

if(isset($_POST["tap1_mask"])){
    $exec = "/bin/sed -i 's/tap1_mask=.*/tap1_mask=\\\"".$_POST["tap1_mask"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

if(isset($_POST["tap1_gw"])){
    $exec = "/bin/sed -i 's/tap1_gw=.*/tap1_gw=\\\"".$_POST["tap1_gw"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

// [TAP1] ROUTE
if(isset($_POST["tap1_iface_route"])){
    $exec = "/bin/sed -i 's/tap1_iface_route=.*/tap1_iface_route=\\\"".$_POST["tap1_iface_route"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

// ---- TAP 2 ---->

if(isset($_POST["tap2_iface"])){
    $exec = "/bin/sed -i 's/tap2_iface=.*/tap2_iface=\\\"".$_POST["tap2_iface"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

if(isset($_POST["tap2_set"])){
    $exec = "/bin/sed -i 's/tap2_set=.*/tap2_set=\\\"".$_POST["tap2_set"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

if(isset($_POST["tap2_ip"])){
    $exec = "/bin/sed -i 's/tap2_ip=.*/tap2_ip=\\\"".$_POST["tap2_ip"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

if(isset($_POST["tap2_mask"])){
    $exec = "/bin/sed -i 's/tap2_mask=.*/tap2_mask=\\\"".$_POST["tap2_mask"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

if(isset($_POST["tap2_gw"])){
    $exec = "/bin/sed -i 's/tap2_gw=.*/tap2_gw=\\\"".$_POST["tap2_gw"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
}

// ------------ IN | OUT (END) -------------

// -------------- WIRELESS ------------------

if(isset($_POST['newSSID'])){
	
    $hostapd_ssid=$_POST['newSSID'];
    
    $exec = "sed -i 's/hostapd_ssid=.*/hostapd_ssid=\\\"".$_POST['newSSID']."\\\";/g' options_config.php";
    exec_fruitywifi($exec);

    $exec = "/usr/sbin/karma-hostapd_cli -p /var/run/hostapd-phy0 karma_change_ssid " . $_POST['newSSID'];
    exec_fruitywifi($exec);
    
    // replace interface in hostapd.conf and hostapd-secure.conf
    $exec = "/bin/sed -i 's/^ssid=.*/ssid=".$_POST["newSSID"]."/g' $mod_path/includes/conf/hostapd.conf";
    exec_fruitywifi($exec);
    $exec = "/bin/sed -i 's/^ssid=.*/ssid=".$_POST["newSSID"]."/g' $mod_path/includes/conf/hostapd-secure.conf";
    exec_fruitywifi($exec);
}


if (isset($_POST['hostapd_secure'])) {
    $exec = "sed -i 's/hostapd_secure=.*/hostapd_secure=\\\"".$_POST["hostapd_secure"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);

    $hostapd_secure = $_POST["hostapd_secure"];
}

if (isset($_POST['hostapd_wpa_passphrase'])) {
    $exec = "sed -i 's/hostapd_wpa_passphrase=.*/hostapd_wpa_passphrase=\\\"".$_POST["hostapd_wpa_passphrase"]."\\\";/g' options_config.php";
    exec_fruitywifi($exec);
    
    $exec = "sed -i 's/wpa_passphrase=.*/wpa_passphrase=".$_POST["hostapd_wpa_passphrase"]."/g' $mod_path/includes/conf/hostapd-secure.conf";
    exec_fruitywifi($exec);
    
    $hostapd_wpa_passphrase = $_POST["hostapd_wpa_passphrase"];
}

header('Location: ../index.php?tab=3');
exit;

if ($type == "settings") {

    $exec = "/bin/sed -i 's/tap1_iface.*/tap1_iface = \\\"".$tap1_iface."\\\";/g' options_config.php";
    //$output = exec_fruitywifi($exec);

	$exec = "/bin/sed -i 's/tap1_ip.*/tap1_ip = \\\"".$tap1_ip."\\\";/g' options_config.php";
    //$output = exec_fruitywifi($exec);
	
    header('Location: ../index.php?tab=3');
    exit;

}

header('Location: ../index.php');

?>
