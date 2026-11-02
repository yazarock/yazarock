<?php
if(!defined('ABSPATH'))exit;
/* Instagram görselleri (tema 1.21.0): haftalık konser görseli + yazı görseli. Görseller tarayıcıda (canvas) çizilir, PNG olarak indirilir. */

add_action('admin_menu',function(){
	add_menu_page('Instagram Görselleri','Instagram','edit_posts','yr-instagram','yr_ig_sayfa','dashicons-instagram',4);
});
add_filter('post_row_actions',function($a,$p){
	if(in_array($p->post_type,['post','grup'],true)&&$p->post_status==='publish'&&current_user_can('edit_posts'))$a['yr_ig']='<a href="'.esc_url(admin_url('admin.php?page=yr-instagram&sekme=yazi&yazi='.$p->ID)).'">Instagram görseli</a>';
	return $a;
},10,2);
/* Pazartesi hatırlatması */
add_action('admin_notices',function(){
	if(!current_user_can('edit_posts')||(int)current_time('N')!==1)return;
	$s=get_current_screen();if(!$s||$s->id!=='dashboard')return;
	echo '<div class="notice notice-info"><p><strong>Pazartesi:</strong> bu haftanın yeraltı konserlerini seçip Instagram görselini hazırlayabilirsin. <a href="'.esc_url(admin_url('admin.php?page=yr-instagram')).'">Instagram görselini aç →</a></p></div>';
});

function yr_ig_logo(){
	$s=function_exists('yr_logo_svg')?yr_logo_svg():'';
	if(!preg_match('~<svg.*</svg>~s',$s,$m))return '';
	return preg_replace('~^<svg~','<svg xmlns="http://www.w3.org/2000/svg" width="1120" height="220"',$m[0]);
}
function yr_ig_hafta($bas){
	$bit=date('Y-m-d',strtotime($bas.' +6 days'));
	$q=get_posts(['post_type'=>'etkinlik','post_status'=>'publish','numberposts'=>40,'meta_key'=>'_yr_tarih','orderby'=>'meta_value','order'=>'ASC','meta_query'=>[['key'=>'_yr_tarih','value'=>[$bas,$bit],'compare'=>'BETWEEN','type'=>'DATE']]]);
	$o=[];foreach($q as $e){$o[]=['t'=>(string)get_post_meta($e->ID,'_yr_tarih',true),'s'=>(string)get_post_meta($e->ID,'_yr_saat',true),'ad'=>html_entity_decode(get_the_title($e),ENT_QUOTES,'UTF-8'),'sehir'=>(string)get_post_meta($e->ID,'_yr_sehir',true),'mekan'=>(string)get_post_meta($e->ID,'_yr_mekan',true)];}
	return ['bas'=>$bas,'bit'=>$bit,'e'=>$o];
}
/* Yazıyla ilgili gruplar: etiketleri ya da başlığı grup adıyla eşleşen, fotoğrafı olan gruplar */
function yr_ig_grup_aday($id){
	$p=get_post($id);if(!$p)return [];
	$bas=mb_strtolower(get_the_title($p));$tags=array_map('mb_strtolower',(array)wp_get_post_tags($id,['fields'=>'names']));$o=[];
	foreach(get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>-1,'meta_key'=>'_thumbnail_id','orderby'=>'title','order'=>'ASC']) as $g){
		$ad=mb_strtolower($g->post_title);if(mb_strlen($ad)<3)continue;
		/* Yalnızca etiketle tam eşleşme: başlıkta geçen sözcükler ("death metal" → Death grubu) yanlış eşleşmesin */
		if(in_array($ad,$tags,true))$o[]=$g->ID;
	}
	return $o;
}
function yr_ig_yazi($id,$grup=0){
	$p=get_post($id);if(!$p||!in_array($p->post_type,['post','grup'],true))return null;
	if($p->post_type==='grup'){/* grup sayfası: grubun fotoğrafı, adı, şehri ve tanıtımı */
		$img=get_post_thumbnail_id($id)?wp_get_attachment_image_url(get_post_thumbnail_id($id),'large'):'';
		$t=get_the_terms($id,'grup_sehir');$sehir=($t&&!is_wp_error($t))?$t[0]->name:'';
		$stil=function_exists('yr_meta')?(string)yr_meta('stil',$id):'';
		$ex=has_excerpt($p)?get_the_excerpt($p):wp_trim_words(wp_strip_all_tags(preg_replace('~</(p|h\d|li|div)>|<br\s*/?>~i',' ',$p->post_content)),30);
		$et=array_values(array_filter(array_merge([$p->post_title,$sehir],array_map('trim',preg_split('/[,\/]/',$stil)))));/* sıralı dizi: JSON'da nesneye dönüşmesin */
		return ['baslik'=>html_entity_decode(get_the_title($p),ENT_QUOTES,'UTF-8'),'ozet'=>html_entity_decode(($stil?$stil.'. ':'').$ex,ENT_QUOTES,'UTF-8'),'kat'=>'Grup'.($sehir?' · '.$sehir:''),'etiket'=>$et,'img'=>$img,'url'=>get_permalink($p)];
	}
	$c=get_the_category($id);$tags=wp_get_post_tags($id,['fields'=>'names']);
	$img=get_post_thumbnail_id($id)?wp_get_attachment_image_url(get_post_thumbnail_id($id),'large'):'';
	if($grup===-1&&!$img){$a=yr_ig_grup_aday($id);if($a)$grup=$a[0];}/* yazının görseli yoksa ilgili grubun fotoğrafı */
	if($grup>0&&get_post_type($grup)==='grup'&&get_post_thumbnail_id($grup))$img=wp_get_attachment_image_url(get_post_thumbnail_id($grup),'large');
	$ex=has_excerpt($p)?get_the_excerpt($p):wp_trim_words(wp_strip_all_tags(preg_replace('~</(p|h\d|li|div)>|<br\s*/?>~i',' ',$p->post_content)),35);
	$bs=html_entity_decode(get_the_title($p),ENT_QUOTES,'UTF-8');$kat=$c?$c[0]->name:'';
	/* Başlık kategori adıyla başlıyorsa (ör. "Keşif: …") görselde tekrar etmesin; kategori zaten kırmızı etikette */
	foreach(array_filter([$kat,'Keşif','Haber','Röportaj','Yeni Çıkış']) as $on){if(mb_stripos($bs,$on)===0&&preg_match('/^.{'.mb_strlen($on).'}\s*[:–—-]\s*(.+)$/us',$bs,$m)){$bs=$m[1];break;}}
	return ['baslik'=>$bs,'ozet'=>html_entity_decode($ex,ENT_QUOTES,'UTF-8'),'kat'=>$c?$c[0]->name:'','etiket'=>array_values((array)$tags),'img'=>$img,'url'=>get_permalink($p)];
}

