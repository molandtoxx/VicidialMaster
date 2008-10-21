<?
# manager_send.php    version 2.0.5
# 
# Copyright (C) 2008  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed purely to insert records into the vicidial_manager table to signal Actions to an asterisk server
# This script depends on the server_ip being sent and also needs to have a valid user/pass from the vicidial_users table
# 
# required variables:
#  - $server_ip
#  - $session_name
#  - $user
#  - $pass
# optional variables:
#  - $ACTION - ('Originate','Redirect','Hangup','Command','Monitor','StopMonitor','SysCIDOriginate','RedirectName','RedirectNameVmail','MonitorConf','StopMonitorConf','RedirectXtra','RedirectXtraCX','RedirectVD','HangupConfDial','VolumeControl','OriginateVDRelogin')
#  - $queryCID - ('CN012345678901234567',...)
#  - $format - ('text','debug')
#  - $channel - ('Zap/41-1','SIP/test101-1jut','IAX2/iaxy@iaxy',...)
#  - $exten - ('1234','913125551212',...)
#  - $ext_context - ('default','demo',...)
#  - $ext_priority - ('1','2',...)
#  - $filename - ('20050406-125623_44444',...)
#  - $extenName - ('phone100',...)
#  - $parkedby - ('phone100',...)
#  - $extrachannel - ('Zap/41-1','SIP/test101-1jut','IAX2/iaxy@iaxy',...)
#  - $auto_dial_level - ('0','1','1.1',...)
#  - $campaign - ('CLOSER','TESTCAMP',...)
#  - $uniqueid - ('1120232758.2406800',...)
#  - $lead_id - ('1234',...)
#  - $seconds - ('32',...)
#  - $outbound_cid - ('3125551212','0000000000',...)
#  - $agent_log_id - ('123456',...)
#  - $call_server_ip - ('10.10.10.15',...)
#  - $CalLCID - ('VD01234567890123456',...)
#  - $stage - ('UP','DOWN','2NDXfeR')
#  - $session_id - ('8600051')
#  - $FROMvdc - ('YES','NO')
#  - $agentchannel - ('SIP/cc101-g7yr','Zap/1-1',...)

# CHANGELOG:
# 50401-1002 - First build of script, Hangup function only
# 50404-1045 - Redirect basic function enabled
# 50406-1522 - Monitor basic function enabled
# 50407-1647 - Monitor and StopMonitor full functions enabled
# 50422-1120 - basic Originate function enabled
# 50428-1451 - basic SysCIDOriginate function enabled for checking voicemail
# 50502-1539 - basic RedirectName and RedirectNameVmail added
# 50503-1227 - added session_name checking for extra security
# 50523-1341 - added Conference call start/stop recording
# 50523-1421 - added OriginateName and OriginateNameVmail for local calls
# 50524-1602 - added RedirectToPark and RedirectFromPark
# 50531-1203 - added RedirecXtra for dual channel redirection
# 50630-1100 - script changed to not use HTTP login vars, user/pass instead
# 50804-1148 - Added RedirectVD for VICIDIAL blind redirection with logging
# 50815-1204 - Added NEXTAVAILABLE to RedirectXtra function
# 50903-2343 - Added HangupConfDial function to hangup in-dial channels in conf
# 50913-1057 - Added outbound_cid set if present to originate call
# 51020-1556 - Added agent_log_id framework for detailed agent activity logging
# 51118-1204 - Fixed Blind transfer bug from VICIDIAL when in manual dial mode
# 51129-1014 - Added ability to accept calls from other VICIDIAL servers
# 51129-1253 - Fixed Hangups of other agents channels in VICIDIAL AD
# 60310-2022 - Fixed NEXTAVAILABLE bug in leave-3way-call redirect function
# 60421-1413 - check GET/POST vars lines with isset to not trigger PHP NOTICES
# 60619-1158 - Added variable filters to close security holes for login form
# 60809-1544 - Added direct transfers to leave-3ways in consultative transfers
# 61004-1526 - Added parsing of volume control command and lookup or number
# 61130-1617 - Added lead_id to MonitorConf for recording_log
# 61201-1115 - Added user to MonitorConf for recording_log
# 70111-1600 - added ability to use BLEND/INBND/*_C/*_B/*_I as closer campaigns
# 70226-1251 - Added Mute/UnMute to conference volume control
# 70320-1502 - Added option to allow retry of leave-3way-call and debug logging
# 70322-1636 - Added sipsak display ability
# 80331-1433 - Added second transfer try for VICIDIAL transfers on manual dial calls
# 80402-0121 - Fixes for manual dial transfers on some systems
# 80424-0442 - Added non_latin lookup from system_settings
# 80707-2325 - Added vicidial_id to recording_log for tracking of vicidial or closer log to recording
# 80915-1755 - Rewrote leave-3way functions for external calling
# 81011-1404 - Fixed bugs in leave3way when transferring a manual dial call
# 81020-1459 - Fixed bugs in queue_log logging
#

require("dbconnect.php");

### These are variable assignments for PHP globals off
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["pass"]))					{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))			{$pass=$_POST["pass"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["session_name"]))			{$session_name=$_GET["session_name"];}
	elseif (isset($_POST["session_name"]))	{$session_name=$_POST["session_name"];}
if (isset($_GET["ACTION"]))					{$ACTION=$_GET["ACTION"];}
	elseif (isset($_POST["ACTION"]))		{$ACTION=$_POST["ACTION"];}
if (isset($_GET["queryCID"]))				{$queryCID=$_GET["queryCID"];}
	elseif (isset($_POST["queryCID"]))		{$queryCID=$_POST["queryCID"];}
if (isset($_GET["format"]))					{$format=$_GET["format"];}
	elseif (isset($_POST["format"]))		{$format=$_POST["format"];}
if (isset($_GET["channel"]))				{$channel=$_GET["channel"];}
	elseif (isset($_POST["channel"]))		{$channel=$_POST["channel"];}
if (isset($_GET["exten"]))					{$exten=$_GET["exten"];}
	elseif (isset($_POST["exten"]))			{$exten=$_POST["exten"];}
if (isset($_GET["ext_context"]))			{$ext_context=$_GET["ext_context"];}
	elseif (isset($_POST["ext_context"]))	{$ext_context=$_POST["ext_context"];}
if (isset($_GET["ext_priority"]))			{$ext_priority=$_GET["ext_priority"];}
	elseif (isset($_POST["ext_priority"]))	{$ext_priority=$_POST["ext_priority"];}
if (isset($_GET["filename"]))				{$filename=$_GET["filename"];}
	elseif (isset($_POST["filename"]))		{$filename=$_POST["filename"];}
if (isset($_GET["extenName"]))				{$extenName=$_GET["extenName"];}
	elseif (isset($_POST["extenName"]))		{$extenName=$_POST["extenName"];}
if (isset($_GET["parkedby"]))				{$parkedby=$_GET["parkedby"];}
	elseif (isset($_POST["parkedby"]))		{$parkedby=$_POST["parkedby"];}
if (isset($_GET["extrachannel"]))			{$extrachannel=$_GET["extrachannel"];}
	elseif (isset($_POST["extrachannel"]))	{$extrachannel=$_POST["extrachannel"];}
if (isset($_GET["auto_dial_level"]))			{$auto_dial_level=$_GET["auto_dial_level"];}
	elseif (isset($_POST["auto_dial_level"]))	{$auto_dial_level=$_POST["auto_dial_level"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["uniqueid"]))				{$uniqueid=$_GET["uniqueid"];}
	elseif (isset($_POST["uniqueid"]))		{$uniqueid=$_POST["uniqueid"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["secondS"]))				{$secondS=$_GET["secondS"];}
	elseif (isset($_POST["secondS"]))		{$secondS=$_POST["secondS"];}
if (isset($_GET["outbound_cid"]))			{$outbound_cid=$_GET["outbound_cid"];}
	elseif (isset($_POST["outbound_cid"]))	{$outbound_cid=$_POST["outbound_cid"];}
if (isset($_GET["agent_log_id"]))			{$agent_log_id=$_GET["agent_log_id"];}
	elseif (isset($_POST["agent_log_id"]))	{$agent_log_id=$_POST["agent_log_id"];}
