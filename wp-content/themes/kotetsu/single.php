<?php get_header(); ?>

<div class="row">
	<div class="col-12">
		<div class="row">

<main id="main" role="main" class="col-9">
	<?php while ( have_posts() ) : the_post(); ?>
	<article <?php post_class(); ?>>
		<?php Kotetsu::the_bread_crumb(); ?>
		<h1 class="entry-title"><?php the_title(); ?></h1>

		<?php Kotetsu::the_entry_meta(); ?>

		<div class="entry-content">
			<?php the_content(); ?>
		<!-- end .entry-content --></div>

		<?php Kotetsu::the_link_pages(); ?>

		<?php comments_template( '', true ); ?>
	</article>
	<?php endwhile; ?>
<!-- end #main --></main>

<?php get_sidebar(); ?>

		<!-- end .row --></div>
	<!-- end col-12 --></div>
<!-- end .row --></div>

<?php get_footer(); ?>