add_action('admin_enqueue_scripts',function($h){if(strpos((string)$h,'yr-instagram')!==false)wp_enqueue_media();});
/* Röportaj kaydırmalı gönderisi için veri: giriş bilgisi + soru/cevaplar (1.24.0) */
function yr_ig_roportajlar(){
	$k=function_exists('yr_roportaj_kat')?yr_roportaj_kat():0;
	return get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>100,'cat'=>$k?:0,'s'=>$k?'':'Garajdan Sahneye']);
}
function yr_ig_rop($id){
	$p=get_post($id);if(!$p||$p->post_type!=='post')return null;
	$h=$p->post_content;$t=function($x){return trim(html_entity_decode(wp_strip_all_tags(preg_replace('~<br\s*/?>~i',"\n",$x)),ENT_QUOTES,'UTF-8'));};
	$grup=preg_replace('/^Garajdan Sahneye:\s*/u','',html_entity_decode(get_the_title($p),ENT_QUOTES,'UTF-8'));
	$sehir=$tur=$yil='';
	if(preg_match('~<p class="rop-giris">(.*?)</p>~s',$h,$m)){$g=$t($m[1]);
		if(preg_match('/^(.+?) merkezli .+?, (.+?) çizgisinde müzik yapan bir grup(?: \((\d{4}))?/us',$g,$mm)){$sehir=$mm[1];$tur=$mm[2];$yil=$mm[3]??'';}}
	$qa=[];
	if(preg_match_all('~<p class="soru">(.*?)</p>.*?<p class="cevap">(.*?)</p>~s',$h,$mm,PREG_SET_ORDER))foreach($mm as $x)$qa[]=['s'=>$t($x[1]),'c'=>$t($x[2])];
	if(!$qa){foreach(preg_split('~</p>~',$h) as $par){$x=$t($par);if(mb_strlen($x)>30)$qa[]=['s'=>'','c'=>$x];}}
	$img=get_post_thumbnail_id($id)?wp_get_attachment_image_url(get_post_thumbnail_id($id),'large'):'';
	return ['grup'=>$grup,'sehir_den'=>($sehir!==''&&function_exists('yr_ek'))?$sehir.yr_ek($sehir,'den'):'','sehir'=>$sehir,'tur'=>$tur,'yil'=>$yil,'img'=>$img,'ozet'=>html_entity_decode(has_excerpt($p)?get_the_excerpt($p):'',ENT_QUOTES,'UTF-8'),'qa'=>$qa,'url'=>get_permalink($p)];
}
function yr_ig_sayfa(){
	$sekme=in_array($_GET['sekme']??'',['yazi','rop'],true)?$_GET['sekme']:'hafta';
	$bas=isset($_GET['bas'])&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['bas'])?$_GET['bas']:current_time('Y-m-d');
	$yid=(int)($_GET['yazi']??0);$gsec=isset($_GET['gimg'])?(int)$_GET['gimg']:-1;/* -1: otomatik, 0: yazının görseli, >0: seçilen grup */
	$son=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>500]);
	$gruplar=get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
	if(!$yid&&$son)$yid=$son[0]->ID;
	if($yid&&get_post_type($yid)==='post'&&!in_array($yid,wp_list_pluck($son,'ID'),true))array_unshift($son,get_post($yid));
	$grupmu=$yid&&get_post_type($yid)==='grup';
	if($sekme==='rop'){$roplar=yr_ig_roportajlar();$rid=(int)($_GET['rop']??0);if(!$rid&&$roplar)$rid=$roplar[0]->ID;$veri=yr_ig_rop($rid);}
	else $veri=$sekme==='hafta'?yr_ig_hafta($bas):yr_ig_yazi($yid,$gsec);
	$u=admin_url('admin.php?page=yr-instagram');
	?>
	<div class="wrap yr-ig"><h1>Instagram Görselleri</h1>
	<h2 class="nav-tab-wrapper"><a class="nav-tab<?php echo $sekme==='hafta'?' nav-tab-active':'';?>" href="<?php echo esc_url($u);?>">Yeraltında Bu Hafta</a><a class="nav-tab<?php echo $sekme==='yazi'?' nav-tab-active':'';?>" href="<?php echo esc_url($u.'&sekme=yazi');?>">Yazı görseli</a><a class="nav-tab<?php echo $sekme==='rop'?' nav-tab-active':'';?>" href="<?php echo esc_url($u.'&sekme=rop');?>">Röportaj (kaydırmalı)</a></h2>
	<form method="get" class="yr-ig-form"><input type="hidden" name="page" value="yr-instagram">
	<?php if($sekme==='hafta'):?>
		<label>Başlangıç günü <input type="date" name="bas" value="<?php echo esc_attr($bas);?>"></label> <button class="button">Göster</button>
		<span class="description">Seçilen günden itibaren 7 günün yayındaki konserleri.</span>
	<?php elseif($sekme==='rop'):?>
		<input type="hidden" name="sekme" value="rop"><label>Röportaj <select name="rop" style="max-width:420px" onchange="this.form.submit()"><?php foreach($roplar as $p)echo '<option value="'.$p->ID.'"'.selected($p->ID,$rid,false).'>'.esc_html(get_the_date('j M',$p).' · '.wp_trim_words(get_the_title($p),10)).'</option>';?></select></label>
	<?php else:?>
		<input type="hidden" name="sekme" value="yazi"><label>Yazı ya da grup <select name="yazi" style="max-width:420px" onchange="if(this.form.gimg)this.form.gimg.value='-1';this.form.submit()"><?php
		echo '<optgroup label="Yazılar (en yeniden eskiye)">';foreach($son as $p)echo '<option value="'.$p->ID.'"'.selected($p->ID,$yid,false).'>'.esc_html(get_the_date('j M',$p).' · '.wp_trim_words(get_the_title($p),12)).'</option>';echo '</optgroup>';
		echo '<optgroup label="Gruplar (A–Z)">';foreach($gruplar as $p)echo '<option value="'.$p->ID.'"'.selected($p->ID,$yid,false).'>'.esc_html($p->post_title).(has_post_thumbnail($p)?'':' (fotoğrafsız)').'</option>';echo '</optgroup>';?></select></label>
		<?php if(!$grupmu):$aday=yr_ig_grup_aday($yid);$tum=get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>-1,'meta_key'=>'_thumbnail_id','orderby'=>'title','order'=>'ASC']);?>
		<label style="margin-left:12px">Görsel <select name="gimg" onchange="this.form.submit()">
			<option value="-1"<?php selected($gsec,-1);?>>Otomatik (yazının görseli, yoksa ilgili grup)</option>
			<option value="0"<?php selected($gsec,0);?>>Yalnızca yazının kendi görseli</option>
			<?php if($aday){echo '<optgroup label="Bu yazıdaki gruplar">';foreach($aday as $g)echo '<option value="'.$g.'"'.selected($gsec,$g,false).'>'.esc_html(get_the_title($g)).'</option>';echo '</optgroup>';}
			echo '<optgroup label="Tüm gruplar (fotoğrafı olanlar)">';foreach($tum as $g)echo '<option value="'.$g->ID.'"'.selected($gsec,$g->ID,false).'>'.esc_html($g->post_title).'</option>';echo '</optgroup>';?>
		</select></label><?php endif;?>
	<?php endif;?></form>
	<?php if($sekme==='rop'&&$veri):?><fieldset class="yr-ig-sec yr-ig-rop"><legend><strong>Gönderinin metinleri</strong> — her kutuyu istediğin gibi düzenleyebilir ya da listeden grubun bir cümlesini seçebilirsin</legend>
		<label>1 · Kapak alt yazısı<textarea id="rp-kapak" rows="2"></textarea></label>
		<label>2 · Büyük söz <select class="rp-sec" data-hedef="rp-s1"></select><textarea id="rp-s1" rows="2"></textarea><input type="text" id="rp-k1" placeholder="Altındaki açıklama"></label>
		<label>3 · Alıntı <select class="rp-sec" data-hedef="rp-s2"></select><textarea id="rp-s2" rows="3"></textarea><input type="text" id="rp-k2" placeholder="Altındaki açıklama"></label>
		<label>4 · Alıntı <select class="rp-sec" data-hedef="rp-s3"></select><textarea id="rp-s3" rows="3"></textarea><input type="text" id="rp-k3" placeholder="Altındaki açıklama"></label>
		<label>5 · Kapanış metni<textarea id="rp-son" rows="2"></textarea></label>
		<p><button type="button" class="button button-primary" id="rp-guncelle">Görselleri güncelle</button></p>
	</fieldset><?php endif;?>
	<?php if($sekme==='yazi'||$sekme==='rop'):?><fieldset class="yr-ig-sec yr-ig-foto"><legend><strong>Kendi fotoğrafın</strong> — görseldeki fotoğrafı değiştir (siteye yüklenmez, sadece bu görselde kullanılır)</legend>
		<label class="button" for="yr-ig-dosya">Bilgisayardan / telefondan seç</label><input type="file" id="yr-ig-dosya" accept="image/*" hidden>
		<button type="button" class="button" id="yr-ig-ortam">Ortam kütüphanesinden seç</button>
		<button type="button" class="button-link" id="yr-ig-sifirla" hidden>Fotoğrafı geri al</button>
		<label class="yr-ig-kaydir">Fotoğrafın yeri (yukarı ↔ aşağı) <input type="range" id="yr-ig-konum" min="0" max="100" value="30"></label>
	</fieldset><?php endif;?>
	<?php if($sekme==='hafta'&&!empty($veri['e'])):?><fieldset class="yr-ig-sec"><legend><strong>Görsele girecek konserler</strong> — yalnızca öne çıkarmak istediklerini işaretle</legend><?php foreach($veri['e'] as $i=>$e)echo '<label><input type="checkbox" class="yr-ig-k" value="'.$i.'"> '.esc_html(date_i18n('j M',strtotime($e['t'])).' · '.$e['ad'].($e['sehir']?' ('.$e['sehir'].')':'')).'</label>';?><p><button type="button" class="button-link" id="yr-ig-hepsi">Tümünü seç / kaldır</button></p></fieldset><?php endif;?>
	<div class="yr-ig-in"><div id="yr-ig-gorseller"><p>Görsel hazırlanıyor…</p></div>
	<div class="yr-ig-yan"><h3>Açıklama</h3><textarea id="yr-ig-metin" rows="16"></textarea>
	<p><button type="button" class="button button-primary" id="yr-ig-kopya">Açıklamayı kopyala</button></p>
	<ol class="description"><li>Görsel(ler)i indir (birden fazlaysa sırayla, kaydırmalı gönderi olarak paylaş).</li><li>Açıklamayı kopyala, Instagram'da yapıştır.</li><li>Gruplar Instagram'daysa gönderide onları etiketle.</li><li>İstersen Meta Business Suite'ten ileri bir saate planla.</li></ol></div></div></div>
	<style>.yr-ig-form{margin:14px 0}.yr-ig-in{display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start}#yr-ig-gorseller{display:flex;gap:16px;flex-wrap:wrap}#yr-ig-gorseller figure{margin:0}#yr-ig-gorseller canvas{width:360px;max-width:100%;height:auto;display:block;box-shadow:0 2px 12px rgba(0,0,0,.25)}#yr-ig-gorseller figcaption{margin-top:8px}.yr-ig-sec{border:1px solid #c3c4c7;background:#fff;padding:10px 14px;margin:0 0 18px;max-width:900px}.yr-ig-sec label{display:block;margin:4px 0}.yr-ig-rop label{display:block;margin:10px 0}.yr-ig-rop textarea,.yr-ig-rop input[type=text]{width:100%;margin-top:4px}.yr-ig-rop select{max-width:100%;margin:4px 0}.yr-ig-foto{display:flex;flex-wrap:wrap;gap:8px 12px;align-items:center}.yr-ig-foto label.button{display:inline-block;margin:0}.yr-ig-kaydir{display:flex!important;gap:8px;align-items:center;margin:0!important}.yr-ig-yan{flex:1;min-width:280px;max-width:520px}.yr-ig-yan textarea{width:100%;font:13px/1.5 monospace}</style>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Barlow:wght@400;600&display=swap">
	<script>
	window.YR_IG={sekme:<?php echo wp_json_encode($sekme);?>,veri:<?php echo wp_json_encode($veri);?>,logo:<?php echo wp_json_encode(yr_ig_logo());?>,site:<?php echo wp_json_encode(preg_replace('~^https?://~','',untrailingslashit(home_url())));?>};
	<?php echo yr_ig_js();?>
	</script>
	<?php
}

