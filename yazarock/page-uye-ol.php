<?php if(!defined('ABSPATH'))exit; get_header();?>
<div class="ph"><h1>Üye Ol</h1><p>Üyelerin kendi favori listelerini oluşturup paylaşabildiği alan.</p></div>
<div class="box" id="uye"><?php yr_flash('yr_u',['tamam'=>['İşlem alındı. E-posta adresine bir mesaj gönderdik; içindeki bağlantıdan şifreni belirleyebilirsin (spam klasörünü de kontrol et).','ok'],'gecersiz'=>['Lütfen alanları kontrol edip tekrar dene.','err'],'bekle'=>['Biraz bekleyip tekrar dene.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenile.','err']]);
if(is_user_logged_in()):?><p>Zaten giriş yaptın. <a href="<?php echo esc_url(yr_page_url('listelerim'));?>">Listelerim</a></p>
<?php else:?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" style="max-width:460px"><input type="hidden" name="action" value="yr_uye"><?php wp_nonce_field('yr_uye','yr_n');?><input class="hp" tabindex="-1" autocomplete="off" name="website">
<label>Görünecek ad<input name="ad" autocomplete="nickname" required minlength="2" maxlength="40"></label>
<label>E-posta<input name="email" type="email" autocomplete="email" required></label>
<label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?php echo esc_url(yr_page_url('kvkk-aydinlatma-metni'));?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum; ad ve e-posta bilgimin üyelik için işlenmesini, listelerimin adımla yayımlanmasını kabul ediyorum.</span></label>
<?php yr_ts_widget();?><button class="btn" type="submit">Üye Ol</button></form>
<p><a href="<?php echo esc_url(yr_page_url('giris'));?>">Zaten üyeyim, giriş yap</a></p><?php endif;?></div>
<?php get_footer();