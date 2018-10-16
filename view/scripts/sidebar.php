<div id="sidebar">
<?php
foreach ($this->getSidebar() as $sub) {
?>
	<div class="sidebar-sub">
		<span class="sidebar-sub-title"><?php echo $sub['title']; ?></span>
		<div class="sidebar-sub-links">
<?php
	foreach($sub['items'] as $item) {
?>
			<span class="sidebar-sub-link"><a href="<?php echo $item['link']; ?>"><?php echo $item['label']; ?></a></span>
<?php
	}
?>
		</div>
	</div>
<?php
}
?>
</div>
