<?php 
if (post_password_required()) :
return;
endif;
?>


<section id='comments'>
<?php
if(have_comments()):
?>

<h2 id="comments-title">
<?php echo '「<em>' . get_the_title() . '</em>」に' .get_comments_number() . '件のコメント' ; ?>
</h2>

<ol class="commentlist">
<?php wp_list_comments('avatar_size=40'); ?>
</ol>

<?php if (get_comment_pages_count() > 1 && get_option('page_comments')):
?>

<nav class="navigation">
<ul>
<li class="nav-previous">
<?php previous_comments_link('古いコメント'); ?>
</li>
<li class="nav-next">
<?php next_comments_link('新しいコメント') ; ?>
</li>
</ul>
</nav>

<?php
endif;
endif;
?>


<?php comment_form(); ?>
</section>