if (isset($_GET["call_server_ip"]))				{$call_server_ip=$_GET["call_server_ip"];}
	elseif (isset($_POST["call_server_ip"]))	{$call_server_ip=$_POST["call_server_ip"];}
if (isset($_GET["CalLCID"]))				{$CalLCID=$_GET["CalLCID"];}
	elseif (isset($_POST["CalLCID"]))		{$CalLCID=$_POST["CalLCID"];}
if (isset($_GET["phone_code"]))				{$phone_code=$_GET["phone_code"];}
	elseif (isset($_POST["phone_code"]))	{$phone_code=$_POST["phone_code"];}
if (isset($_GET["phone_number"]))			{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["extension"]))				{$extension=$_GET["extension"];}
	elseif (isset($_POST["extension"]))		{$extension=$_POST["extension"];}
if (isset($_GET["protocol"]))				{$protocol=$_GET["protocol"];}
	elseif (isset($_POST["protocol"]))		{$protocol=$_POST["protocol"];}
if (isset($_GET["phone_ip"]))				{$phone_ip=$_GET["phone_ip"];}
	elseif (isset($_POST["phone_ip"]))		{$phone_ip=$_POST["phone_ip"];}
if (isset($_GET["enable_sipsak_messages"]))				{$enable_sipsak_messages=$_GET["enable_sipsak_messages"];}
	elseif (isset($_POST["enable_sipsak_messages"]))	{$enable_sipsak_messages=$_POST["enable_sipsak_messages"];}
if (isset($_GET["allow_sipsak_messages"]))				{$allow_sipsak_messages=$_GET["allow_sipsak_messages"];}
	elseif (isset($_POST["allow_sipsak_messages"]))		{$allow_sipsak_messages=$_POST["allow_sipsak_messages"];}
if (isset($_GET["session_id"]))				{$session_id=$_GET["session_id"];}
	elseif (isset($_POST["session_id"]))	{$session_id=$_POST["session_id"];}
if (isset($_GET["FROMvdc"]))				{$FROMvdc=$_GET["FROMvdc"];}
	elseif (isset($_POST["FROMvdc"]))		{$FROMvdc=$_POST["FROMvdc"];}
if (isset($_GET["agentchannel"]))			{$agentchannel=$_GET["agentchannel"];}
	elseif (isset($_POST["agentchannel"]))	{$agentchannel=$_POST["agentchannel"];}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin FROM system_settings;";
$rslt=mysql_query($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysql_num_rows($rslt);
$i=0;
while ($i < $qm_conf_ct)
	{
	$row=mysql_fetch_row($rslt);
	$non_latin =					$row[0];
	$i++;
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
{
$user=ereg_replace("[^0-9a-zA-Z]","",$user);
$pass=ereg_replace("[^0-9a-zA-Z]","",$pass);
$secondS = ereg_replace("[^0-9]","",$secondS);
}

# default optional vars if not set
if (!isset($ACTION))   {$ACTION="Originate";}
if (!isset($format))   {$format="alert";}
if (!isset($ext_priority))   {$ext_priority="1";}

$version = '2.0.5-34';
$build = '81020-1459';
$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$NOWnum = date("YmdHis");
if (!isset($query_date)) {$query_date = $NOW_DATE;}

$stmt="SELECT count(*) from vicidial_users where user='$user' and pass='$pass' and user_level > 0;";
if ($DB) {echo "|$stmt|\n";}
if ($non_latin > 0) {$rslt=mysql_query("SET NAMES 'UTF8'");}
$rslt=mysql_query($stmt, $link);
$row=mysql_fetch_row($rslt);
$auth=$row[0];

  if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0))
	{
    echo "Utentename/Password non validi: |$user|$pass|\n";
    exit;
	}
  else
	{

	if( (strlen($server_ip)<6) or (!isset($server_ip)) or ( (strlen($session_name)<12) or (!isset($session_name)) ) )
		{
		echo "ip del server non valido: |$server_ip|  or  Nome sessione non valido: |$session_name|\n";
		exit;
		}
	else
		{
		$stmt="SELECT count(*) from web_client_sessions where session_name='$session_name' and server_ip='$server_ip';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_query($stmt, $link);
		$row=mysql_fetch_row($rslt);
		$SNauth=$row[0];
		  if($SNauth==0)
			{
			echo "Nome sessione non valido: |$session_name|$server_ip|\n";
			exit;
			}
		  else
			{
			# do nothing for now
			}
		}
	}

if ($format=='debug')
{
echo "<html>\n";
echo "<head>\n";
echo "<!-- VERSIONE: $version     BUILD: $build    ACTION: $ACTION   server_ip: $server_ip-->\n";
echo "<title>Invio Manager: ";
if ($ACTION=="Originate")		{echo "Originate";}
if ($ACTION=="Redirect")		{echo "Redirect";}
if ($ACTION=="RedirectName")	{echo "RedirectName";}
if ($ACTION=="Hangup")			{echo "Hangup";}
if ($ACTION=="Command")			{echo "Command";}
if ($ACTION==99999)	{echo "AIUTO";}
echo "</title>\n";
echo "</head>\n";
echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
}





######################
# ACTION=SysCIDOriginate  - insert Originate Manager statement allowing small CIDs for system calls
######################
if ($ACTION=="SysCIDOriginate")
{
	if ( (strlen($exten)<1) or (strlen($channel)<1) or (strlen($ext_context)<1) or (strlen($queryCID)<1) )
	{
		echo "Exten $exten non e` valido or queryCID $queryCID non e` valido, Originate comando non inserito\n";
	}
	else
	{
	$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$queryCID','Channel: $channel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','Callerid: $queryCID','','','','','');";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_query($stmt, $link);
	echo "Originate comando inviato per Exten $exten Canale $channel su $server_ip\n";
	}
}



######################
# ACTION=Originate, OriginateName, OriginateNameVmail  - insert Originate Manager statement
######################
if ($ACTION=="OriginateName")
{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($extenName)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
	{
		$channel_live=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "extenName $extenName deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "\nOriginateName Action non inviato\n";
	}
	else
	{
		$stmt="SELECT dialplan_number FROM phones where server_ip = '$server_ip' and extension='$extenName';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$name_count = mysql_num_rows($rslt);
		if ($name_count>0)
		{
		$row=mysql_fetch_row($rslt);
		$exten = $row[0];
		$ACTION="Originate";
		}
	}
}

if ($ACTION=="OriginateNameVmail")
{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($extenName)<1)  or (strlen($exten)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
	{
		$channel_live=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "extenName $extenName deve essere impostato\n";
		echo "exten $exten deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "\nOriginateNameVmail Action non inviato\n";
	}
	else
	{
		$stmt="SELECT voicemail_id FROM phones where server_ip = '$server_ip' and extension='$extenName';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$name_count = mysql_num_rows($rslt);
		if ($name_count>0)
		{
		$row=mysql_fetch_row($rslt);
		$exten = "$exten$row[0]";
		$ACTION="Originate";
		}
	}
}

if ($ACTION=="OriginateVDRelogin")
{
	if ( ($enable_sipsak_messages > 0) and ($allow_sipsak_messages > 0) and (eregi("SIP",$protocol)) )
	{
	$CIDdate = date("ymdHis");
	$DS='-';
	$SIPSAK_prefix = 'LIN-';
	print "<!-- sending login sipsak message: $SIPSAK_prefix$VD_campaign -->\n";
	passthru("/usr/local/bin/sipsak -M -O desktop -B \"$SIPSAK_prefix$campaign\" -r 5060 -s sip:$extension@$phone_ip > /dev/null");
	$queryCID = "$SIPSAK_prefix$campaign$DS$CIDdate";

	}
	$ACTION="Originate";
}

