<?php if(!defined('ABSPATH'))exit; get_header();
$t=get_queried_object();$il=$t->parent?get_term($t->parent,'grup_sehir'):$t;
$ad=$t->name;$ilad=$il&&!is_wp_error($il)?$il->name:$ad;
$alt=get_terms(['taxonomy'=>'grup_sehir','parent'=>$il->term_id,'hide_empty'=>true]);
$terms=get_terms(['taxonomy'=>'grup_tur','hide_empty'=>true]);
global $wp_query;$n=(int)$wp_query->found_posts;?>
<div class="ph"><p class="sh-yol"><a href="<?php echo esc_url(get_post_type_archive_link('grup'));?>">Gruplar</a><?php if($t->parent)echo ' / <a href="'.esc_url(get_term_link($il)).'">'.esc_html($ilad).'</a>';?></p>
<h1><?php echo esc_html($ad);?> rock ve metal grupları</h1>
<p><?php echo $t->description!==''?wp_kses_post($t->description):esc_html($ad.yr_ek($ad,'den').' '.$n.' bağımsız rock ve metal grubu. '.($t->parent?$ilad.yr_ek($ilad,'in'):'Şehrin').' yeraltı sahnesini tanı, yaklaşan konserlere göz at.');?></p></div>
<?php if($alt&&!is_wp_error($alt)):?><div class="tabs sh-semt"><a class="<?php echo $t->parent?'':'on';?>" href="<?php echo esc_url(get_term_link($il));?>">Tüm <?php echo esc_html($ilad);?></a><?php foreach($alt as $a)echo '<a class="'.($a->term_id===$t->term_id?'on':'').'" href="'.esc_url(get_term_link($a)).'">'.esc_html($a->name).' <small>'.(int)$a->count.'</small></a>';?></div><?php endif;?>
<div class="box" data-filter>
<div class="fb"><input type="search" data-search placeholder="<?php echo esc_attr($ad);?> grupları arasında ara" aria-label="Grup ara"></div>
<?php if($terms&&!is_wp_error($terms)):?><div class="tabs" data-k="g"><button data-v="all" aria-pressed="true">Tüm Türler</button><?php foreach($terms as $g):?><button data-v="<?php echo esc_attr($g->slug);?>" aria-pressed="false"><?php echo esc_html($g->name);?></button><?php endforeach;?></div><?php endif;?>
<div class="bands"><?php while(have_posts()){the_post();yr_band_card();}?></div>
<p class="empty" <?php echo $wp_query->post_count?'hidden':'';?>>Sonuç bulunamadı.</p></div>
<?php /* Bu şehirde yaklaşan konserler */
$ev=new WP_Query(['post_type'=>'etkinlik','posts_per_page'=>6,'no_found_rows'=>true,'meta_key'=>'_yr_tarih','orderby'=>'meta_value','order'=>'ASC','meta_query'=>['relation'=>'AND',['key'=>'_yr_tarih','value'=>current_time('Y-m-d'),'compare'=>'>=','type'=>'DATE'],['key'=>'_yr_sehir','value'=>$ilad,'compare'=>'LIKE']]]);
if($ev->have_posts()):?><div class="box" style="margin-top:22px"><h2><?php echo esc_html($ilad.yr_ek($ilad,'de'));?> yaklaşan konserler</h2><div class="events"><?php while($ev->have_posts()){$ev->the_post();yr_event_card();}?></div><a class="more" href="<?php echo esc_url(get_post_type_archive_link('etkinlik'));?>">Tüm konser takvimi →</a></div><?php endif;wp_reset_postdata();?>
<div class="box gt-cta" style="margin-top:22px"><h2><?php echo esc_html($ilad.yr_ek($ilad,'den'));?> bir grubun mu var?</h2><p>Haritada yerini al: grubunu tanıt ya da röportaj sorularımızı yanıtla.</p><div class="btnrow"><a class="btn" href="<?php echo esc_url(yr_page_url('grubunu-tanit'));?>">Grubunu Tanıt</a><a class="btn alt" href="<?php echo esc_url(yr_page_url('garajdan-sahneye'));?>">Garajdan Sahneye</a></div></div>
<?php get_footer();
