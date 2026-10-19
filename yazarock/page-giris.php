<?php if(!defined('ABSPATH'))exit; /* Template Name: Giriş ve Yazar Paneli */ get_header();?>
<div class="ph"><h1><?php echo is_user_logged_in()?'Yazar Paneli':'Giriş Yap';?></h1><p><?php echo is_user_logged_in()?'Yazılarını buradan takip et.':'Yazarlar ve editörler için giriş.';?></p></div>
<?php while(have_posts()){the_post();if(trim(get_the_content())){echo '<div class="box entry" style="margin-bottom:22px"><div class="content">';the_content();echo '</div></div>';}}?>
<div class="box">
<?php if(yr_is_member()):?>
<h2>Hoş geldin</h2><p>Üyesin. Kendi listelerini oluşturabilirsin.</p><div class="btnrow"><a class="btn" href="<?php echo esc_url(yr_page_url('listelerim'));?>">Listelerim</a><a class="btn alt" href="<?php echo esc_url(wp_logout_url(home_url('/')));?>">Çıkış</a></div>
<?php elseif(is_user_logged_in()):$u=wp_get_current_user();
$cnt=function($s)use($u){$q=new WP_Query(['author'=>$u->ID,'post_status'=>$s,'posts_per_page'=>1,'fields'=>'ids']);return (int)$q->found_posts;};
$ST=['publish'=>'Yayında','pending'=>'İncelemede','draft'=>'Taslak'];?>
<h2>Hoş geldin, <?php echo esc_html($u->display_name);?></h2>
<div class="panel"><div><b><?php echo $cnt('publish');?></b>Yayında</div><div><b><?php echo $cnt('pending');?></b>İncelemede</div><div><b><?php echo $cnt('draft');?></b>Taslak</div></div>
<div class="btnrow"><a class="btn" href="<?php echo esc_url(admin_url('post-new.php'));?>">Yeni Yazı</a><a class="btn alt" href="<?php echo esc_url(admin_url('edit.php'));?>">Yazılarım</a><a class="btn alt" href="<?php echo esc_url(admin_url('profile.php'));?>">Profilim</a><a class="btn alt" href="<?php echo esc_url(wp_logout_url(home_url('/')));?>">Çıkış</a></div>
<h2 style="margin-top:20px">Son Yazılarım</h2><ul class="plist">
<?php $ps=get_posts(['author'=>$u->ID,'post_status'=>['publish','pending','draft'],'numberposts'=>8]);foreach($ps as $p):?><li><a href="<?php echo esc_url(get_edit_post_link($p->ID));?>"><?php echo esc_html(get_the_title($p)?get_the_title($p):'(Başlıksız)');?></a><span class="pill"><?php echo esc_html($ST[$p->post_status]??$p->post_status);?></span></li><?php endforeach;if(!$ps)echo '<li>Henüz yazın yok.</li>';?></ul>
<p style="color:var(--mute);font-size:14px">Yazıların yayına alınmadan önce editör tarafından incelenir.</p>
<?php else:?>
<div style="max-width:420px"><?php if(($_GET['login']??'')==='failed')echo '<div class="notice err" role="alert">Kullanıcı adı veya şifre hatalı.</div>';
wp_login_form(['redirect'=>home_url('/giris/'),'label_username'=>'Kullanıcı adı veya e-posta','label_password'=>'Şifre','label_remember'=>'Beni hatırla','label_log_in'=>'Giriş Yap']);?>
<p><a href="<?php echo esc_url(wp_lostpassword_url());?>">Şifremi unuttum</a> · <a href="<?php echo esc_url(yr_page_url('uye-ol'));?>">Üye ol</a> · <a href="<?php echo esc_url(home_url('/yazar-ol/'));?>">Yazar olmak istiyorum</a></p></div>
<?php endif;?></div>
<?php get_footer();