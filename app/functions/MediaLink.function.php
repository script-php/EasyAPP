<?php

function MediaLink($id, $otherPath=NULL) {
	GLOBAL $serverUPL, $DB;
	$medialink = NULL;
	$media = $DB->query("SELECT * FROM ".DB_UPLOADS." WHERE id=:id", [":id"=>$id])->fetch();
		
	if($media) {
		$path_file = $media['path'] ?? $otherPath;
		$medialink = $serverUPL["".$media['server'].""].'/'.$path_file.'/'.$media['file'];
	}
		
	return $medialink;
}

?>