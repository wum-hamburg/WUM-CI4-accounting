# WUM CI4 Accounting

Buchhaltungs- und Beleganwendung auf CodeIgniter 4 für Kleinunternehmer (§ 19 UStG): Angebote, Rechnungen, Eingangsrechnungen und die Einnahmen-Überschuss-Rechnung (EÜR). Dieselbe Installation kann die Vollversion und eine zurücksetzbare Demo enthalten.

Accounting and document app on CodeIgniter 4 for small businesses under the German small-business VAT scheme (§ 19 UStG): quotes, invoices, incoming invoices, and a cash-basis profit-and-loss report (EÜR). One installation can run the full version and a resettable demo together.

Hervorgegangen aus dem CodeIgniter 4 Application Starter.

---

## Deutsch

### Über das Programm

WUM CI4 Accounting führt Stammdaten, Ausgangsbelege und Eingangsrechnungen in einer Anwendung. Gebuchte Rechnungen werden als PDF mit eingebettetem ZUGFeRD-/Factur-X-XML ausgegeben. Angebote können per öffentlichem Link zur Auftragsbestätigung werden. Die EÜR fasst bezahlte Ausgangsrechnungen und gebuchte Eingangsrechnungen zusammen.

### Komponenten

- **CodeIgniter 4** — Anwendung, Routing, Session, Migrationen und CLI (`php spark`)
- **TCPDF** (`app/ThirdParty/TCPDF`, `app/Libraries/TcpdfDocument.php`) — PDF für Angebote, Rechnungen und die EÜR. Gebuchte Rechnungen werden als PDF/A-3 mit eingebettetem ZUGFeRD-XML geschrieben. Demo-PDFs tragen das Wasserzeichen MUSTER/DEMO.
- **easybill/zugferd-php** (`app/Services/XRechnungXmlService.php`, `app/Services/IncomingInvoiceImportService.php`) — erzeugt ZUGFeRD/Factur-X nach EN 16931 und liest dasselbe XML aus Eingangs-PDFs und XML-Dateien (XRechnung)
- **PHPMailer** — Versand von Dokumenten per E-Mail

### Vollversion und Demo in einer Installation

Ein Setup, zwei Datenbanken. Die Codebasis ist dieselbe.

- **Vollversion** (immer): eigene MySQL-Datenbank, z. B. `wum-ci4-accounting`. Superadmin-Benutzer **`admin`**.
- **Demo** (optional, beim Setup „Demo-Datenbank installieren?“): zweite, leere Datenbank, z. B. `wum-demo`. Login `demo` / `demo`, Einstieg `/start`. Die Demo enthält Beispieldaten, ein Dokumentlimit und das Wasserzeichen auf den PDFs. `php spark demo:reset` setzt nur die Demo zurück (stündlicher Cron), die Vollversion bleibt unberührt.

Standardantwort beim Setup ist **`n`**: Installation nur der Vollversion. **`j`** (oder `y`) installiert die Demo zusätzlich.

### Funktionen

- **Stammdaten:** Debitoren, Kreditoren, Artikel, Textbausteine
- **Dokumente:** Angebote und Rechnungen (Vorschau, Buchen, Status, E-Mail)
- **E-Rechnung:** PDF mit XRechnung/ZUGFeRD
- **Auftragsbestätigung:** öffentlicher Link aus dem Angebot
- **Eingangsrechnungen:** Erfassung inkl. PDF-/ZUGFeRD-/XRechnung-Import
- **EÜR:** Einnahmen-Überschuss-Rechnung inkl. PDF
- **Benutzer & Rollen:** `registered`, `admin`, `superadmin` (optional `demo`)
- **Sicherung:** Online-Backup (Hetzner Storage Box), Offline-Restore (Superadmin)

### Anforderungen

- PHP 8.1+ mit `intl`, `mbstring` (für MySQL: `mysqlnd`)
- MySQL / MariaDB
- Composer
- Webserver-Document-Root = Ordner **`public/`** (nicht Projektwurzel)

### Installation

1. Repository klonen und Abhängigkeiten installieren:
   ```bash
   composer install --no-dev
   ```
2. Leere MySQL-Datenbank anlegen (z. B. `wum-ci4-accounting`). Für die Demo zusätzlich eine leere Datenbank (z. B. `wum-demo`).
3. Interaktives Setup (fragt DB-Zugang und Admin-Passwort; Superadmin-User = **`admin`**):
   ```bash
   php spark setup
   ```
   Standardantwort **Demo-Datenbank installieren? → `n`** = nur Vollversion. **`j`** = Vollversion und Demo.
