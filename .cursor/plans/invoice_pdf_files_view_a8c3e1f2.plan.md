---
name: Invoice PDF files/view
overview: Rechnungs-PDFs werden nur eingeloggt über die App-Route files/view gestreamt. Speicherort bleibt der Geschwisterordner html/invoices (offline www/invoices). Default webspaceconfig.de fällt weg. Korrekturrechnungen erben den Ordner; leere invoice_folder werden aus Suffix/Disk befüllt.
todos:
  - id: drop-webspaceconfig
    content: "Default invoicesPublicBaseUrl leeren; webspaceconfig.de entfernen; Staff-UI immer files/view (wum_pdf_url / wum_use_public_invoice_urls)"
    status: pending
  - id: files-view-stream
    content: "files/view/shared/invoices/{folder}/{file} muss die Datei aus dirname(ROOTPATH)/invoices/ lesen und streamen (offline+online); Routing/Decoding prüfen"
    status: pending
  - id: correction-inherit
    content: "Korrekturrechnung: Ordner von Originalrechnung/Disk/Suffix erben, nicht mit „kein Rechnungsordner“ blockieren; persistieren"
    status: pending
  - id: autofill-folder
    content: "Normale Buchung/Kundenspeicher: leeres invoice_folder auto-füllen (Match auf volandi, mbb-24, silberring, ihrtaxi-hamburg) und persistieren"
    status: pending
  - id: form-hint
    content: "Kundenformular-Feld behalten; kurzer Hinweis zum PDF-Speicherordner (kein Public-URL-Setup nötig)"
    status: pending
isProject: false
---

# Rechnungs-PDFs: nur eingeloggt über `files/view`

## Entscheidung (fest)

Staff öffnet PDFs **nur nach Login** über die CI-Route `files/view`.

- **Ja:** `…/files/view/shared/invoices/{folder}/{filename}.pdf` ([`AuthFilter`](app/Filters/AuthFilter.php) auf `files/*` in [`app/Config/Filters.php`](app/Config/Filters.php) Zeilen 81–108).
- **Nein:** öffentliches `/invoices/{folder}/{file}.pdf` als Staff-Link (auch nicht analog zu `https://wumpi/invoices/volandi/…`).
- **Nein:** `webspaceconfig.de` / geschützte Subdomain als primärer Mechanismus.
- **Kein** Apache-Alias und **kein** zweites vHost/Domain zum Anzeigen.

Schreiben bleibt Dateisystem: `html/invoices/{folder}/` bzw. offline `www/invoices/{folder}/` (außerhalb der App, analog Akeeba). PHP muss die Datei von dort **lesen** können.

```mermaid
flowchart LR
  click[Dashboard PDF-Button]
  route["files/view/shared/invoices/folder/file.pdf"]
  auth[AuthFilter]
  files[Files::view]
  disk["dirname ROOTPATH / invoices / folder / file"]
  click --> route --> auth --> files --> disk
```

---

## Ist-Zustand (warum es knirscht)

### 1. Falsche Public-URL (Produktion)

[`app/Config/Wum.php`](app/Config/Wum.php) Zeilen 106–121:

- Default `$invoicesPublicBaseUrl = 'https://webspaceconfig.de/html'`
- Das ist das Hetzner-Login-Panel, nicht der PDF-Speicher.
- [`wum_pdf_url()`](app/Helpers/wum_helper.php) Zeilen 259–268 hängt `/invoices/{folder}/{file}` an die Base; ohne sicheren Slash wurde daraus `login.webspaceconfig.dehtml/...`.

[`env.txt`](env.txt) Zeilen 63–67 dokumentiert denselben falschen Host.

[`HetznerUploadService`](app/Services/HetznerUploadService.php) ist nur für **Backup-ZIPs** — nicht anfassen / nicht mit Rechnungs-PDFs vermischen.

### 2. Staff-Links sind uneinheitlich

[`wum_pdf_url()`](app/Helpers/wum_helper.php) Zeilen 242–276 + [`wum_use_public_invoice_urls()`](app/Helpers/wum_helper.php) Zeilen 279–301:

- Live + gesetzte Base → Public-URL (kaputt / Panel).
- Demo / `wumpi` / localhost / Windows-Laufwerk → `site_url('files/view/…')`.

Dashboard / GuV / Flash nutzen alle `wum_pdf_url`:

