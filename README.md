# FWW Social Publisher

WordPress-Plugin für [Feuerwehr Wolfurt](https://feuerwehr.wolfurt.at) – automatisches Veröffentlichen von Beiträgen auf **Facebook**, **Instagram** und eine **WhatsApp-Kopierhilfe**.

---

## Funktionen

| Plattform | Automatisch | Manuell | Beschreibung |
|-----------|:-----------:|:-------:|---|
| **Facebook** | ✅ | ✅ | Bild + Text + Link via Meta Graph API |
| **Instagram** | ✅ | ✅ | Bild (Pflicht) + Caption via zweistufigem Container-Flow |
| **WhatsApp** | — | ✅ | Text in Zwischenablage kopieren + Deep-Link zum Öffnen |

### Beitragstext
Der Plugin verwendet als Beitragstext bevorzugt den **Social-Media-Text aus dem KI Content Creator** (konfigurierbar über den Post-Meta-Key). Ist dieser nicht vorhanden, wird automatisch auf den Auszug oder den Beitragsinhalt zurückgegriffen.

---

## Voraussetzungen

- WordPress 6.0 oder höher
- PHP 8.0 oder höher
- Facebook-Seite mit gültigem **Page Access Token**
- Instagram **Business Account** (verbunden mit der Facebook-Seite)
- Meta Graph API Version: `v19.0`

---

## Installation

### Option A – ZIP hochladen (empfohlen)
1. Die Datei `fww-social-publisher.zip` herunterladen
2. Im WordPress-Backend: **Plugins → Neu hinzufügen → Plugin hochladen**
3. ZIP auswählen und installieren
4. Plugin aktivieren

### Option B – FTP / direkt auf Server
1. Den Ordner `fww-social-publisher` nach `wp-content/plugins/` kopieren
2. Im WordPress-Backend das Plugin aktivieren

Bei der Aktivierung wird automatisch die Datenbanktabelle `wp_fww_social_log` erstellt.

---

## Einrichtung

### 1. Facebook Page Access Token erstellen
1. [Meta for Developers](https://developers.facebook.com/) öffnen
2. App erstellen (Typ: **Business**)
3. Produkt **Facebook Login** hinzufügen
4. Unter **Graph API Explorer**: Token für die Seite mit folgenden Berechtigungen generieren:
   - `pages_manage_posts`
   - `pages_read_engagement`
   - `publish_to_groups` (optional)
5. Token in den Plugin-Einstellungen eintragen

### 2. Instagram Business Account einrichten
1. Instagram-Konto muss als **Business Account** konfiguriert sein
2. Instagram-Konto mit der Facebook-Seite verknüpfen
3. Im **Graph API Explorer** die Instagram Business Account ID abrufen:
   ```
   GET /{facebook-page-id}?fields=instagram_business_account
   ```
4. ID und Token (derselbe wie Facebook oder separater) in den Einstellungen eintragen

### 3. Plugin konfigurieren
Unter **Einstellungen → FWW Social Publisher**:

| Feld | Beschreibung |
|------|---|
| Facebook Page Access Token | Langlebiger Token der Seite |
| Facebook Page ID | Numerische ID der Facebook-Seite |
| Instagram Business Account ID | Numerische ID des IG Business Accounts |
| Instagram Access Token | Leer lassen = Facebook-Token wird verwendet |
| Automatisch auf Facebook posten | Checkbox (Standard: aktiv) |
| Automatisch auf Instagram posten | Checkbox (Standard: aktiv) |
| Kategorie-Filter | Nur aus bestimmten Kategorien posten (leer = alle) |
| KI Content Creator Meta-Key | Post-Meta-Schlüssel für den Social-Media-Text (Standard: `_ki_social_media_text`) |

---

## Verwendung

### Automatisches Posten
Sobald ein Beitrag veröffentlicht wird (`status → publish`), postet das Plugin automatisch auf den konfigurierten Plattformen – vorausgesetzt, die Auto-Post-Optionen sind aktiviert und die Kategorie stimmt überein.

### Manuelles Posten
In der Seitenleiste des Beitragseditors (**FWW Social Publisher**):

- **Post to Facebook now** – sofort auf Facebook posten (auch erneut möglich)
- **Post to Instagram now** – sofort auf Instagram posten (nur wenn Beitragsbild vorhanden)
- **Copy to Clipboard** – WhatsApp-Text in die Zwischenablage kopieren
- **Open WhatsApp** – WhatsApp direkt öffnen

### WhatsApp-Text
Das Format des kopierten Textes:
```
[Beitragstitel]

[Social-Media-Text / Auszug]

[Permalink]
```

---

## Aktivitätsprotokoll

Alle Post-Versuche werden in der Datenbanktabelle `wp_fww_social_log` gespeichert. Die letzten 50 Einträge sind auf der Einstellungsseite einsehbar.

**Statuswerte:**
- `success` – erfolgreich gepostet
- `error` – Fehler beim Posten (Fehlermeldung sichtbar)
- `skipped` – übersprungen (z. B. kein Beitragsbild für Instagram)

---

## Doppelpost-Schutz

Nach einem erfolgreichen Post wird das Post-Meta `_fww_facebook_posted` bzw. `_fww_instagram_posted` mit dem Zeitstempel gesetzt. Solange dieses Meta vorhanden ist, wird ein erneutes automatisches Posten verhindert. Über die manuellen Buttons kann trotzdem erneut gepostet werden.

---

## Dateistruktur

```
fww-social-publisher/
├── fww-social-publisher.php          Plugin-Header & Bootstrap
├── includes/
│   ├── class-fww-social-publisher.php   Hauptklasse (Hooks, AJAX, Logik)
│   ├── class-fww-facebook-api.php        Facebook Graph API Wrapper
│   └── class-fww-instagram-api.php       Instagram Graph API Wrapper
├── admin/
│   ├── settings-page.php             Einstellungsseite (Template)
│   └── meta-box.php                  Meta Box im Beitragseditor (Template)
└── assets/
    └── admin.js                      AJAX-Handler, Clipboard, Spinner
```

---

## Technische Details

- **API-Version:** Meta Graph API `v19.0`
- **Facebook-Endpunkte:** `/{page-id}/photos` (mit Bild) · `/{page-id}/feed` (ohne Bild)
- **Instagram-Flow:** `/{ig-id}/media` (Container erstellen) → `/{ig-id}/media_publish` (veröffentlichen)
- **WordPress-Hook:** `transition_post_status` – feuert nur bei `neu → publish`
- **Sicherheit:** Nonces für alle AJAX-Aktionen · `sanitize_*` / `esc_*` überall · Capability Checks
- **Keine externen Abhängigkeiten** – kein Composer, keine NPM-Pakete

---

## Lizenz

GPL-2.0-or-later – siehe [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html)
