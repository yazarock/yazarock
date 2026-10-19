<?php if(!defined('ABSPATH'))exit; get_header();?>
<section class="gt-hero">
<div class="gt-in">
<span class="pill">Ücretsiz · Türkiye'den bağımsız gruplar</span>
<h1>Grubunu Tanıt</h1>
<p class="gt-lead">Yazarock, Türkiye'de rock ve metal yapan bilinmeyen, tanınmayan grupların sesi olmak için var. Garajda, prova stüdyosunda, küçük sahnelerde müzik üretiyorsan seni dinlemek, seninle konuşmak ve okurlarımıza tanıtmak istiyoruz.</p>
<ol class="gt-steps">
<li><b>1</b><span><strong>Formu doldur</strong>Grubunu, müziğini ve hikâyeni anlat.</span></li>
<li><b>2</b><span><strong>Dinliyoruz</strong>Gönderilen her başvuruyu dinliyoruz.</span></li>
<li><b>3</b><span><strong>Sahne senin</strong>Röportaj, inceleme ya da tanıtım için sana yazıyoruz.</span></li>
</ol>
</div></section>
<?php while(have_posts()){the_post();if(trim(get_the_content())){echo '<div class="box entry" style="margin:22px 0"><div class="content">';the_content();echo '</div></div>';}}?>
<div class="gt-grid">
<div class="box" id="basvuru">
<?php yr_flash('yr_g',['tamam'=>['Başvurun bize ulaştı, teşekkürler! E-posta adresine bir onay mesajı gönderdik. Uygun bulduğumuzda bu adresten sana yazacağız.','ok'],'gecersiz'=>['Lütfen zorunlu alanları kontrol et: grup adı, şehir, tür, en az 80 karakterlik hikâye, en az bir dinleme bağlantısı, ad, e-posta ve iki onay kutusu.','err'],'tekrar'=>['Bu grup için zaten değerlendirmede olan bir başvuru var. Yeni bir şey eklemek istersen info@yazarock.com adresine yazabilirsin.','err'],'bekle'=>['Biraz bekleyip tekrar dene.','err'],'limit'=>['Bugün bu bağlantıdan çok fazla başvuru yapıldı; yarın tekrar dene.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenileyip tekrar dene.','err'],'hata'=>['Bir sorun oluştu, tekrar dene.','err']]);
if(($_GET['yr_g']??'')==='tamam'):?>
<div class="gt-ok"><?php echo yr_ikon('heart');?><h2>Sahnede görüşmek üzere!</h2><p>Bu arada sitede tanıttığımız diğer gruplara göz atabilirsin.</p><div class="btnrow" style="justify-content:center"><a class="btn" href="<?php echo esc_url(get_post_type_archive_link('grup'));?>">Gruplar</a><a class="btn alt" href="<?php echo esc_url(remove_query_arg('yr_g'));?>">Başka bir grup için başvur</a></div></div>
<?php else:?>
<form class="fg pform gtf" id="gtform" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="yr_grup_basvuru"><?php wp_nonce_field('yr_gb','yr_n');?><input class="hp" tabindex="-1" autocomplete="off" name="website">
<fieldset><legend><span>1</span>Grup</legend>
<div class="g2"><label>Grup adı *<input name="grup" required minlength="2" maxlength="80"></label>
<label>Şehir *<input name="sehir" list="gt-sehir" required minlength="2" maxlength="40" placeholder="ör. İzmir"></label></div>
<datalist id="gt-sehir"><?php foreach(yr_gb_sehirler() as $s)echo '<option value="'.esc_attr($s).'">';?></datalist>
<div class="g2"><label>Tür *<input name="tur" required minlength="2" maxlength="80" placeholder="ör. Stoner rock, Thrash metal"></label>
<label>Kuruluş yılı<input name="yil" inputmode="numeric" pattern="(19[6-9][0-9]|20[0-9][0-9])" maxlength="4" placeholder="ör. 2019"></label></div>
<label>Üyeler<textarea name="uyeler" rows="3" maxlength="600" placeholder="Her satıra bir kişi: Ad – enstrüman"></textarea></label>
<label>Grubun hikâyesi * <small>(<span id="gtcnt">0</span>/2000, en az 80)</small><textarea name="hikaye" id="gthk" rows="6" required minlength="80" maxlength="2000" placeholder="Nasıl bir araya geldiniz, neyi anlatıyorsunuz, sizi etkileyen gruplar kimler, şimdiye kadar neler yaptınız?"></textarea></label>
</fieldset>
<fieldset><legend><span>2</span>Müzik</legend>
<p class="note">En az bir bağlantı zorunlu. Kendi bestelerinizin yer aldığı kayıtları paylaşın.</p>
<div class="g2"><?php foreach(yr_gb_linkler() as $k=>$l)echo '<label>'.esc_html($l).'<input type="url" name="l_'.esc_attr($k).'" placeholder="https://"></label>';?></div>
<div class="g2"><label>Son çıkış (albüm, EP ya da tekli)<input name="son" maxlength="120" placeholder="ör. Karanlık Şehir EP (2025)"></label>
<label>Yaklaşan konser<input name="konser" maxlength="200" placeholder="ör. 14 Kasım, İzmir, Mekân adı"></label></div>
<label>Grup fotoğrafı <small>(isteğe bağlı · JPG, PNG, WEBP · en fazla 5 MB)</small><input type="file" name="foto" id="gtfoto" accept="image/jpeg,image/png,image/webp"></label>
<p class="note">Bizden ne istersiniz?</p>
<div class="gt-chips"><?php foreach(yr_gb_istekler() as $k=>$l)echo '<label class="chip"><input type="checkbox" name="istek[]" value="'.esc_attr($k).'"'.($k==='tanitim'?' checked':'').'><span>'.esc_html($l).'</span></label>';?></div>
</fieldset>
<fieldset><legend><span>3</span>İletişim</legend>
<div class="g2"><label>Adın soyadın *<input name="ad" required minlength="2" maxlength="60" autocomplete="name"></label>
<label>Gruptaki rolün<input name="rol" maxlength="40" placeholder="ör. Vokal, menajer"></label></div>
<div class="g2"><label>E-posta *<input name="email" type="email" required autocomplete="email"></label>
<label>Telefon <small>(isteğe bağlı)</small><input name="tel" type="tel" maxlength="25" autocomplete="tel"></label></div>
<p class="note">İletişim bilgilerin yayımlanmaz; yalnızca sana ulaşmak için kullanılır.</p>
<label class="chk"><input type="checkbox" name="hak" value="1" required> <span>Paylaştığım müziğin ve fotoğrafın haklarına sahip olduğumu (ya da grubun iznini aldığımı) ve bunların Yazarock'ta grubun tanıtımı amacıyla kullanılabileceğini kabul ediyorum.</span></label>
<label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?php echo esc_url(yr_page_url('kvkk-aydinlatma-metni'));?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum; bilgilerimin başvurunun değerlendirilmesi ve benimle iletişim kurulması için işlenmesini kabul ediyorum.</span></label>
</fieldset>
<?php yr_ts_widget();?>
<button class="btn gt-send" type="submit"><?php echo yr_ikon('plus');?>Başvuruyu Gönder</button>
</form>
<?php endif;?>
</div>
<aside class="gt-side">
<div class="box"><h2>Sık Sorulanlar</h2>
<details open><summary>Ücretli mi?</summary><p>Hayır. Yazarock'ta yer almak tamamen ücretsizdir; gruplardan hiçbir ücret talep etmeyiz.</p></details>
<details><summary>Kimler başvurabilir?</summary><p>Türkiye'de rock, metal ve alt türlerinde kendi bestelerini yapan bağımsız gruplar ve solo sanatçılar. Daha önce hiç yayın yapmamış olmanız sorun değil; bir demo kaydı yeterli.</p></details>
<details><summary>Ne zaman dönüş yapılır?</summary><p>Her başvuruyu dinliyoruz. Röportaj, inceleme ya da tanıtım için uygun bulduğumuzda e-postayla sana yazıyoruz.</p></details>
<details><summary>Neye dikkat ediyorsunuz?</summary><p>Özgün müzik, grubun hikâyesi ve sahnedeki emek. Kayıt kalitesinin mükemmel olması gerekmez.</p></details>
<details><summary>Bilgilerimi nasıl sildiririm?</summary><p>info@yazarock.com adresine yazman yeterli; başvurun ve fotoğrafın silinir.</p></details>
</div>
<div class="box gt-q"><?php echo yr_ikon('cmt');?><p>"Bilinmeyen grupların sesi olmak" için yola çıktık. Sahne büyüdükçe herkes kazanır.</p></div>
</aside>
</div>
<?php get_footer();