4. Browser auf die App öffnen und mit `admin` / gewähltem Passwort anmelden. Demo: `/start` oder `demo` / `demo`.

Erneutes Setup ist durch `writable/install.lock` gesperrt.

Stündlicher Demo-Reset (Cron), nur wenn die Demo installiert wurde:

```bash
php spark demo:reset
```

### Online-Backup (Hetzner)

In `.env` setzen: `wum.hetznerHost`, `wum.hetznerUser`, `wum.hetznerPassword`, optional `wum.hetznerRemoteDir`.  
Wöchentlich: `php spark backup:weekly` — manuell: `/backup` bzw. `/restore`.

Vorlage: siehe `env.txt`.

---

## English

### About

WUM CI4 Accounting keeps master data, outgoing documents, and incoming invoices in one application. Booked invoices are issued as a PDF with embedded ZUGFeRD/Factur-X XML. A quote can be confirmed through a public order-confirmation link. The EÜR report sums paid outgoing invoices and booked incoming invoices.

### Components

- **CodeIgniter 4** — application, routing, sessions, migrations, and the CLI (`php spark`)
- **TCPDF** (`app/ThirdParty/TCPDF`, `app/Libraries/TcpdfDocument.php`) — PDFs for quotes, invoices, and the EÜR report. Booked invoices are written as PDF/A-3 with embedded ZUGFeRD XML. Demo PDFs carry a MUSTER/DEMO watermark.
- **easybill/zugferd-php** (`app/Services/XRechnungXmlService.php`, `app/Services/IncomingInvoiceImportService.php`) — builds ZUGFeRD/Factur-X (EN 16931) and reads the same XML from incoming PDFs and XML files (XRechnung)
- **PHPMailer** — sends documents by email

### Full version and demo in one installation

One setup, two databases. The codebase is the same.

- **Full version** (always): its own MySQL database, e.g. `wum-ci4-accounting`. Superadmin user **`admin`**.
- **Demo** (optional; setup asks “Demo-Datenbank installieren?”): a second empty database, e.g. `wum-demo`. Login `demo` / `demo`, entry point `/start`. The demo ships with sample data, a document limit, and the watermark on PDFs. `php spark demo:reset` resets only the demo (hourly cron) and leaves the full version untouched.

The setup default is **`n`**: full version only. **`j`** (or `y`) installs the demo as well.

### Features

- **Master data:** customers (debtors), creditors, articles, text blocks
- **Documents:** quotes and invoices (preview, book, status, email)
- **E-invoicing:** PDF with XRechnung/ZUGFeRD
- **Order confirmation:** public link from a quote
- **Incoming invoices:** capture including PDF/ZUGFeRD/XRechnung import
- **Cash-basis P&L (EÜR):** report plus PDF
- **Users & roles:** `registered`, `admin`, `superadmin` (optional `demo`)
- **Backup:** online backup (Hetzner Storage Box), offline restore (superadmin)

### Requirements

- PHP 8.1+ with `intl`, `mbstring` (MySQL: `mysqlnd`)
- MySQL / MariaDB
- Composer
- Web server document root = **`public/`** (not the project root)

### Installation

1. Clone the repository and install dependencies:
   ```bash
   composer install --no-dev
   ```
2. Create an empty MySQL database (e.g. `wum-ci4-accounting`). For the demo, create a second empty database (e.g. `wum-demo`).
3. Run interactive setup (prompts for DB credentials and admin password; superadmin user = **`admin`**):
   ```bash
   php spark setup
   ```
   Default **Demo-Datenbank installieren? → `n`** = full version only. **`j`** = full version and demo.
4. Open the app in a browser and sign in with `admin` / your chosen password. Demo: `/start` or `demo` / `demo`.

Re-running setup is blocked by `writable/install.lock`.

Hourly demo reset (cron), only when the demo was installed:

```bash
php spark demo:reset
```

### Online backup (Hetzner)

Set in `.env`: `wum.hetznerHost`, `wum.hetznerUser`, `wum.hetznerPassword`, optional `wum.hetznerRemoteDir`.  
Weekly: `php spark backup:weekly` — UI: `/backup` and `/restore`.

Template: see `env.txt`.
