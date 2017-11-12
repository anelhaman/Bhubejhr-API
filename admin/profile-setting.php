<?php
include_once 'autoload.php';

if(!$user_online){
	header('Location: '.DOMAIN.'/login.php');
	die();
}
$app_id = $_GET['id'];
$app 		= new app;
$log = new Log;
$app->get($app_id);
$log_today = $log->today($app_id);
$log_allday = $log->allday($app_id);
$currentPage = 'profile';
?>
<!doctype html>
<html lang="en-US" itemscope itemtype="http://schema.org/Blog" prefix="og: http://ogp.me/ns#">
<head>

<!--[if lt IE 9]>
<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js"></script>
<![endif]-->

<!-- Meta Tag -->
<meta charset="utf-8">

<!-- Viewport (Responsive) -->
<meta name="viewport" content="width=device-width">
<meta name="viewport" content="user-scalable=no">
<meta name="viewport" content="initial-scale=1,maximum-scale=1">

<?php include'favicon.php';?>
<title>Apps | <?php echo SITENAME;?></title>

<link rel="stylesheet" type="text/css" href="css/style.css"/>
<link rel="stylesheet" type="text/css" href="plugin/font-awesome/css/font-awesome.min.css"/>
</head>
<body>
<?php include'header.php';?>

<div class="progressbar" id="progressbar"></div>
<div class="navigation">
	<div class="group">
		<div class="head">
			<div class="name"><?php echo $app->name;?></div>
			<div class="desc"><?php echo $app->description;?></div>
		</div>
	</div>
</div>

<div class="container">
	<h2>Profile Detail</h2>
	<div class="setting">
		<div class="setting-items">
			<label for="app_name">Fullname</label>
			<input type="text" class="inputtext" id="name" value="<?php echo $user->name;?>">
		</div>
		<div class="setting-items">
			<label for="app_name">Username</label>
			<input type="text" class="inputtext" id="username" value="<?php echo $user->username;?>">
		</div>
		<div class="setting-items">
			<label for="app_name">Email</label>
			<input type="text" class="inputtext" id="email" value="<?php echo $user->email;?>">
		</div>
		<div class="setting-items">
			<label for="app_name">Company</label>
			<input type="text" class="inputtext" id="company" value="<?php echo $user->company;?>">
		</div>
		<div class="setting-items">
			<label for="app_name">Position</label>
			<input type="text" class="inputtext" id="position" value="<?php echo $user->position;?>">
		</div>

		<div class="setting-control">
			<button class="btn-submit" id="btnUpdate">Update Profile</button>
		</div>
	</div>
</div>

<input type="hidden" id="app_id" value="<?php echo $app->id;?>">
<script type="text/javascript" src="js/lib/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="js/profile.setting.js"></script>
</body>
</html>