if ($ACTION=="Originate")
{
	if ( (strlen($exten)<1) or (strlen($channel)<1) or (strlen($ext_context)<1) or (strlen($queryCID)<10) )
	{
		echo "Exten $exten non e` valido or queryCID $queryCID non e` valido, Originate comando non inserito\n";
	}
	else
	{
	if (strlen($outbound_cid)>1)
		{$outCID = "\"$queryCID\" <$outbound_cid>";}
	else
		{$outCID = "$queryCID";}
	$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$queryCID','Channel: $channel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','Callerid: $outCID','','','','','');";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_query($stmt, $link);
	echo "Originate comando inviato per Exten $exten Canale $channel su $server_ip\n";
	}
}



######################
# ACTION=HangupConfDial  - find the Local channel that is in the conference and needs to be hung up
######################
if ($ACTION=="HangupConfDial")
{
	$row='';   $rowx='';
	$channel_live=1;
	if ( (strlen($exten)<3) or (strlen($queryCID)<15) or (strlen($ext_context)<1) )
	{
		$channel_live=0;
		echo "conference $exten non e` valido or ext_context $ext_context or queryCID $queryCID non e` valido, Hangup comando non inserito\n";
	}
	else
	{
		$local_DEF = 'Local/';
		$local_AMP = '@';
		$hangup_channel_prefix = "$local_DEF$exten$local_AMP$ext_context";

		$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$hangup_channel_prefix%\";";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$row=mysql_fetch_row($rslt);
		if ($row > 0)
		{
			$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$hangup_channel_prefix%\";";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			$channel=$rowx[0];
			$ACTION="Hangup";
			$queryCID = eregi_replace("^.","G",$queryCID);  # GTvdcW...
		}
	}
}



######################
# ACTION=Hangup  - insert Hangup Manager statement
######################
if ($ACTION=="Hangup")
{
$stmt="UPDATE vicidial_live_agents SET external_hangup='0' where user='$user';";
	if ($format=='debug') {echo "\n<!-- $stmt -->";}
$rslt=mysql_query($stmt, $link);

	$row='';   $rowx='';
	$channel_live=1;
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) )
		{
		$channel_live=0;
		echo "Canale $channel non e` valido or queryCID $queryCID non e` valido, Hangup comando non inserito\n";
		}
	else
		{
		if (strlen($call_server_ip)<7) {$call_server_ip = $server_ip;}

#		$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel';";
#			if ($format=='debug') {echo "\n<!-- $stmt -->";}
#		$rslt=mysql_query($stmt, $link);
#		$row=mysql_fetch_row($rslt);
#		if ($row[0]==0)
#		{
#			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$call_server_ip' and channel='$channel';";
#				if ($format=='debug') {echo "\n<!-- $stmt -->";}
#			$rslt=mysql_query($stmt, $link);
#			$rowx=mysql_fetch_row($rslt);
#			if ($rowx[0]==0)
#			{
#				$channel_live=0;
#				echo "Channel $channel is not live on $call_server_ip, Hangup command not inserted\n";
#			}	
#		}
		if ( ($auto_dial_level > 0) and (strlen($CalLCID)>2) and (strlen($exten)>2) and ($secondS > 0))
			{
			$stmt="SELECT count(*) FROM vicidial_auto_calls where channel='$channel' and callerid='$CalLCID';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			if ($rowx[0]==0)
				{
				echo "Call $CalLCID $channel non e` attivo su $call_server_ip, Checking Live Canale...\n";

				$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel' and extension LIKE \"%$exten\";";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				$row=mysql_fetch_row($rslt);
				if ($row[0]==0)
					{
					$channel_live=0;
					echo "Canale $channel non e` attivo su $call_server_ip, Hangup comando non inserito $rowx[0]\n$stmt\n";
					}
				else
					{
					echo "$stmt\n";
					}
				}
			}

		if ($channel_live==1)
			{
			if ( (strlen($CalLCID)>15) and ($secondS > 0))
				{
				$stmt="SELECT count(*) FROM vicidial_auto_calls where callerid='$CalLCID';";
				$rslt=mysql_query($stmt, $link);
				$rowx=mysql_fetch_row($rslt);
				if ($format=='debug') {echo "\n<!-- $rowx[0]|$stmt -->";}
				if ($rowx[0] > 0)
					{
					#############################################
					##### INIZIO QUEUEMETRICS LOGGING LOOKUP #####
					$stmt = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id FROM system_settings;";
					$rslt=mysql_query($stmt, $link);
					if ($format=='debug') {echo "\n<!-- $rowx[0]|$stmt -->";}
					$qm_conf_ct = mysql_num_rows($rslt);
					$i=0;
					while ($i < $qm_conf_ct)
						{
						$row=mysql_fetch_row($rslt);
						$enable_queuemetrics_logging =	$row[0];
						$queuemetrics_server_ip	=		$row[1];
						$queuemetrics_dbname =			$row[2];
						$queuemetrics_login	=			$row[3];
						$queuemetrics_pass =			$row[4];
						$queuemetrics_log_id =			$row[5];
						$i++;
						}
					##### END QUEUEMETRICS LOGGING LOOKUP #####
					###########################################
					if ($enable_queuemetrics_logging > 0)
						{
						$linkB=mysql_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass");
						mysql_select_db("$queuemetrics_dbname", $linkB);

						$stmt="SELECT count(*) from queue_log where call_id='$CalLCID' and verb='CONNECT';";
						$rslt=mysql_query($stmt, $linkB);
						$VAC_cn_ct = mysql_num_rows($rslt);
						if ($VAC_cn_ct > 0)
							{
							$row=mysql_fetch_row($rslt);
							$caller_connect	= $row[0];
							}
						if ($format=='debug') {echo "\n<!-- $caller_connect|$stmt -->";}
						if ($caller_connect > 0)
							{
							### grab call lead information needed for QM logging
							$stmt="SELECT auto_call_id,lead_id,phone_number,status,campaign_id,phone_code,alt_dial,stage,callerid,uniqueid from vicidial_auto_calls where callerid='$CalLCID' order by call_time limit 1;";
							$rslt=mysql_query($stmt, $link);
							$VAC_qm_ct = mysql_num_rows($rslt);
							if ($VAC_qm_ct > 0)
								{
								$row=mysql_fetch_row($rslt);
								$auto_call_id	= $row[0];
								$CLlead_id		= $row[1];
								$CLphone_number	= $row[2];
								$CLstatus		= $row[3];
								$CLcampaign_id	= $row[4];
								$CLphone_code	= $row[5];
								$CLalt_dial		= $row[6];
								$CLstage		= $row[7];
								$CLcallerid		= $row[8];
								$CLuniqueid		= $row[9];
								}
							if ($format=='debug') {echo "\n<!-- $CLcampaign_id|$stmt -->";}

							$CLstage = preg_replace("/.*-/",'',$CLstage);
							if (strlen($CLstage) < 1) {$CLstage=0;}

							$stmt="SELECT count(*) from queue_log where call_id='$CalLCID' and verb='COMPLETECALLER' and queue='$CLcampaign_id';";
							$rslt=mysql_query($stmt, $linkB);
							$VAC_cc_ct = mysql_num_rows($rslt);
							if ($VAC_cc_ct > 0)
								{
								$row=mysql_fetch_row($rslt);
								$caller_complete	= $row[0];
								}
							if ($format=='debug') {echo "\n<!-- $caller_complete|$stmt -->";}

							if ($caller_complete < 1)
								{
								$time_id=0;
								$stmt="SELECT time_id from queue_log where call_id='$CalLCID' and verb='ENTERQUEUE' and queue='$CLcampaign_id';";
								$rslt=mysql_query($stmt, $linkB);
								$VAC_eq_ct = mysql_num_rows($rslt);
								if ($VAC_eq_ct > 0)
									{
									$row=mysql_fetch_row($rslt);
									$time_id	= $row[0];
									}
								$StarTtime = date("U");
								if ($time_id > 100000) 
									{$secondS = ($StarTtime - $time_id);}

								if ($format=='debug') {echo "\n<!-- $caller_complete|$stmt -->";}
								$stmt = "INSERT INTO queue_log SET partition='P01',time_id='$StarTtime',call_id='$CalLCID',queue='$CLcampaign_id',agent='Agent/$user',verb='COMPLETEAGENT',data1='$CLstage',data2='$secondS',data3='1',serverid='$queuemetrics_log_id';";
								$rslt=mysql_query($stmt, $linkB);
								$affected_rows = mysql_affected_rows($linkB);
								if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}
								}
							}
						}
					}
				}

			$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$call_server_ip','','Hangup','$queryCID','Channel: $channel','','','','','','','','','');";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			echo "Hangup comando inviato per Canale $channel su $call_server_ip\n";
			}
		}
}



