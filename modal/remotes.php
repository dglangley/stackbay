	<div id="remote-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="remote-label" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<h3 class="remote-name" id="remote-name">Remote Network Login</h3>
				</div>
				<div class="modal-body">
					<p>
						<label class="control-label" id="remote-login-label" for="remote-login">Your login:</label>
						<input type="text" name="remote_login" id="remote-login" class="form-control" placeholder="Login Name/Email" required>
					</p>
					<p>
						<label class="control-label" id="remote-password-label" for="remote-password">Your password:</label>
						<input type="password" name="remote_password" id="remote-password" class="form-control" placeholder="Password" required><br/>
						<em>Who sees my password?</em><br/>
						Your password is sent directly to the remote site only to initialize a session,
						and once the session is initialized (or rejected), your password is immediately discarded.
						Passwords are not stored or seen by human eyes.
					</p>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-success" id="remote-activate" data-remote="">ACTIVATE</button>
				</div>
			</div>
		</div>
	</div>
