<?php 
# AST_agent_days_detail.php
# 
# Copyright (C) 2013  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 90206-2202 - First build
# 90225-1051 - Added CSV download option
# 90310-0752 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation and allowed campaigns restrictions
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 111104-1302 - Added user_group restrictions for selecting in-groups
# 120224-0910 - Added HTML display option with bar graphs
# 130414-0143 - Added report logging
#

$startMS = microtime();

require("dbconnect.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))				{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Single Agent Daily';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db FROM system_settings;";
$rslt=mysql_query($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysql_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysql_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	}
##### END SETTINGS LOOKUP #####
###########################################

$PHP_AUTH_USER = ereg_replace("[^0-9a-zA-Z]","",$PHP_AUTH_USER);
$PHP_AUTH_PW = ereg_replace("[^0-9a-zA-Z]","",$PHP_AUTH_PW);

$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and pass='$PHP_AUTH_PW' and user_level > 6 and view_reports='1' and active='Y';";
if ($DB) {echo "|$stmt|\n";}
if ($non_latin > 0) { $rslt=mysql_query("SET NAMES 'UTF8'");}
$rslt=mysql_query($stmt, $link);
$row=mysql_fetch_row($rslt);
$auth=$row[0];

$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and pass='$PHP_AUTH_PW' and user_level='7' and view_reports='1' and active='Y';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_query($stmt, $link);
$row=mysql_fetch_row($rslt);
$reports_only_user=$row[0];

if( (strlen($PHP_AUTH_USER)<2) or (strlen($PHP_AUTH_PW)<2) or (!$auth))
	{
    Header("WWW-Authenticate: Basic realm=\"VICI-PROJECTS\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo "Unzulässiges Username/Kennwort:|$PHP_AUTH_USER|$PHP_AUTH_PW|\n";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group[0], $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_query($stmt, $link);
$report_log_id = mysql_insert_id($link);
##### END log visit to the vicidial_report_log table #####

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysql_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect.php");
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER' and pass='$PHP_AUTH_PW' and user_level > 6 and view_reports='1' and active='Y';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_query($stmt, $link);
$row=mysql_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_query($stmt, $link);
$row=mysql_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"VICI-PROJECTS\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo "Sie sind nicht berechtigt, diesen Bericht zu sehen: |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!eregi("-ALL",$LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

$LOGadmin_viewable_groupsSQL='';
$vuLOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!eregi("--ALL--",$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vuLOGadmin_viewable_groupsSQL = "and vicidial_users.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}

$LOGadmin_viewable_call_timesSQL='';
$whereLOGadmin_viewable_call_timesSQL='';
if ( (!eregi("--ALL--",$LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
	{
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
	$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	}

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = '';}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$i++;
	}

$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_query($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysql_num_rows($rslt);
$i=0;
while ($i < $campaigns_to_print)
	{
	$row=mysql_fetch_row($rslt);
	$groups[$i] =$row[0];
	if (ereg("-ALL",$group_string) )
		{$group[$i] = $groups[$i];}
	$i++;
	}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	if ( (preg_match("/ $group[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
		{
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$groupQS .= "&group[]=$group[$i]";
		}
	$i++;
	}
if ( (ereg("--ALL--",$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	$group_SQL = eregi_replace(",$",'',$group_SQL);
	$group_SQL = "and campaign_id IN($group_SQL)";
	}

$customer_interactive_statuses='';
$stmt="select status from vicidial_statuses where human_answered='Y';";
$rslt=mysql_query($stmt, $link);
if ($DB) {echo "$stmt\n";}
$statha_to_print = mysql_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysql_fetch_row($rslt);
	$customer_interactive_statuses .= "|$row[0]";
	$i++;
	}
$stmt="select status from vicidial_campaign_statuses where human_answered='Y';";
$rslt=mysql_query($stmt, $link);
if ($DB) {echo "$stmt\n";}
$statha_to_print = mysql_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysql_fetch_row($rslt);
	$customer_interactive_statuses .= "|$row[0]";
	$i++;
	}
if (strlen($customer_interactive_statuses)>0)
	{$customer_interactive_statuses .= '|';}

#$customer_interactive_statuses = '|NI|DNC|CALLBK|AP|SALE|COMP|HAP1|HAP2|HBED|DIED|';
#$customer_interactive_statuses = '|NI|DNC|CALLBK|XFER|C2|B7|B8|C1|';

$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date&shift=$shift&DB=$DB&user=$user$groupQS";

if ($file_download < 1)
	{
	?>

	<HTML>
	<HEAD>
	<STYLE type="text/css">
	<!--
	   .yellow {color: white; background-color: yellow}
	   .red {color: white; background-color: red}
	   .blue {color: white; background-color: blue}
	   .purple {color: white; background-color: purple}
	-->
	 </STYLE>

	<?php

	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>Single Agent Daily</TITLE></HEAD><BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<span style=\"position:absolute;left:0px;top:0px;z-index:20;\"  id=admin_header>";

	$short_header=1;

	require("admin_header.php");

	echo "</span>\n";
	$ASCII_text.="<span style=\"position:absolute;left:3px;top:3px;z-index:19;\"  id=agent_status_stats>\n";
	$ASCII_text.="<PRE><FONT SIZE=2>\n";
	$GRAPH_text.="<PRE><FONT SIZE=2>\n";
	}

if (strlen($group[0]) < 1)
	{
	echo "\n";
	echo "Bitte wählen Sie einen Benutzernamen und Datum-Zeit oben ein und klicken dann SUBMIT\n";
	echo " NOTE: stats taken from shift specified\n";
	}

else
	{
	if ($shift == 'AM') 
		{
		$time_BEGIN=$AM_shift_BEGIN;
		$time_END=$AM_shift_END;
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "03:45:00";}   
		if (strlen($time_END) < 6) {$time_END = "15:15:00";}
		}
	if ($shift == 'PM') 
		{
		$time_BEGIN=$PM_shift_BEGIN;
		$time_END=$PM_shift_END;
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "15:15:00";}
		if (strlen($time_END) < 6) {$time_END = "23:15:00";}
		}
	if ($shift == 'ALL') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}
		if (strlen($time_END) < 6) {$time_END = "23:59:59";}
		}
	$query_date_BEGIN = "$query_date $time_BEGIN";   
	$query_date_END = "$end_date $time_END";

	if ($file_download < 1)
		{
		$ASCII_text.="Agent Tage Status Report: $user                     $NOW_TIME\n";
		$ASCII_text.="Time range: $query_date_BEGIN to $query_date_END\n\n";
		$ASCII_text.="---------- MITTELDetails -------------\n\n";
		$GRAPH_text.="Agent Tage Status Report: $user                     $NOW_TIME\n";
		$GRAPH_text.="Time range: $query_date_BEGIN to $query_date_END\n\n";
		$GRAPH_text.="---------- MITTELDetails -------------\n";
		}
	else
		{
		$file_output .= "Agent Tage Status Report: $user                     $NOW_TIME\n";
		$file_output .= "Time range: $query_date_BEGIN to $query_date_END\n\n";
		}

	$statuses='-';
	$statusesTXT='';
	$statusesHEAD='';
	$statusesHTML='';
	$statusesFILE='';
	$statusesARY[0]='';
	$j=0;
	$dates='-';
	$datesARY[0]='';
	$date_namesARY[0]='';
	$k=0;

	$stmt="select date_format(event_time, '%Y-%m-%d') as date,count(*) as calls,status from vicidial_users,vicidial_agent_log where event_time <= '$query_date_END' and event_time >= '$query_date_BEGIN' and vicidial_users.user=vicidial_agent_log.user and vicidial_agent_log.user='$user' $group_SQL $user_group_SQL $vuLOGadmin_viewable_groupsSQL group by date,status order by date,status desc limit 500000;";
	$rslt=mysql_query($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysql_num_rows($rslt);
	$i=0;
	while ($i < $rows_to_print)
		{
		$row=mysql_fetch_row($rslt);

		if ( ($row[1] > 0) and (strlen($row[2]) > 0) )
			{
			$date[$i] =			$row[0];
			$calls[$i] =		$row[1];
			$status[$i] =		$row[2];
			if ( (!eregi("-$status[$i]-", $statuses)) and (strlen($status[$i])>0) )
				{
				$statusesTXT = sprintf("%8s", $status[$i]);
				$statusesHEAD .= "----------+";
				$statusesHTML .= " $statusesTXT |";
				$statusesFILE .= "$statusesTXT,";
				$statuses .= "$status[$i]-";
				$statusesARY[$j] = $status[$i];
				$j++;
				}
			if (!eregi("-$date[$i]-", $dates))
				{
				$dates .= "$date[$i]-";
				$datesARY[$k] = $date[$i];
				$k++;
				}
			}
		$i++;
		}

	if ($file_download < 1)
		{
		$ASCII_text.="LEAD STATS BREAKDOWN:\n";
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";
		$ASCII_text.="| <a href=\"$LINKbase\">DATE</a>       | <a href=\"$LINKbase&stage=LEADS\">CALLS</a>  | <a href=\"$LINKbase&stage=CI\">CIcalls</a>| <a href=\"$LINKbase&stage=DNCCI\">DNC/CI%</a>|$statusesHTML\n";
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";
		for ($i=0; $i<count($statusesARY); $i++) {
			$Sstatus=$statusesARY[$i];
			$SstatusTXT=$Sstatus;
			if ($Sstatus=="") {$SstatusTXT="(blank)";}
			$GRAPH2.="<th class='column_header grey_graph_cell' id='callgraph".($i+4)."'><a href='#' onClick=\"DrawGraph('$Sstatus', '".($i+4)."'); return false;\">$SstatusTXT</a></th>";
		}

		}
	else
		{
		$file_output .= "DATE,CALLS,CIcalls,DNC-CI%,$statusesFILE\n";
		}

	### BEGIN loop through each user ###
	$m=0;
	$CIScountTOT=0;
	$DNCcountTOT=0;

	$graph_stats=array();
	$max_calls=1;
	$max_cicalls=1;
	$max_dncci=1;
	$GRAPH="<BR><BR><a name='callgraph'/><table border='0' cellpadding='0' cellspacing='2' width='800'>";
	$GRAPH2="<tr><th class='column_header grey_graph_cell' id='callgraph1'><a href='#' onClick=\"DrawGraph('CALLS', '1'); return false;\">CALLS</a></th><th class='column_header grey_graph_cell' id='callgraph2'><a href='#' onClick=\"DrawGraph('CICALLS', '2'); return false;\">CI/CALLS</a></th><th class='column_header grey_graph_cell' id='callgraph3'><a href='#' onClick=\"DrawGraph('DNCCI', '3'); return false;\">DNC/CI</a></th>";
	$graph_header="<table cellspacing='0' cellpadding='0' class='horizontalgraph'><caption align='top'>LEAD STATS BREAKDOWN</caption><tr><th class='thgraph' scope='col'>STATUS</th>";
	$CALLS_graph=$graph_header."<th class='thgraph' scope='col'>CALLS </th></tr>";
	$CICALLS_graph=$graph_header."<th class='thgraph' scope='col'>CI CALLS</th></tr>";
	$DNCCI_graph=$graph_header."<th class='thgraph' scope='col'>DNC/CI%</th></tr>";

	while ($m < $k)
		{
		$Sdate=$datesARY[$m];
		$Scalls=$calls[$m];
		$SstatusesHTML='';
		$SstatusesFILE='';
		$CIScount=0;
		$DNCcount=0;

		### BEGIN loop through each status ###
		$n=0;
		while ($n < $j)
			{
			$Sstatus=$statusesARY[$n];
			$SstatusTXT='';
			$varname=$Sstatus."_graph";
			$$varname=$graph_header."<th class='thgraph' scope='col'>$Sstatus</th></tr>";
			$max_varname="max_".$Sstatus;
			$graph_stats[$m][(4+$n)]=0;
			### BEGIN loop through each stat line ###
			$i=0; $status_found=0;
			while ($i < $rows_to_print)
				{
	#			if ( (eregi("$date[$i]", $Sdate)) and ($Sstatus=="$status[$i]") )
				if ( ($Sdate=="$date[$i]") and ($Sstatus=="$status[$i]") )
					{
					$Scalls =		($Scalls + $calls[$i]);
					if (eregi("\|$status[$i]\|",$customer_interactive_statuses))
						{
						$CIScount =	($CIScount + $calls[$i]);
						$CIScountTOT =	($CIScountTOT + $calls[$i]);
						}
					if (eregi("DNC", $status[$i]))
						{
						$DNCcount =	($DNCcount + $calls[$i]);
						$DNCcountTOT =	($DNCcountTOT + $calls[$i]);
						}

					if ($calls[$i]>$$max_varname) {$$max_varname=$calls[$i];}
					$graph_stats[$m][(4+$n)]=$calls[$i];					

					$SstatusTXT = sprintf("%8s", $calls[$i]);
					$SstatusesHTML .= " $SstatusTXT |";
					$SstatusesFILE .= "$SstatusTXT,";
					$status_found++;
					}
				$i++;
				}
			if ($status_found < 1)
				{
				$SstatusesHTML .= "        0 |";
				}
			### END loop through each stat line ###
			$n++;
			}
		### END loop through each status ###
		$TOTcalls=($TOTcalls + $Scalls);

		$RAWdate = $Sdate;
		$RAWcalls = $Scalls;
		$RAWcis = $CIScount;
		$Scalls =	sprintf("%6s", $Scalls);
		$CIScount =	sprintf("%6s", $CIScount);

		$Sdate =		sprintf("%-10s", $Sdate);
			while(strlen($Suser)>10) {$Suser = substr("$Sdate", 0, -1);}

		if ( ($DNCcount < 1) or ($CIScount < 1) )
			{$DNCcountPCTs=0;}
		else
			{
			$DNCcountPCTs = ( ($DNCcount / $CIScount) * 100);
			}
		$RAWdncPCT = $DNCcountPCTs;
	#	$DNCcountPCTs = round($DNCcountPCTs,2);
		$DNCcountPCTs = round($DNCcountPCTs);
		$rawDNCcountPCTs = $DNCcountPCTs;
	#	$DNCcountPCTs = sprintf("%3.2f", $DNCcountPCTs);
		$DNCcountPCTs = sprintf("%6s", $DNCcountPCTs);

		if (trim($Scalls)>$max_calls) {$max_calls=trim($Scalls);}
		if (trim($CIScount)>$max_cicalls) {$max_cicalls=trim($CIScount);}
		if (trim($DNCcountPCTs)>$max_dncci) {$max_dncci=trim($DNCcountPCTs);}
		$graph_stats[$m][1]=trim("$Scalls");
		$graph_stats[$m][2]=trim("$CIScount");
		$graph_stats[$m][3]=trim("$DNCcountPCTs");

		if ($file_download < 1)
			{
			$Toutput = "| <a href=\"./user_stats.php?user=$user&start_date=$RAWdate\">$Sdate</a> | $Scalls | $CIScount | $DNCcountPCTs%|$SstatusesHTML\n";
			$graph_stats[$m][0]=trim("$Sdate");
			}
		else
			{
			$fileToutput = "$RAWdate,$RAWcalls,$RAWcis,$rawDNCcountPCTs%,$SstatusesFILE\n";
			}

		$TOPsorted_output[$m] = $Toutput;
		$TOPsorted_outputFILE[$m] = $fileToutput;

		if ($stage == 'ID')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWdate) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$RAWcalls;
			}
		if ($stage == 'LEADS')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWcalls) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$RAWcalls;
			}
		if ($stage == 'TIME')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $Stime) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$Stime;
			}
		if ($stage == 'CI')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWcis) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$RAWcis;
			}
		if ($stage == 'DNCCI')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWdncPCT) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$RAWdncPCT;
			}
		if (!ereg("ID|TIME|LEADS|CI|DNCCI",$stage))
			{
			if ($file_download < 1)
				{$ASCII_text.="$Toutput";}
			else
				{$file_output .= "$fileToutput";}
			}

		if ($TOPsortMAX < $TOPsortTALLY[$m]) {$TOPsortMAX = $TOPsortTALLY[$m];}

		$m++;
		}
	### END loop through each user ###

	$TOT_AGENTS = sprintf("%4s", $m);


	### BEGIN sort through output to display properly ###
	if (ereg("ID|TIME|LEADS|CI|DNCCI",$stage))
		{
		if (ereg("ID",$stage))
			{sort($TOPsort, SORT_NUMERIC);}
		if (ereg("TIME|LEADS|CI|DNCCI",$stage))
			{rsort($TOPsort, SORT_NUMERIC);}

		$m=0;
		while ($m < $k)
			{
			$sort_split = explode("-----",$TOPsort[$m]);
			$i = $sort_split[1];
			$sort_order[$m] = "$i";
			if ($file_download < 1)
				{$ASCII_text.="$TOPsorted_output[$i]";}
			else
				{$file_output .= "$TOPsorted_outputFILE[$i]";}
			$m++;
			}
		}
	### END sort through output to display properly ###



	###### LAST LINE FORMATTING ##########
	### BEGIN loop through each status ###
	$SUMstatusesHTML='';
	$n=0;
	while ($n < $j)
		{
		$Scalls=0;
		$Sstatus=$statusesARY[$n];
		$SUMstatusTXT='';
		$total_var=$Sstatus."_total";
		### BEGIN loop through each stat line ###
		$i=0; $status_found=0;
		while ($i < $rows_to_print)
			{
			if ($Sstatus=="$status[$i]")
				{
				$Scalls =		($Scalls + $calls[$i]);
				$status_found++;
				}
			$i++;
			}
		### END loop through each stat line ###
		if ($status_found < 1)
			{
			$SUMstatusesHTML .= "        0 |";
			$$total_var=0;
			}
		else
			{
			$SUMstatusTXT = sprintf("%8s", $Scalls);
			$SUMstatusesHTML .= " $SUMstatusTXT |";
			$SUMstatusesFILE .= "$SUMstatusTXT,";
			$$total_var=$Scalls;
			}
		$n++;
		}
	### END loop through each status ###

	$TOTcalls = sprintf("%7s", $TOTcalls);
	$CIScountTOT = sprintf("%7s", $CIScountTOT);
	if ( ($DNCcountTOT < 1) or ($CIScountTOT < 1) )
		{$DNCcountPCT=0;}
	else
		{
		$DNCcountPCT = ( ($DNCcountTOT / $CIScountTOT) * 100);
		}
	#$DNCcountPCT = round($DNCcountPCT,2);
	$DNCcountPCT = round($DNCcountPCT);
	#$DNCcountPCT = sprintf("%3.2f", $DNCcountPCT);
	$DNCcountPCT = sprintf("%6s", $DNCcountPCT);


	if ($file_download < 1)
		{
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";
		$ASCII_text.="| TOTALS     | $TOTcalls| $CIScountTOT| $DNCcountPCT%|$SUMstatusesHTML\n";
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";

		$ASCII_text.="\n\n</PRE>";


		for ($e=0; $e<count($statusesARY); $e++) {
			$Sstatus=$statusesARY[$e];
			$SstatusTXT=$Sstatus;
			if ($Sstatus=="") {$SstatusTXT="(blank)";}
			$GRAPH2.="<th class='column_header grey_graph_cell' id='callgraph".($e+4)."'><a href='#' onClick=\"DrawGraph('$Sstatus', '".($e+4)."'); return false;\">$SstatusTXT</a></th>";
		}
		
		for ($d=0; $d<count($graph_stats); $d++) {
			if ($d==0) {$class=" first";} else if (($d+1)==count($graph_stats)) {$class=" last";} else {$class="";}
			$CALLS_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='../vicidial/images/bar.png' alt='' width='".round(400*$graph_stats[$d][1]/$max_calls)."' height='16' />".$graph_stats[$d][1]."</td></tr>";
			$CICALLS_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='../vicidial/images/bar.png' alt='' width='".round(400*$graph_stats[$d][2]/$max_cicalls)."' height='16' />".$graph_stats[$d][2]."</td></tr>";
			$DNCCI_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='../vicidial/images/bar.png' alt='' width='".round(400*$graph_stats[$d][3]/$max_dncci)."' height='16' />".$graph_stats[$d][3]."%</td></tr>";

			for ($e=0; $e<count($statusesARY); $e++) {
				$Sstatus=$statusesARY[$e];
				$varname=$Sstatus."_graph";
				$max_varname="max_".$Sstatus;
			
				$$varname.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='../vicidial/images/bar.png' alt='' width='".round(400*$graph_stats[$d][($e+4)]/$$max_varname)."' height='16' />".$graph_stats[$d][($e+4)]."</td></tr>";
			}
		}
		
		$CALLS_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($TOTcalls)."</th></tr></table>";
		$CICALLS_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($CIScountTOT)."</th></tr></table>";
		$DNCCI_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($DNCcountPCT)."%</th></tr></table>";
		for ($e=0; $e<count($statusesARY); $e++) {
			$Sstatus=$statusesARY[$e];
			$total_var=$Sstatus."_total";
			$graph_var=$Sstatus."_graph";
			$$graph_var.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($$total_var)."</th></tr></table>";
		}
		$JS_onload.="\tDrawGraph('CALLS', '1');\n"; 
		$JS_text.="function DrawGraph(graph, th_id) {\n";
		$JS_text.="	var CALLS_graph=\"$CALLS_graph\";\n";
		$JS_text.="	var CICALLS_graph=\"$CICALLS_graph\";\n";
		$JS_text.="	var DNCCI_graph=\"$DNCCI_graph\";\n";

		for ($e=0; $e<count($statusesARY); $e++) {
			$Sstatus=$statusesARY[$e];
			$graph_var=$Sstatus."_graph";
			$JS_text.="	var ".$Sstatus."_graph=\"".$$graph_var."\";\n";
		}

		$JS_text.="\n";
		$JS_text.="	for (var i=1; i<=".(3+count($statusesARY))."; i++) {\n";
		$JS_text.="		var cellID=\"callgraph\"+i;\n";
		$JS_text.="		document.getElementById(cellID).style.backgroundColor='#DDDDDD';\n";
		$JS_text.="	}\n";
		$JS_text.="	var cellID=\"callgraph\"+th_id;\n";
		$JS_text.="	document.getElementById(cellID).style.backgroundColor='#999999';\n";
		$JS_text.="	var graph_to_display=eval(graph+\"_graph\");\n";
		$JS_text.="	document.getElementById('agent_time_detail_graph').innerHTML=graph_to_display;\n";
		$JS_text.="}\n";

		$GRAPH3="<tr><td colspan='".(3+count($statusesARY))."' class='graph_span_cell'><span id='agent_time_detail_graph'><BR>&nbsp;<BR></span></td></tr></table><BR><BR>";
		
		$GRAPH_text.=$GRAPH.$GRAPH2.$GRAPH3;

		}
	else
		{
		$file_output .= "TOTALS,$TOTcalls,$CIScountTOT,$DNCcountPCT%,$SUMstatusesFILE\n";
		}
	}


