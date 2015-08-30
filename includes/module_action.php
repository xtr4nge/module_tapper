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
include "../../../login_check.php";
include "../../../config/config.php";
include "../_info_.php";
include "../../../functions.php";

include "options_config.php";

// Checking POST & GET variables...
if ($regex == 1) {
    regex_standard($_GET["service"], "../msg.php", $regex_extra);
    regex_standard($_GET["action"], "../msg.php", $regex_extra);
    regex_standard($_GET["page"], "../msg.php", $regex_extra);
    regex_standard($_GET["install"], "../msg.php", $regex_extra);
}

$service = $_GET['service'];
$action = $_GET['action'];
$page = $_GET['page'];
$install = $_GET['install'];

function flushIptables() {	
	global $bin_iptables;
	
	$exec = "$bin_iptables -F";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -t nat -F";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -t mangle -F";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -X";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -t nat -X";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -t mangle -X";
	exec_fruitywifi($exec);
	echo $exec;
}

function setNetworkManager() {
	
	global $io_in_iface;
	global $bin_sed;
	global $bin_echo;
	
	$exec = "macchanger --show $io_in_iface |grep 'Permanent'";
	exec($exec, $output);
	$mac = explode(" ", $output[0]);
	
	$exec = "grep '^unmanaged-devices' /etc/NetworkManager/NetworkManager.conf";
	$ispresent = exec($exec);
	
	$exec = "$bin_sed -i '/unmanaged/d' /etc/NetworkManager/NetworkManager.conf";
	exec_fruitywifi($exec);
	$exec = "$bin_sed -i '/\[keyfile\]/d' /etc/NetworkManager/NetworkManager.conf";
	exec_fruitywifi($exec);
	
	if ($ispresent == "") {
		$exec = "$bin_echo '[keyfile]' >> /etc/NetworkManager/NetworkManager.conf";
		exec_fruitywifi($exec);

		$exec = "$bin_echo 'unmanaged-devices=mac:".$mac[2].";interface-name:".$io_in_iface."' >> /etc/NetworkManager/NetworkManager.conf";
		exec_fruitywifi($exec);
	}
	
}

function cleanNetworkManager() {
	
	global $bin_sed;
	
	// REMOVE lines from NetworkManager
	$exec = "$bin_sed -i '/unmanaged/d' /etc/NetworkManager/NetworkManager.conf";
	exec_fruitywifi($exec);
	$exec = "$bin_sed -i '/\[keyfile\]/d' /etc/NetworkManager/NetworkManager.conf";
	exec_fruitywifi($exec);
}

function killRegex($regex){
	
	$exec = "ps aux|grep -iEe '$regex' | grep -v grep | awk '{print $2}'";
	exec($exec,$output);
	
	if (count($output) > 0) {
		$exec = "kill " . $output[0];
		exec_fruitywifi($exec);
	}
	
}

function copyLogsHistory() {
	
	global $bin_cp;
	global $bin_mv;
	global $mod_logs;
	global $mod_logs_history;
	global $bin_echo;
	
	if ( 0 < filesize( $mod_logs ) ) {
		$exec = "$bin_cp $mod_logs $mod_logs_history/".gmdate("Ymd-H-i-s").".log";
		exec_fruitywifi($exec);
		
		$exec = "$bin_echo '' > $mod_logs";
		exec_fruitywifi($exec);
	}
}

if($tap_mode == "1") {
	if ($action == "start") {
		
		$exec = "/etc/init.d/dhcpcd stop";
		exec_fruitywifi($exec);
		
		$exec = "brctl addbr bridge0";
		exec_fruitywifi($exec);
		$exec = "brctl addif bridge0 $tap1_iface";
		exec_fruitywifi($exec);
		$exec = "brctl addif bridge0 $tap2_iface";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $tap1_iface 0.0.0.0";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $tap2_iface 0.0.0.0";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig bridge0 up";
		exec_fruitywifi($exec);
	} else {
		$exec = "brctl delif bridge0 $tap1_iface";
		exec_fruitywifi($exec);
		$exec = "brctl delif bridge0 $tap2_iface";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig bridge0 down";
		exec_fruitywifi($exec);
		$exec = "brctl delbr bridge0";
		exec_fruitywifi($exec);
		
	}
}

if($tap_mode == "2") {
	if ($action == "start") {
		
		$exec = "/etc/init.d/dhcpcd stop";
		exec_fruitywifi($exec);
		
		killRegex("dhcpcd.+$tap2_iface");
		
		$exec = "$bin_ifconfig $tap1_iface down";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $tap1_iface 0.0.0.0";
		exec_fruitywifi($exec);
		
		$exec = "$bin_ifconfig $tap2_iface down";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $tap2_iface 0.0.0.0";
		exec_fruitywifi($exec);
		
		// TAP2
		$exec = "dhcpcd -i $tap2_iface";
		exec_fruitywifi($exec);
		
		// TAP1
		$exec = "$bin_ifconfig $tap1_iface up";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $tap1_iface up $tap1_ip netmask 255.255.255.0";
		exec_fruitywifi($exec);
		
		$exec = "$bin_echo 'nameserver $tap1_ip\nnameserver 8.8.8.8' > /etc/resolv.conf ";
		exec_fruitywifi($exec);
		
		$exec = "chattr +i /etc/resolv.conf";
        exec_fruitywifi($exec);
		
		$exec = "$bin_dnsmasq -C $mod_path/includes/conf/dnsmasq.conf";
		exec_fruitywifi($exec);
		
		// IPTABLES	FLUSH	
		flushIptables();
		
		$exec = "$bin_echo 1 > /proc/sys/net/ipv4/ip_forward";
		exec_fruitywifi($exec);
		$exec = "$bin_iptables -t nat -A POSTROUTING -o $tap2_iface -j MASQUERADE";
		exec_fruitywifi($exec);
		
	} else {
		
		// REMOVE lines from NetworkManager
		cleanNetworkManager();

		killRegex("dhcpcd.+$tap2_iface");

		$exec = "chattr -i /etc/resolv.conf";
        exec_fruitywifi($exec);

		killRegex("dnsmasq.+$mod_name.+dnsmasq");
		
		$exec = "$bin_ifconfig $tap1_iface down";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $tap1_iface 0.0.0.0";
		exec_fruitywifi($exec);
		
		$exec = "$bin_ifconfig $tap2_iface down";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $tap2_iface 0.0.0.0";
		exec_fruitywifi($exec);
		
		// IPTABLES	FLUSH	
		flushIptables();
		
		// LOGS COPY
		copyLogsHistory();
		
	}
}


if ($install == "install_$mod_name") {

    $exec = "chmod 755 install.sh";
    exec_fruitywifi($exec);

    $exec = "$bin_sudo ./install.sh > $log_path/install.txt &";
    exec_fruitywifi($exec);

    header('Location: ../../install.php?module='.$mod_name);
    exit;
}

if ($page == "status") {
    header('Location: ../../../action.php');
} else {
    header('Location: ../../action.php?page='.$mod_name);
}

?>
