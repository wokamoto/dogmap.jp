<?php get_header(); ?>

<div class="row">
	<div class="col-12">
		<div class="row">

<main id="main" role="main" class="col-9">
	<?php if ( !is_front_page() ) : ?>
		<?php Kotetsu::the_bread_crumb(); ?>
	<?php endif; ?>
	<div class="entries">
		<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class(); ?>>
			<div class="entry-thumbnail">
				<a href="<?php the_permalink(); ?>">
					<div class="attachment-thumbnail-wrapper">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'thumbnail' ); ?>
						<?php else : ?>
							No image
						<?php endif; ?>
					<!-- end .attachment-thumbnail-wrapper --></div>
				</a>
			<!-- end .entry-thumbnail --></div>
			<div class="entry-body">
				<h1 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
				<div class="entry-summary">
					<?php the_excerpt(); ?>
				<!-- end .entry-summary --></div>
				<?php Kotetsu::the_entry_meta(); ?>
			<!-- end .entry-body --></div>
		</article>
		<?php endwhile; ?>
	<!-- end .entries --></div>

	<?php Kotetsu::pager(); ?>
<!-- end #main --></main>

<?php get_sidebar(); ?>

		<!-- end .row --></div>
	<!-- end col-12 --></div>
<!-- end .row --></div>

<?php get_footer(); ?>
