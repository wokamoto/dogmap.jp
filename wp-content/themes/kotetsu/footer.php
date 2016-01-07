
	<!-- end #contents --></div>
	<footer id="footer" class="row">
		<div class="col-12">

			<div class="footer-nav">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer-nav',
					'depth' => 1
				) );
				?>
			<!-- end .footer-nav --></div>

			<div class="copyright">
				<p>
					<?php Kotetsu::the_copyright(); ?>
				</p>
			<!-- end .copyright --></div>
		<!-- end .col-12 --></div>
	<!-- end #footer --></footer>
<!-- end #container --></div>
<?php wp_footer(); ?>
</body>
</html>
