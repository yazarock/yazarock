<?php if(!defined('ABSPATH'))exit; get_header();while(have_posts()):the_post();$c=get_the_category();$u=rawurlencode(get_permalink());$t=rawurlencode(get_the_title());?>
<div class="rp"><i></i></div>
<article class="box entry"><div class="mt"><?php if($c)echo '<a href="'.esc_url(get_category_link($c[0])).'">'.esc_html($c[0]->name).'</a>';?></div>
<h1><?php the_title();?></h1>
<div class="meta"><?php echo get_avatar(get_the_author_meta('ID'),32);?><a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID')));?>"><?php the_author();?></a> · <?php echo esc_html(get_the_date('j F Y'));?> · <?php echo yr_read();?> dk okuma</div>
<?php if(has_post_thumbnail())the_post_thumbnail('large');?>
<div class="content"><?php the_content();?></div>
<?php yr_embeds(get_the_ID());?>
<?php the_tags('<p class="tags">','',' </p>');?>
<div class="share"><a class="btn alt" href="https://wa.me/?text=<?php echo $t.'%20'.$u;?>" rel="noopener" target="_blank">WhatsApp</a><a class="btn alt" href="https://twitter.com/intent/tweet?url=<?php echo $u;?>&amp;text=<?php echo $t;?>" rel="noopener" target="_blank">X</a></div>
<?php if(get_the_author_meta('description')):?><div class="abox"><?php echo get_avatar(get_the_author_meta('ID'),64);?><div><b><?php the_author();?></b><br><?php echo esc_html(get_the_author_meta('description'));?></div></div><?php endif;?>
</article>
<?php if(comments_open()||get_comments_number())comments_template();
$r=new WP_Query(['posts_per_page'=>3,'post__not_in'=>[get_the_ID()],'category__in'=>wp_get_post_categories(get_the_ID()),'ignore_sticky_posts'=>1,'no_found_rows'=>true]);
if($r->have_posts()):?><div class="box" style="margin-top:22px"><h2>İlgili Yazılar</h2><div class="news"><?php while($r->have_posts()){$r->the_post();yr_card();}?></div></div><?php endif;wp_reset_postdata();
yr_newsletter();endwhile;get_footer();