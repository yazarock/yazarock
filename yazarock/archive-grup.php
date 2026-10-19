<?php if(!defined('ABSPATH'))exit; get_header();$terms=get_terms(['taxonomy'=>'grup_tur','hide_empty'=>true]);?>
<div class="ph"><h1>Gruplar</h1><p>Türkiye ve dünyadan rock ile metalin önemli isimleri.</p></div>
<div class="box" data-filter>
<div class="fb"><input type="search" data-search placeholder="Grup ara" aria-label="Grup ara"></div>
<div class="tabs" data-k="r"><button data-v="all" aria-pressed="true">Tümü</button><button data-v="tr" aria-pressed="false">Türkiye</button><button data-v="world" aria-pressed="false">Dünya</button></div>
<?php if($terms&&!is_wp_error($terms)):?><div class="tabs" data-k="g"><button data-v="all" aria-pressed="true">Tüm Türler</button><?php foreach($terms as $t):?><button data-v="<?php echo esc_attr($t->slug);?>" aria-pressed="false"><?php echo esc_html($t->name);?></button><?php endforeach;?></div><?php endif;?>
<div class="bands"><?php while(have_posts()){the_post();yr_band_card();}?></div>
<p class="empty" <?php echo $wp_query->post_count?'hidden':'';?>>Sonuç bulunamadı.</p></div>
<?php get_footer();