if ($file_download > 0)
	{
	$US='_';
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AGENT_DAYS_$user$US$FILE_TIME.csv";

	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');

	// It will be called LIST_101_20090209-121212.txt
	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$file_output";

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

	exit;
	}

$JS_onload.="}\n";
$JS_text.=$JS_onload;
$JS_text.="</script>\n";

if ($report_display_type=="HTML")
	{
	echo $JS_text;
	echo $GRAPH_text;
	}
else
	{
	echo $ASCII_text;
	}

echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
echo "<TABLE CELLSPACING=3><TR><TD VALIGN=TOP> Termine:<BR>";
echo "<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

?>
<script language="JavaScript">
var o_cal = new tcal ({
	// form name
	'formname': 'vicidial_report',
	// input name
	'controlname': 'query_date'
});
o_cal.a_tpl.yearscroll = false;
// o_cal.a_tpl.weekstart = 1; // Montag week start
</script>
<?php

echo "<BR> to <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

?>
<script language="JavaScript">
var o_cal = new tcal ({
	// form name
	'formname': 'vicidial_report',
	// input name
	'controlname': 'end_date'
});
o_cal.a_tpl.yearscroll = false;
// o_cal.a_tpl.weekstart = 1; // Montag week start
</script>
<?php

echo "</TD><TD VALIGN=TOP> Kampagnen:<BR>";
echo "<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (eregi("--ALL--",$group_string))
	{echo "<option value=\"--ALL--\" selected>-- ALL KAMPAGNEN --</option>\n";}
