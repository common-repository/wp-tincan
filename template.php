<?php
/**
 * Template Name: No Formatting
 */

if ( have_posts() ) : while ( have_posts() ) : the_post(); 
?>
<div style="background: url('<?php echo plugins_url('img/cert.png', __FILE__); ?>') no-repeat; background-size:100% auto; height:583px; width:850px; min-width: 850px; min-height:583px;padding-top:55px;" >
<?php
the_content();
					
endwhile; endif;
?>
</div>