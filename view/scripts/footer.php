<!--content-end-->
	</div>
</div>
<div id="footer">
	<span id="footer-copyright">
	<?php
	$author = Config::get('app.info.author');
	$date = Config::get('app.info.date');
	if (!empty($author)) {
		echo 'Copyright &copy; '.$author;
		if (!empty($date)) {
			$startYear = DateTime::createFromFormat('Y-m-d', $date)->format('Y');
			$currentYear = (new DateTime())->format('Y');
			echo ' '.($currentYear <= $startYear ? $currentYear : $startYear.'-'.$currentYear);
		}
	}
?>
</span>
</div>
<script type="text/javascript">
(function() {
	if (typeof doOnLoad == 'function') {
		window.onload = doOnLoad;
	}
})();
</script>
</body>
</html>
