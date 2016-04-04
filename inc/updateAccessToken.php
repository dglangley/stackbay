<?php
	function updateAccessToken($access_token,$userid,$refresh_token='') {
		$t = json_decode($access_token);

		if (! $refresh_token AND isset($t->refresh_token)) { $refresh_token = $t->refresh_token; }

		$query = "REPLACE google_tokens (access_token, token_type, expires_in, created, refresh_token, userid) ";
		$query .= "VALUES ('".$t->access_token."','".$t->token_type."','".$t->expires_in."','".$t->created."',";
		if ($refresh_token) { $query .= "'".$refresh_token."',"; } else { $query .= "NULL,"; }
		$query .= "'".$userid."'); ";
		$result = qdb($query);
	}
?>
