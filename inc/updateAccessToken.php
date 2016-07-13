<?php
	function updateAccessToken($access_token,$userid,$refresh_token='') {
		$t = json_decode($access_token);

		if (! $refresh_token AND isset($t->refresh_token)) { $refresh_token = $t->refresh_token; }
		$token_type = '';
		if (isset($t->token_type)) { $token_type = $t->token_type; }

		$query = "REPLACE google_tokens (access_token, token_type, expires_in, created, refresh_token, userid) ";
		$query .= "VALUES ('".$t->access_token."',";
		if ($token_type) { $query .= "'".$token_type."',"; } else { $query .= "NULL,"; }
		$query .= "'".$t->expires_in."','".$t->created."',";
		if ($refresh_token) { $query .= "'".$refresh_token."',"; } else { $query .= "NULL,"; }
		$query .= "'".$userid."'); ";
		$result = qdb($query);
	}
?>
