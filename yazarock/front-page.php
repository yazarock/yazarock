<?php if(!defined('ABSPATH'))exit; get_header();
$s=get_option('sticky_posts');$a=['posts_per_page'=>1,'ignore_sticky_posts'=>1,'no_found_rows'=>true];
if($s)$a['post__in']=$s;else{$np=max(1,min(5,(int)wp_count_posts()->publish));$a['offset']=(int)(floor(time()/21600)%$np);}/* Sabitlenmiş yazı yoksa son 5 yazı arasında 6 saatte bir otomatik değişir */
$h=new WP_Query($a);if(!$h->have_posts()&&!$s){$h=new WP_Query(['posts_per_page'=>1,'ignore_sticky_posts'=>1,'no_found_rows'=>true]);}?>
<?php
$st=array_slice(array_filter((array)get_option('sticky_posts')),0,5);
$ha=['posts_per_page'=>5,'ignore_sticky_posts'=>1,'no_found_rows'=>true];if($st)$ha['post__in']=$st;
$h=new WP_Query($ha);$sn=$h->post_count;?>
<article class="hero" data-hero aria-roledescription="carousel" aria-label="Öne çıkan yazılar"><?php
if($h->have_posts()):$i=0;while($h->have_posts()):$h->the_post();$id=get_the_ID();?>
<div class="sl<?php echo $i?'':' on';?>" role="group" aria-roledescription="slide" aria-label="<?php echo ($i+1).' / '.$sn;?>"<?php echo $i?' aria-hidden="true"':'';?>>
<?php if(has_post_thumbnail($id))echo '<div class="hbg" style="background-image:url(\''.esc_url(get_the_post_thumbnail_url($id,'large')).'\')"></div>';else echo '<div class="hbg art">'.yr_post_cover($id).'</div>';?>
<div class="in"><h1><?php the_title();?></h1><p><?php echo esc_html(wp_trim_words(get_the_excerpt(),28));?></p><a class="btn" href="<?php the_permalink();?>"<?php echo $i?' tabindex="-1"':'';?>>Yazıyı Oku</a></div></div>
<?php $i++;endwhile;wp_reset_postdata();else:?>
<div class="sl on"><div class="in"><h1><?php echo esc_html(get_theme_mod('yr_hero_t',"Anadolu'nun Sesi: Rock Kültürü ve İsyanın Tarihi"));?></h1><p><?php echo esc_html(get_theme_mod('yr_hero_d',"Türkiye'de rock müziğin derin köklerini keşfedin."));?></p><a class="btn" href="<?php echo esc_url(get_theme_mod('yr_hero_u')?get_theme_mod('yr_hero_u'):yr_haber_url());?>"><?php echo esc_html(get_theme_mod('yr_hero_b','Haberleri Oku'));?></a></div></div>
<?php endif;?>
<svg viewBox="0 0 400 430" preserveAspectRatio="xMaxYMid slice" aria-hidden="true"><g fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M390 20 L330 120 L360 125 L290 250 L330 255 L250 410" stroke="#8ec0ff" stroke-width="3"/><path d="M400 90 L350 170 L372 172" stroke="#8ec0ff" stroke-width="2" opacity=".7"/><path d="M200 30 L170 130 L195 134 L150 240" stroke="#e5393f" stroke-width="3"/><path d="M60 60 L90 150 L70 158 L110 260" stroke="#e5393f" stroke-width="2" opacity=".7"/></g></svg>
<?php if($sn>1):?><div class="hctl"><button type="button" class="hp-prev" aria-label="Önceki yazı">‹</button><span class="hdots"><?php for($k=0;$k<$sn;$k++)echo '<button type="button" class="hd'.($k?'':' on').'" aria-label="'.($k+1).'. yazıya git"></button>';?></span><button type="button" class="hp-next" aria-label="Sonraki yazı">›</button><button type="button" class="hp-pp" aria-label="Otomatik geçişi durdur">❚❚</button></div><?php endif;?>
</article>
<?php if(is_active_sidebar('home-top'))dynamic_sidebar('home-top');?>
<?php while(have_posts()){the_post();if(trim(get_the_content())){echo '<div class="box entry" style="margin-bottom:22px"><div class="content">';the_content();echo '</div></div>';}}?>
<div class="grid"><div>
<?php if(yr_on('hafta'))yr_week_box();?>
<div class="box"><h2>Son Yazılar</h2><div class="news">
<?php $q=new WP_Query(['posts_per_page'=>6,'ignore_sticky_posts'=>1,'no_found_rows'=>true]);if($q->have_posts()){while($q->have_posts()){$q->the_post();yr_card();}}else{echo '<p>Henüz yazı yok.</p>';}wp_reset_postdata();?>
</div><a class="more" href="<?php echo esc_url(yr_haber_url());?>">Tüm haberler ve yazılar →</a></div>
<?php if(yr_on('etkinlik')):?><div class="box" style="margin-top:22px"><h2>Yaklaşan Etkinlikler</h2><div class="events">
<?php $e=yr_events(4);if($e->have_posts()){while($e->have_posts()){$e->the_post();yr_event_card();}}else{echo '<p>Yaklaşan etkinlik yok.</p>';}wp_reset_postdata();?>
</div><a class="more" href="<?php echo esc_url(get_post_type_archive_link('etkinlik'));?>">Etkinlik takvimi →</a></div><?php endif;?>
<?php if(yr_on('grup')):?><div class="box" style="margin-top:22px"><h2>Gruplar</h2><div class="bands">
<?php $g=new WP_Query(['post_type'=>'grup','posts_per_page'=>4,'orderby'=>'rand','no_found_rows'=>true]);if($g->have_posts()){while($g->have_posts()){$g->the_post();yr_band_card();}}else{echo '<p>Henüz grup eklenmedi.</p>';}wp_reset_postdata();?>
</div><a class="more" href="<?php echo esc_url(get_post_type_archive_link('grup'));?>">Tüm gruplar →</a></div><?php endif;?>
</div>
<aside class="side">
<?php $au=!yr_on('yazar')?[]:get_users(['role__in'=>['author','editor','administrator','yazarock_yazar'],'orderby'=>'post_count','order'=>'DESC','number'=>3,'has_published_posts'=>['post']]);if($au):?>
<div class="box"><h2>Öne Çıkan Yazarlar</h2>
<?php foreach($au as $u):?><a class="auth" href="<?php echo esc_url(get_author_posts_url($u->ID));?>"><?php echo get_avatar($u->ID,42,'','',['class'=>'av']);?><div><?php echo esc_html($u->display_name);?><small><?php echo (int)count_user_posts($u->ID);?> yazı</small></div></a><?php endforeach;?>
</div><?php endif;?>
<?php $cm=!yr_on('yorum')?[]:get_comments(['number'=>4,'status'=>'approve','post_type'=>'post']);if($cm):?>
<div class="box"><h2>Son Yorumlar</h2>
<?php foreach($cm as $c):?><div class="cm"><b><?php echo esc_html($c->comment_author);?>:</b> <a href="<?php echo esc_url(get_comment_link($c));?>"><?php echo esc_html(wp_trim_words($c->comment_content,10));?></a></div><?php endforeach;?>
</div><?php endif;?>
<?php if(yr_on('anket'))yr_poll_box();?>
<?php $qs=yr_quotes();if(yr_on('soz')):?>
<div class="box"><h2>Günün Sözü</h2><p class="kq" id="kq" data-q="<?php echo esc_attr(wp_json_encode($qs,JSON_UNESCAPED_UNICODE));?>">“<?php echo esc_html($qs[(int)floor(time()/86400)%count($qs)]);?>”</p><small style="color:var(--mute)">Yazarock editörlüğünden</small><div class="row"><button class="btn alt" id="knext" type="button">Başka Bir Söz</button></div></div><?php endif;?>
<?php if(is_active_sidebar('home-side'))dynamic_sidebar('home-side');?>
</aside></div>
<?php if(is_active_sidebar('home-bottom'))dynamic_sidebar('home-bottom');if(yr_on('bulten'))yr_newsletter();get_footer();