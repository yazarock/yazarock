<?php if(!defined('ABSPATH'))exit; get_header();while(have_posts()):the_post();$pid=get_the_ID();$aid=(int)get_the_author_meta('ID');$c=get_the_category();$u=rawurlencode(get_permalink());$t=rawurlencode(get_the_title());$ok=yr_okunma($pid);?>
<div class="rp"><i></i></div>
<article class="box entry<?php echo yr_roportaj_mi($pid)?' rop':'';?>" data-okundu="<?php echo (int)$pid;?>" data-api="<?php echo esc_url(rest_url('yazarock/v1/okundu'));?>"><div class="mt"><?php if(yr_roportaj_mi($pid))echo '<span class="pill">Röportaj</span> ';if($c&&!(yr_roportaj_mi($pid)&&(int)$c[0]->term_id===yr_roportaj_kat()))echo '<a href="'.esc_url(get_category_link($c[0])).'">'.esc_html($c[0]->name).'</a>';?></div>
<h1><?php the_title();?></h1>
<div class="meta"><?php echo get_avatar($aid,32);?><a href="<?php echo esc_url(get_author_posts_url($aid));?>"><?php the_author();?></a> · <?php echo esc_html(get_the_date('j F Y'));?> · <?php echo (int)yr_read();?> dk okuma<?php if($ok)echo ' · '.esc_html(yr_sayi($ok)).' okunma';?></div>
<?php if(has_post_thumbnail()){the_post_thumbnail('large');yr_credit();}?>
<div class="content"><?php the_content();?></div>
<?php yr_embeds($pid);$rg=(int)get_post_meta($pid,'_yr_roportaj_grup',true);if($rg)yr_grup_kart($rg);?>
<?php the_tags('<p class="tags">',' ',' </p>');?>
<div class="post-foot" id="tepki">
<div class="share"><a class="btn alt" href="https://wa.me/?text=<?php echo $t.'%20'.$u;?>" rel="noopener" target="_blank">WhatsApp</a><a class="btn alt" href="https://twitter.com/intent/tweet?url=<?php echo $u;?>&amp;text=<?php echo $t;?>" rel="noopener" target="_blank">X</a></div>
<?php yr_tepki_kutu($pid);?>
</div>
<?php yr_yazar_kutu($aid);?>
</article>
<?php if(comments_open()||get_comments_number())comments_template();
$ex=[$pid];
$ay=new WP_Query(['post_type'=>'post','author'=>$aid,'posts_per_page'=>3,'post__not_in'=>$ex,'ignore_sticky_posts'=>1,'no_found_rows'=>true]);
if($ay->have_posts()):?><div class="box" style="margin-top:22px"><h2>Yazarın Diğer Yazıları</h2><div class="news"><?php while($ay->have_posts()){$ay->the_post();$ex[]=get_the_ID();yr_card();}?></div><p style="margin:14px 0 0"><a class="more" href="<?php echo esc_url(get_author_posts_url($aid));?>">Tüm yazıları →</a></p></div><?php endif;wp_reset_postdata();
$r=new WP_Query(['posts_per_page'=>3,'post__not_in'=>$ex,'category__in'=>wp_get_post_categories($pid),'ignore_sticky_posts'=>1,'no_found_rows'=>true]);
if($r->have_posts()):?><div class="box" style="margin-top:22px"><h2>İlgili Yazılar</h2><div class="news"><?php while($r->have_posts()){$r->the_post();yr_card();}?></div></div><?php endif;wp_reset_postdata();
yr_instagram_kutu();yr_newsletter();endwhile;get_footer();
