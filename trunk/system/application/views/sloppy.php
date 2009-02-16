<?php
	$ci =& get_instance();
	$data = $ci->model->sloppy();
?>
<html>
	<head>
		<title><?php echo $data['title']; ?></title>
	</head>
	<body>
		<h1><?php echo $data['title']; ?></h1>
		<p><?php echo $data['desc']; ?></p>
	</body>
</html>