######################
# ACTION=Redirect, RedirectName, RedirectNameVmail, RedirectToPark, RedirectFromPark, RedirectVD, RedirectXtra, RedirectXtraCX
# - insert Redirect Manager statement using extensions name
######################
if ($ACTION=="RedirectVD")
{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($campaign)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($uniqueid)<2) or (strlen($lead_id)<1) )
	{
		$channel_live=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "exten $exten deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "auto_dial_level $auto_dial_level deve essere impostato\n";
		echo "campaign $campaign deve essere impostato\n";
		echo "uniqueid $uniqueid deve essere impostato\n";
		echo "lead_id $lead_id deve essere impostato\n";
		echo "\nRedirectVD Action non inviato\n";
	}
	else
	{
		if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
		$stmt = "select count(*) from vicidial_campaigns where campaign_id='$campaign' and campaign_allow_inbound='Y';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
			$row=mysql_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "UPDATE vicidial_closer_log set end_epoch='$StarTtime', length_in_sec='$secondS',status='XFER' where lead_id='$lead_id' order by start_epoch desc limit 1;";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			}
		if ($auto_dial_level < 1)
			{
			$stmt = "UPDATE vicidial_log set end_epoch='$StarTtime', length_in_sec='$secondS',status='XFER' where uniqueid='$uniqueid';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			}
		else
			{
			$stmt = "DELETE from vicidial_auto_calls where uniqueid='$uniqueid';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			}
		$ACTION="Redirect";
	}
}

if ($ACTION=="RedirectToPark")
{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($extenName)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($parkedby)<1) )
	{
		$channel_live=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "exten $exten deve essere impostato\n";
		echo "extenName $extenName deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "parkedby $parkedby deve essere impostato\n";
		echo "\nRedirectToPark Action non inviato\n";
	}
	else
	{
		if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
		$stmt = "INSERT INTO parked_channels values('$channel','$server_ip','','$extenName','$parkedby','$NOW_TIME');";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$ACTION="Redirect";
	}
}

if ($ACTION=="RedirectFromPark")
{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) )
	{
		$channel_live=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "exten $exten deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "\nRedirectFromPark Action non inviato\n";
	}
	else
	{
		if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
		$stmt = "DELETE FROM parked_channels where server_ip='$server_ip' and channel='$channel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$ACTION="Redirect";
	}
}

if ($ACTION=="RedirectName")
{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($extenName)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
	{
		$channel_live=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "extenName $extenName deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "\nRedirectName Action non inviato\n";
	}
	else
	{
		$stmt="SELECT dialplan_number FROM phones where server_ip = '$server_ip' and extension='$extenName';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$name_count = mysql_num_rows($rslt);
		if ($name_count>0)
		{
		$row=mysql_fetch_row($rslt);
		$exten = $row[0];
		$ACTION="Redirect";
		}
	}
}

if ($ACTION=="RedirectNameVmail")
{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($extenName)<1)  or (strlen($exten)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
	{
		$channel_live=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "extenName $extenName deve essere impostato\n";
		echo "exten $exten deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "\nRedirectNameVmail Action non inviato\n";
	}
	else
	{
		$stmt="SELECT voicemail_id FROM phones where server_ip = '$server_ip' and extension='$extenName';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$name_count = mysql_num_rows($rslt);
		if ($name_count>0)
		{
		$row=mysql_fetch_row($rslt);
		$exten = "$exten$row[0]";
		$ACTION="Redirect";
		}
	}
}






if ($ACTION=="RedirectXtraCXNeW")
{
	$DBout='';
	$row='';   $rowx='';
	$channel_liveX=1;
	$channel_liveY=1;
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($session_id)<3) or ( ( (strlen($extrachannel)<3) or (strlen($exten)<1) ) and (!ereg("NEXTAVAILABLE",$exten)) ) )
	{
		$channel_liveX=0;
		$channel_liveY=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "ExtraCanale $extrachannel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "exten $exten deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "\nRedirect Action non inviato\n";
		if (ereg("SECOND|FIRST|DEBUG",$filename))
			{
			if ($WeBRooTWritablE > 0)
				{
				$fp = fopen ("./vicidial_debug.txt", "a");
				fwrite ($fp, "$NOW_TIME|RDCXC|$filename|$user|$campaign|$channel|$extrachannel|$queryCID|$exten|$ext_context|ext_priority|\n");
				fclose($fp);
				}
			}
	}
	else
	{
		if (ereg("NEXTAVAILABLE",$exten))
			{
			$stmtA="SELECT count(*) FROM vicidial_conferences where server_ip='$server_ip' and ((extension='') or (extension is null)) and conf_exten != '$session_id';";
				if ($format=='debug') {echo "\n<!-- $stmtA -->";}
			$rslt=mysql_query($stmtA, $link);
			$row=mysql_fetch_row($rslt);
			if ($row[0] > 1)
				{
				$stmtB="UPDATE vicidial_conferences set extension='$protocol/$extension$NOWnum', leave_3way='0' where server_ip='$server_ip' and ((extension='') or (extension is null)) and conf_exten != '$session_id' limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmtB -->";}
				$rslt=mysql_query($stmtB, $link);

				$stmtC="SELECT conf_exten from vicidial_conferences where server_ip='$server_ip' and extension='$protocol/$extension$NOWnum' and conf_exten != '$session_id';";
					if ($format=='debug') {echo "\n<!-- $stmtC -->";}
				$rslt=mysql_query($stmtC, $link);
				$row=mysql_fetch_row($rslt);
				$exten = $row[0];

				$stmtD="UPDATE vicidial_conferences set extension='$protocol/$extension' where server_ip='$server_ip' and conf_exten='$exten' limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmtD -->";}
				$rslt=mysql_query($stmtD, $link);

				$stmtE="UPDATE vicidial_conferences set leave_3way='1', leave_3way_datetime='$NOW_TIME', extension='3WAY_$user' where server_ip='$server_ip' and conf_exten='$session_id';";
					if ($format=='debug') {echo "\n<!-- $stmtE -->";}
				$rslt=mysql_query($stmtE, $link);

				$queryCID = "CXAR24$NOWnum";
				$stmtF="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $agentchannel','Context: $ext_context','Exten: $exten','Priority: 1','CallerID: $queryCID','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmtF -->";}
				$rslt=mysql_query($stmtF, $link);

				$stmtG="UPDATE vicidial_live_agents set conf_exten='$exten' where server_ip='$server_ip' and user='$user';";
					if ($format=='debug') {echo "\n<!-- $stmtG -->";}
				$rslt=mysql_query($stmtG, $link);

				if ($auto_dial_level < 1)
					{
					$stmtH = "DELETE from vicidial_auto_calls where lead_id='$lead_id' and callerid LIKE \"M%\";";
						if ($format=='debug') {echo "\n<!-- $stmtH -->";}
					$rslt=mysql_query($stmtH, $link);
					}

			//	$fp = fopen ("./vicidial_debug_3way.txt", "a");
			//	fwrite ($fp, "$NOW_TIME|$filename|\n|$stmtA|\n|$stmtB|\n|$stmtC|\n|$stmtD|\n|$stmtE|\n|$stmtF|\n|$stmtG|\n|$stmtH|\n\n");
			//	fclose($fp);

				echo "NeWSessioN|$exten|\n";
				echo "|$stmtG|\n";
				
				exit;
				}
			else
				{
				$channel_liveX=0;
				echo "Cannot find empty vicidial_conference su $server_ip, Redirect comando non inserito\n|$stmt|";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "Impossibile trovare conferenze vuote su $server_ip";}
				}
			}

		if (strlen($call_server_ip)<7) {$call_server_ip = $server_ip;}

		$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$row=mysql_fetch_row($rslt);
		if ($row[0]==0)
		{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$call_server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			if ($rowx[0]==0)
			{
				$channel_liveX=0;
				echo "Canale $channel non e` attivo su $call_server_ip, Redirect comando non inserito\n";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel non e` attivo su $call_server_ip";}
			}	
		}
		$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$extrachannel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$row=mysql_fetch_row($rslt);
		if ($row[0]==0)
		{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$extrachannel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			if ($rowx[0]==0)
			{
				$channel_liveY=0;
				echo "Canale $channel non e` attivo su $server_ip, Redirect comando non inserito\n";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel non e` attivo su $server_ip";}
			}	
		}
		if ( ($channel_liveX==1) && ($channel_liveY==1) )
		{
			$stmt="SELECT count(*) FROM vicidial_live_agents where lead_id='$lead_id' and user!='$user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			if ($rowx[0] < 1)
			{
				$channel_liveY=0;
				echo "No Local agent to send call to, Redirect comando non inserito\n";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "No Local agent to send call to";}
			}	
			else
			{
				$stmt="SELECT server_ip,conf_exten,user FROM vicidial_live_agents where lead_id='$lead_id' and user!='$user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				$rowx=mysql_fetch_row($rslt);
				$dest_server_ip = $rowx[0];
				$dest_session_id = $rowx[1];
				$dest_user = $rowx[2];
				$S='*';

				$D_s_ip = explode('.', $dest_server_ip);
				if (strlen($D_s_ip[0])<2) {$D_s_ip[0] = "0$D_s_ip[0]";}
				if (strlen($D_s_ip[0])<3) {$D_s_ip[0] = "0$D_s_ip[0]";}
				if (strlen($D_s_ip[1])<2) {$D_s_ip[1] = "0$D_s_ip[1]";}
				if (strlen($D_s_ip[1])<3) {$D_s_ip[1] = "0$D_s_ip[1]";}
				if (strlen($D_s_ip[2])<2) {$D_s_ip[2] = "0$D_s_ip[2]";}
				if (strlen($D_s_ip[2])<3) {$D_s_ip[2] = "0$D_s_ip[2]";}
				if (strlen($D_s_ip[3])<2) {$D_s_ip[3] = "0$D_s_ip[3]";}
				if (strlen($D_s_ip[3])<3) {$D_s_ip[3] = "0$D_s_ip[3]";}
				$dest_dialstring = "$D_s_ip[0]$S$D_s_ip[1]$S$D_s_ip[2]$S$D_s_ip[3]$S$dest_session_id$S$lead_id$S$dest_user$S$phone_code$S$phone_number$S$campaign$S";

				$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$call_server_ip','','Redirect','$queryCID','Channel: $channel','Context: $ext_context','Exten: $dest_dialstring','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);

				$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Hangup','$queryCID','Channel: $extrachannel','','','','','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);

				echo "RedirectXtraCX comando inviato per Canale $channel su $call_server_ip and \nHungup $extrachannel su $server_ip\n";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel su $call_server_ip, Hungup $extrachannel su $server_ip";}
			}
		}
		else
		{
			if ($channel_liveX==1)
			{$ACTION="Redirect";   $server_ip = $call_server_ip;}
			if ($channel_liveY==1)
			{$ACTION="Redirect";   $channel=$extrachannel;}
			if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "Changed to Redirect: $channel su $server_ip";}
		}

	if (ereg("SECOND|FIRST|DEBUG",$filename))
		{
		if ($WeBRooTWritablE > 0)
			{
			$fp = fopen ("./vicidial_debug.txt", "a");
			fwrite ($fp, "$NOW_TIME|RDCXC|$filename|$user|$campaign|$DBout|\n");
			fclose($fp);
			}
		}

	}
}










