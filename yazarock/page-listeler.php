<?php if(!defined('ABSPATH'))exit; get_header();$pg=max(1,(int)get_query_var('paged'),(int)get_query_var('page'));
$q=new WP_Query(['post_type'=>'liste','post_status'=>'publish','posts_per_page'=>12,'paged'=>$pg]);?>
<div class="ph"><h1>Okuyucu Listeleri</h1><p>Okuyucularımızın hazırladığı favori listeleri. <a href="<?php echo esc_url(yr_page_url('listelerim'));?>" style="color:var(--blue);text-decoration:underline">Sen de liste oluştur</a></p></div>
<div class="box"><div class="bands"><?php if($q->have_posts()){while($q->have_posts()){$q->the_post();$it=yr_list_items(get_post());
echo '<a class="band" href="'.esc_url(get_permalink()).'"><div class="bb"><h3>'.esc_html(get_the_title()).'</h3><em>'.esc_html(get_the_author()).' · '.count($it).' madde</em><p>'.esc_html(implode(' · ',array_slice($it,0,3))).'…</p></div></a>';}}else echo '<p>Henüz yayımlanmış liste yok.</p>';?></div>
<?php echo paginate_links(['total'=>$q->max_num_pages,'current'=>$pg,'type'=>'list']);wp_reset_postdata();?></div>
<?php get_footer();