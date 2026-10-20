<?php if(!defined('ABSPATH'))exit; get_header();?>
<?php if(is_home()&&!is_front_page()&&($hp=(int)get_option('page_for_posts'))){yr_ph(yr_title(),'Türkiye ve dünyadan rock ve metal gündemi, incelemeler ve rehberler.',$hp);}else{?><div class="ph"><h1><?php echo esc_html(yr_title());?></h1><p><?php echo is_category()?wp_kses_post(category_description()):'Türkiye ve dünyadan rock ve metal gündemi, incelemeler ve rehberler.';?></p></div><?php }?>
<div class="box"><?php get_search_form();?>
<div class="tabs"><a href="<?php echo esc_url(yr_haber_url());?>" class="<?php echo is_home()?'on':'';?>">Tümü</a>
<?php foreach(get_categories(['hide_empty'=>true]) as $c):if($c->slug==='uncategorized')continue;?><a href="<?php echo esc_url(get_category_link($c));?>" class="<?php echo is_category($c->term_id)?'on':'';?>"><?php echo esc_html($c->name);?></a><?php endforeach;?></div>
<div class="news"><?php if(have_posts()){while(have_posts()){the_post();if(get_post_type()==='post')yr_card();elseif(get_post_type()==='grup')yr_band_card();else yr_event_card();}}else{echo '<p>Sonuç bulunamadı.</p>';}?></div>
<?php the_posts_pagination(['mid_size'=>1,'prev_text'=>'←','next_text'=>'→']);?></div>
<?php get_footer();