if ($ACTION=="RedirectXtraNeW")
{
	if ($channel=="$extrachannel")
	{$ACTION="Redirect";}
	else
	{
		$row='';   $rowx='';
		$channel_liveX=1;
		$channel_liveY=1;
		if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($session_id)<3) or ( ( (strlen($extrachannel)<3) or (strlen($exten)<1) ) and (!ereg("NEXTAVAILABLE",$exten)) ) )
		{
			$channel_liveX=0;
			$channel_liveY=0;
			echo "Una di queste variabili non e` valido:\n";
			echo "Canale $channel deve avere piu' di 2 caratteri\n";
			echo "ExtraCanale $extrachannel deve avere piu' di 2 caratteri\n";
			echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
			echo "exten $exten deve essere impostato\n";
			echo "ext_context $ext_context deve essere impostato\n";
			echo "ext_priority $ext_priority deve essere impostato\n";
			echo "session_id $session_id deve essere impostato\n";
			echo "\nRedirect Action non inviato\n";
			if (ereg("SECOND|FIRST|DEBUG",$filename))
				{
				if ($WeBRooTWritablE > 0)
					{
					$fp = fopen ("./vicidial_debug.txt", "a");
					fwrite ($fp, "$NOW_TIME|RDX|$filename|$user|$campaign|$$channel|$extrachannel|$queryCID|$exten|$ext_context|ext_priority|$session_id|\n");
					fclose($fp);
					}
				}
		}
	else
		{
		if (ereg("NEXTAVAILABLE",$exten))
			{
			$stmt="SELECT count(*) FROM vicidial_conferences where server_ip='$server_ip' and ((extension='') or (extension is null)) and conf_exten != '$session_id';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$row=mysql_fetch_row($rslt);
			if ($row[0] > 1)
				{
				$stmt="UPDATE vicidial_conferences set extension='$protocol/$extension$NOWnum', leave_3way='0' where server_ip='$server_ip' and ((extension='') or (extension is null)) and conf_exten != '$session_id' limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);

				$stmt="SELECT conf_exten from vicidial_conferences where server_ip='$server_ip' and extension='$protocol/$extension$NOWnum' and conf_exten != '$session_id';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				$row=mysql_fetch_row($rslt);
				$exten = $row[0];

				$stmt="UPDATE vicidial_conferences set extension='$protocol/$extension' where server_ip='$server_ip' and conf_exten='$exten' limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);

				$stmt="UPDATE vicidial_conferences set leave_3way='1', leave_3way_datetime='$NOW_TIME', extension='3WAY_$user' where server_ip='$server_ip' and conf_exten='$session_id';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);

				$queryCID = "CXAR23$NOWnum";
				$stmtB="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $agentchannel','Context: $ext_context','Exten: $exten','Priority: 1','CallerID: $queryCID','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmtB, $link);

				$stmt="UPDATE vicidial_live_agents set conf_exten='$exten' where server_ip='$server_ip' and user='$user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);

				if ($auto_dial_level < 1)
					{
					$stmt = "DELETE from vicidial_auto_calls where lead_id='$lead_id' and callerid LIKE \"M%\";";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_query($stmt, $link);
					}

				echo "NeWSessioN|$exten|\n";
				echo "|$stmtB|\n";
				
				exit;
				}
			else
				{
				$channel_liveX=0;
				echo "Cannot find empty vicidial_conference su $server_ip, Redirect comando non inserito\n|$stmt|";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "Impossibile trovare conferenze vuote su $server_ip";}
				}
			}

		if (strlen($call_server_ip)<7) {$call_server_ip = $server_ip;}

			$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$row=mysql_fetch_row($rslt);
			if ( ($row[0]==0) && (!ereg("SECOND",$filename)) )
			{
				$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$call_server_ip' and channel='$channel';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				$rowx=mysql_fetch_row($rslt);
				if ($rowx[0]==0)
				{
					$channel_liveX=0;
					echo "Canale $channel non e` attivo su $call_server_ip, Redirect comando non inserito\n";
					if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel non e` attivo su $call_server_ip";}
				}	
			}
			$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$extrachannel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$row=mysql_fetch_row($rslt);
			if ( ($row[0]==0) && (!ereg("SECOND",$filename)) )
			{
				$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$extrachannel';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				$rowx=mysql_fetch_row($rslt);
				if ($rowx[0]==0)
				{
					$channel_liveY=0;
					echo "Canale $channel non e` attivo su $server_ip, Redirect comando non inserito\n";
					if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel non e` attivo su $server_ip";}
				}	
			}
			if ( ($channel_liveX==1) && ($channel_liveY==1) )
			{
				if ( ($server_ip=="$call_server_ip") or (strlen($call_server_ip)<7) )
				{
					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $channel','ExtraChannel: $extrachannel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','CallerID: $queryCID','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_query($stmt, $link);

					echo "RedirectXtra comando inviato per Canale $channel and \nExtraCanale $extrachannel\n to $exten su $server_ip\n";
					if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel and $extrachannel to $exten su $server_ip";}
				}
				else
				{
					$S='*';
					$D_s_ip = explode('.', $server_ip);
					if (strlen($D_s_ip[0])<2) {$D_s_ip[0] = "0$D_s_ip[0]";}
					if (strlen($D_s_ip[0])<3) {$D_s_ip[0] = "0$D_s_ip[0]";}
					if (strlen($D_s_ip[1])<2) {$D_s_ip[1] = "0$D_s_ip[1]";}
					if (strlen($D_s_ip[1])<3) {$D_s_ip[1] = "0$D_s_ip[1]";}
					if (strlen($D_s_ip[2])<2) {$D_s_ip[2] = "0$D_s_ip[2]";}
					if (strlen($D_s_ip[2])<3) {$D_s_ip[2] = "0$D_s_ip[2]";}
					if (strlen($D_s_ip[3])<2) {$D_s_ip[3] = "0$D_s_ip[3]";}
					if (strlen($D_s_ip[3])<3) {$D_s_ip[3] = "0$D_s_ip[3]";}
					$dest_dialstring = "$D_s_ip[0]$S$D_s_ip[1]$S$D_s_ip[2]$S$D_s_ip[3]$S$exten";

					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$call_server_ip','','Redirect','$queryCID','Channel: $channel','Context: $ext_context','Exten: $dest_dialstring','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_query($stmt, $link);

					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $extrachannel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_query($stmt, $link);

					echo "RedirectXtra comando inviato per Canale $channel su $call_server_ip and \nExtraCanale $extrachannel\n to $exten su $server_ip\n";
					if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel/$call_server_ip and $extrachannel/$server_ip to $exten";}
				}
			}
			else
			{
				if ($channel_liveX==1)
				{$ACTION="Redirect";   $server_ip = $call_server_ip;}
				if ($channel_liveY==1)
				{$ACTION="Redirect";   $channel=$extrachannel;}

			}

		if (ereg("SECOND|FIRST|DEBUG",$filename))
			{
			if ($WeBRooTWritablE > 0)
				{
				$fp = fopen ("./vicidial_debug.txt", "a");
				fwrite ($fp, "$NOW_TIME|RDX|$filename|$user|$campaign|$DBout|\n");
				fclose($fp);
				}
			}

		}
	}
}







/*
if ($ACTION=="RedirectXtraCX")
{
	$DBout='';
	$row='';   $rowx='';
	$channel_liveX=1;
	$channel_liveY=1;
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($extrachannel)<3) )
	{
		$channel_liveX=0;
		$channel_liveY=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "ExtraCanale $extrachannel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "exten $exten deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "\nRedirect Action non inviato\n";
		if (ereg("SECOND|FIRST|DEBUG",$filename))
			{
			if ($WeBRooTWritablE > 0)
				{
				$fp = fopen ("./vicidial_debug.txt", "a");
				fwrite ($fp, "$NOW_TIME|RDCXC|$filename|$user|$campaign|$channel|$extrachannel|$queryCID|$exten|$ext_context|ext_priority|\n");
				fclose($fp);
				}
			}
	}
	else
	{
		if (strlen($call_server_ip)<7) {$call_server_ip = $server_ip;}

		$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$row=mysql_fetch_row($rslt);
		if ($row[0]==0)
		{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$call_server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			if ($rowx[0]==0)
			{
				$channel_liveX=0;
				echo "Canale $channel non e` attivo su $call_server_ip, Redirect comando non inserito\n";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel non e` attivo su $call_server_ip";}
			}	
		}
		$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$extrachannel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$row=mysql_fetch_row($rslt);
		if ($row[0]==0)
		{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$extrachannel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			if ($rowx[0]==0)
			{
				$channel_liveY=0;
				echo "Canale $channel non e` attivo su $server_ip, Redirect comando non inserito\n";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel non e` attivo su $server_ip";}
			}	
		}
		if ( ($channel_liveX==1) && ($channel_liveY==1) )
		{
			$stmt="SELECT count(*) FROM vicidial_live_agents where lead_id='$lead_id' and user!='$user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			if ($rowx[0] < 1)
			{
				$channel_liveY=0;
				echo "No Local agent to send call to, Redirect comando non inserito\n";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "No Local agent to send call to";}
			}	
			else
			{
				$stmt="SELECT server_ip,conf_exten,user FROM vicidial_live_agents where lead_id='$lead_id' and user!='$user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				$rowx=mysql_fetch_row($rslt);
				$dest_server_ip = $rowx[0];
				$dest_session_id = $rowx[1];
				$dest_user = $rowx[2];
				$S='*';

				$D_s_ip = explode('.', $dest_server_ip);
				if (strlen($D_s_ip[0])<2) {$D_s_ip[0] = "0$D_s_ip[0]";}
				if (strlen($D_s_ip[0])<3) {$D_s_ip[0] = "0$D_s_ip[0]";}
				if (strlen($D_s_ip[1])<2) {$D_s_ip[1] = "0$D_s_ip[1]";}
				if (strlen($D_s_ip[1])<3) {$D_s_ip[1] = "0$D_s_ip[1]";}
				if (strlen($D_s_ip[2])<2) {$D_s_ip[2] = "0$D_s_ip[2]";}
				if (strlen($D_s_ip[2])<3) {$D_s_ip[2] = "0$D_s_ip[2]";}
				if (strlen($D_s_ip[3])<2) {$D_s_ip[3] = "0$D_s_ip[3]";}
				if (strlen($D_s_ip[3])<3) {$D_s_ip[3] = "0$D_s_ip[3]";}
				$dest_dialstring = "$D_s_ip[0]$S$D_s_ip[1]$S$D_s_ip[2]$S$D_s_ip[3]$S$dest_session_id$S$lead_id$S$dest_user$S$phone_code$S$phone_number$S$campaign$S";

				$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$call_server_ip','','Redirect','$queryCID','Channel: $channel','Context: $ext_context','Exten: $dest_dialstring','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);

				$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Hangup','$queryCID','Channel: $extrachannel','','','','','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);

				echo "RedirectXtraCX comando inviato per Canale $channel su $call_server_ip and \nHungup $extrachannel su $server_ip\n";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel su $call_server_ip, Hungup $extrachannel su $server_ip";}
			}
		}
		else
		{
			if ($channel_liveX==1)
			{$ACTION="Redirect";   $server_ip = $call_server_ip;}
			if ($channel_liveY==1)
			{$ACTION="Redirect";   $channel=$extrachannel;}
			if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "Changed to Redirect: $channel su $server_ip";}
		}

	if (ereg("SECOND|FIRST|DEBUG",$filename))
		{
		if ($WeBRooTWritablE > 0)
			{
			$fp = fopen ("./vicidial_debug.txt", "a");
			fwrite ($fp, "$NOW_TIME|RDCXC|$filename|$user|$campaign|$DBout|\n");
			fclose($fp);
			}
		}

	}
}



###### BEGIN OLD LEAVE-3-WAY FOR EXTERNAL CALLS - DEPRICATED AND WILL BE DELETED SOON #####
if ($ACTION=="RedirectXtra")
{
	if ($channel=="$extrachannel")
	{$ACTION="Redirect";}
	else
	{
		$row='';   $rowx='';
		$channel_liveX=1;
		$channel_liveY=1;
		if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($extrachannel)<3) )
		{
			$channel_liveX=0;
			$channel_liveY=0;
			echo "Una di queste variabili non e` valido:\n";
			echo "Canale $channel deve avere piu' di 2 caratteri\n";
			echo "ExtraCanale $extrachannel deve avere piu' di 2 caratteri\n";
			echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
			echo "exten $exten deve essere impostato\n";
			echo "ext_context $ext_context deve essere impostato\n";
			echo "ext_priority $ext_priority deve essere impostato\n";
			echo "\nRedirect Action non inviato\n";
			if (ereg("SECOND|FIRST|DEBUG",$filename))
				{
				if ($WeBRooTWritablE > 0)
					{
					$fp = fopen ("./vicidial_debug.txt", "a");
					fwrite ($fp, "$NOW_TIME|RDX|$filename|$user|$campaign|$$channel|$extrachannel|$queryCID|$exten|$ext_context|ext_priority|\n");
					fclose($fp);
					}
				}
		}
		else
		{
		if (ereg("NEXTAVAILABLE",$exten))
			{
			$stmt="SELECT conf_exten FROM conferences where server_ip='$server_ip' and ((extension='') or (extension is null)) limit 1;";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$row=mysql_fetch_row($rslt);
				if (strlen($row[0]) > 3)
				{
				$stmt="UPDATE conferences set extension='$user' where server_ip='$server_ip' and conf_exten='$row[0]';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				$exten = $row[0];
				}
				else
				{
				$channel_liveX=0;
				echo "Impossibile trovare conferenze vuote su $server_ip, Redirect comando non inserito\n";
				if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "Impossibile trovare conferenze vuote su $server_ip";}
				}
			}

		if (strlen($call_server_ip)<7) {$call_server_ip = $server_ip;}

			$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$row=mysql_fetch_row($rslt);
			if ( ($row[0]==0) && (!ereg("SECOND",$filename)) )
			{
				$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$call_server_ip' and channel='$channel';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				$rowx=mysql_fetch_row($rslt);
				if ($rowx[0]==0)
				{
					$channel_liveX=0;
					echo "Canale $channel non e` attivo su $call_server_ip, Redirect comando non inserito\n";
					if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel non e` attivo su $call_server_ip";}
				}	
			}
			$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$extrachannel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$row=mysql_fetch_row($rslt);
			if ( ($row[0]==0) && (!ereg("SECOND",$filename)) )
			{
				$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$extrachannel';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				$rowx=mysql_fetch_row($rslt);
				if ($rowx[0]==0)
				{
					$channel_liveY=0;
					echo "Canale $channel non e` attivo su $server_ip, Redirect comando non inserito\n";
					if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel non e` attivo su $server_ip";}
				}	
			}
			if ( ($channel_liveX==1) && ($channel_liveY==1) )
			{
				if ( ($server_ip=="$call_server_ip") or (strlen($call_server_ip)<7) )
				{
					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $channel','ExtraChannel: $extrachannel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','CallerID: $queryCID','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_query($stmt, $link);

					echo "RedirectXtra comando inviato per Canale $channel and \nExtraCanale $extrachannel\n to $exten su $server_ip\n";
					if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel and $extrachannel to $exten su $server_ip";}
				}
				else
				{
					$S='*';
					$D_s_ip = explode('.', $server_ip);
					if (strlen($D_s_ip[0])<2) {$D_s_ip[0] = "0$D_s_ip[0]";}
					if (strlen($D_s_ip[0])<3) {$D_s_ip[0] = "0$D_s_ip[0]";}
					if (strlen($D_s_ip[1])<2) {$D_s_ip[1] = "0$D_s_ip[1]";}
					if (strlen($D_s_ip[1])<3) {$D_s_ip[1] = "0$D_s_ip[1]";}
					if (strlen($D_s_ip[2])<2) {$D_s_ip[2] = "0$D_s_ip[2]";}
					if (strlen($D_s_ip[2])<3) {$D_s_ip[2] = "0$D_s_ip[2]";}
					if (strlen($D_s_ip[3])<2) {$D_s_ip[3] = "0$D_s_ip[3]";}
					if (strlen($D_s_ip[3])<3) {$D_s_ip[3] = "0$D_s_ip[3]";}
					$dest_dialstring = "$D_s_ip[0]$S$D_s_ip[1]$S$D_s_ip[2]$S$D_s_ip[3]$S$exten";

					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$call_server_ip','','Redirect','$queryCID','Channel: $channel','Context: $ext_context','Exten: $dest_dialstring','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_query($stmt, $link);

					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $extrachannel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_query($stmt, $link);

					echo "RedirectXtra comando inviato per Canale $channel su $call_server_ip and \nExtraCanale $extrachannel\n to $exten su $server_ip\n";
					if (ereg("SECOND|FIRST|DEBUG",$filename)) {$DBout .= "$channel/$call_server_ip and $extrachannel/$server_ip to $exten";}
				}
			}
			else
			{
				if ($channel_liveX==1)
				{$ACTION="Redirect";   $server_ip = $call_server_ip;}
				if ($channel_liveY==1)
				{$ACTION="Redirect";   $channel=$extrachannel;}

			}

		if (ereg("SECOND|FIRST|DEBUG",$filename))
			{
			if ($WeBRooTWritablE > 0)
				{
				$fp = fopen ("./vicidial_debug.txt", "a");
				fwrite ($fp, "$NOW_TIME|RDX|$filename|$user|$campaign|$DBout|\n");
				fclose($fp);
				}
			}

		}
	}
}
###### END OLD LEAVE-3-WAY FOR EXTERNAL CALLS - DEPRICATED AND WILL BE DELETED SOON #####
*/

if ($ACTION=="Redirect")
{
	### for manual dial VICIDIAL calls send the second attempt to transfer the call
	if ($stage=="2NDXfeR")
	{
		$local_DEF = 'Local/';
		$local_AMP = '@';
		$hangup_channel_prefix = "$local_DEF$session_id$local_AMP$ext_context";

		$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$hangup_channel_prefix%\";";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$row=mysql_fetch_row($rslt);
		if ($row > 0)
		{
			$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$hangup_channel_prefix%\";";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			$channel=$rowx[0];
			$channel = eregi_replace("1$","2",$channel);
			$queryCID = eregi_replace("^.","Q",$queryCID);
		}
	}

	$row='';   $rowx='';
	$channel_live=1;
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($exten)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
	{
		$channel_live=0;
		echo "Una di queste variabili non e` valido:\n";
		echo "Canale $channel deve avere piu' di 2 caratteri\n";
		echo "queryCID $queryCID deve avere piu' di 14 caratteri\n";
		echo "exten $exten deve essere impostato\n";
		echo "ext_context $ext_context deve essere impostato\n";
		echo "ext_priority $ext_priority deve essere impostato\n";
		echo "\nRedirect Action non inviato\n";
	}
	else
	{
		if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
		$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$channel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$row=mysql_fetch_row($rslt);
		if ($row[0]==0)
		{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			if ($rowx[0]==0)
			{
				$channel_live=0;
				echo "Canale $channel non e` attivo su $server_ip, Redirect comando non inserito\n";
			}	
		}
		if ($channel_live==1)
		{
		$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $channel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);

		echo "Redirect comando inviato per Canale $channel su $server_ip\n";
		}
	}
}



