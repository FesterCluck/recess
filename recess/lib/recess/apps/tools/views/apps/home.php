<?php
$title = 'Applications';
$selectedNav = 'apps';
include_once($viewsDir . 'common/header.php');
?>
<h1>Applications</h1>

<?php
if(isset($flash)) {
	echo '<p class="highlight">' . $flash . '</p>';
}
?>

<p>Applications Directory: <span class="loud"><?php echo $_ENV['dir.apps']; ?></span></p>
<?php
foreach($apps as $app) {
	if(strpos($app->controllersPrefix,'recess.apps') === false) {
		echo '<h2><a href="' . $controller->urlToMethod('app',get_class($app)) . '">' . $app->name . '</a></h2>';
	}
}

foreach($apps as $app) {
	if(strpos($app->controllersPrefix,'recess.apps') !== false) {
		echo '<h2><a href="' . $controller->urlToMethod('app',get_class($app)) . '">' . $app->name . '</a></h2>';
	}
}
?>
<hr />
<h3><a href="<?php echo $controller->urlToMethod('newApp'); ?>">Start a New Application</a></h3>

<?php include_once($viewsDir . 'common/footer.php'); ?>