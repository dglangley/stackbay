<h3 class="text-center pb-20">User Dashboard</h3>

 <div class="list-group">
    <!-- Get the ID of admin and print it out, in case ID's change as long as Admin exists the ID will be pulled -->
    <a class="list-group-item <?php echo ($pageName == 'user_profile.php' ? 'active' : ''); ?>" href="user_profile.php">My Profile</a>
    <?php if($USER_ROLES[array_search(array_search('Administration', $ROLES), $USER_ROLES)] == array_search('Administration', $ROLES)) { ?>
    	<!-- Each Tenary if statement is checking if the page is actually the active page based on the global page name variable set in dbconnect -->
        <a class="list-group-item <?php echo ($pageName == 'user_management.php' ? 'active' : ''); ?>" href="user_management.php">User Management</a>
        <a class="list-group-item <?php echo ($pageName == 'class_management.php' ? 'active' : ''); ?>" href="class_management.php">Class Management</a>
        <a class="list-group-item <?php echo ($pageName == 'user_commissions.php' ? 'active' : ''); ?>" href="user_commissions.php">Commissions</a>
        <a class="list-group-item <?php echo ($pageName == 'page_permissions.php' ? 'active' : ''); ?>" href="page_permissions.php">Page Permissions</a>
        <a class="list-group-item <?php echo ($pageName == 'password.php' ? 'active' : ''); ?>" href="password.php">Password Policy</a>
        <a class="list-group-item <?php echo ($pageName == 'subscriptions.php' ? 'active' : ''); ?>" href="subscriptions.php">Subscriptions</a>
    <?php } ?>
<!--
    <a class="list-group-item" href="signout.php">Logout</a>
-->
</div>