######################
# ACTION=Monitor or Stop Monitor  - insert Monitor/StopMonitor Manager statement to start recording on a channel
######################
if ( ($ACTION=="Monitor") || ($ACTION=="StopMonitor") )
{
	if ($ACTION=="StopMonitor")
		{$SQLfile = "";}
	else
		{$SQLfile = "File: $filename";}

	$row='';   $rowx='';
	$channel_live=1;
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($filename)<15) )
	{
		$channel_live=0;
		echo "Canale $channel non e` valido or queryCID $queryCID non e` valido or filename: $filename non e` valido, $ACTION comando non inserito\n";
	}
	else
	{
		$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$channel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$row=mysql_fetch_row($rslt);
		if ($row[0]==0)
		{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$rowx=mysql_fetch_row($rslt);
			if ($rowx[0]==0)
			{
				$channel_live=0;
				echo "Canale $channel non e` attivo su $server_ip, $ACTION comando non inserito\n";
			}	
		}
		if ($channel_live==1)
		{
		$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','$ACTION','$queryCID','Channel: $channel','$SQLfile','','','','','','','','');";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);

		if ($ACTION=="Monitor")
			{
			$stmt = "INSERT INTO recording_log (channel,server_ip,extension,start_time,start_epoch,filename,lead_id,user) values('$channel','$server_ip','$exten','$NOW_TIME','$StarTtime','$filename','$lead_id','$user')";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);

			$stmt="SELECT recording_id FROM recording_log where filename='$filename'";
			$rslt=mysql_query($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$row=mysql_fetch_row($rslt);
			$recording_id = $row[0];
			}
		else
			{
			$stmt="SELECT recording_id,start_epoch FROM recording_log where filename='$filename'";
			$rslt=mysql_query($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$rec_count = mysql_num_rows($rslt);
				if ($rec_count>0)
				{
				$row=mysql_fetch_row($rslt);
				$recording_id = $row[0];
				$start_time = $row[1];
				$length_in_sec = ($StarTtime - $start_time);
				$length_in_min = ($length_in_sec / 60);
				$length_in_min = sprintf("%8.2f", $length_in_min);

				$stmt = "UPDATE recording_log set end_time='$NOW_TIME',end_epoch='$StarTtime',length_in_sec=$length_in_sec,length_in_min='$length_in_min' where filename='$filename'";
					if ($DB) {echo "$stmt\n";}
				$rslt=mysql_query($stmt, $link);
				}

			}
		echo "$ACTION comando inviato per Canale $channel su $server_ip\nFilename: $filename\nRecorDing_ID: $recording_id\n";
		}
	}
}






