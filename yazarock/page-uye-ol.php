<?php if(!defined('ABSPATH'))exit; get_header();?>
<?php yr_ph('Üye Ol','Ücretsiz üye ol; beğendiğin yazıları biriktir, kendi listelerini oluştur.');?>
<div class="lgrid">
<div class="box" id="uye"><h2>Hesap oluştur</h2><?php yr_flash('yr_u',['tamam'=>['İşlem alındı.','ok'],'bekle'=>['Biraz bekleyip tekrar dene.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenileyip tekrar dene.','err'],'gecersiz'=>['Lütfen alanları kontrol edip tekrar dene.','err']]);yr_hata_goster();
if(is_user_logged_in()):?><p>Zaten giriş yaptın.</p><p><a class="btn" href="<?php echo esc_url(yr_panel_url());?>">Panelime git</a></p>
<?php else:?>
<form class="fg pform hesapf" id="uyeform" data-hatirla="yr_uye_f" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="yr_uye"><?php wp_nonce_field('yr_uye','yr_n');?><input class="hp" tabindex="-1" autocomplete="off" name="website">
<label>Adın soyadın <small>(sitede görünecek ad)</small><input name="ad" autocomplete="name" required minlength="2" maxlength="40"></label>
<label>Kullanıcı adı<span class="kadi-w"><input name="kadi" data-kadi autocomplete="username" required minlength="3" maxlength="24" pattern="[a-z0-9._\-]{3,24}" placeholder="ör. ecemetal" autocapitalize="none" spellcheck="false"></span><small class="kadi-m note">Küçük harf, rakam, nokta, alt çizgi ve tire · 3-24 karakter</small></label>
<label>E-posta<input name="email" type="email" autocomplete="email" required></label>
<label>Şifre<span class="sw"><input name="sifre" type="password" data-sifre autocomplete="new-password" required minlength="8" maxlength="100"><button type="button" class="goz" aria-label="Şifreyi göster">Göster</button></span><span class="sg" aria-hidden="true"><i></i></span><small class="sg-m note">En az 8 karakter; en az bir harf ve bir rakam</small></label>
<label>Şifre (tekrar)<input name="sifre2" type="password" data-sifre2 autocomplete="new-password" required minlength="8" maxlength="100"></label>
<label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?php echo esc_url(yr_page_url('kvkk-aydinlatma-metni'));?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum; ad, kullanıcı adı ve e-posta bilgimin üyelik için işlenmesini, listelerimin adımla yayımlanmasını kabul ediyorum.</span></label>
<?php yr_ts_widget();?><button class="btn" type="submit">Üye Ol</button>
<p class="note" style="margin-top:12px">Kayıttan sonra hemen giriş yaparsın. E-posta adresine bir onay bağlantısı göndeririz; onaylanmayan hesaplar 7 gün sonra silinir.</p></form>
<?php endif;?></div>
<?php $x=yr_bloklar_html(yr_parca()['bloklar']);if(yr_dolu($x))echo '<div class="box join"><div class="jm">'.$x.'</div>';else echo '<div>';?></div>
</div>
<?php get_footer();
