<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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
include("../inc/inc.LogInit.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}
$folderid = $_GET["folderid"];
$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

if ($folderid == $settings->_rootFolderID || !$folder->getParent()) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("cannot_rm_root"));
}

if ($folder->getAccessMode($user) < M_ALL) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

$parent=$folder->getParent();

if ($folder->remove()) {
	// Send notification to subscribers.
	if ($notifier) {
		$folder->getNotifyList();
		$subject = "###SITENAME###: ".$folder->_name." - ".getMLText("folder_deleted_email");
		$message = getMLText("folder_deleted_email")."\r\n";
		$message .= 
			getMLText("name").": ".$folder->_name."\r\n".
			getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
			getMLText("comment").": ".$folder->_comment."\r\n".
			"URL: ###URL_PREFIX###out/out.ViewFolder.php?folderid=".$folder->_id."\r\n";

		$subject=mydmsDecodeString($subject);
		$message=mydmsDecodeString($message);
		
		$notifier->toList($user, $folder->_notifyList["users"], $subject, $message);
		foreach ($folder->_notifyList["groups"] as $grp) {
			$notifier->toGroup($user, $grp, $subject, $message);
		}
	}
} else {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));
}

add_log_line();

header("Location:../out/out.ViewFolder.php?folderid=".$parent->getID()."&showtree=".$_GET["showtree"]);

?>
