<?php if(!defined('ABSPATH'))exit; get_header();
$gt=yr_page_url('grubunu-tanit');
$tr_say=(int)(new WP_Query(['post_type'=>'grup','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_yr_bolge','meta_value'=>'tr']))->found_posts;
$rk=yr_roportaj_kat();$gi=yr_giris_icerik();
?>
<section class="mhero">
<div class="mh-in">
<?php $ust=$gi['ust']??'Türkiye · Rock & Metal · Underground';if($ust!=='')echo '<span class="pill">'.esc_html($ust).'</span>';?>
<h1><?php echo esc_html($gi['baslik']);?></h1>
<p><?php echo esc_html($gi['metin']);?></p>
<div class="btnrow"><?php if(yr_t('hero_btn1')!=='')echo '<a class="btn" href="'.esc_url($gt).'">'.esc_html(yr_t('hero_btn1')).'</a>';if(yr_t('hero_btn2')!=='')echo '<a class="btn alt" href="#kesfet">'.esc_html(yr_t('hero_btn2')).'</a>';?></div>
</div>
<svg viewBox="0 0 400 430" preserveAspectRatio="xMaxYMid slice" aria-hidden="true"><g fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M390 20 L330 120 L360 125 L290 250 L330 255 L250 410" stroke="#8ec0ff" stroke-width="3"/><path d="M400 90 L350 170 L372 172" stroke="#8ec0ff" stroke-width="2" opacity=".7"/><path d="M200 30 L170 130 L195 134 L150 240" stroke="#e5393f" stroke-width="3"/><path d="M60 60 L90 150 L70 158 L110 260" stroke="#e5393f" stroke-width="2" opacity=".7"/></g></svg>
</section>

<?php /* Sabitlenmiş yazı varsa manşet şeridi */
$st=array_slice(array_filter((array)get_option('sticky_posts')),0,5);
if($st):$h=new WP_Query(['posts_per_page'=>5,'ignore_sticky_posts'=>1,'no_found_rows'=>true,'post__in'=>$st]);$sn=$h->post_count;if($sn):?>
<article class="hero" data-hero aria-roledescription="carousel" aria-label="Öne çıkan yazılar"><?php $i=0;while($h->have_posts()):$h->the_post();$id=get_the_ID();?>
<div class="sl<?php echo $i?'':' on';?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr(($i+1).' / '.$sn);?>"<?php echo $i?' aria-hidden="true"':'';?>>
<?php if(has_post_thumbnail($id))echo '<div class="hbg" style="background-image:url(\''.esc_url(get_the_post_thumbnail_url($id,'large')).'\')"></div>';else echo '<div class="hbg art">'.yr_post_cover($id).'</div>';?>
<div class="in"><h2 class="h1"><?php the_title();?></h2><p><?php echo esc_html(wp_trim_words(get_the_excerpt(),28));?></p><a class="btn" href="<?php the_permalink();?>"<?php echo $i?' tabindex="-1"':'';?>>Yazıyı Oku</a></div></div>
<?php $i++;endwhile;wp_reset_postdata();?>
<?php if($sn>1):?><div class="hctl"><button type="button" class="hp-prev" aria-label="Önceki yazı">‹</button><span class="hdots"><?php for($k=0;$k<$sn;$k++)echo '<button type="button" class="hd'.($k?'':' on').'" aria-label="'.($k+1).'. yazıya git"></button>';?></span><button type="button" class="hp-next" aria-label="Sonraki yazı">›</button><button type="button" class="hp-pp" aria-label="Otomatik geçişi durdur">❚❚</button></div><?php endif;?>
</article><?php endif;endif;?>

<?php if(is_active_sidebar('home-top'))dynamic_sidebar('home-top');?>
<?php if(trim(wp_strip_all_tags($gi['kalan'],true))!==''||strpos($gi['kalan'],'<img')!==false)echo '<div class="box entry" style="margin-bottom:22px"><div class="content">'.$gi['kalan'].'</div></div>';?>

<div class="grid" id="kesfet"><div>
<?php if(yr_on('kesif')&&($kg=yr_kesif_grup())):?><div class="box kbox"><?php yr_grup_kart($kg->ID,true);?></div><?php endif;?>

<?php if(yr_on('roportaj')):$rq=$rk?new WP_Query(['post_type'=>'post','cat'=>$rk,'posts_per_page'=>3,'ignore_sticky_posts'=>1,'no_found_rows'=>true]):null;?>
<div class="box" style="margin-top:22px"><h2><?php echo esc_html(yr_t('rop_b'));?></h2>
<?php if($rq&&$rq->have_posts()):?><div class="news"><?php while($rq->have_posts()){$rq->the_post();yr_card();}wp_reset_postdata();?></div><a class="more" href="<?php echo esc_url(get_category_link($rk));?>"><?php echo esc_html(yr_t('rop_tum'));?></a>
<?php else:?><div class="bos-rop"><?php echo yr_ikon('cmt');?><div><h3><?php echo esc_html(yr_t('rop_bos_b'));?></h3><p><?php echo esc_html(yr_t('rop_bos_m'));?></p><?php if(yr_t('rop_bos_btn')!=='')echo '<a class="btn" href="'.esc_url($gt).'">'.esc_html(yr_t('rop_bos_btn')).'</a>';?></div></div><?php endif;?>
</div><?php endif;?>

