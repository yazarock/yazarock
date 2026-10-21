(function(){
document.querySelectorAll('[data-filter]').forEach(function(box){
var st={},items=[].slice.call(box.querySelectorAll('.fi')),q='',empty=box.querySelector('.empty');
function run(){var n=0;items.forEach(function(el){var ok=Object.keys(st).every(function(k){return st[k]==='all'||el.dataset[k]===st[k]})&&(!q||el.textContent.toLowerCase().indexOf(q)>-1);el.hidden=!ok;if(ok)n++});if(empty)empty.hidden=n>0}
box.querySelectorAll('.tabs[data-k]').forEach(function(g){g.addEventListener('click',function(e){var b=e.target.closest('button');if(!b)return;g.querySelectorAll('button').forEach(function(x){x.setAttribute('aria-pressed',x===b)});st[g.dataset.k]=b.dataset.v;run()})});
box.querySelectorAll('select[data-k]').forEach(function(s){s.addEventListener('change',function(){st[s.dataset.k]=s.value;run()})});
var i=box.querySelector('[data-search]');if(i)i.addEventListener('input',function(){q=i.value.toLowerCase();run()});
});
var kq=document.getElementById('kq'),kn=document.getElementById('knext');
if(kq&&kn){var Q=JSON.parse(kq.dataset.q),i=Math.floor(Date.now()/864e5);kn.onclick=function(){i++;kq.textContent='\u201c'+Q[i%Q.length]+'\u201d'}}
var r=document.querySelector('.rp i');
if(r)addEventListener('scroll',function(){var d=document.documentElement;r.style.width=(d.scrollTop/((d.scrollHeight-d.clientHeight)||1)*100)+'%'},{passive:true});
if(document.cookie.indexOf('yr_ab=1')>-1)document.querySelectorAll('.nl-box').forEach(function(x){x.remove()});
var ck=document.getElementById('ckb'),ok=document.getElementById('ckok');if(ck&&document.cookie.indexOf('yr_ck=')<0)ck.hidden=false;if(ok)ok.onclick=function(){document.cookie='yr_ck=1;max-age=31536000;path=/;SameSite=Lax'+(location.protocol==='https:'?';Secure':'');ck.hidden=true};

document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('.emb button');if(!b)return;var w=b.parentNode,f=document.createElement('iframe');f.src=w.getAttribute('data-src');f.loading='lazy';f.title=w.getAttribute('data-t')||'Harici içerik';f.allow='autoplay; encrypted-media; fullscreen; picture-in-picture';f.referrerPolicy='strict-origin-when-cross-origin';f.setAttribute('allowfullscreen','');var h=w.getAttribute('data-h');if(h)f.style.height=h+'px';w.innerHTML='';w.className+=' on';w.appendChild(f)});
document.addEventListener('click',function(e){var i=e.target;if(!i.matches||!i.matches('.content .wp-block-gallery img,.content .wp-block-image img'))return;var l=document.createElement('div');l.className='lb';var m=document.createElement('img');m.src=i.currentSrc||i.src;m.alt=i.alt||'';l.appendChild(m);var c=function(){l.remove();document.removeEventListener('keydown',k)},k=function(ev){if(ev.key==='Escape')c()};l.onclick=c;document.addEventListener('keydown',k);document.body.appendChild(l)});
if('serviceWorker' in navigator&&window.YR_SW&&location.protocol==='https:')navigator.serviceWorker.register(window.YR_SW).catch(function(){});

(function(){var h=document.querySelector('[data-hero]');if(!h)return;var S=h.querySelectorAll('.sl'),D=h.querySelectorAll('.hd'),n=S.length;if(n<2)return;
var i=0,t=null,paused=false,hover=false,rm=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches,pp=h.querySelector('.hp-pp');
function go(k){i=(k+n)%n;S.forEach(function(s,j){var on=j===i;s.classList.toggle('on',on);s.setAttribute('aria-hidden',on?'false':'true');var a=s.querySelector('a');if(a)a.tabIndex=on?0:-1});D.forEach(function(d,j){d.classList.toggle('on',j===i)})}
function stop(){if(t){clearInterval(t);t=null}}
function start(){stop();if(!paused&&!hover)t=setInterval(function(){go(i+1)},5000)}
h.querySelector('.hp-prev').onclick=function(){go(i-1);start()};
h.querySelector('.hp-next').onclick=function(){go(i+1);start()};
D.forEach(function(d,j){d.onclick=function(){go(j);start()}});
if(pp)pp.onclick=function(){paused=!paused;pp.textContent=paused?'▶':'❚❚';pp.setAttribute('aria-label',paused?'Otomatik geçişi başlat':'Otomatik geçişi durdur');start()};
h.addEventListener('mouseenter',function(){hover=true;stop()});h.addEventListener('mouseleave',function(){hover=false;start()});
h.addEventListener('focusin',function(){hover=true;stop()});h.addEventListener('focusout',function(){hover=false;start()});
document.addEventListener('visibilitychange',function(){document.hidden?stop():start()});
if(rm){paused=true;if(pp){pp.textContent='▶';pp.setAttribute('aria-label','Otomatik geçişi başlat')}}else start();
})();
})();

