<?php
//    MyDMS. Document Management System
//    Copyright (C) 2010 Matteo Lucarelli
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (!isset($_GET["logname"]) || !file_exists($settings->_contentDir.$_GET["logname"]) ) {
	UI::exitError(getMLText("admin_tools"),getMLText("unknown_id"));
}

$logname = $_GET["logname"];

UI::htmlStartPage(getMLText("backup_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");
UI::contentHeading(getMLText("rm_file"));
UI::contentContainerStart();

?>
<form action="../op/op.RemoveLog.php" name="form1" method="POST">
	<input type="Hidden" name="logname" value="<?php echo $logname?>">
	<p><?php printMLText("confirm_rm_log", array ("logname" => $logname));?></p>
	<input type="Submit" value="<?php printMLText("rm_file");?>">
</form>
<?php
UI::contentContainerEnd();
UI::htmlEndPage();
?>