- [`app/Views/Home/dashboard.php`](app/Views/Home/dashboard.php) Zeilen 70–78 und 151–152
- [`app/Views/ProfitLoss/index.php`](app/Views/ProfitLoss/index.php) Zeile 81
- [`app/Views/partials/flash.php`](app/Views/partials/flash.php) Zeile 28
- [`app/Views/Invoices/preview.php`](app/Views/Invoices/preview.php) (gebuchtes PDF)

**Offline-Mismatch (zu schließen):** HTML kann `https://wumpi/wum-ci4-accounting/files/view/shared/invoices/volandi/202601014volandi.pdf` erzeugen, der Browser öffnet aber `https://wumpi/invoices/volandi/202601014volandi.pdf` (UniServer-Docroot `www/` serviert den Geschwisterordner direkt). Beide Umgebungen müssen **dieselbe** `files/view`-URL erzeugen **und** die Datei dort wirklich streamen. Kein Fallback auf `/invoices/…`.

### 3. Lesepfad ist schon fast richtig

[`wum_shared_invoices_root()`](app/Helpers/wum_helper.php) Zeilen 169–176:

```php
dirname(ROOTPATH) . '/invoices/'
```

CI4 setzt `ROOTPATH` in `Boot::definePathConstants` auf `realpath(APPPATH . '../')` = **Projektwurzel**.

| Umgebung | ROOTPATH | Shared-Root |
|----------|----------|-------------|
| Offline UniServer | `…/www/wum-ci4-accounting/` | `…/www/invoices/` |
| Online Hetzner | `/home/www/p482496/html/demo.wum.hamburg/` | `/home/www/p482496/html/invoices/` |

Online-App-Docroot ist `…/demo.wum.hamburg/public` — deshalb würde `https://demo.wum.hamburg/invoices/…` in `public/invoices` landen. **Irrelevant**, weil Staff nicht über diese URL geht.

[`Files::resolveArchivePath()`](app/Controllers/Files.php) Zeilen 52–65 mappt bereits `shared/invoices/{folder}/{file}` → `wum_shared_invoices_root() . $folder . '/' . $filename`. Route: [`app/Config/Routes.php`](app/Config/Routes.php) Zeile 91 `files/view/(.+)`.

Umsetzung: prüfen, ob Routing/urldecode/Leerzeichen im Dateinamen (`202601015 mbb-24.pdf` vs. `202601014volandi.pdf`) 404 erzeugen; ggf. `view(...$segments)` vs. ein Capture-String reparieren. Inline-PDF-Headers bleiben.

Existierende Ordner auf Disk: `ihrtaxi-hamburg`, `mbb-24`, `silberring`, `volandi`.

### 4. Buchung blockiert ohne `invoice_folder`

[`Invoices::book()`](app/Controllers/Invoices.php) Zeilen 102–104: non-demo **immer** Fehler „Kunde hat keinen Rechnungsordner…“, auch bei **Korrekturrechnung**.

[`Invoices::correction()`](app/Controllers/Invoices.php) Zeilen 303–327 setzt nur `customer_number` + `correction_of` (Original-`invoice_number`). Der Ordner der Originalrechnung (Dateiname / Disk / Kunde) wird nicht übernommen.

[`Invoices::sendEmail()`](app/Controllers/Invoices.php) Zeilen 283–285: gleicher Hard-Fail.

[`DocumentOverviewService::mapInvoice()`](app/Services/DocumentOverviewService.php) Zeilen 154–166: leeres `invoice_folder` → `pdf_path` null → kein PDF-Button in „Letzte Dokumente“.

Kundenfeld: [`app/Views/Customers/form.php`](app/Views/Customers/form.php), [`Customers.php`](app/Controllers/Customers.php), Migration `2026-08-12-100000_AddCustomerInvoiceFolder.php`, Validierung `alpha_dash|max_length[255]`.

---

## Soll-Verhalten

### A. URLs (Staff)

[`wum_pdf_url()`](app/Helpers/wum_helper.php) für `shared/invoices/…` (non-demo):

- **Immer** `site_url('files/view/' . rawurlencode-Segmente)` → `/files/view/shared/invoices/{folder}/{file}.pdf`
- **Nie** `invoicesPublicBaseUrl` anhängen
- Slash-Join nur noch für `files/view`-Segmente; niemals Host + `html` ohne `/`