######################
# ACTION=MonitorConf or StopMonitorConf  - insert Monitor/StopMonitor Manager statement to start recording on a conference
######################
if ( ($ACTION=="MonitorConf") || ($ACTION=="StopMonitorConf") )
{
	$row='';   $rowx='';
	$channel_live=1;
	$uniqueidSQL='';

	if ( (strlen($exten)<3) or (strlen($channel)<4) or (strlen($filename)<15) )
	{
		$channel_live=0;
		echo "Canale $channel non e` valido or exten $exten non e` valido or filename: $filename non e` valido, $ACTION comando non inserito\n";
	}
	else
	{

	$VDvicidial_id='';

	if ($ACTION=="MonitorConf")
		{
		$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$filename','Channel: $channel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','Callerid: $filename','','','','','');";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);

		$stmt = "INSERT INTO recording_log (channel,server_ip,extension,start_time,start_epoch,filename,lead_id,user) values('$channel','$server_ip','$exten','$NOW_TIME','$StarTtime','$filename','$lead_id','$user')";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
		$RLaffected_rows = mysql_affected_rows($link);
		if ($RLaffected_rows > 0)
			{
			$recording_id = mysql_insert_id($link);
			}

		if ($FROMvdc=='YES')
			{
			##### get call type from vicidial_live_agents table
			$VLA_inOUT='NONE';
			$stmt="SELECT comments FROM vicidial_live_agents where user='$user' order by last_update_time desc limit 1;";
			$rslt=mysql_query($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$VLA_inOUT_ct = mysql_num_rows($rslt);
			if ($VLA_inOUT_ct > 0)
				{
				$row=mysql_fetch_row($rslt);
				$VLA_inOUT =		$row[0];
				}
			if ($VLA_inOUT == 'INBOUND')
				{
				$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));

				##### look for the vicidial ID in the vicidial_closer_log table
				$stmt="SELECT closecallid FROM vicidial_closer_log where lead_id='$lead_id' and user='$user' and call_date > \"$four_hours_ago\" order by closecallid desc limit 1;";
				}
			else
				{
				##### look for the vicidial ID in the vicidial_log table
				$stmt="SELECT uniqueid FROM vicidial_log where uniqueid='$uniqueid' and lead_id='$lead_id';";
				}
			$rslt=mysql_query($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$VM_mancall_ct = mysql_num_rows($rslt);
			if ($VM_mancall_ct > 0)
				{
				$row=mysql_fetch_row($rslt);
				$VDvicidial_id =	$row[0];

				$stmt = "UPDATE recording_log SET vicidial_id='$VDvicidial_id' where recording_id='$recording_id';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_query($stmt, $link);
				}
			}
		}

	##### StopMonitorConf steps #####
	else
		{
		if ($uniqueid=='IN')
			{
			$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));

			### find the value to put in the vicidial_id field if this was an inbound call
			$stmt="SELECT closecallid from vicidial_closer_log where lead_id='$lead_id' and call_date > \"$four_hours_ago\" order by call_date desc limit 1;";
			$rslt=mysql_query($stmt, $link);
			$VAC_qm_ct = mysql_num_rows($rslt);
			if ($VAC_qm_ct > 0)
				{
				$row=mysql_fetch_row($rslt);
				$uniqueidSQL	= ",vicidial_id='$row[0]'";
				}
			}
		else
			{
			if (strlen($uniqueid) > 8)
				{$uniqueidSQL	= ",vicidial_id='$uniqueid'";}
			}
		
		$stmt="SELECT recording_id,start_epoch FROM recording_log where filename='$filename'";
		$rslt=mysql_query($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$rec_count = mysql_num_rows($rslt);
			if ($rec_count>0)
			{
			$row=mysql_fetch_row($rslt);
			$recording_id = $row[0];
			$start_time = $row[1];
			$length_in_sec = ($StarTtime - $start_time);
			$length_in_min = ($length_in_sec / 60);
			$length_in_min = sprintf("%8.2f", $length_in_min);

			$stmt = "UPDATE recording_log set end_time='$NOW_TIME',end_epoch='$StarTtime',length_in_sec=$length_in_sec,length_in_min='$length_in_min' $uniqueidSQL where filename='$filename'";
				if ($DB) {echo "$stmt\n";}
			$rslt=mysql_query($stmt, $link);
			}

		# find and hang up all recordings going su in this conference # and extension = '$exten' 
		$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$channel%\" and channel LIKE \"%,1\";";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_query($stmt, $link);
	#	$rec_count = intval(mysql_num_rows($rslt) / 2);
		$rec_count = mysql_num_rows($rslt);
		$h=0;
			while ($rec_count>$h)
			{
			$rowx=mysql_fetch_row($rslt);
			$HUchannel[$h] = $rowx[0];
			$h++;
			}
		$i=0;
			while ($h>$i)
			{
			$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Hangup','RH12345$StarTtime$i','Channel: $HUchannel[$i]','','','','','','','','','');";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_query($stmt, $link);
			$i++;
			}

		}
		echo "$ACTION comando inviato per Canale $channel su $server_ip\nFilename: $filename\nRecorDing_ID: $recording_id\n LA REGISTRAZIONE DURERA` FINO A 60 MINUTI\n";
	}
}





