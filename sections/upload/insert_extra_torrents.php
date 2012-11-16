<?
foreach ($ExtraTorrentsInsert as $ExtraTorrent) {
	$ExtraHasLog = "'0'";
	$ExtraHasCue = "'0'";

	// Torrent
	$DB->query("
	INSERT INTO torrents
		(GroupID, UserID, Media, Format, Encoding, 
		Remastered, RemasterYear, RemasterTitle, RemasterRecordLabel, RemasterCatalogueNumber, 
		HasLog, HasCue, info_hash, FileCount, FileList, FilePath, Size, Time,
		Description, LogScore, FreeTorrent, FreeLeechType) 
	VALUES
		(" . $GroupID . ", " . $LoggedUser['ID'] . ", " . $T['Media'] . ", " . $ExtraTorrent['Format'] . ", " . $ExtraTorrent['Encoding'] . ",
		" . $T['Remastered'] . ", " . $T['RemasterYear'] . ", " . $T['RemasterTitle'] . ", " . $T['RemasterRecordLabel'] . ", " . $T['RemasterCatalogueNumber'] . ", " . $ExtraHasLog . ", " . $ExtraHasCue . ", '" . db_string($ExtraTorrent['InfoHash']) . "', " . $ExtraTorrent['NumFiles'] . ", " . $ExtraTorrent['FileString'] . ", '" . $ExtraTorrent['FilePath'] . "', " . $ExtraTorrent['TotalSize'] . ", '" . sqltime() . "',
		" . $ExtraTorrent['TorrentDescription'] . ", '" . (($HasLog == "'1'") ? $LogScoreAverage : 0) . "', '" . $T['FreeLeech'] . "', '" . $T['FreeLeechType'] . "')");

	$Cache->increment('stats_torrent_count');
	$ExtraTorrentID = $DB->inserted_id();

	Tracker::update_tracker('add_torrent', array('id' => $ExtraTorrentID, 'info_hash' => rawurlencode($ExtraTorrent['InfoHash']), 'freetorrent' => $T['FreeLeech']));

	

	//******************************************************************************//
	//--------------- Write torrent file -------------------------------------------//

	$DB->query("INSERT INTO torrents_files (TorrentID, File) VALUES ($ExtraTorrentID, '" . db_string($ExtraTorrent['Tor']->dump_data()) . "')");

	Misc::write_log("Torrent $ExtraTorrentID ($LogName) (" . number_format($ExtraTorrent['TotalSize'] / (1024 * 1024), 2) . " MB) was uploaded by " . $LoggedUser['Username']);
	Torrents::write_group_log($GroupID, $ExtraTorrentID, $LoggedUser['ID'], "uploaded (" . number_format($ExtraTorrent['TotalSize'] / (1024 * 1024), 2) . " MB)", 0);

	Torrents::update_hash($GroupID);

	//IRC
	$Announce = "";
	$Announce .= Artists::display_artists($ArtistForm, false);
	$Announce .= trim($Properties['Title']) . " ";
	$Announce .= '[' . trim($Properties['Year']) . ']';
	if (($Properties['ReleaseType'] > 0)) {
		$Announce .= ' [' . $ReleaseTypes[$Properties['ReleaseType']] . ']';
	}
	$Announce .= " - ";
	$Announce .= trim(str_replace("'", "", $ExtraTorrent['Format'])) . " / " . trim(str_replace("'", "", $ExtraTorrent['Encoding']));
	$Announce .= " / " . trim($Properties['Media']);
	if ($T['FreeLeech'] == "1") {
		$Announce .= " / Freeleech!";
	}
	$Title = $Announce;

	$AnnounceSSL = $Announce . " - https://" . SSL_SITE_URL . "/torrents.php?id=$GroupID / https://" . SSL_SITE_URL . "/torrents.php?action=download&id=$TorrentID";
	$Announce .= " - https://" . SSL_SITE_URL . "/torrents.php?id=$GroupID / https://" . SSL_SITE_URL . "/torrents.php?action=download&id=$TorrentID";

	$AnnounceSSL .= " - " . trim($Properties['TagList']);
	$Announce .= " - " . trim($Properties['TagList']);

	send_irc('PRIVMSG #' . NONSSL_SITE_URL . '-announce :' . html_entity_decode($Announce));
	send_irc('PRIVMSG #' . NONSSL_SITE_URL . '-announce-ssl :' . $AnnounceSSL);
//send_irc('PRIVMSG #'.NONSSL_SITE_URL.'-announce :'.html_entity_decode($Announce));

}
?>