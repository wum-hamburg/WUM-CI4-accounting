<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1><?= esc($moduleTitle ?? 'Eingangsrechnung') ?></h1>
	<?= $this->include('partials/flash') ?>
	<div class="card" style="max-width: 36rem;">
		<div class="card-body">
			<h5 class="card-title">Gebuchte Eingangsrechnung</h5>
			<p class="card-text">Folgende Daten wurden gespeichert:</p>
		</div>
		<ul class="list-group list-group-flush">
			<li class="list-group-item"><strong>Buchungsjahr:</strong> <?= (int) $invoice['booking_year'] ?></li>
			<li class="list-group-item"><strong>Firmenname:</strong> <?= esc($invoice['company_name'] ?? '—') ?></li>
			<li class="list-group-item"><strong>Rechnungsnummer:</strong> <?= esc($invoice['invoice_number']) ?></li>
			<li class="list-group-item"><strong>Rechnungsdatum:</strong> <?= esc(format_german_date($invoice['invoice_date'])) ?></li>
			<li class="list-group-item"><strong>Betrag:</strong> <?= esc(format_price((int) $invoice['amount_cents'])) ?></li>
		</ul>
		<div class="card-body">
			<a href="<?= site_url('incoming-invoices/capture') ?>" class="btn btn-success">Weiterer</a>
			<a href="<?= site_url('incoming-invoices/edit/' . (int) $invoice['id']) ?>" class="btn btn-primary">Ändern</a>
			<a href="<?= site_url('incoming-invoices') ?>" class="btn btn-secondary">Zur Liste</a>
			<form method="post" action="<?= site_url('incoming-invoices/delete/' . (int) $invoice['id']) ?>" class="d-inline"
				onsubmit="return confirm('Eingangsrechnung wirklich löschen?');">
				<?= csrf_field() ?>
				<button type="submit" class="btn btn-danger">Löschen</button>
			</form>
		</div>
	</div>
</div>
<?= $this->endSection() ?>