######################
# ACTION=VolumeControl  - raise or lower the volume of a meetme participant
######################
if ($ACTION=="VolumeControl")
{
	if ( (strlen($exten)<1) or (strlen($channel)<1) or (strlen($stage)<1) or (strlen($queryCID)<1) )
	{
		echo "Conferenza $exten, Stage $stage non e` valido or queryCID $queryCID non e` valido, Originate comando non inserito\n";
	}
	else
	{
	$participant_number='XXYYXXYYXXYYXX';
	if (eregi('UP',$stage)) {$vol_prefix='4';}
	if (eregi('DOWN',$stage)) {$vol_prefix='3';}
	if (eregi('UNMUTE',$stage)) {$vol_prefix='2';}
	if (eregi('MUTING',$stage)) {$vol_prefix='1';}
	$local_DEF = 'Local/';
	$local_AMP = '@';
	$volume_local_channel = "$local_DEF$participant_number$vol_prefix$exten$local_AMP$ext_context";

	$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$queryCID','Channel: $volume_local_channel','Context: $ext_context','Exten: 8300','Priority: 1','Callerid: $queryCID','','','','$channel','$exten');";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_query($stmt, $link);
	echo "Volume comando inviato per Conferenza $exten, Stage $stage Canale $channel su $server_ip\n";
	}
}












$ENDtime = date("U");
$RUNtime = ($ENDtime - $StarTtime);
if ($format=='debug') {echo "\n<!-- script runtime: $RUNtime secondi -->";}
if ($format=='debug') {echo "\n</body>\n</html>\n";}
	
exit; 

?>