function yr_ig_js(){return <<<'JS'
(function(){
const D=window.YR_IG,W=1080,H=1350,RED='#c0282d',RED2='#e5393f',TXT='#ece6e6',MUTE='#a69a9b',BG='#0d0809',PAN='#170e10',LINE='#3a2024';
const up=s=>(s||'').toLocaleUpperCase('tr-TR');
const AY=['OCA','ŞUB','MAR','NİS','MAY','HAZ','TEM','AĞU','EYL','EKİ','KAS','ARA'],AYU=['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'],GUN=['PAZAR','PAZARTESİ','SALI','ÇARŞAMBA','PERŞEMBE','CUMA','CUMARTESİ'];
const tarih=s=>{const [y,m,d]=s.split('-').map(Number);return new Date(y,m-1,d)};
function img(src){return new Promise(r=>{if(!src)return r(null);const i=new Image();i.onload=()=>r(i);i.onerror=()=>r(null);i.src=src})}
function sar(c,t,w){const k=(t||'').split(/\s+/),o=[];let l='';for(const x of k){const n=l?l+' '+x:x;if(c.measureText(n).width>w&&l){o.push(l);l=x}else l=n}if(l)o.push(l);return o}
function kisalt(c,t,w){if(c.measureText(t).width<=w)return t;while(t.length>1&&c.measureText(t+'…').width>w)t=t.slice(0,-1);return t+'…'}
function zemin(c){c.fillStyle=BG;c.fillRect(0,0,W,H);const g=c.createRadialGradient(W/2,-140,40,W/2,-140,1100);g.addColorStop(0,'#4a1218');g.addColorStop(1,'rgba(13,8,9,0)');c.fillStyle=g;c.fillRect(0,0,W,H)}
async function logo(c,x,y,w){const svg=D.logo;if(!svg)return;const i=await img('data:image/svg+xml;charset=utf-8,'+encodeURIComponent(svg));if(i)c.drawImage(i,x,y,w,w*220/1120)}
function alt(c){c.fillStyle=RED;c.fillRect(0,H-96,W,96);c.fillStyle='#fff';c.font='600 34px Barlow, Arial, sans-serif';c.textAlign='center';c.fillText(D.site.toLocaleLowerCase('tr-TR'),W/2,H-36);c.textAlign='left'}
function tuval(){const c=document.createElement('canvas');c.width=W;c.height=H;return c}

async function hafta(){
	const v=D.veri,sec=[...document.querySelectorAll('.yr-ig-k:checked')].map(x=>+x.value),e=v.e.filter((x,i)=>sec.includes(i)),on=[];const per=6,sayfa=Math.max(1,Math.ceil(e.length/per));
	const b=tarih(v.bas),s=tarih(v.bit);
	const aralik=b.getMonth()===s.getMonth()?b.getDate()+'–'+s.getDate()+' '+AYU[s.getMonth()]:b.getDate()+' '+AYU[b.getMonth()]+' – '+s.getDate()+' '+AYU[s.getMonth()];
	for(let p=0;p<sayfa;p++){
		const cv=tuval(),c=cv.getContext('2d');zemin(c);await logo(c,70,64,340);
		c.fillStyle=RED2;c.font='700 30px Oswald, Impact, sans-serif';c.fillText(up(aralik)+(sayfa>1?'  ·  '+(p+1)+'/'+sayfa:''),70,200);
		c.fillStyle=TXT;c.font='700 112px Oswald, Impact, sans-serif';c.fillText('YERALTINDA',66,318);c.fillText('BU HAFTA',66,428);
		const parca=e.slice(p*per,p*per+per);let y=492;
		if(!parca.length){c.fillStyle=MUTE;c.font='400 40px Barlow, Arial, sans-serif';c.fillText('Görsele girecek konserleri yukarıdan işaretle.',70,y+80)}
		for(const x of parca){
			const d=tarih(x.t),h=110;
			c.fillStyle=PAN;c.fillRect(70,y,W-140,h);c.fillStyle=RED;c.fillRect(70,y,132,h);
			c.fillStyle='#fff';c.textAlign='center';c.font='700 54px Oswald, Impact, sans-serif';c.fillText(d.getDate(),136,y+60);c.font='500 22px Oswald, Impact, sans-serif';c.fillText(AY[d.getMonth()]+' · '+GUN[d.getDay()].slice(0,3),136,y+92);c.textAlign='left';
			let ad=x.ad,mk=x.mekan;const i=ad.lastIndexOf(' – ');if(i>0){if(!mk)mk=ad.slice(i+3);ad=ad.slice(0,i)}
			c.fillStyle=TXT;c.font='700 40px Oswald, Impact, sans-serif';c.fillText(kisalt(c,up(ad),W-140-132-56),230,y+52);
			c.fillStyle=MUTE;c.font='400 29px Barlow, Arial, sans-serif';c.fillText(kisalt(c,[mk,x.sehir,x.s].filter(Boolean).join(' · '),W-140-132-56),230,y+92);
			y+=h+16;
		}
		alt(c);on.push(cv);
	}
	let m='🤘 YERALTINDA BU HAFTA · '+aralik+'\n\n';
	for(const x of e){const d=tarih(x.t);m+='▪️ '+d.getDate()+' '+AYU[d.getMonth()]+' '+GUN[d.getDay()].charAt(0)+GUN[d.getDay()].slice(1).toLocaleLowerCase('tr-TR')+(x.s?' '+x.s:'')+' · '+x.ad+(x.sehir?' ('+x.sehir+')':'')+'\n'}
	if(!e.length)m+='(Henüz konser seçilmedi.)\n';
	m+='\nBağımsız grupları sahnede yalnız bırakmayın! Biletler ve tüm takvim: link profilde 👆\nGrubunun konserini eklemek için bize yaz.\n\n#yazarock #yeraltı #underground #konser #canlımüzik #rock #metal'+[...new Set(e.map(x=>x.sehir).filter(Boolean))].map(s=>' #'+s.toLocaleLowerCase('tr-TR').replace(/\s+/g,'')).join('');
	return [on,m];
}
async function yazi(){
	const v=D.veri;if(!v)return [[], ''];
	const cv=tuval(),c=cv.getContext('2d');zemin(c);const ih=700;
	const i=window.YR_OZEL||await img(v.img);const pos=(+((document.getElementById('yr-ig-konum')||{}).value||30))/100;
	if(i){const r=Math.max(W/i.width,ih/i.height),w=i.width*r,h=i.height*r;c.save();c.beginPath();c.rect(0,0,W,ih);c.clip();/* fotoğraf yalnızca üst alana çizilir */
		if(i.width/i.height>W/ih*1.08){/* geniş grup fotoğrafı: kenardaki üyeler kesilmesin, arkası bulanık doldurulur */
			c.save();c.filter='blur(28px) brightness(.55)';c.drawImage(i,(W-w)/2-40,(ih-h)/2-40,w+80,h+80);c.restore();
			const h2=i.height*W/i.width;c.drawImage(i,0,(ih-h2)/2,W,h2);
		}else c.drawImage(i,(W-w)*0.5,(ih-h)*pos,w,h);c.restore();const g=c.createLinearGradient(0,ih-260,0,ih);g.addColorStop(0,'rgba(13,8,9,0)');g.addColorStop(1,BG);c.fillStyle=g;c.fillRect(0,ih-260,W,260)}
	else{c.fillStyle='#2a1015';c.fillRect(0,0,W,ih);await logo(c,140,ih/2-200,W-280)}
	let y=ih-10;
	if(v.kat){c.font='700 30px Oswald, Impact, sans-serif';const t=up(v.kat),w=c.measureText(t).width+40;c.fillStyle=RED;c.fillRect(70,y-48,w,54);c.fillStyle='#fff';c.fillText(t,90,y-10);y+=36}
	c.fillStyle=TXT;let fs=74;c.font='700 '+fs+'px Oswald';let sat=sar(c,up(v.baslik),W-140);if(sat.length>4){fs=58;c.font='700 '+fs+'px Oswald';sat=sar(c,up(v.baslik),W-140)}
	for(const l of sat.slice(0,5)){y+=fs*1.08;c.fillText(l,70,y)}
	c.fillStyle=MUTE;c.font='400 32px Barlow, Arial, sans-serif';y+=24;
	const kalan=Math.max(0,Math.floor((H-96-60-y)/44));const oz=sar(c,v.ozet,W-140);
	for(let k=0;k<Math.min(kalan,oz.length);k++){let l=oz[k];if(k===kalan-1&&oz.length>kalan)l=kisalt(c,l+' …',W-140);y+=44;c.fillText(l,70,y)}
	await logo(c,W-70-230,H-96-30-45,230);
	alt(c);
	const m=v.baslik+'\n\n'+v.ozet+'\n\n'+(/^Grup/.test(v.kat||'')?'Grubu tanı, dinle: yazarock.com\'da (link profilde) 👆':'Yazının tamamı yazarock.com\'da: link profilde 👆')+'\n\n#yazarock #rock #metal #underground'+[].concat(Object.values(v.etiket||{})).slice(0,6).map(t=>' #'+t.toLocaleLowerCase('tr-TR').replace(/[^0-9a-zçğıöşü]/g,'')).filter((t,i,a)=>t.length>2&&a.indexOf(t)===i&&![' #rock',' #metal',' #underground',' #yazarock'].includes(t)).join('');
	return [[cv],m];
}
function cumleler(t){return (t||'').split(/\n+|(?<=[.!?…])\s+/u).map(x=>x.trim()).filter(x=>x.length>=8)}
function sec(qa,ara,minL,maxL,yedek,kul){for(const n of ara.concat(qa.map((_,i)=>i))){if(kul&&kul.includes(n))continue;const q=qa[n];if(!q)continue;const c=cumleler(q.c).filter(x=>x.length>=minL&&x.length<=maxL);if(maxL<60)c.sort((a,b)=>a.length-b.length);if(c.length)return [c[0],n]}return [yedek||'',-1]}
let ROPHAZIR=false;
function ropHazirla(){
	if(ROPHAZIR||D.sekme!=='rop'||!D.veri)return;ROPHAZIR=true;const v=D.veri,qa=v.qa||[];
	const G=v.grup;const kisa=s=>(s||'').replace(/\?.*$/,'').replace(/^\d+\.\s*/,'').slice(0,60);
	const [a1,n1]=sec(qa,[0,3,6,1,2],8,48,G);const [a2,n2]=sec(qa,[7,5,3,6],50,190,'',[n1]);const [a3,n3]=sec(qa,[5,6,3,8,4],50,190,'',[n1,n2]);
	const set=(id,x)=>{const e=document.getElementById(id);if(e)e.value=x};
	set('rp-kapak',v.ozet||'');set('rp-s1',a1);set('rp-s2',a2);set('rp-s3',a3);
	const lab=n=>n>=0&&qa[n]&&qa[n].s?(G+' · '+kisa(qa[n].s)):G;
	set('rp-k1',lab(n1));set('rp-k2',lab(n2));set('rp-k3',lab(n3));
	set('rp-son',[v.sehir_den||'',G+' müziğini, sahnesini ve planlarını Garajdan Sahneye\'de anlattı.'].filter(Boolean).join(' '));
	document.querySelectorAll('.rp-sec').forEach(s=>{
		s.innerHTML='<option value="">Grubun cümlelerinden seç…</option>'+qa.map((q,i)=>'<optgroup label="'+(q.s||'Metin').replace(/"/g,'&quot;').slice(0,80)+'">'+cumleler(q.c).map(c=>'<option data-i="'+i+'">'+c.replace(/&/g,'&amp;').replace(/</g,'&lt;').slice(0,160)+'</option>').join('')+'</optgroup>').join('');
		s.onchange=()=>{const o=s.selectedOptions[0];if(!o||!o.dataset.i)return;const h=s.dataset.hedef;const tam=cumleler(qa[+o.dataset.i].c).find(c=>c.slice(0,160)===o.textContent)||o.textContent;set(h,tam);set(h.replace('rp-s','rp-k'),lab(+o.dataset.i));calis()};
	});
	const b=document.getElementById('rp-guncelle');if(b)b.onclick=calis;
}
const deger=id=>((document.getElementById(id)||{}).value||'').trim();
function ust(c,etiket){logoKucuk(c);c.font='600 22px Barlow, Arial, sans-serif';const w=c.measureText(etiket).width+36;c.strokeStyle=RED;c.lineWidth=2;c.strokeRect(W-72-w,52,w,44);c.fillStyle=TXT;c.fillText(etiket,W-72-w+18,82)}
function logoKucuk(c){c.fillStyle=TXT;c.font='700 30px Oswald, Impact, sans-serif';c.fillText('YAZA',72,86);const a=c.measureText('YAZA').width;c.fillStyle=RED2;c.fillText('R',72+a,86);const b=c.measureText('R').width;c.fillStyle=TXT;c.fillText('OCK',72+a+b,86)}
function altSatir(c,sol,sag){c.font='400 24px Barlow, Arial, sans-serif';c.fillStyle=MUTE;c.fillText(sol,72,H-56);c.fillStyle=RED2;c.font='700 24px Barlow, Arial, sans-serif';const w=c.measureText(sag).width;c.fillText(sag,W-72-w,H-56)}
function cokSatir(c,t,x,y,w,lh,max){const sat=[];for(const p of (t||'').split('\n')){sat.push(...sar(c,p,w))}const s=sat.slice(0,max);if(sat.length>max)s[max-1]=kisalt(c,s[max-1]+'…',w);s.forEach((l,i)=>c.fillText(l,x,y+i*lh));return y+s.length*lh}
function sigdir(c,t,w,maxS,fs,minF,font){let sat;for(;;){c.font=font.replace('{f}',fs);sat=sar(c,t,w);if((sat.length<=maxS&&sat.every(l=>c.measureText(l).width<=w))||fs<=minF)break;fs-=6}return [sat,fs]}
async function rop(){
	const v=D.veri;if(!v)return [[],''];ropHazirla();const G=v.grup;const on=[];const etiket='GARAJDAN SAHNEYE';
	// 1 kapak
	{const cv=tuval(),c=cv.getContext('2d');zemin(c);const i=window.YR_OZEL||await img(v.img);const ph=1050;
	if(i){const pos=(+((document.getElementById('yr-ig-konum')||{}).value||30))/100;const r=Math.max(W/i.width,ph/i.height),w=i.width*r,h=i.height*r;c.save();c.beginPath();c.rect(0,0,W,ph);c.clip();c.drawImage(i,(W-w)/2,(ph-h)*pos,w,h);c.restore();
		let g=c.createLinearGradient(0,0,0,240);g.addColorStop(0,'rgba(13,8,9,.9)');g.addColorStop(1,'rgba(13,8,9,0)');c.fillStyle=g;c.fillRect(0,0,W,240);
		g=c.createLinearGradient(0,ph-420,0,ph);g.addColorStop(0,'rgba(13,8,9,0)');g.addColorStop(1,BG);c.fillStyle=g;c.fillRect(0,ph-420,W,420)}
	ust(c,etiket);
	c.fillStyle=RED2;c.font='600 28px Barlow, Arial, sans-serif';c.fillText(up([v.sehir,v.tur].filter(Boolean).join(' · ')),72,ph-150);
	c.fillStyle=TXT;let [sat,fs]=sigdir(c,up(G),W-144,2,150,60,'700 {f}px Oswald, Impact, sans-serif');
	let y=ph-150+fs*.95;sat.slice(0,2).forEach((l,k)=>c.fillText(l,68,y+k*fs*.95));y+=sat.slice(0,2).length*fs*.95-fs*.95+20;
	c.fillStyle='#d6cdcd';c.font='400 32px Barlow, Arial, sans-serif';cokSatir(c,deger('rp-kapak'),72,Math.max(y+40,ph+20),W-144,42,2);
	altSatir(c,D.site,'kaydır →');on.push(cv)}
	// 2 büyük söz
	{const cv=tuval(),c=cv.getContext('2d');zemin(c);ust(c,etiket);c.fillStyle=RED;c.fillRect(72,300,96,8);
	const t=up(deger('rp-s1')||G);let [sat,fs]=sigdir(c,t,W-144,5,150,56,'700 {f}px Oswald, Impact, sans-serif');
	sat.forEach((l,k)=>{c.fillStyle=k===1?RED2:TXT;c.fillText(l,68,380+fs*.85+k*fs*.98)});
	c.fillStyle=MUTE;c.font='600 24px Barlow, Arial, sans-serif';cokSatir(c,'— '+up(deger('rp-k1')),72,380+fs*.85+sat.length*fs*.98+50,W-144,32,2);altSatir(c,D.site,'2/5');on.push(cv)}
	// 3-4 alıntılar
	for(const [n,id,kid] of [[3,'rp-s2','rp-k2'],[4,'rp-s3','rp-k3']]){const cv=tuval(),c=cv.getContext('2d');zemin(c);ust(c,etiket);c.fillStyle=RED;c.fillRect(72,330,96,8);
	c.fillStyle=RED;c.font='700 200px Oswald, Impact, sans-serif';c.fillText('“',64,560);
	const t=deger(id);let [sat,fs]=sigdir(c,t,W-144,8,64,36,'600 {f}px Barlow, Arial, sans-serif');
	c.fillStyle=TXT;const y=cokSatir(c,t,72,620,W-144,fs*1.2,9);c.fillStyle=MUTE;c.font='600 24px Barlow, Arial, sans-serif';cokSatir(c,'— '+up(deger(kid)),72,y+40,W-144,32,2);altSatir(c,D.site,n+'/5');on.push(cv)}
	// 5 kapanış
	{const cv=tuval(),c=cv.getContext('2d');zemin(c);ust(c,etiket);c.fillStyle=TXT;c.font='700 92px Oswald, Impact, sans-serif';c.fillText('RÖPORTAJIN',72,340);c.fillText('TAMAMI',72,440);
	c.fillStyle=RED2;const s1=D.site.toLocaleUpperCase('tr-TR');c.fillText(s1,72,540);const w=c.measureText(s1).width;c.fillStyle=TXT;c.fillText('\'DA',72+w,540);
	c.fillStyle='#d6cdcd';c.font='400 36px Barlow, Arial, sans-serif';const y=cokSatir(c,deger('rp-son'),72,630,W-144,48,4);
	const by=Math.max(y+40,860);c.strokeStyle=RED;c.lineWidth=3;c.strokeRect(72,by,W-144,190);c.fillStyle=TXT;c.font='700 34px Barlow, Arial, sans-serif';c.fillText('Grubun mu var?',106,by+62);
	c.fillStyle='#d6cdcd';c.font='400 30px Barlow, Arial, sans-serif';cokSatir(c,'Garajdan Sahneye\'nin 10 sorusunu yanıtla, sıradaki röportaj seninki olsun. 🤘',106,by+112,W-212,40,2);
	altSatir(c,'Link profilde','5/5');on.push(cv)}
	const hs='#'+G.toLocaleLowerCase('tr-TR').replace(/[^0-9a-zçğıöşü]/g,'');
	const m='Garajdan Sahneye\'de bu kez '+(v.sehir_den?v.sehir_den+' ':'')+G+' var 🤘\n\n'+(deger('rp-kapak')||v.ozet||'')+'\n\nRöportajın tamamı yazarock.com\'da, link profilde 👆\n\n#yazarock #garajdansahneye '+hs+(v.sehir?' #'+v.sehir.toLocaleLowerCase('tr-TR').replace(/\s+/g,''):'')+' #bağımsızmüzik #underground #rock';
	return [on,m];
}
async function calis(){
	try{await Promise.all(['700 40px Oswald, Impact, sans-serif','500 20px Oswald, Impact, sans-serif','400 20px Barlow, Arial, sans-serif','600 20px Barlow, Arial, sans-serif'].map(f=>document.fonts.load(f)))}catch(e){}
	const [cs,m]=D.sekme==='rop'?await rop():D.sekme==='yazi'?await yazi():await hafta();
	const k=document.getElementById('yr-ig-gorseller');k.innerHTML='';
	cs.forEach((cv,n)=>{const f=document.createElement('figure');f.appendChild(cv);const a=document.createElement('a');a.className='button';a.textContent='İndir'+(cs.length>1?' ('+(n+1)+')':'');a.href='#';
		a.onclick=ev=>{ev.preventDefault();cv.toBlob(b=>{const l=document.createElement('a');l.href=URL.createObjectURL(b);l.download='yazarock-'+(D.sekme==='rop'?'roportaj':D.sekme==='yazi'?'yazi':'hafta-'+D.veri.bas)+(cs.length>1?'-'+(n+1):'')+'.png';document.body.appendChild(l);l.click();setTimeout(()=>l.remove(),500)},'image/png')};
		const fc=document.createElement('figcaption');fc.appendChild(a);f.appendChild(fc);k.appendChild(f)});
	if(!cs.length)k.innerHTML='<p>Yazı bulunamadı.</p>';
	const t=document.getElementById('yr-ig-metin');t.value=m;
	document.getElementById('yr-ig-kopya').onclick=()=>{t.select();(navigator.clipboard?navigator.clipboard.writeText(t.value):Promise.reject()).catch(()=>document.execCommand('copy'));};
}
calis();
(function(){
	const d=document.getElementById('yr-ig-dosya');if(!d)return;
	const sif=document.getElementById('yr-ig-sifirla');
	const kullan=src=>{const i=new Image();i.onload=()=>{window.YR_OZEL=i;sif.hidden=false;calis()};i.onerror=()=>alertsiz('Fotoğraf açılamadı.');i.src=src};
	const alertsiz=m=>{const k=document.getElementById('yr-ig-gorseller');const p=document.createElement('p');p.textContent=m;p.style.color='#b32d2e';k.prepend(p)};
	d.addEventListener('change',()=>{const f=d.files&&d.files[0];if(!f)return;if(f.size>25*1024*1024)return alertsiz('Fotoğraf 25 MB\'tan küçük olmalı.');kullan(URL.createObjectURL(f));d.value=''});
	sif.onclick=()=>{window.YR_OZEL=null;sif.hidden=true;calis()};
	let t;document.getElementById('yr-ig-konum').addEventListener('input',()=>{clearTimeout(t);t=setTimeout(calis,120)});
	const om=document.getElementById('yr-ig-ortam');
	let fr;om.onclick=()=>{if(!(window.wp&&wp.media))return alertsiz('Ortam kütüphanesi yüklenemedi; sayfayı yenileyip tekrar dene.');if(!fr){fr=wp.media({title:'Instagram görseli için fotoğraf seç',button:{text:'Bu fotoğrafı kullan'},library:{type:'image'},multiple:false});fr.on('select',()=>{const a=fr.state().get('selection').first().toJSON();const u=(a.sizes&&(a.sizes.large||a.sizes.full)||a).url;kullan(u)})}fr.open()};
})();
document.querySelectorAll('.yr-ig-k').forEach(x=>x.addEventListener('change',calis));
const hb=document.getElementById('yr-ig-hepsi');if(hb)hb.onclick=()=>{const k=[...document.querySelectorAll('.yr-ig-k')],h=k.some(x=>!x.checked);k.forEach(x=>x.checked=h);calis()};
})();
JS;}
