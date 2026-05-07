<?php
// Halı Yıkama — Sektöre Özel Seed Data
// Bu dosya install.php tarafından include edilir
// $db, $messages, $errors değişkenleri zaten tanımlıdır

// ══════════════════════════════════════
// VARSAYILAN HİZMETLER
// ══════════════════════════════════════
try {
    $forceServices = isset($_GET['force_services']);
    $count = $db->query('SELECT COUNT(*) FROM services')->fetchColumn();
    if ($forceServices && (int)$count > 0) {
        $db->exec('DELETE FROM services');
        $messages[] = 'Eski hizmetler silindi (force reset).';
        $count = 0;
    }
    if ($forceServices) {
        try { $db->exec('DELETE FROM service_items'); } catch (Exception $e) {}
    }
    if ((int)$count === 0) {
        $defaultServices = [
            ['Halı Yıkama', 'Tüm halı türleri için derinlemesine, hijyenik ve renk koruyucu yıkama.', 'dry_cleaning', '₺80/m²\'den'],
            ['Koltuk Yıkama', 'Yerinde profesyonel koltuk yıkama ile kumaşlarınızı canlandırıyoruz.', 'weekend', '₺1.500\'den'],
            ['Yorgan & Battaniye', 'Yorgan ve battaniyeleriniz için özel yıkama ve kurutma hizmeti.', 'bed', '₺600'],
            ['Perde Yıkama', 'Stor, zebra ve tül perdeleriniz için profesyonel yıkama hizmeti.', 'curtains', '₺100\'den'],
            ['Yatak Yıkama', 'Yatak temizliği ve hijyen hizmeti ile sağlıklı uyku ortamı.', 'king_bed', '₺1.250\'den'],
            ['Leke Çıkarma', 'İnatçı lekelere özel formüllerle etkili ve güvenli çözüm.', 'auto_awesome', 'Ücretsiz Analiz'],
            ['Halı Onarım', 'Halılarınızdaki küçük hasarlar için profesyonel restorasyon hizmeti.', 'history', 'Keşif Üzerine'],
            ['Ücretsiz Servis', 'Hizmet bölgelerimizde ücretsiz evden alım ve eve teslimat hizmeti. Halılarınız en kısa sürede temizlenip teslim edilir.', 'local_shipping', 'Ücretsiz'],
        ];
        $stmt = $db->prepare('INSERT INTO services (title, description, icon, price, sort_order) VALUES (:t, :d, :i, :p, :s)');
        foreach ($defaultServices as $i => $svc) {
            $stmt->execute([':t' => $svc[0], ':d' => $svc[1], ':i' => $svc[2], ':p' => $svc[3], ':s' => $i]);
        }

        // Fiyat kalemlerini ekle (service_items tablosu varsa)
        try {
            $db->query('SELECT 1 FROM service_items LIMIT 1');
            $itemStmt = $db->prepare('INSERT INTO service_items (service_id, name, description, price, unit, sort_order) VALUES (:sid, :n, :d, :p, :u, :s)');

            $haliId = $db->query("SELECT id FROM services WHERE title='Halı Yıkama' LIMIT 1")->fetchColumn();
            if ($haliId) {
                $haliItems = [
                    ['Çift Mekik / Polyester Halı', '', '80', 'm²'],
                    ['Mega / Shaggy Halı', '', '90', 'm²'],
                    ['Yün Tuşe / İskandinav / Pamuk Halı', '', '110', 'm²'],
                    ['Yünlü / Isparta Halı', '', '140', 'm²'],
                    ['Jüt / Patchwork / Step Halı', '', '175', 'm²'],
                    ['Visco / Bambu / Doğal İplik Halı', '', '200', 'm²'],
                    ['Yün Nepal Halı', '', '300', 'm²'],
                    ['Dokuma Halılar', 'El dokuma, antika ve özel halılar', '350', 'm²+'],
                    ['Yerinde Halı Yıkama', 'Minimum 10 m²', '125', 'm²'],
                ];
                foreach ($haliItems as $ii => $item) {
                    $itemStmt->execute([':sid' => $haliId, ':n' => $item[0], ':d' => $item[1], ':p' => $item[2], ':u' => $item[3], ':s' => $ii]);
                }
            }

            $koltukId = $db->query("SELECT id FROM services WHERE title='Koltuk Yıkama' LIMIT 1")->fetchColumn();
            if ($koltukId) {
                $koltukItems = [
                    ['Sabit Minderli Koltuk', '', '1500', 'takım'],
                    ['Minderli / Yastıklı Koltuk', '', '2000', 'takım'],
                    ['Chester / Avangart Koltuk', '', '2500', 'takım'],
                ];
                foreach ($koltukItems as $ii => $item) {
                    $itemStmt->execute([':sid' => $koltukId, ':n' => $item[0], ':d' => $item[1], ':p' => $item[2], ':u' => $item[3], ':s' => $ii]);
                }
            }

            $yorganId = $db->query("SELECT id FROM services WHERE title='Yorgan & Battaniye' LIMIT 1")->fetchColumn();
            if ($yorganId) {
                $itemStmt->execute([':sid' => $yorganId, ':n' => 'Yorgan / Battaniye Yıkama', ':d' => '', ':p' => '600', ':u' => 'adet', ':s' => 0]);
            }

            $perdeId = $db->query("SELECT id FROM services WHERE title='Perde Yıkama' LIMIT 1")->fetchColumn();
            if ($perdeId) {
                $itemStmt->execute([':sid' => $perdeId, ':n' => 'Stor / Zebra Perde', ':d' => '', ':p' => '100', ':u' => 'adet', ':s' => 0]);
            }

            $yatakId = $db->query("SELECT id FROM services WHERE title='Yatak Yıkama' LIMIT 1")->fetchColumn();
            if ($yatakId) {
                $yatakItems = [
                    ['Çift Kişilik Yatak', '', '1500', 'adet'],
                    ['Tek Kişilik Yatak', '', '1250', 'adet'],
                ];
                foreach ($yatakItems as $ii => $item) {
                    $itemStmt->execute([':sid' => $yatakId, ':n' => $item[0], ':d' => $item[1], ':p' => $item[2], ':u' => $item[3], ':s' => $ii]);
                }
            }

            $messages[] = 'Hizmet fiyat kalemleri yüklendi.';
        } catch (Exception $e) {
            // service_items tablosu henüz yoksa sorun değil
        }

        $messages[] = 'Varsayılan hizmetler yüklendi (' . count($defaultServices) . ' hizmet).';
    } else {
        $messages[] = 'Hizmetler zaten mevcut (' . $count . ' kayıt), atlanıyor.';
    }
} catch (Exception $e) {
    $errors[] = 'Hizmet yükleme hatası: ' . $e->getMessage();
}

