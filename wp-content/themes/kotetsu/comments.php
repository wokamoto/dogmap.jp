<?php
if ( 'comments.php' == basename( $_SERVER['SCRIPT_FILENAME'] ) ) {
	die ( '<p lang=\"ja\">Don\'t direct access this page.' );
	if ( !empty( $post->post_password ) ) {
		if ( $_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password ) { ?>
			<p class="nocomments">
				<?php _e( 'This post is password protected. Enter the password to view any comments.', 'kotetsu' ); ?>
			<p>
			<?php
			return;
		}
	}
}
?>
<?php if ( 'open' == $post->comment_status || 'open' == $post->ping_status ) : ?>
<div id="commentarea">
	<?php if ( 'open' == $post->comment_status ) : ?>
	<div id="comments">
		<h2><?php _e( 'Comments on this post', 'kotetsu' ); ?></h2>
		<?php if ( !empty( $comments_by_type['comment'] ) ) : ?>
		<ol class="commentlist">
			<?php
			wp_list_comments( array(
				'type' => 'comment',
				'callback' => 'Kotetsu::the_comments'
			) );
			?>
		</ol>
		<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
		<div class="pager">
			<p>
				<?php paginate_comments_links( array(
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;' )
				); ?>
			</p>
		<!-- end .pager --></div>
		<?php endif; ?>

		<?php else : ?>
		<p class="nocomments"><?php _e( 'No comments.', 'kotetsu' ); ?></p>
		<?php endif; ?>

		<div id="respond">
			<?php if ( get_option( 'comment_registration' ) && !$user_ID ) : ?>
			<p>
				<?php
				printf( __( 'It is necessary to <a href="%/wp-login.php?redirect_to=%s">login</a> to write comment.', 'kotetsu' ),
					home_url(),get_permalink()
				);
				?>
			</p>
			<?php else : ?>
			<div id="comment-form">
				<?php comment_form(); ?>
			<!-- end #comment-form --></div>
			<?php endif; ?>
		<!-- end #respond --></div>
	<!-- end #comments --></div>
	<?php endif; ?>

	<?php if ( 'open' == $post->ping_status ) : ?>
	<div id="trackback">
		<h2><?php _e( 'Trackbacks and Pinbacks on this post', 'kotetsu' ); ?></h2>
		<?php if ( ! empty($comments_by_type['pings']) ) : ?>
		<ol class="trackbacklist">
			<?php
			wp_list_comments( array(
				'type' => 'pings',
				'callback' => 'Kotetsu::the_comments'
			) );
			?>
		</ol>
		<?php else : ?>
		<p class="nocomments"><?php _e( 'No trackbacks.', 'kotetsu' ); ?></p>
		<?php endif; ?>

		<div class="trackback-url">
			<dl>
				<dt><?php _e( 'TrackBack URL', 'kotetsu' ); ?></dt>
				<dd><input id="tburl" type="text" size="50" value="<?php trackback_url( true ); ?>" readonly="readonly" /></dd>
			</dl>
		<!-- end .trackback-url --></div>
	<!-- end #trackback --></div>
	<?php endif; ?>
<!-- end #commentarea --></div>
<?php endif; ?>
