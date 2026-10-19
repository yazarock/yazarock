<?php if(!defined('ABSPATH'))exit; get_header();$a=get_queried_object();$id=(int)$a->ID;$s=yr_yazar_ist($id);$pg=max(1,(int)get_query_var('paged'));
$kat=yr_yazar_kategoriler($id,6);?>
<section class="phead ahead">
<div class="phead-bg" aria-hidden="true"></div>
<div class="phead-in">
<?php echo get_avatar($id,128,'','',['class'=>'pav']);?>
<div class="pmeta"><span class="pill"><?php echo esc_html(yr_rol_adi($a));?></span>
<h1><?php echo esc_html($a->display_name);?></h1>
<p class="psub"><?php echo yr_ikon('clock');?>Yazarock'ta <?php echo esc_html(date_i18n('F Y',strtotime($a->user_registered)));?> tarihinden beri<?php echo yr_sosyal($id)?'<span class="psoc">'.yr_sosyal($id).'</span>':'';?></p>
<?php if($a->description)echo '<p class="pbio">'.esc_html($a->description).'</p>';?>
<?php if($kat){echo '<p class="kchips">';foreach($kat as $k)echo '<a href="'.esc_url(get_category_link($k[0])).'">'.esc_html($k[0]->name).' <b>'.(int)$k[1].'</b></a>';echo '</p>';}?>
</div>
<?php if(get_current_user_id()===$id)echo '<div class="pact"><a class="btn alt" href="'.esc_url(yr_panel_url('profil')).'">'.yr_ikon('pen').'Profili Düzenle</a></div>';?>
</div></section>
<div class="pst pst4"><?php foreach([['Yazı',$s['yazi'],'doc'],['Okunma',$s['okunma'],'eye'],['Beğeni',$s['begeni'],'up'],['Yorum',$s['yorum'],'cmt']] as $x)echo '<div>'.yr_ikon($x[2]).'<b>'.esc_html(yr_sayi($x[1])).'</b><span>'.esc_html($x[0]).'</span></div>';?></div>
<?php if($pg===1&&$s['yazi']>3):
	$top=new WP_Query(['post_type'=>'post','author'=>$id,'posts_per_page'=>3,'meta_key'=>'_yr_like','orderby'=>['meta_value_num'=>'DESC','date'=>'DESC'],'ignore_sticky_posts'=>1,'no_found_rows'=>true]);
	if($top->post_count>0&&yr_meta_toplam($id,'_yr_like')>0):?><div class="box" style="margin-bottom:22px"><h2>En Beğenilen Yazıları</h2><div class="news"><?php while($top->have_posts()){$top->the_post();yr_card();}?></div></div><?php endif;wp_reset_postdata();
endif;?>
<div class="box"><h2><?php echo $pg>1?'Yazıları · Sayfa '.(int)$pg:'Tüm Yazıları';?></h2><div class="news"><?php if(have_posts()){while(have_posts()){the_post();yr_card();}}else{echo '<p class="empty-s">Henüz yayımlanmış yazısı yok.</p>';}?></div>
<?php the_posts_pagination(['mid_size'=>1,'prev_text'=>'←','next_text'=>'→']);?></div>
<p style="margin:18px 0 0"><a class="more" href="<?php echo esc_url(yr_page_url('yazarlar'));?>">← Tüm yazarlar</a></p>
<?php get_footer();
