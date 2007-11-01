<!-- Sub-Albums -->
<div id="albums">
	<?php while (next_album()): ?>
		<div class="album">
			<a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumThumbImage(getAlbumTitle()); ?></a>
			<div class="albumdesc">
				<h3><a href="<?php echo getAlbumLinkURL();?>" title="View album: <?php echo getAlbumTitle();?>"><?php printAlbumTitle(); ?></a></h3>
				<p><?php printAlbumDesc(); ?></p>
                <small><?php printAlbumDate("Date: "); ?></small>
			</div>
			<p style="clear: both; "></p>
		</div>
	<?php endwhile; ?>
</div>

<div id="images">
		<?php 
		  while (next_image(false, $firstPageImages)): 
		?>
		<div class="image">
			<div class="imagethumb"><a href="<?php echo getImageLinkURL();?>" title="<?php echo getImageTitle();?>"><?php printImageThumb(getImageTitle()); ?></a></div>
		</div>
		<?php endwhile; ?>
</div>