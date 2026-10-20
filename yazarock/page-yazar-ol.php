<?php if(!defined('ABSPATH'))exit; /* Template Name: Yazar Ol */ get_header();$u=wp_get_current_user();$giris=$u->exists();?>
<?php yr_ph('Yazar Ol','Rock ve metal hakkında yazmak mı istiyorsun? Kendini tanıt, ilk yazı fikrini paylaş.');yr_sayfa_ek();?>
<div class="box ybasvuru" id="basvuru-form"><h2>Yazarlık Başvurusu</h2><?php yr_hata_goster();
yr_flash('yr_y',['tamam'=>['Başvurun alındı! Değerlendirip e-postayla dönüş yapacağız.','ok'],'onaylandi'=>['E-posta adresin onaylandı, başvurun değerlendirmeye alındı. Teşekkürler!','ok'],'bekle'=>['Biraz bekleyip tekrar dene.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenileyip tekrar dene.','err'],'uye'=>['Yazarlık başvurusu yapmak için önce üye olup giriş yapmalısın.','err']]);
if(!$giris):?>
<p>Yazarlık başvurusu yalnızca Yazarock üyelerine açık. Ücretsiz üye ol ya da hesabınla giriş yap; ardından bu sayfadan başvurabilirsin.</p>
<div class="btnrow"><a class="btn" href="<?php echo esc_url(yr_page_url('uye-ol'));?>">Ücretsiz Üye Ol</a><a class="btn alt" href="<?php echo esc_url(add_query_arg('redirect_to',rawurlencode(yr_page_url('yazar-ol')),yr_page_url('giris')));?>">Giriş Yap</a></div>
<?php elseif(yr_is_writer($u)):?><p>Zaten Yazarock yazarısın.</p><div class="btnrow"><a class="btn" href="<?php echo esc_url(admin_url('post-new.php'));?>">Yeni Yazı</a><a class="btn alt" href="<?php echo esc_url(yr_panel_url());?>">Panelim</a></div>
<?php elseif(!in_array($_GET['yr_y']??'',['tamam','onaylandi'],true)):?>
<form class="pform hesapf" id="ap" data-hatirla="yr_yazar_f" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="yr_yazar_basvuru"><?php wp_nonce_field('yr_yazar','yr_n');?>
<input class="hp" tabindex="-1" autocomplete="off" name="website">
<p class="note">Başvurun <b><?php echo esc_html($u->display_name);?></b> (<?php echo esc_html($u->user_email);?>) hesabınla yapılacak. Onaylandığında hesabın yazar hesabına dönüşür; aynı kullanıcı adı ve şifreyle giriş yaparsın.</p>
<div class="g2"><label>Alan<select name="alan"><option>Underground &amp; Yeni Gruplar</option><option>Röportaj</option><option>Konser &amp; Festival</option><option>Albüm İncelemesi</option><option>Grup Biyografisi</option><option>Türkiye Rock Tarihi</option></select></label>
<label>Örnek yazı bağlantısı <small>(isteğe bağlı)</small><input name="link" type="url" placeholder="https://"></label></div>
<label>Kendini ve yazmak istediğin konuları anlat<textarea name="msg" rows="6" minlength="40" maxlength="1000" required></textarea></label>
<label class="chk"><input type="checkbox" name="ok" value="1" required> <span>Yazılarımın Yazarock'ta yayımlanabileceğini kabul ederim.</span></label>
<label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?php echo esc_url(yr_page_url('kvkk-aydinlatma-metni'));?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum; kişisel verilerimin başvurum kapsamında işlenmesini kabul ediyorum.</span></label>
<?php yr_ts_widget();?><button class="btn" type="submit">Başvuruyu Gönder</button></form>
<?php endif;?></div>
<?php get_footer();
