<?php if(!defined('ABSPATH'))exit; /* Template Name: Yazar Ol */ get_header();?>
<div class="ph"><h1>Yazar Ol</h1><p>Rock ve metal hakkında yazmak mı istiyorsun? Kendini tanıt, ilk yazı fikrini paylaş.</p></div>
<?php while(have_posts()){the_post();if(trim(get_the_content())){echo '<div class="box entry" style="margin-bottom:22px"><div class="content">';the_content();echo '</div></div>';}}?>
<div class="box" id="basvuru-form"><h2>Yazarlık Başvurusu</h2><?php yr_notice('basvuru');?>
<form id="ap" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="yr_form"><input type="hidden" name="t" value="basvuru"><?php wp_nonce_field('yr_form','yr_n');?>
<input class="hp" tabindex="-1" autocomplete="off" name="website">
<div class="fg"><label>Ad Soyad<input name="ad" autocomplete="name" required minlength="3"></label><label>E-posta<input name="email" type="email" autocomplete="email" required></label>
<label>Alan<select name="alan"><option>Konser &amp; Festival</option><option>Albüm İncelemesi</option><option>Grup Biyografisi</option><option>Metal &amp; Underground</option><option>Türkiye Rock Tarihi</option></select></label>
<label>Örnek yazı bağlantısı (isteğe bağlı)<input name="link" type="url" placeholder="https://"></label></div>
<label>Kendini ve yazmak istediğin konuları anlat<textarea name="msg" rows="5" minlength="40" maxlength="600" required></textarea></label>
<label class="chk"><input type="checkbox" name="ok" value="1" required> Yazılarımın Yazarock'ta yayımlanabileceğini kabul ederim.</label>
<label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?php echo esc_url(yr_page_url('kvkk-aydinlatma-metni'));?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum; kişisel verilerimin başvurum kapsamında işlenmesini kabul ediyorum.</span></label>
<?php yr_ts_widget();?><button class="btn" type="submit">Başvuruyu Gönder</button></form></div>
<?php get_footer();