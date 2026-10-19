<?php if(!defined('ABSPATH'))exit; get_header();$a=get_queried_object();$id=$a->ID;$ln=[];
foreach(['yr_twitter'=>'X','yr_instagram'=>'Instagram','yr_web'=>'Web'] as $k=>$l){$u=get_user_meta($id,$k,true);if($u)$ln[$l]=$u;}?>
<article class="box entry aut"><?php echo get_avatar($id,120,'','',['class'=>'avl']);?>
<div><h1><?php echo esc_html($a->display_name);?></h1>
<?php if($a->description)echo '<p>'.esc_html($a->description).'</p>';?>
<div class="btnrow"><?php foreach($ln as $l=>$u)echo '<a class="btn alt" href="'.esc_url($u).'" rel="noopener nofollow" target="_blank">'.esc_html($l).'</a>';?></div>
<small style="color:var(--mute)"><?php echo (int)count_user_posts($id);?> yazı</small></div></article>
<div class="box" style="margin-top:22px"><h2>Yazıları</h2><div class="news"><?php if(have_posts()){while(have_posts()){the_post();yr_card();}}else{echo '<p>Henüz yazı yok.</p>';}?></div>
<?php the_posts_pagination(['mid_size'=>1,'prev_text'=>'←','next_text'=>'→']);?></div>
<?php get_footer();