/* v1.5: beğen / beğenme, okunma sayacı ve panel yardımcıları */
(function(){
var LS='yr_tp';
function getMap(){try{return JSON.parse(localStorage.getItem(LS)||'{}')||{}}catch(e){return {}}}
function setMap(m){try{var k=Object.keys(m);if(k.length>500)k.slice(0,k.length-500).forEach(function(x){delete m[x]});localStorage.setItem(LS,JSON.stringify(m))}catch(e){}}
function fmt(n){n=+n||0;return n>=1e6?(Math.round(n/1e5)/10+' Mn').replace('.',','):n>=1e4?(Math.round(n/100)/10+' B').replace('.',','):n.toLocaleString('tr-TR')}
var logged=!!window.YR_NONCE;
document.querySelectorAll('.tepki').forEach(function(box){
	var id=box.getAttribute('data-id'),api=box.getAttribute('data-api'),btns=box.querySelectorAll('.tp-b'),msg=box.querySelector('.tp-msg');
	var cur=0;btns.forEach(function(b){if(b.getAttribute('aria-pressed')==='true')cur=+b.getAttribute('data-v')});
	if(!logged){var m=getMap();if(m[id])cur=+m[id];}
	function paint(st,counts){cur=st;btns.forEach(function(b){var v=+b.getAttribute('data-v');b.setAttribute('aria-pressed',v===st?'true':'false');if(counts){var n=b.querySelector('.tp-n');if(n)n.textContent=fmt(v===1?counts.l:counts.d)}})}
	paint(cur);
	btns.forEach(function(b){b.addEventListener('click',function(e){
		if(!window.fetch)return;e.preventDefault();
		var v=+b.getAttribute('data-v'),want=cur===v?0:v,prev=cur,cs={};
		btns.forEach(function(x){var n=x.querySelector('.tp-n');cs[x.getAttribute('data-v')]=n?n.textContent:''});
		/* anında göster */
		var est={l:0,d:0};btns.forEach(function(x){var n=+(x.querySelector('.tp-n').textContent.replace(/\D/g,''))||0;if(x.getAttribute('data-v')==='1')est.l=n;else est.d=n});
		if(prev===1)est.l--;else if(prev===-1)est.d--;if(want===1)est.l++;else if(want===-1)est.d++;
		paint(want,{l:Math.max(0,est.l),d:Math.max(0,est.d)});if(want)b.classList.remove('pop'),void b.offsetWidth,b.classList.add('pop');
		btns.forEach(function(x){x.disabled=true});if(msg)msg.textContent='';
		var h={'Content-Type':'application/json'};if(logged)h['X-WP-Nonce']=window.YR_NONCE;
		fetch(api,{method:'POST',credentials:'same-origin',headers:h,body:JSON.stringify({id:+id,v:want})}).then(function(r){return r.json().then(function(j){if(!r.ok)throw j;return j})}).then(function(j){
			paint(+j.ben,{l:j.l,d:j.d});if(!logged){var m=getMap();if(+j.ben)m[id]=+j.ben;else delete m[id];setMap(m)}
		}).catch(function(err){
			paint(prev);btns.forEach(function(x){var n=x.querySelector('.tp-n');if(n)n.textContent=cs[x.getAttribute('data-v')]});
			if(msg)msg.textContent=(err&&err.message)||'Oy kaydedilemedi, tekrar dene.';
		}).then(function(){btns.forEach(function(x){x.disabled=false})});
	})});
});
/* Okunma: sayfada birkaç saniye kalan okur bir kez sayılır */
var art=document.querySelector('[data-okundu]');
if(art&&window.fetch){var pid=art.getAttribute('data-okundu'),k='yr_ok_'+pid,seen=false;try{seen=!!sessionStorage.getItem(k)}catch(e){}
	if(!seen)setTimeout(function(){if(document.hidden)return;try{sessionStorage.setItem(k,'1')}catch(e){}
		var h={'Content-Type':'application/json'};if(logged)h['X-WP-Nonce']=window.YR_NONCE;
		fetch(art.getAttribute('data-api'),{method:'POST',credentials:'same-origin',headers:h,body:JSON.stringify({id:+pid}),keepalive:true}).catch(function(){})},5000)}
/* Panel: biyografi sayacı ve fotoğraf önizleme */
var bio=document.getElementById('bio'),bc=document.getElementById('biocnt');if(bio&&bc)bio.addEventListener('input',function(){bc.textContent=bio.value.length});
var af=document.getElementById('avfile'),ap=document.getElementById('avprev');
if(af&&ap)af.addEventListener('change',function(){var f=af.files&&af.files[0];if(!f)return;if(f.size>2*1024*1024){alert('Fotoğraf en fazla 2 MB olmalı.');af.value='';return}var r=new FileReader();r.onload=function(){ap.src=r.result;ap.removeAttribute('srcset')};r.readAsDataURL(f)});
})();

