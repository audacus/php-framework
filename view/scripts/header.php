<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<base href="<?php \Helper::getBaseUrl(); ?>">
	<title><?php echo Config::get('app.name').(isset($_SERVER['PATH_INFO']) ? ' - '.substr($_SERVER['PATH_INFO'], 1) : ''); ?></title>
	<?php
		// make the data of the view accessible on the site as json
		echo '<script type="text/javascript">window.data = JSON.parse(\''.addslashes(json_encode($this->getData(), JSON_NUMERIC_CHECK)).'\');</script>'."\n";
		// make the error of the view accessible on the site as json
		echo '<script type="text/javascript">window.error = JSON.parse(\''.addslashes(json_encode($this->getError(), JSON_NUMERIC_CHECK)).'\');</script>'."\n";

		// include css files
		foreach ($this->getCssFiles() as $css) {
			echo '<link type="text/css" rel="stylesheet" href="'.$css.'">'."\n";
		}

		// include js files
		foreach ($this->getJsFiles() as $js) {
			echo '<script src="'.$js.'" type="text/javascript" charset="utf-8"></script>'."\n";
		}
	?>
</head>
<body>
<div id="wrapper">
	<div id="wrapper-content">
