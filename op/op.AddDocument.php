<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderid = $_POST["folderid"];
$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))),getMLText("invalid_folder_id"));
}

$folderPathHTML = getFolderPathHTML($folder, true);

if ($folder->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("access_denied"));
}

$comment  = sanitizeString($_POST["comment"]);
$version_comment = sanitizeString($_POST["version_comment"]);

$keywords = sanitizeString($_POST["keywords"]);
$categories = sanitizeString($_POST["categoryidform1"]);

$reqversion = (int)$_POST["reqversion"];
if ($reqversion<1) $reqversion=1;

$sequence = $_POST["sequence"];
if (!is_numeric($sequence)) {
	UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("invalid_sequence"));
}

$expires = ($_POST["expires"] == "true") ? mktime(0,0,0, intval($_POST["expmonth"]), intval($_POST["expday"]), intval($_POST["expyear"])) : false;

// Get the list of reviewers and approvers for this document.
$reviewers = array();
$approvers = array();
$reviewers["i"] = array();
$reviewers["g"] = array();
$approvers["i"] = array();
$approvers["g"] = array();

// Retrieve the list of individual reviewers from the form.
if (isset($_POST["indReviewers"])) {
	foreach ($_POST["indReviewers"] as $ind) {
		$reviewers["i"][] = $ind;
	}
}
// Retrieve the list of reviewer groups from the form.
if (isset($_POST["grpReviewers"])) {
	foreach ($_POST["grpReviewers"] as $grp) {
		$reviewers["g"][] = $grp;
	}
}

// Retrieve the list of individual approvers from the form.
if (isset($_POST["indApprovers"])) {
	foreach ($_POST["indApprovers"] as $ind) {
		$approvers["i"][] = $ind;
	}
}
// Retrieve the list of approver groups from the form.
if (isset($_POST["grpApprovers"])) {
	foreach ($_POST["grpApprovers"] as $grp) {
		$approvers["g"][] = $grp;
	}
}

// add mandatory reviewers/approvers
$docAccess = $folder->getApproversList();
$res=$user->getMandatoryReviewers();
foreach ($res as $r){

	if ($r['reviewerUserID']!=0){
		foreach ($docAccess["users"] as $usr)
			if ($usr->getID()==$r['reviewerUserID']){
				$reviewers["i"][] = $r['reviewerUserID'];
				break;
			}
	}
	else if ($r['reviewerGroupID']!=0){
		foreach ($docAccess["groups"] as $grp)
			if ($grp->getID()==$r['reviewerGroupID']){
				$reviewers["g"][] = $r['reviewerGroupID'];
				break;
			}
	}
}
$res=$user->getMandatoryApprovers();
foreach ($res as $r){

	if ($r['approverUserID']!=0){
		foreach ($docAccess["users"] as $usr)
			if ($usr->getID()==$r['approverUserID']){
				$approvers["i"][] = $r['approverUserID'];
				break;
			}
	}
	else if ($r['approverGroupID']!=0){
		foreach ($docAccess["groups"] as $grp)
			if ($grp->getID()==$r['approverGroupID']){
				$approvers["g"][] = $r['approverGroupID'];
				break;
			}
	}
}

for ($file_num=0;$file_num<count($_FILES["userfile"]["tmp_name"]);$file_num++){

	if ($_FILES["userfile"]["size"][$file_num]==0) continue;

	if (is_uploaded_file($_FILES["userfile"]["tmp_name"][$file_num]) && $_FILES['userfile']['error'][$file_num]!=0){
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("uploading_failed"));
	}

	$userfiletmp = $_FILES["userfile"]["tmp_name"][$file_num];
	$userfiletype = sanitizeString($_FILES["userfile"]["type"][$file_num]);
	$userfilename = sanitizeString($_FILES["userfile"]["name"][$file_num]);
	
	$lastDotIndex = strrpos(basename($userfilename), ".");
	if (is_bool($lastDotIndex) && !$lastDotIndex) $fileType = ".";
	else $fileType = substr($userfilename, $lastDotIndex);

	if ((count($_FILES["userfile"]["tmp_name"])==1)&&($_POST["name"]!=""))
		$name = sanitizeString($_POST["name"]);
	else $name = basename($userfilename);

	$cats = array();
	if($categories) {
		$catids = explode(',', $categories);
		foreach($catids as $catid) {
			$cats[] = $dms->getDocumentCategory($catid);
		}
	}
	$res = $folder->addDocument($name, $comment, $expires, $user, $keywords,
															$cats, $userfiletmp, basename($userfilename),
	                            $fileType, $userfiletype, $sequence,
	                            $reviewers, $approvers, $reqversion,$version_comment);

	if (is_bool($res) && !$res) {
		UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));
	} else {
		$document = $res[0];
		if(isset($GLOBALS['LETODMS_HOOKS']['postAddDocument'])) {
			foreach($GLOBALS['LETODMS_HOOKS']['postAddDocument'] as $hookObj) {
				if (method_exists($hookObj, 'postAddDocument')) {
					$hookObj->postAddDocument($document);
				}
			}
		}
		// Send notification to subscribers.
		if($notifier) {
			$folder->getNotifyList();
			$subject = "###SITENAME###: ".$folder->_name." - ".getMLText("new_document_email");
			$message = getMLText("new_document_email")."\r\n";
			$message .= 
				getMLText("name").": ".$name."\r\n".
				getMLText("folder").": ".$folder->getFolderPathPlain()."\r\n".
				getMLText("comment").": ".$comment."\r\n".
				getMLText("comment_for_current_version").": ".$version_comment."\r\n".
				"URL: ###URL_PREFIX###out/out.ViewDocument.php?documentid=".$document->getID()."\r\n";

			$subject=mydmsDecodeString($subject);
			$message=mydmsDecodeString($message);
			
			$notifier->toList($user, $folder->_notifyList["users"], $subject, $message);
			foreach ($folder->_notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message);
			}
		}
	}
	
	add_log_line("?name=".$name."&folderid=".$folderid);
}

header("Location:../out/out.ViewFolder.php?folderid=".$folderid."&showtree=".$_POST["showtree"]);

?>
