<?php if(!defined('ABSPATH'))exit; get_header();while(have_posts()):the_post();$d=yr_meta('tarih');$ts=$d?strtotime($d.' 12:00'):0;$b=yr_meta('bilet');
$cal=$ts?'https://calendar.google.com/calendar/render?action=TEMPLATE&text='.rawurlencode(get_the_title()).'&dates='.date('Ymd',$ts).'/'.date('Ymd',$ts+86400).'&location='.rawurlencode(yr_meta('mekan').', '.yr_meta('sehir')):'';?>
<article class="box entry"><span class="pill"><?php echo esc_html(yr_meta('tur')?yr_meta('tur'):'Etkinlik');?></span><h1><?php the_title();?></h1>
<div class="meta"><?php echo $ts?esc_html(date_i18n('j F Y',$ts)):'';?><?php echo yr_meta('saat')?' · '.esc_html(yr_meta('saat')):'';?> · <?php echo esc_html(yr_meta('sehir'));?> · <?php echo esc_html(yr_meta('mekan'));?></div>
<?php if(has_post_thumbnail())the_post_thumbnail('large');?>
<div class="content"><?php the_content();?></div>
<?php yr_map(get_the_ID());?>
<div class="btnrow"><?php if($b):?><a class="btn" href="<?php echo esc_url($b);?>" rel="noopener" target="_blank">Bilet Al</a><?php endif;if($cal):?><a class="btn alt" href="<?php echo esc_url($cal);?>" rel="noopener" target="_blank">Takvime Ekle</a><?php endif;?><a class="btn alt" href="<?php echo esc_url(get_post_type_archive_link('etkinlik'));?>">← Tüm Etkinlikler</a></div></article>
<?php endwhile;get_footer();