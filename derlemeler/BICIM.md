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
  ]
}
```

Kurallar:

- `id` her yazı için benzersiz olmalı (tarih + kısa ad). Aynı `id` ikinci kez eklenmez.
- `baslik` en az 5 karakter, `icerik` boş olmamalı. HTML olarak `<p>`, `<h3>`, `<ul>/<li>`, `<strong>`, `<em>`, `<a>` kullanılabilir; script vb. temizlenir.
- `kategori`: `Haber`, `Konser Duyurusu`, `Festival`, `Röportaj`, `Kültür & Sanat`, `Metal Rehberi` gibi. Yoksa site kategoriyi oluşturur.
- `kaynaklar` yazının sonuna "Kaynaklar" başlığıyla bağlantı listesi olarak eklenir.
- Metinler kaynaklardan kopyalanmaz; tamamen yeniden yazılır.