else
	{echo "<option value=\"--ALL--\">-- ALL KAMPAGNEN --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
{
	if (eregi("$groups[$o]\|",$group_string)) {echo "<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	  else {echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
}
echo "</SELECT>\n";
echo "</TD><TD VALIGN=TOP>";
echo "Anzeige as:&nbsp;&nbsp;&nbsp;<BR>";
echo "<select name='report_display_type'>";
if ($report_display_type) {echo "<option value='$report_display_type' selected>$report_display_type</option>";}
echo "<option value='TEXT'>TEXT</option><option value='HTML'>HTML</option></select>\n<BR><BR>";
echo "</TD><TD VALIGN=TOP>User:<BR>";
echo "<INPUT TYPE=TEXT SIZE=10 NAME=user value=\"$user\">\n";
echo "</TD><TD VALIGN=TOP>Shift:<BR>";
echo "<SELECT SIZE=1 NAME=shift>\n";
echo "<option selected value=\"$shift\">$shift</option>\n";
echo "<option value=\"\">--</option>\n";
echo "<option value=\"AM\">AM</option>\n";
echo "<option value=\"PM\">PM</option>\n";
echo "<option value=\"ALL\">ALL</option>\n";
echo "</SELECT><BR><BR>\n";
echo "<INPUT TYPE=Submit NAME=SUBMIT VALUE=SUBMIT>\n";
echo "</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
if (strlen($user) > 1)
	{
	echo " <a href=\"$LINKbase&stage=$stage&file_download=1\">DOWNLOAD</a> | \n";
	echo " <a href=\"./admin.php?ADD=3&user=$user\">USER</a> | \n";
	echo " <a href=\"./user_stats.php?user=$user&begin_date=$query_date&end_date=$end_date\">USER STATS</a> | \n";
	}
else
	{echo " <a href=\"./admin.php?ADD=0\">BENUTZER</a> | \n";}
echo "<a href=\"./admin.php?ADD=999999\">REPORTS</a> </FONT>\n";
echo "</TD></TR></TABLE>";

echo "</FORM>\n\n<BR>$db_source";

echo "</span>\n";

if ($report_display_type=="TEXT" || !$report_display_type) 
	{
	echo "<span style=\"position:absolute;left:3px;top:3px;z-index:18;\"  id=agent_status_bars>\n";
	echo "<PRE><FONT SIZE=2>\n\n\n\n\n\n\n\n\n\n";

	$m=0;
	while ($m < $k)
		{
		$sort_split = explode("-----",$TOPsort[$m]);
		$i = $sort_split[1];
		$sort_order[$m] = "$i";

		if ( ($TOPsortTALLY[$i] < 1) or ($TOPsortMAX < 1) )
			{echo "              \n";}
		else
			{
			echo "              <SPAN class=\"yellow\">";
			$TOPsortPLOT = ( ($TOPsortTALLY[$i] / $TOPsortMAX) * 120 );
			$h=0;
			while ($h <= $TOPsortPLOT)
				{
				echo " ";
				$h++;
				}
			echo "</SPAN>\n";
			}
		$m++;
		}

	echo "</span>\n";
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

</BODY></HTML>
