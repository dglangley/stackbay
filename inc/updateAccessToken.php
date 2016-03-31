<?php
	function updateAccessToken($access_token,$userid) {
		$t = json_decode($access_token);

		$query = "REPLACE google_tokens (access_token, token_type, expires_in, created, refresh_token, userid) ";
		$query .= "VALUES ('".$t->access_token."','".$t->token_type."','".$t->expires_in."','".$t->created."',";
		if ($t->refresh_token) { $query .= "'".$t->refresh_token."',"; } else { $query .= "NULL,"; }
		$query .= "'".$userid."'); ";
		$result = qdb($query);
	}
?>
