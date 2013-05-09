<?php 
# AST_VICIDIAL_ingrouplist.php
# 
# shows the agents logged into vicidial and set to take calls from in-group
#
# Copyright (C) 2013  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 100320-2102 - First Build
# 130414-0222 - Added report logging
#

$startMS = microtime();

$report_name='In-Group Usuário List';

require("dbconnect.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["ENVIAR"]))				{$ENVIAR=$_GET["ENVIAR"];}
	elseif (isset($_POST["ENVIAR"]))	{$ENVIAR=$_POST["ENVIAR"];}

$PHP_AUTH_USER = ereg_replace("[^0-9a-zA-Z]","",$PHP_AUTH_USER);
$PHP_AUTH_PW = ereg_replace("[^0-9a-zA-Z]","",$PHP_AUTH_PW);

$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and pass='$PHP_AUTH_PW' and user_level > 6 and view_reports='1';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_query($stmt, $link);
$row=mysql_fetch_row($rslt);
$auth=$row[0];

if( (strlen($PHP_AUTH_USER)<2) or (strlen($PHP_AUTH_PW)<2) or (!$auth))
	{
    Header("WWW-Authenticate: Basic realm=\"VICI-PROJECTS\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo "Nome ou Senha inválidos: |$PHP_AUTH_USER|$PHP_AUTH_PW|\n";
    exit;
	}

##### BEGIN log visit to the vicidial_report_log table #####
$LOGip = getenv("REMOTE_ADDR");
$LOGbrowser = getenv("HTTP_USER_AGENT");
$LOGscript_name = getenv("SCRIPT_NAME");
$LOGserver_name = getenv("SERVER_NAME");
$LOGserver_port = getenv("SERVER_PORT");
$LOGrequest_uri = getenv("REQUEST_URI");
$LOGhttp_referer = getenv("HTTP_REFERER");
if (preg_match("/443/i",$LOGserver_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($LOGserver_port == '80') or ($LOGserver_port == '443') ) {$LOGserver_port='';}
else {$LOGserver_port = ":$LOGserver_port";}
$LOGfull_url = "$HTTPprotocol$LOGserver_name$LOGserver_port$LOGrequest_uri";

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group|', url='$LOGfull_url';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_query($stmt, $link);
$report_log_id = mysql_insert_id($link);
##### END log visit to the vicidial_report_log table #####

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = '';}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($server_ip)) {$server_ip = '10.10.10.15';}

$stmt="select group_id,group_name from vicidial_inbound_groups order by group_id;";
$rslt=mysql_query($stmt, $link);
if ($DB) {echo "$stmt\n";}
$ingroups_to_print = mysql_num_rows($rslt);
$i=0;
while ($i < $ingroups_to_print)
	{
	$row=mysql_fetch_row($rslt);
	$group_id[$i] =$row[0];
	$group_name[$i] =$row[1];
	$i++;
	}
?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}
-->
 </STYLE>

<?php 
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<TITLE>Live In-Group Agent Report</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

	require("admin_header.php");

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<SELECT SIZE=1 NAME=group>\n";
$o=0;
while ($ingroups_to_print > $o)
	{
	if ($group_id[$o] == $group) 
		{
		echo "<option selected value=\"$group_id[$o]\">$group_id[$o] - $group_name[$o]</option>\n";
		$selected_name=$group_name[$o];
		}
	else 
		{
		echo "<option value=\"$group_id[$o]\">$group_id[$o] - $group_name[$o]</option>\n";
		}
	$o++;
	}
echo "</SELECT>\n";
echo "<INPUT TYPE=Submit NAME=ENVIAR VALUE=ENVIAR>\n";
echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=3111&group_id=$group\">ALTERAR</a> \n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n\n";


if (!$group)
	{
	echo "\n\n";
	echo "PLEASE SELECT A IN-GROUP ABOVE AND CLICK ENVIAR\n";
	}