[`wum_use_public_invoice_urls()`](app/Helpers/wum_helper.php): für Staff-UI **false** / nicht mehr als Schalter für Dashboard-Links (Funktion kann tot oder immer false sein).

Config:

- `$invoicesPublicBaseUrl` Default **`''`**
- `webspaceconfig.de` nirgends in App-Code (allenfalls Kommentar „war Hetzner-Panel, nicht verwenden“)
- [`env.txt`](env.txt): Key weglassen oder auskommentiert leer; **kein** Live-Domain-Beispiel außer Platzhalter, und klar: Staff nutzt das nicht

Demo-Rechnungen bleiben `writable/demo/archives/invoices/` über `files/view/demo/archives/invoices/…`.

Angebote unverändert über `files/view` + `writable/archives/quotes/`.

### B. Schreiben / Lesen

- Schreiben: `wum_invoice_dir($folder)` → Shared-Root + Ordner (unverändert).
- Lesen: nur über `Files::view` aus demselben Pfad.
- Leeren Ordner **nicht** als Dump ins Root `invoices/` nutzen (`wum_ensure_invoice_folder` bleibt ablehnend bei `''`).

### C. Ordner-Auflösung beim Buchen

Neue Hilfslogik (z. B. an [`DocumentNumberService`](app/Services/DocumentNumberService.php), analog [`InvoicePdfSyncService::matchCustomer()`](app/Services/InvoicePdfSyncService.php) Zeilen 154–187 / Dateiname-Regex Zeile 206):

**Korrekturrechnung** (`session('correction_of')` gesetzt) — nicht blockieren:

1. Originalrechnung laden (`invoice_number` = `correction_of`): `filename` + Kunde
2. Ordner erben: `customers.invoice_folder` der Originalkunde, sonst Suffix aus Dateiname (`YYYYMMNNN` + optional Space + suffix), sonst existierender Disk-Ordner (`volandi`, `mbb-24`, …)
3. Persistieren, Verzeichnis sicherstellen, PDF dorthin schreiben

**Normale Rechnung**, Feld leer:

1. `customerSuffix` / last_name / company (alpha_dash: Buchstaben, Zahlen, `_`, `-`; Umlaute transliterieren)
2. Case-insensitive Match gegen vorhandene Ordner unter `wum_shared_invoices_root()`
3. Sonst neuen sicheren Slug anlegen (nicht still ins Root)
4. Am Kunden persistieren (auch beim Speichern in [`Customers`](app/Controllers/Customers.php), wenn noch leer)

Kundenformular behalten; kurzer deutscher Hinweis: Ordnername = Unterordner unter `invoices/` für PDF-Ablage.

### D. Schreibrechte (Hetzner, 755)

`drwxr-xr-x` = Owner rwx, Gruppe/Andere nur rx.

- PHP als Account-User `p482496` **und** Ordner-Owner derselbe → **Lesen und Schreiben** in bestehenden Ordnern; neue Ordner, wenn Parent `html/invoices` ebenfalls Owner-schreibbar.
- PHP als `www-data`/`nobody` → lesen ja, schreiben nein → 775 + gemeinsame Gruppe oder PHP als Owner.

Nach Deploy prüfen: `ls -ld invoices invoices/*` und einzeiliges PHP `is_writable()` auf Root + einen Kundenordner.

---

## Nicht tun

- Public-URL als Staff-Link (`/invoices/…` oder Subdomain)
- Default auf `webspaceconfig.de`
- Neuen Speicherort erfinden
- Alias/zweite Domain zum Anzeigen voraussetzen
- Unrelated Refactors / Commit ohne Auftrag

## Kurz-Check danach

- Offline: PDF-Button-href ist `…/files/view/shared/invoices/{folder}/{file}`; Klick streamt die Datei (Addressleiste bleibt App-Route, nicht `https://wumpi/invoices/…`).
- Online: dasselbe unter `demo.wum.hamburg`, Datei kommt aus `/home/www/p482496/html/invoices/{folder}/`.
- Korrekturrechnung ohne gesetztes DB-Feld bucht in den Ordner der Originalrechnung.
- Neue Rechnung ohne Feld füllt/persistiert einen passenden Ordner.
- `grep webspaceconfig` im App-Code leer (außer optionaler Warnkommentar).
