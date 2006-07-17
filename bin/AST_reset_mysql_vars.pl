#!/usr/bin/perl
#
# AST_reset_mysql_vars.pl version 0.3   *DBI-version*
#
#  !!! DO NOT RUN THIS WHILE THERE ARE ACTIVE CALLS ON THE ASTERISK SERVER !!!
#
# DESCRIPTION:
# clears out mysql records for this server
#
# It is recommended that you run this program on the local Asterisk machine
#
# Copyright (C) 2006  Matt Florell <vicidial@gmail.com>    LICENSE: GPLv2
#
# CHANGES
# 60717-1237 - changed to DBI by Marin Blu
#

### Make sure this file is in a libs path or put the absolute path to it
require("/home/cron/AST_SERVER_conf.pl");	# local configuration file

if (!$DB_port) {$DB_port='3306';}

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$DB_database:$DB_server:$DB_port", "$DB_user", "$DB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


	$stmtA = "UPDATE conferences set extension='' where server_ip='$server_ip';";
		if($DB){print STDERR "\n|$stmtA|\n";}
	$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query: |$stmtA|\n";
	print " - conferences reset\n";

	$stmtA = "UPDATE vicidial_conferences set extension='' where server_ip='$server_ip';";
		if($DB){print STDERR "\n|$stmtA|\n";}
	$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query: |$stmtA|\n";
	print " - vicidial_conferences reset\n";

#	$stmtA = "UPDATE vicidial_manager set status='DEAD' where server_ip='$server_ip' and status='NEW';";
#		if($DB){print STDERR "\n|$stmtA|\n";}
#	$dbhA->query($stmtA); #  or die  "Couldn't execute query: |$stmtA|\n";
#	print " - vicidial_manager queue reset\n";

	$stmtA = "DELETE from vicidial_manager where server_ip='$server_ip';";
		if($DB){print STDERR "\n|$stmtA|\n";}
	$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query: |$stmtA|\n";
	print " - vicidial_manager delete\n";

	$stmtA = "DELETE from vicidial_auto_calls where server_ip='$server_ip';";
			if($DB){print STDERR "\n|$stmtA|\n";}
	$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query: |$stmtA|\n";
	print " - vicidial_auto_calls delete\n";

	$stmtA = "DELETE from vicidial_live_agents where server_ip='$server_ip';";
			if($DB){print STDERR "\n|$stmtA|\n";}
	$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query: |$stmtA|\n";
	print " - vicidial_live_agents delete\n";

	$stmtA = "DELETE from vicidial_users where full_name='5555';";
			if($DB){print STDERR "\n|$stmtA|\n";}
	$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query: |$stmtA|\n";
	print " - vicidial__users delete\n";

	$dbhA->disconnect();


exit;