<?php if(yr_on('sahne')):$cats=array_filter(array_map(function($s){$t=get_term_by('slug',$s,'category');return $t?(int)$t->term_id:0;},['kesif','yeni-cikis','konser-duyurusu']));
$sq=$cats?new WP_Query(['post_type'=>'post','category__in'=>$cats,'posts_per_page'=>6,'ignore_sticky_posts'=>1,'no_found_rows'=>true]):null;
$baslik=yr_t('sahne_b');if(!$sq||!$sq->have_posts()){$sq=new WP_Query(['posts_per_page'=>6,'ignore_sticky_posts'=>1,'no_found_rows'=>true]);$baslik=yr_t('sahne_b2');}?>
<div class="box" style="margin-top:22px"><h2><?php echo esc_html($baslik);?></h2><div class="news">
<?php if($sq->have_posts()){while($sq->have_posts()){$sq->the_post();yr_card();}}else{echo '<p>Henüz yazı yok.</p>';}wp_reset_postdata();?>
</div><a class="more" href="<?php echo esc_url(yr_haber_url());?>"><?php echo esc_html(yr_t('sahne_tum'));?></a></div><?php endif;?>

<?php if(yr_on('etkinlik')):?><div class="box" style="margin-top:22px"><h2><?php echo esc_html(yr_t('etk_b'));?></h2><div class="events">
<?php $e=yr_events(4);if($e->have_posts()){while($e->have_posts()){$e->the_post();yr_event_card();}}else{echo '<p class="empty-s">'.esc_html(yr_t('etk_bos')).(yr_t('etk_bos_l')!==''?' <a href="'.esc_url($gt).'" style="color:var(--blue);text-decoration:underline">'.esc_html(yr_t('etk_bos_l')).'</a>':'').'</p>';}wp_reset_postdata();?>
</div><a class="more" href="<?php echo esc_url(get_post_type_archive_link('etkinlik'));?>"><?php echo esc_html(yr_t('etk_tum'));?></a></div><?php endif;?>

<?php if(yr_on('trgrup')&&$tr_say):?><div class="box" style="margin-top:22px"><h2><?php echo esc_html(yr_t('tr_b'));?></h2><div class="bands">
<?php $g=new WP_Query(['post_type'=>'grup','posts_per_page'=>4,'meta_key'=>'_yr_bolge','meta_value'=>'tr','orderby'=>'date','order'=>'DESC','no_found_rows'=>true]);while($g->have_posts()){$g->the_post();yr_band_card();}wp_reset_postdata();?>
</div><a class="more" href="<?php echo esc_url(get_post_type_archive_link('grup'));?>"><?php echo esc_html(yr_t('tr_tum'));?></a></div><?php endif;?>

<?php if(yr_on('hafta'))yr_week_box();?>
</div>
<aside class="side">
<div class="box gt-cta"><h2><?php echo esc_html(yr_t('cta_b'));?></h2><p><?php echo esc_html(yr_t('cta_m'));?></p><?php if(yr_t('cta_btn')!=='')echo '<a class="btn" href="'.esc_url($gt).'">'.esc_html(yr_t('cta_btn')).'</a>';if(yr_t('cta_l')!=='')echo '<p class="note" style="margin:12px 0 0"><a href="'.esc_url(yr_page_url('hakkimizda')).'">'.esc_html(yr_t('cta_l')).'</a></p>';?></div>
<?php $au=!yr_on('yazar')?[]:get_users(['role__in'=>['author','editor','administrator','yazarock_yazar'],'orderby'=>'post_count','order'=>'DESC','number'=>3,'has_published_posts'=>['post']]);if($au):?>
<div class="box"><h2><?php echo esc_html(yr_t('yazar_b'));?></h2>
<?php foreach($au as $u):?><a class="auth" href="<?php echo esc_url(get_author_posts_url($u->ID));?>"><?php echo get_avatar($u->ID,42,'','',['class'=>'av']);?><div><?php echo esc_html($u->display_name);?><small><?php echo (int)count_user_posts($u->ID);?> yazı</small></div></a><?php endforeach;?>
</div><?php endif;?>
<?php $cm=!yr_on('yorum')?[]:get_comments(['number'=>4,'status'=>'approve','post_type'=>'post']);if($cm):?>
<div class="box"><h2><?php echo esc_html(yr_t('yorum_b'));?></h2>
<?php foreach($cm as $c):?><div class="cm"><b><?php echo esc_html($c->comment_author);?>:</b> <a href="<?php echo esc_url(get_comment_link($c));?>"><?php echo esc_html(wp_trim_words($c->comment_content,10));?></a></div><?php endforeach;?>
</div><?php endif;?>
<?php if(yr_on('anket'))yr_poll_box();?>
<?php $qs=yr_quotes();if(yr_on('soz')):?>
<div class="box"><h2><?php echo esc_html(yr_t('soz_b'));?></h2><p class="kq" id="kq" data-q="<?php echo esc_attr(wp_json_encode($qs,JSON_UNESCAPED_UNICODE));?>">“<?php echo esc_html($qs[(int)floor(time()/86400)%count($qs)]);?>”</p><?php if(yr_t('soz_alt')!=='')echo '<small style="color:var(--mute)">'.esc_html(yr_t('soz_alt')).'</small>';?><?php if(count($qs)>1&&yr_t('soz_btn')!=='')echo '<div class="row"><button class="btn alt" id="knext" type="button">'.esc_html(yr_t('soz_btn')).'</button></div>';?></div><?php endif;?>
<?php if(is_active_sidebar('home-side'))dynamic_sidebar('home-side');?>
</aside></div>
<?php if(is_active_sidebar('home-bottom'))dynamic_sidebar('home-bottom');if(yr_on('bulten'))yr_newsletter();get_footer();
