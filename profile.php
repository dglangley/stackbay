<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_price.php';
	include_once 'inc/getCompany.php';
	include_once 'inc/getPart.php';

	function getContacts($companyid) {
		$contacts = array();
		$query = "SELECT * FROM contacts WHERE companyid = '".res($companyid)."' ORDER BY name ASC; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$r['emails'] = array();
			$r['phones'] = array();
			$contacts[] = $r;
		}

		// one more row as blank to add new
		$contacts[] = array('name'=>'','emails'=>array(),'phones'=>array(),'im'=>'','notes'=>'');
		return ($contacts);
	}

	$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
?>
<!DOCTYPE html>
<html>
<head>
	<title>VMM Company Profile</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body class="sub-nav profile-body">

	<?php include 'inc/navbar.php'; ?>

	<form class="form-inline" method="get" action="/profile.php">

    <table class="table table-header">
		<tr>
			<td class="col-md-2">
                <input type="text" class="search order-search" id="accounts-search" placeholder="Order#, Part#, HECI..." autofocus />
			</td>
			<td class="text-center col-md-6">
			</td>
			<td class="col-md-3">
				<div class="pull-right form-group">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
<?php if ($companyid) { echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); } else { echo '<option value="">- Select a Company -</option>'.chr(10); } ?>
					</select>
					<input class="btn btn-primary btn-sm" type="submit" value="Go">
				</div>
			</td>
		</tr>
	</table>


    <div id="pad-wrapper" class="user-profile">

            <!-- header -->
            <div class="row header">
                <div class="col-md-9">
					<div class="business-icon"><i class="fa fa-building fa-4x"></i></div>
                    <h2 class="name"><?php echo getCompany($companyid); ?></h2>
                </div>
                <div class="col-md-3 text-right">
	                <a class="btn btn-default icon pull-right" data-toggle="tooltip" title="Delete" data-placement="top"><i class="fa fa-trash text-danger"></i></a>
	                <a class="btn btn-default icon pull-right" data-toggle="tooltip" title="Edit" data-placement="top"><i class="fa fa-pencil"></i></a>
				</div>
            </div>

            <div class="row">
                <!-- bio, new note & orders column -->
                <div class="col-md-9 bio">
                    <div class="profile-box">

                        <!-- recent orders table -->
                        <table class="table table-hover table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th class="col-md-3">
                                        Name
                                    </th>
                                    <th class="col-md-3">
                                        <span class="line"></span>
                                        Email(s)
                                    </th>
                                    <th class="col-md-2">
                                        <span class="line"></span>
                                        Phone Number(s)
                                    </th>
                                    <th class="col-md-2">
                                        <span class="line"></span>
                                        IM
                                    </th>
                                    <th class="col-md-2">
                                        <span class="line"></span>
                                        Notes
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
<?php
	$contacts = getContacts($companyid);
	foreach ($contacts as $contact) {
		$emails = '';
		foreach($contact['emails'] as $email) {
			$emails .= '<input type="text" class="form-control input-sm inline" value="'.$email.'">'.chr(10);
		}
		$emails .= '<input type="text" class="form-control input-sm inline" value="">'.chr(10);
		$phones = '';
		foreach($contact['phones'] as $phone) {
			$phones .= '<input type="text" class="form-control input-sm inline" value="'.$phone.'">'.chr(10);
		}
		$phones .= '<input type="text" class="form-control input-sm inline" value="">'.chr(10);
?>
                                <tr>
                                    <td>
                                        <input type="text" class="form-control input-sm inline" value="<?php echo $contact['name']; ?>">
                                    </td>
                                    <td>
										<?php echo $emails; ?>
                                    </td>
                                    <td>
										<?php echo $phones; ?>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control input-sm inline" value="<?php echo $contact['im']; ?>">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control input-sm inline" value="<?php echo $contact['notes']; ?>">
                                    </td>
                                </tr>
<?php
	}
?>
                            </tbody>
                        </table>

                        <!-- new comment form -->
                        <div class="col-md-12 section comment">
                            <h6>Add a quick note</h6>
                            <p>Add a note about this user to keep a history of your interactions.</p>
                            <textarea></textarea>
                            <a href="#">Attach files</a>
                            <div class="col-md-12 submit-box pull-right">
                                <input type="submit" class="btn-glow primary" value="Add Note">
                                <span>OR</span>
                                <input type="reset" value="Cancel" class="reset">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- side address column -->
                <div class="col-md-3 col-xs-12 address pull-right">
                    <h6>Address</h6>
                    <iframe width="300" height="133" scrolling="no" src="https://maps.google.com.mx/?ie=UTF8&amp;t=m&amp;ll=19.715081,-155.071421&amp;spn=0.010746,0.025749&amp;z=14&amp;output=embed"></iframe>
                    <ul>
                        <li>2301 East Lamar Blvd. Suite 140. </li>
                        <li>City, Arlington. United States,</li>
                        <li>Zip Code, TX 76006.</li>
                        <li class="ico-li">
                            <i class="ico-phone"></i>
                            1817 274 2933
                        </li>
                         <li class="ico-li">
                            <i class="ico-mail"></i>
                            <a href="#">alejandra@detail.com</a>
                        </li>
                    </ul>
                </div>

        </div>

	</div>
    <!-- end main container -->

<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
        $(document).ready(function() {
        });
    </script>

</body>
</html>