/* v1.6: Grubunu Tanıt formu */
(function(){
var f=document.getElementById('gtform');if(!f)return;
var K='yr_gt_form',hk=document.getElementById('gthk'),cnt=document.getElementById('gtcnt');
function upd(){if(hk&&cnt)cnt.textContent=hk.value.length}
/* hata sonrası geri dönüldüyse doldurulanları geri getir */
try{var saved=JSON.parse(sessionStorage.getItem(K)||'null');if(saved&&/[?&]yr_g=(?!tamam)/.test(location.search)){Object.keys(saved).forEach(function(n){var els=f.querySelectorAll('[name="'+n.replace(/"/g,'')+'"]');els.forEach(function(el){if(el.type==='checkbox')el.checked=saved[n].indexOf(el.value)>-1;else if(el.type!=='file'&&el.type!=='hidden')el.value=saved[n][0]||''})})}}catch(e){}
upd();if(hk)hk.addEventListener('input',upd);
var ft=document.getElementById('gtfoto');if(ft)ft.addEventListener('change',function(){if(ft.files[0]&&ft.files[0].size>5*1024*1024){alert('Fotoğraf en fazla 5 MB olmalı.');ft.value=''}});
f.addEventListener('submit',function(e){
	var links=[].slice.call(f.querySelectorAll('input[name^="l_"]')).filter(function(i){return i.value.trim()});
	var old=f.querySelector('.gt-err');if(old)old.remove();
	if(!links.length){e.preventDefault();var d=document.createElement('div');d.className='gt-err';d.setAttribute('role','alert');d.textContent='Lütfen müziğinizi dinleyebileceğimiz en az bir bağlantı ekleyin (Bandcamp, Spotify, YouTube…).';var fs=f.querySelectorAll('fieldset')[1];fs.insertBefore(d,fs.querySelector('.g2'));d.scrollIntoView({behavior:'smooth',block:'center'});return}
	var o={};[].slice.call(f.elements).forEach(function(el){if(!el.name||el.type==='file'||el.type==='hidden'||el.name==='website')return;if(el.type==='checkbox'){o[el.name]=o[el.name]||[];if(el.checked)o[el.name].push(el.value)}else o[el.name]=[el.value]});
	try{sessionStorage.setItem(K,JSON.stringify(o))}catch(e){}
	var b=f.querySelector('.gt-send');if(b){b.disabled=true;b.lastChild.textContent='Gönderiliyor…'}
});
if(/[?&]yr_g=tamam/.test(location.search)){try{sessionStorage.removeItem(K)}catch(e){}}
})();

/* v1.6.1: e-posta yazım hatası uyarısı (ör. gmaill.com → gmail.com) */
(function(){
var T={'gmaill.com':'gmail.com','gmial.com':'gmail.com','gamil.com':'gmail.com','gmai.com':'gmail.com','gmal.com':'gmail.com','gmail.co':'gmail.com','gmail.con':'gmail.com','gmail.cm':'gmail.com','gmail.om':'gmail.com','gmail.com.tr':'gmail.com','gnail.com':'gmail.com','gmeil.com':'gmail.com','hotmial.com':'hotmail.com','hotmal.com':'hotmail.com','hotmai.com':'hotmail.com','hotmail.con':'hotmail.com','hotmail.co':'hotmail.com','hotmil.com':'hotmail.com','hotmial.com.tr':'hotmail.com.tr','outlok.com':'outlook.com','outloo.com':'outlook.com','outlook.con':'outlook.com','yaho.com':'yahoo.com','yahooo.com':'yahoo.com','yahoo.con':'yahoo.com','icloud.con':'icloud.com','iclod.com':'icloud.com','yandex.con':'yandex.com'};
function check(i){var h=i.parentNode.querySelector('.em-hint');if(h)h.remove();i.setCustomValidity('');var v=i.value.trim().toLowerCase(),p=v.split('@');if(p.length!==2||!T[p[1]])return;
	var fix=p[0]+'@'+T[p[1]],s=document.createElement('span');s.className='em-hint';s.setAttribute('role','alert');s.appendChild(document.createTextNode('Bunu mu demek istedin: '));var b=document.createElement('button');b.type='button';b.textContent=fix;b.onclick=function(){i.value=fix;check(i);i.focus()};s.appendChild(b);s.appendChild(document.createTextNode(' ?'));i.insertAdjacentElement('afterend',s);
	i.setCustomValidity('E-posta adresinde yazım hatası var: '+fix+' olabilir mi?')}
document.querySelectorAll('form input[type=email]').forEach(function(i){i.addEventListener('blur',function(){check(i)});i.addEventListener('input',function(){if(i.parentNode.querySelector('.em-hint'))check(i)})});
})();

/* v1.7: kayıt formları — kullanıcı adı kontrolü, şifre gücü, göster/gizle, hata sonrası geri doldurma */
(function(){
document.querySelectorAll('form[data-hatirla]').forEach(function(f){
	var K=f.getAttribute('data-hatirla');
	try{var s=JSON.parse(sessionStorage.getItem(K)||'null');if(s&&/[?&](yr_e|yr_u|yr_y)=(?!tamam|onay)/.test(location.search))Object.keys(s).forEach(function(n){f.querySelectorAll('[name="'+n.replace(/"/g,'')+'"]').forEach(function(el){if(el.type==='checkbox')el.checked=s[n].indexOf(el.value)>-1;else el.value=s[n][0]||''})})}catch(e){}
	f.addEventListener('submit',function(){var o={};[].slice.call(f.elements).forEach(function(el){if(!el.name||el.type==='password'||el.type==='hidden'||el.type==='file'||el.name==='website')return;if(el.type==='checkbox'){o[el.name]=o[el.name]||[];if(el.checked)o[el.name].push(el.value)}else o[el.name]=[el.value]});try{sessionStorage.setItem(K,JSON.stringify(o))}catch(e){}
		var b=f.querySelector('button[type=submit]');if(b){b.disabled=true;b.textContent='Gönderiliyor…'}});
});
var tr={'ç':'c','ğ':'g','ı':'i','İ':'i','ö':'o','ş':'s','ü':'u','â':'a','î':'i','û':'u'};
function temiz(v){return v.replace(/[çğıİöşüâîû]/gi,function(c){return tr[c]||tr[c.toLowerCase()]||c}).toLowerCase().replace(/\s+/g,'').replace(/[^a-z0-9._-]/g,'')}
document.querySelectorAll('input[data-kadi]').forEach(function(i){
	var m=i.closest('label').querySelector('.kadi-m'),def=m?m.textContent:'',t=null,api=(window.YR_API||'/wp-json/')+'yazarock/v1/kadi?ad=';
	var ad=i.form.querySelector('input[name=ad]');
	if(ad)ad.addEventListener('blur',function(){if(!i.value&&ad.value){i.value=temiz(ad.value).slice(0,24);i.dataset.oto='1';chk();setTimeout(function(){if(document.activeElement===i)i.select()},0)}});
	i.addEventListener('focus',function(){if(i.dataset.oto==='1')setTimeout(function(){i.select()},0)});i.addEventListener('keydown',function(){i.dataset.oto=''});
	function set(ok,msg){if(!m)return;m.textContent=msg;m.className='kadi-m note '+(ok===null?'':ok?'ok':'no');i.setCustomValidity(ok===false?msg:'')}
	function chk(){var v=temiz(i.value);if(v!==i.value)i.value=v;clearTimeout(t);if(v.length<3){set(null,def);i.setCustomValidity(v.length?'Kullanıcı adı en az 3 karakter olmalı.':'');return}
		set(null,'Kontrol ediliyor…');t=setTimeout(function(){if(!window.fetch)return;fetch(api+encodeURIComponent(v),{credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){if(i.value!==v)return;if(typeof j.uygun==='boolean')set(j.uygun,(j.uygun?'✓ ':'')+j.mesaj);else set(null,def)}).catch(function(){set(null,def)})},400)}
	i.addEventListener('input',chk);
});
document.querySelectorAll('input[data-sifre]').forEach(function(p){
	var f=p.form,p2=f.querySelector('input[data-sifre2]'),bar=p.closest('label').querySelector('.sg i'),msg=p.closest('label').querySelector('.sg-m'),def=msg?msg.textContent:'',g=p.closest('label').querySelector('.goz');
	function guc(v){var s=0;if(v.length>=8)s++;if(v.length>=12)s++;if(/[a-zçğıöşü]/.test(v)&&/[A-ZÇĞİÖŞÜ]/.test(v))s++;if(/\d/.test(v))s++;if(/[^A-Za-z0-9çğıöşüÇĞİÖŞÜ]/.test(v))s++;return s}
	function upd(){var v=p.value,s=guc(v),ok=v.length>=8&&/[A-Za-zçğıöşüÇĞİÖŞÜ]/.test(v)&&/\d/.test(v);
		if(bar){bar.style.width=(v?Math.max(12,s*20):0)+'%';bar.className=s<=2?'z':s===3?'o':'g'}
		if(msg)msg.textContent=!v?def:!ok?'En az 8 karakter; en az bir harf ve bir rakam':s<=2?'Şifre gücü: zayıf':s===3?'Şifre gücü: orta':'Şifre gücü: güçlü';
		p.setCustomValidity(v&&!ok?'Şifre en az 8 karakter olmalı; en az bir harf ve bir rakam içermeli.':'');m2()}
	function m2(){if(p2)p2.setCustomValidity(p2.value&&p2.value!==p.value?'Şifreler birbiriyle aynı değil.':'')}
	p.addEventListener('input',upd);if(p2)p2.addEventListener('input',m2);
	if(g)g.addEventListener('click',function(){var s=p.type==='password';p.type=s?'text':'password';if(p2)p2.type=p.type;g.textContent=s?'Gizle':'Göster';g.setAttribute('aria-label',s?'Şifreyi gizle':'Şifreyi göster')});
});
})();

/* v1.14: Garajdan Sahneye formu — yazılanlar tarayıcıda saklanır */
(function(){
var K='yr_rp_taslak';
if(document.querySelector('[data-rp-tamam]')){try{localStorage.removeItem(K)}catch(e){}return}
var f=document.getElementById('rpform');if(!f)return;
function topla(){var o={};[].slice.call(f.elements).forEach(function(el){if(!el.name||el.type==='file'||el.type==='hidden'||el.name==='website'||el.type==='checkbox')return;o[el.name]=el.value});return o}
try{var s=JSON.parse(localStorage.getItem(K)||'null');if(s)Object.keys(s).forEach(function(n){var el=f.querySelector('[name="'+n.replace(/"/g,'')+'"]');if(el&&!el.value)el.value=s[n]})}catch(e){}
var t;f.addEventListener('input',function(){clearTimeout(t);t=setTimeout(function(){try{localStorage.setItem(K,JSON.stringify(topla()))}catch(e){}},400)});
var ft=document.getElementById('rpfoto');if(ft)ft.addEventListener('change',function(){if(ft.files[0]&&ft.files[0].size>5*1024*1024){alert('Fotoğraf en fazla 5 MB olmalı.');ft.value=''}});
f.addEventListener('submit',function(e){
	var old=f.querySelector('.gt-err');if(old)old.remove();
	var hata=function(m,yer){e.preventDefault();var d=document.createElement('div');d.className='gt-err';d.setAttribute('role','alert');d.textContent=m;yer.parentNode.insertBefore(d,yer);d.scrollIntoView({behavior:'smooth',block:'center'})};
	var links=[].slice.call(f.querySelectorAll('input[name^="l_"]')).filter(function(i){return i.value.trim()});
	if(!links.length)return hata('Lütfen müziğinizi dinleyebileceğimiz en az bir bağlantı ekleyin (Bandcamp, Spotify, YouTube…).',f.querySelector('input[name^="l_"]').closest('.g2'));
	var dolu=[].slice.call(f.querySelectorAll('textarea[name^="s"]')).filter(function(t){return t.value.trim().length>=20}).length;
	if(dolu<6)return hata('Lütfen en az 6 soruyu yanıtlayın (her biri en az 20 karakter). Şu an '+dolu+' soru yanıtlı.',f.querySelectorAll('fieldset')[1].querySelector('label'));
	try{localStorage.setItem(K,JSON.stringify(topla()))}catch(e){}
	var b=f.querySelector('.gt-send');if(b){b.disabled=true;b.lastChild.textContent='Gönderiliyor…'}
});
})();