// ══════════════════════════════════════
// VARSAYILAN GALERİ GÖRSELLERİ
// ══════════════════════════════════════
try {
    $forceGallery = isset($_GET['force_gallery']);
    $count = $db->query("SELECT COUNT(*) FROM gallery WHERE category = 'gallery'")->fetchColumn();
    if ($forceGallery && (int)$count > 0) {
        $db->exec("DELETE FROM gallery WHERE category = 'gallery'");
        $messages[] = 'Eski galeri görselleri silindi (force reset).';
        $count = 0;
    }
    if ((int)$count === 0) {
        $defaultGallery = [
            ['images/gallery-1.webp', 'gallery', 'Profesyonel halı yıkama hizmeti', 0],
            ['images/gallery-2.webp', 'gallery', 'Halı temizlik sonucu', 1],
            ['images/gallery-3.webp', 'gallery', 'Koltuk yıkama işlemi', 2],
            ['images/gallery-4.webp', 'gallery', 'Halı onarım ve bakım', 3],
            ['images/gallery-5.webp', 'gallery', 'Temizlik ekibimiz', 4],
            ['images/gallery-6.webp', 'gallery', 'Müşteri memnuniyeti', 5],
        ];
        $stmt = $db->prepare('INSERT INTO gallery (filename, category, alt_text, sort_order) VALUES (:f, :c, :a, :s)');
        foreach ($defaultGallery as $g) {
            $stmt->execute([':f' => $g[0], ':c' => $g[1], ':a' => $g[2], ':s' => $g[3]]);
        }
        $messages[] = 'Varsayılan galeri görselleri yüklendi (' . count($defaultGallery) . ' görsel).';
    } else {
        $messages[] = 'Galeri görselleri zaten mevcut (' . $count . ' kayıt), atlanıyor.';
    }
} catch (Exception $e) {
    $errors[] = 'Galeri yükleme hatası: ' . $e->getMessage();
}

// ══════════════════════════════════════
// VARSAYILAN YORUMLAR
// ══════════════════════════════════════
try {
    $count = $db->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
    if ((int)$count === 0) {
        $defaultTestimonials = [
            ['Ayşe Yılmaz', '3 yıldır müşterimiz', 5, 'Bank Halı Yıkama gerçekten harika iş çıkarıyor. Halılarım ilk günkü gibi tertemiz ve mis gibi kokuyor. Kesinlikle tavsiye ederim!'],
            ['Mehmet Demir', '1 yıldır müşterimiz', 5, 'Koltuk yıkama hizmetinizden çok memnun kaldım. Evdeki alerji problemim azaldı, sanki yeni koltuk almış gibi oldum. Teşekkürler.'],
            ['Elif Can', '2 yıldır müşterimiz', 5, 'Zorlu lekeler için birçok yer denedim ama sonuç alamadım. Bank Halı Yıkama mucize yarattı! Güler yüzlü ve profesyonel ekip.'],
        ];
        $stmt = $db->prepare('INSERT INTO testimonials (name, role, rating, text, sort_order) VALUES (:n, :r, :rt, :t, :s)');
        foreach ($defaultTestimonials as $i => $t) {
            $stmt->execute([':n' => $t[0], ':r' => $t[1], ':rt' => $t[2], ':t' => $t[3], ':s' => $i]);
        }
        $messages[] = 'Varsayılan yorumlar yüklendi (' . count($defaultTestimonials) . ' yorum).';
    } else {
        $messages[] = 'Yorumlar zaten mevcut (' . $count . ' kayıt), atlanıyor.';
    }
} catch (Exception $e) {
    $errors[] = 'Yorum yükleme hatası: ' . $e->getMessage();
}
