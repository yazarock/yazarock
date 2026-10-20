# Derleme dosyası biçimi

Her sabah bir dosya eklenir: `derlemeler/YYYY-AA-GG.json` (örnek: `2026-10-20.json`).
Ardından dosya adı `derlemeler/index.json` içindeki `dosyalar` listesinin **sonuna** eklenir.
Site, listedeki son 14 dosyaya bakar ve daha önce almadığı yazıları **taslak** olarak ekler.

## index.json

```json
{"dosyalar": ["2026-10-20.json", "2026-10-21.json"]}
```

## Günlük dosya

```json
{
  "tarih": "2026-10-20",
  "yazilar": [
    {
      "id": "2026-10-20-grup-yeni-album",
      "baslik": "Grup yeni albümünü duyurdu",
      "ozet": "Bir iki cümlelik özet (liste ve paylaşımlarda görünür).",
      "icerik": "<p>Haberin tamamı, kendi cümlelerimizle.</p><p>İkinci paragraf.</p>",
      "kategori": "Haber",
      "etiketler": ["Grup Adı", "Metal"],
      "kaynaklar": [
        {"ad": "Kaynak sitesi", "url": "https://..."}
      ]
    }
  ],
  "etkinlikler": [
    {
      "id": "2026-10-20-malt-blind-istanbul-2410",
      "baslik": "Malt – Blind İstanbul",
      "tarih": "2026-10-24",
      "saat": "21:30",
      "sehir": "İstanbul",
      "mekan": "Blind İstanbul",
      "tur": "Konser",
      "bilet": "https://...",
      "ozet": "İsteğe bağlı kısa not.",
      "aciklama": "<p>Kısa tanıtım, kendi cümlelerimizle.</p>",
      "kaynaklar": [
        {"ad": "Mekân / bilet sayfası", "url": "https://..."}
      ]
    }
  ]
}
```

Kurallar:

- `id` her yazı için benzersiz olmalı (tarih + kısa ad). Aynı `id` ikinci kez eklenmez.
- `baslik` en az 5 karakter, `icerik` boş olmamalı. HTML olarak `<p>`, `<h3>`, `<ul>/<li>`, `<strong>`, `<em>`, `<a>` kullanılabilir; script vb. temizlenir.
- `kategori`: `Haber`, `Konser Duyurusu`, `Festival`, `Röportaj`, `Kültür & Sanat`, `Metal Rehberi` gibi. Yoksa site kategoriyi oluşturur.
- `kaynaklar` yazının sonuna "Kaynaklar" başlığıyla bağlantı listesi olarak eklenir.
- Metinler kaynaklardan kopyalanmaz; tamamen yeniden yazılır.

## Etkinlikler (konser takvimi) — tema 1.10.0 ve sonrası

- `etkinlikler` listesi isteğe bağlıdır. Her kayıt sitede **Etkinlikler** bölümüne **taslak** olarak eklenir; yayınlanınca konser takviminde görünür.
- Zorunlu: `id` (benzersiz), `baslik`, `tarih` (`YYYY-AA-GG`). İsteğe bağlı: `saat` (`SS:DD`), `sehir`, `mekan`, `tur` (`Konser`, `Festival`, `Kulüp Gecesi`; başka değer `Konser` sayılır), `bilet`, `ozet`, `aciklama` (HTML), `kaynaklar`.
- Tarihi geçmiş etkinlikler, aynı `id` ile daha önce eklenenler ve aynı gün aynı başlıkla zaten var olan etkinlikler atlanır.
- Başlık biçimi: `Grup – Mekân` (birden çok grup varsa `Grup 1, Grup 2 – Mekân`).

## Gruplar (grup arşivi) — tema 1.12.0 ve sonrası
Grup listeleri ayrı dosyalarda tutulur ve `index.json` içindeki `"grup_dosyalari"` listesine eklenir (ör. `"gruplar-2026-10-20.json"`). Site bunları **taslak** olarak Gruplar bölümüne ekler; sitede aynı adlı grup varsa (taslak dahil) atlar.
```json
{"gruplar":[{"id":"grup-ornek","ad":"Grup Adı","bolge":"tr","tur":"Metal","stil":"Death metal","kurulus":"Kadıköy, İstanbul · 2012",
 "ozet":"Tek cümlelik özet.","icerik":"<p>…</p>","diskografi":["Albüm (2016)"],"link":"","spotify":"","youtube":"",
 "kaynaklar":[{"ad":"Kaynak","url":"https://…"}]}]}
```
- `durum`: `"yayin"` ise grup doğrudan yayımlanır; yoksa taslak olur.
- `bolge`: `tr` ya da `world`. `tur`: `Rock`, `Metal`, `Alternatif` ya da `Progresif`.

## Yayımlama
- Bir yazı, etkinlik ya da grupta `"durum": "yayin"` varsa doğrudan yayımlanır; yoksa taslak olur.
- Daha önce taslak olarak gelmiş içerikleri yayımlamak için kimliklerini `derlemeler/yayinla.json` içindeki `"idler"` listesine ekle (tema 1.13.1+). Her içerik bir kez yayımlanır.
