<?php if(!defined('ABSPATH'))exit; get_header();while(have_posts()):the_post();$it=yr_list_items(get_post());?>
<article class="box entry"><span class="pill">Okuyucu Listesi</span><h1><?php the_title();?></h1>
<div class="meta"><?php echo esc_html(get_the_author());?> · <?php echo esc_html(get_the_date('j F Y'));?></div>
<ol class="rl"><?php foreach($it as $x)echo '<li>'.esc_html($x).'</li>';?></ol>
<div class="btnrow"><a class="btn alt" href="<?php echo esc_url(yr_page_url('listeler'));?>">← Tüm Listeler</a></div></article>
<?php endwhile;get_footer();