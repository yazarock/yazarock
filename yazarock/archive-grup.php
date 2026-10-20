<?php if(!defined('ABSPATH'))exit; get_header();$terms=get_terms(['taxonomy'=>'grup_tur','hide_empty'=>true]);?>
<div class="ph"><h1><?php echo esc_html(get_theme_mod('yr_t_grup_b','Gruplar'));?></h1><p><?php echo esc_html(get_theme_mod('yr_t_grup_a',"Türkiye'nin bağımsız rock ve metal grupları ve türün ustaları."));?> <a href="<?php echo esc_url(yr_page_url('grubunu-tanit'));?>">Grubunu ekletmek ister misin?</a></p></div>
<div class="box" data-filter>
<div class="fb"><input type="search" data-search placeholder="Grup ara" aria-label="Grup ara"></div>
<div class="tabs" data-k="r"><button data-v="all" aria-pressed="true">Tümü</button><button data-v="tr" aria-pressed="false">Türkiye</button><button data-v="world" aria-pressed="false">Dünya</button></div>
<?php if($terms&&!is_wp_error($terms)):?><div class="tabs" data-k="g"><button data-v="all" aria-pressed="true">Tüm Türler</button><?php foreach($terms as $t):?><button data-v="<?php echo esc_attr($t->slug);?>" aria-pressed="false"><?php echo esc_html($t->name);?></button><?php endforeach;?></div><?php endif;?>
<div class="bands"><?php while(have_posts()){the_post();yr_band_card();}?></div>
<p class="empty" <?php echo $wp_query->post_count?'hidden':'';?>>Sonuç bulunamadı.</p></div>
<?php get_footer();