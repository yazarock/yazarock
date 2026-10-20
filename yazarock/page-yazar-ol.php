<?php if(!defined('ABSPATH'))exit; /* Template Name: Yazar Ol */ get_header();$u=wp_get_current_user();$giris=$u->exists();?>
<div class="ph"><h1>Yazar Ol</h1><p>Rock ve metal hakkında yazmak mı istiyorsun? Kendini tanıt, ilk yazı fikrini paylaş.</p></div>
<?php while(have_posts()){the_post();if(trim(get_the_content())){echo '<div class="box entry" style="margin-bottom:22px"><div class="content">';the_content();echo '</div></div>';}}?>
<div class="box" id="basvuru-form"><h2>Yazarlık Başvurusu</h2><?php yr_notice('basvuru');yr_hata_goster();
yr_flash('yr_y',['tamam'=>['Başvurun alındı! Değerlendirip e-postayla dönüş yapacağız.','ok'],'onay'=>['Başvurun alındı. Değerlendirmeye alınması için e-postana gönderdiğimiz bağlantıya tıklayarak adresini onayla (spam klasörünü de kontrol et).','ok'],'onaylandi'=>['E-posta adresin onaylandı, başvurun değerlendirmeye alındı. Teşekkürler!','ok'],'bekle'=>['Biraz bekleyip tekrar dene.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenileyip tekrar dene.','err']]);
if($giris&&yr_is_writer($u)):?><p>Zaten Yazarock yazarısın.</p><p><a class="btn" href="<?php echo esc_url(admin_url('post-new.php'));?>">Yeni Yazı</a> <a class="btn alt" href="<?php echo esc_url(yr_panel_url());?>">Panelim</a></p>
<?php elseif(!in_array($_GET['yr_y']??'',['tamam','onay','onaylandi'],true)):?>
<form class="fg pform hesapf" id="ap" data-hatirla="yr_yazar_f" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="yr_yazar_basvuru"><?php wp_nonce_field('yr_yazar','yr_n');?>
<input class="hp" tabindex="-1" autocomplete="off" name="website">
<?php if($giris):?>
<p class="note">Başvurun <b><?php echo esc_html($u->display_name);?></b> (<?php echo esc_html($u->user_email);?>) hesabınla yapılacak. Onaylandığında hesabın yazar hesabına dönüşür; aynı kullanıcı adı ve şifreyle giriş yaparsın.</p>
<?php else:?>
<fieldset><legend><span>1</span>Hesap bilgileri</legend>
<p class="note">Zaten üye misin? <a href="<?php echo esc_url(add_query_arg('redirect_to',rawurlencode(yr_page_url('yazar-ol')),yr_page_url('giris')));?>">Önce giriş yap</a>, başvurun hesabınla ilişkilendirilsin.</p>
<div class="g2"><label>Ad Soyad<input name="ad" autocomplete="name" required minlength="3" maxlength="60"></label><label>E-posta<input name="email" type="email" autocomplete="email" required></label></div>
<label>Kullanıcı adı<span class="kadi-w"><input name="kadi" data-kadi autocomplete="username" required minlength="3" maxlength="24" pattern="[a-z0-9._\-]{3,24}" placeholder="ör. denizkaya" autocapitalize="none" spellcheck="false"></span><small class="kadi-m note">Küçük harf, rakam, nokta, alt çizgi ve tire · 3-24 karakter. Yazar sayfanın adresinde de görünür.</small></label>
<div class="g2"><label>Şifre<span class="sw"><input name="sifre" type="password" data-sifre autocomplete="new-password" required minlength="8" maxlength="100"><button type="button" class="goz" aria-label="Şifreyi göster">Göster</button></span><span class="sg" aria-hidden="true"><i></i></span><small class="sg-m note">En az 8 karakter; en az bir harf ve bir rakam</small></label>
<label>Şifre (tekrar)<input name="sifre2" type="password" data-sifre2 autocomplete="new-password" required minlength="8" maxlength="100"></label></div>
<p class="note">Başvurun onaylandığında bu kullanıcı adı ve şifreyle giriş yaparsın; ayrıca şifre e-postası gönderilmez.</p>
</fieldset>
<?php endif;?>
<fieldset><legend><span><?php echo $giris?'1':'2';?></span>Başvuru</legend>
<div class="g2"><label>Alan<select name="alan"><option>Underground &amp; Yeni Gruplar</option><option>Röportaj</option><option>Konser &amp; Festival</option><option>Albüm İncelemesi</option><option>Grup Biyografisi</option><option>Türkiye Rock Tarihi</option></select></label>
<label>Örnek yazı bağlantısı (isteğe bağlı)<input name="link" type="url" placeholder="https://"></label></div>
<label>Kendini ve yazmak istediğin konuları anlat<textarea name="msg" rows="6" minlength="40" maxlength="1000" required></textarea></label>
<label class="chk"><input type="checkbox" name="ok" value="1" required> <span>Yazılarımın Yazarock'ta yayımlanabileceğini kabul ederim.</span></label>
<label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?php echo esc_url(yr_page_url('kvkk-aydinlatma-metni'));?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum; kişisel verilerimin başvurum kapsamında işlenmesini kabul ediyorum.</span></label>
</fieldset>
<?php yr_ts_widget();?><button class="btn" type="submit">Başvuruyu Gönder</button></form>
<?php endif;?></div>
<?php get_footer();
