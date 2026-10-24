<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container py-4">
	<h1 class="pb-2 border-bottom">Willkommen bei WUM-Buchhaltung!</h1>
	<p class="lead">Schön, dass Sie da sind!</p>
	<p class="text-muted">
		Hier sehen Sie Ihre letzten Angebote und Rechnungen auf einen Blick.
		Wählen Sie einen Menüpunkt, um Kunden, Artikel oder neue Dokumente zu bearbeiten.
	</p>

	<h2 class="h4 mt-4">Letzte Dokumente</h2>
	<form method="get" action="<?= site_url('dashboard') ?>" class="row g-2 mb-3">
		<div class="col-auto">
			<input type="text" name="q" class="form-control" placeholder="Angebots- oder Rechnungsnummer" value="<?= esc($searchQuery ?? '') ?>">
		</div>
		<div class="col-auto">
			<button type="submit" class="btn btn-outline-primary">Suchen</button>
			<?php if (!empty($searchQuery)): ?>
				<a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary">Zurücksetzen</a>
			<?php endif; ?>
		</div>
	</form>

	<?php if ($documents === []): ?>
		<p class="text-muted">Keine Dokumente gefunden.</p>
	<?php else: ?>
		<div class="table-responsive">
			<table class="table table-hover table-sm align-middle">
				<thead>
					<tr>
						<th>Typ</th>
						<th>Nummer</th>
						<th>Datum</th>
						<th>Kunde</th>
						<th class="text-end">Betrag</th>
						<th>Status</th>
						<th>Aktionen</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($documents as $doc): ?>
					<tr>
						<td><?= esc($doc['type_label']) ?></td>
						<td><strong><?= esc($doc['number']) ?></strong></td>
						<td><?= esc(date('d.m.Y', strtotime($doc['date']))) ?></td>
						<td><?= esc($doc['customer']) ?></td>
						<td class="text-end"><?= format_price($doc['amount_cents']) ?></td>
						<td>
							<?php if ($doc['type'] === 'invoice' && $doc['status'] !== \Config\Wum::INVOICE_CORRECTION): ?>
								<?php if ($doc['status'] === \Config\Wum::INVOICE_PAID): ?>
									<form method="post" action="<?= site_url('invoices/' . $doc['id'] . '/status') ?>" class="d-inline"
										onsubmit="return confirm('Rechnung <?= esc($doc['number']) ?> wieder als offen markieren?');">
										<?= csrf_field() ?>
										<input type="hidden" name="status" value="<?= \Config\Wum::INVOICE_OPEN ?>">
										<button type="submit" class="btn btn-sm btn-success" title="Bezahlt – klicken zum Zurücksetzen">
											<i class="fa-solid fa-check"></i>
										</button>
									</form>
								<?php else: ?>
									<form method="post" action="<?= site_url('invoices/' . $doc['id'] . '/status') ?>" class="d-inline"
										onsubmit="return confirm('Rechnung <?= esc($doc['number']) ?> als bezahlt markieren?');">
										<?= csrf_field() ?>
										<input type="hidden" name="status" value="<?= \Config\Wum::INVOICE_PAID ?>">
										<button type="submit" class="btn btn-sm btn-outline-success" title="Als bezahlt markieren">
											<i class="fa-regular fa-circle"></i>
										</button>
									</form>
								<?php endif; ?>
							<?php else: ?>
								<span class="badge bg-secondary"><?= esc($doc['status_label']) ?></span>
							<?php endif; ?>
						</td>
						<td class="text-nowrap">
							<?php if (!empty($doc['pdf_path'])): ?>
								<a href="<?= esc(wum_pdf_url((string) $doc['pdf_path'])) ?>"
								   class="btn btn-sm btn-outline-secondary" target="_blank" title="PDF anzeigen">PDF</a>
							<?php elseif ($doc['filename'] && $doc['type'] === 'quote'): ?>
								<a href="<?= esc(wum_pdf_url(wum_archives_web_prefix() . '/quotes/' . $doc['filename'])) ?>"
								   class="btn btn-sm btn-outline-secondary" target="_blank" title="PDF anzeigen">PDF</a>
							<?php elseif ($doc['filename'] && wum_is_demo()): ?>
								<a href="<?= esc(wum_pdf_url(wum_invoice_web_path(null, (string) $doc['filename']))) ?>"
								   class="btn btn-sm btn-outline-secondary" target="_blank" title="PDF anzeigen">PDF</a>
							<?php endif; ?>

							<?php if ($doc['type'] === 'quote'): ?>
								<?php if ($doc['status'] === \Config\Wum::QUOTE_OPEN): ?>
									<form method="post" action="<?= site_url('quotes/' . $doc['id'] . '/status') ?>" class="d-inline"
										onsubmit="return confirm('Angebot <?= esc($doc['number']) ?> als abgelehnt markieren?');">
										<?= csrf_field() ?>
										<input type="hidden" name="status" value="<?= \Config\Wum::QUOTE_DECLINED ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger" title="Kein Auftrag">Abgelehnt</button>
									</form>
									<?php if (! empty($doc['confirmation_url'])): ?>
										<a href="<?= esc($doc['confirmation_url']) ?>"
										   class="btn btn-sm btn-outline-info"
										   <?= wum_is_demo() ? '' : 'target="_blank" rel="noopener"' ?>
										   title="Link zur Auftragsbestätigung öffnen">Bestätigungslink</a>
									<?php endif; ?>
									<?php if (!empty($doc['show_email_button'])): ?>
										<form method="post" action="<?= site_url('quotes/' . $doc['id'] . '/email') ?>" class="d-inline"
											onsubmit="return confirm('Angebot <?= esc($doc['number']) ?> per E-Mail an den Kunden senden?');">
											<?= csrf_field() ?>
											<button type="submit" class="btn btn-sm btn-outline-primary" title="Per E-Mail versenden">E-Mail</button>
										</form>
									<?php endif; ?>
								<?php elseif ($doc['status'] === \Config\Wum::QUOTE_ORDERED): ?>
									<?php if (! empty($doc['confirmation_pdf_path'])): ?>
										<a href="<?= esc(wum_pdf_url((string) $doc['confirmation_pdf_path'])) ?>"
										   class="btn btn-sm btn-success" target="_blank" title="Auftragsbestätigung PDF">Bestätigung</a>
									<?php endif; ?>
									<?php if (! empty($doc['show_decline_confirmation'])): ?>
										<form method="post" action="<?= site_url('quotes/' . $doc['id'] . '/decline-confirmation') ?>" class="d-inline"
											onsubmit="return confirm('Eingegangene Auftragsbestätigung zu <?= esc($doc['number']) ?> ablehnen?');">
											<?= csrf_field() ?>
											<button type="submit" class="btn btn-sm btn-outline-danger" title="Bestätigung ablehnen">Ablehnen</button>
										</form>
									<?php endif; ?>
									<?php if (empty($doc['invoice_id'])): ?>
										<a href="<?= site_url('quotes/' . $doc['id'] . '/convert') ?>" class="btn btn-sm btn-primary" title="In Rechnung umwandeln">Rechnung</a>
									<?php else: ?>
										<a href="<?= site_url('quotes/' . $doc['id'] . '/convert') ?>" class="btn btn-sm btn-primary" title="Weitere Teilrechnung aus Angebot">Weitere Rechnung</a>
									<?php endif; ?>
								<?php endif; ?>
								<?php if ($doc['status'] !== \Config\Wum::QUOTE_OPEN && !empty($doc['show_email_button'])): ?>
									<form method="post" action="<?= site_url('quotes/' . $doc['id'] . '/email') ?>" class="d-inline"
										onsubmit="return confirm('Angebot <?= esc($doc['number']) ?> per E-Mail an den Kunden senden?');">
										<?= csrf_field() ?>
										<button type="submit" class="btn btn-sm btn-outline-primary" title="Per E-Mail versenden">E-Mail</button>
									</form>
								<?php endif; ?>
							<?php elseif ($doc['type'] === 'invoice' && $doc['status'] !== \Config\Wum::INVOICE_CORRECTION): ?>
								<a href="<?= site_url('invoices/' . $doc['id'] . '/correction') ?>" class="btn btn-sm btn-warning"
								   onclick="return confirm('Korrekturrechnung zu <?= esc($doc['number']) ?> erstellen?');"
								   title="Korrekturrechnung">Korrektur</a>
								<?php if (!empty($doc['show_email_button'])): ?>
									<form method="post" action="<?= site_url('invoices/' . $doc['id'] . '/email') ?>" class="d-inline"
										onsubmit="return confirm('Rechnung <?= esc($doc['number']) ?> erneut per E-Mail an den Kunden senden?');">
										<?= csrf_field() ?>
										<button type="submit" class="btn btn-sm btn-outline-primary" title="Per E-Mail nachsenden">E-Mail</button>
									</form>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if (! wum_is_demo() && !empty($yearFiles)): ?>
		<h2 class="h4 mt-5">Rechnungen <?= esc($yearLabel ?? date('Y')) ?> (Dateien)</h2>
		<div class="table-responsive">
			<table class="table table-hover table-sm align-middle">
				<thead>
					<tr>
						<th>Datei</th>
						<th>Ordner</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($yearFiles as $file): ?>
					<tr>
						<td><?= esc($file['filename']) ?></td>
						<td><?= esc($file['folder']) ?></td>
						<td class="text-nowrap">
							<a href="<?= esc(wum_pdf_url((string) $file['pdf_path'])) ?>"
							   class="btn btn-sm btn-outline-secondary" target="_blank" title="PDF anzeigen">PDF</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
<?= $this->endSection() ?>