else
	{
	echo "Live Current Agentes logged in to take calls from $group - $selected_name         $NOW_TIME\n";

	echo "\n";

	$stmt="select count(*) from vicidial_live_agents where closer_campaigns LIKE\"% " . mysql_real_escape_string($group) . " %\";";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysql_fetch_row($rslt);

	$TOTALagents =	sprintf("%10s", $row[0]);

	echo "Total agents:       $TOTALagents\n";


	##############################
	#########  LIVE AGENTE STATS
	$user_list='|';
	echo "\n";
	echo "---------- LIVE AGENTS IN IN-GROUP\n";
	echo "+------+--------------------------------+----------------------+--------+---------------------+\n";
	echo "| #    | USUÁRIO                        | CAMPANHA             | STATUS | LAST ACTIVITY       |\n";
	echo "+------+--------------------------------+----------------------+--------+---------------------+\n";

	$stmt="select vla.user,vu.full_name,vla.campaign_id,vla.status,vla.last_state_change from vicidial_live_agents vla,vicidial_users vu where vla.closer_campaigns LIKE\"% " . mysql_real_escape_string($group) . " %\" and vla.user=vu.user order by vla.user limit 1000;";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$users_to_print = mysql_num_rows($rslt);
	$i=0;
	while ($i < $users_to_print)
		{
		$row=mysql_fetch_row($rslt);

		$i++;

		$FMT_i =		sprintf("%-4s", $i);
		$user =			sprintf("%-30s", "$row[0] - $row[1]");
			while(strlen($user)>30) {$user = substr("$user", 0, -1);}
		$campaign_id =	sprintf("%-8s", $row[2]);
		$status =		sprintf("%-6s", $row[3]);
		$time =			sprintf("%-19s", $row[4]);
		$user_list .=	"$row[0]---$row[3]|";

		echo "| $FMT_i | <a href=\"./user_status.php?user=$row[0]\">$user</a> | <a href=\"./admin.php?ADD=34&campaign_id=$row[2]\">$campaign_id</a>   <a href=\"./AST_timeonVDADall.php?RR=4&DB=0&group=$row[2]\">Tempo Real</a> | $status | $time |\n";
		}

	echo "+------+--------------------------------+----------------------+--------+---------------------+\n";

	
	if ($DB) {echo "\n$user_list\n";}

	##############################
	#########  ALL AGENTE STATS

	echo "\n";
	echo "---------- DEFAULT AGENTS IN IN-GROUP\n";
	echo "+------+--------------------------------+-----------+\n";
	echo "| #    | USUÁRIO                        | LOGGED IN |\n";
	echo "+------+--------------------------------+-----------+\n";

	$stmt="select vu.user,vu.full_name from vicidial_users vu where vu.closer_campaigns LIKE\"% " . mysql_real_escape_string($group) . " %\" order by vu.user limit 2000;";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$Xusers_to_print = mysql_num_rows($rslt);
	$i=0;
	while ($i < $Xusers_to_print)
		{
		$row=mysql_fetch_row($rslt);

		$i++;

		$FMT_i =		sprintf("%-4s", $i);
		$user =			sprintf("%-30s", "$row[0] - $row[1]");
			while(strlen($user)>30) {$user = substr("$user", 0, -1);}
		if (preg_match("/\|$row[0]---/",$user_list))
			{
			$userstatusARY =	explode("|$row[0]---",$user_list);
			$userstatus =		explode('|',$userstatusARY[1]);
			$status_line =		sprintf("%-9s", $userstatus[0]);

			if ($DB) {echo "\n$user_list     |$row[0]---     $userstatusARY[1]     $userstatus[0]\n";}
			}
		else
			{$status_line = '         ';}

		echo "| $FMT_i | <a href=\"./user_status.php?user=$row[0]\">$user</a> | $status_line |\n";
		}

	echo "+------+--------------------------------+-----------+\n";
	
	}


if ($db_source == 'S')
	{
	mysql_close($link);
	$use_slave_server=0;
	$db_source = 'M';
	require("dbconnect.php");
	}

$endMS = microtime();
$startMSary = explode(" ",$startMS);
$endMSary = explode(" ",$endMS);
$runS = ($endMSary[0] - $startMSary[0]);
$runM = ($endMSary[1] - $startMSary[1]);
$TOTALrun = ($runS + $runM);

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_query($stmt, $link);

?>
</PRE>

</TD></TR></TABLE>

</BODY></HTML>
