<?php if(!defined('ABSPATH'))exit; get_header();while(have_posts()):the_post();$l=yr_meta('link');?>
<article class="box entry"><span class="pill"><?php echo yr_meta('bolge')==='tr'?'Türkiye':'Dünya';?></span><h1><?php the_title();?></h1>
<div class="meta"><?php echo esc_html(yr_join(yr_meta('stil'),yr_meta('kurulus')));?><?php echo get_the_term_list(get_the_ID(),'grup_tur',' · ',', ','');?></div>
<div class="gcov"><?php echo yr_cover(get_the_ID(),'large');?></div><?php yr_credit();?>
<div class="content"><?php the_content();?></div>
<?php yr_disco(get_the_ID());yr_embeds(get_the_ID());?>
<div class="btnrow"><?php if($l):?><a class="btn" href="<?php echo esc_url($l);?>" rel="noopener" target="_blank">Resmî Bağlantı</a><?php endif;?><a class="btn alt" href="<?php echo esc_url(get_post_type_archive_link('grup'));?>">← Tüm Gruplar</a></div></article>
<?php $ev=new WP_Query(['post_type'=>'etkinlik','s'=>get_the_title(),'posts_per_page'=>5,'meta_key'=>'_yr_tarih','orderby'=>'meta_value','order'=>'ASC','no_found_rows'=>true,'meta_query'=>[['key'=>'_yr_tarih','value'=>current_time('Y-m-d'),'compare'=>'>=','type'=>'DATE']]]);
if($ev->have_posts()):?><div class="box" style="margin-top:22px"><h2>Yaklaşan Konserler</h2><div class="events"><?php while($ev->have_posts()){$ev->the_post();yr_event_card();}?></div></div><?php endif;wp_reset_postdata();?>
<?php $rr=new WP_Query(['post_type'=>'post','meta_key'=>'_yr_roportaj_grup','meta_value'=>get_the_ID(),'posts_per_page'=>6,'no_found_rows'=>true]);
if($rr->have_posts()):?><div class="box" style="margin-top:22px"><h2>Yazarock Röportajları</h2><div class="news"><?php while($rr->have_posts()){$rr->the_post();yr_card();}?></div></div><?php endif;wp_reset_postdata();?>
<?php $r=new WP_Query(['s'=>get_the_title(),'post_type'=>'post','posts_per_page'=>3,'no_found_rows'=>true]);
if($r->have_posts()):?><div class="box" style="margin-top:22px"><h2>Bu Grupla İlgili Yazılar</h2><div class="news"><?php while($r->have_posts()){$r->the_post();yr_card();}?></div></div><?php endif;wp_reset_postdata();
if(comments_open()||get_comments_number())comments_template();endwhile;get_footer();