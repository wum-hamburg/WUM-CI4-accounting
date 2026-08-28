<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<?php
$amountEuro = old('amount_euro', isset($invoice['amount_cents'])
	? number_format(((int) $invoice['amount_cents']) / 100, 2, ',', '')
	: '');
$bookingYear = (int) ($invoice['booking_year'] ?? date('Y'));
$minDate = $bookingYear . '-01-01';
$maxDate = $bookingYear . '-12-31';
$companyLabel = trim((string) ($invoice['company_name'] ?? ''));
if ($companyLabel === '') {
	$companyLabel = '— (Kreditor gelöscht)';
}
?>
<div class="container mt-4">
	<h1><?= esc($moduleTitle ?? 'Eingangsrechnung ändern') ?></h1>
	<?= $this->include('partials/flash') ?>
	<?php if (session()->getFlashdata('errors')):
		$err = session()->getFlashdata('errors');
	?>
	<div class="alert alert-danger">
		<ul class="mb-0">
			<?php foreach ((array) $err as $msg): ?>
			<li><?= esc(is_array($msg) ? implode(' ', $msg) : $msg) ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>
	<?= $this->include('IncomingInvoices/partials/import_upload') ?>
	<form method="post" action="<?= esc($action) ?>">
		<?= csrf_field() ?>
		<div class="mb-3" style="max-width: 32rem;">
			<label class="form-label">Buchungsjahr</label>
			<input type="text" class="form-control" value="<?= (int) $invoice['booking_year'] ?>" readonly>
		</div>
		<div class="mb-3" style="max-width: 32rem;">
			<label class="form-label">Firmenname</label>
			<input type="text" class="form-control" value="<?= esc($companyLabel) ?>" readonly>
		</div>
		<div class="mb-3" style="max-width: 32rem;">
			<label class="form-label">Rechnungsnummer (*)</label>
			<input type="text" name="invoice_number" class="form-control" required maxlength="100"
				value="<?= esc(old('invoice_number', $invoice['invoice_number'] ?? '')) ?>">
		</div>
		<div class="mb-3" style="max-width: 16rem;">
			<label class="form-label">Rechnungsdatum (*)</label>
			<input type="date" name="invoice_date" class="form-control" required
				min="<?= esc($minDate) ?>" max="<?= esc($maxDate) ?>"
				value="<?= esc(old('invoice_date', $invoice['invoice_date'] ?? '')) ?>">
		</div>
		<div class="mb-3" style="max-width: 16rem;">
			<label class="form-label">Betrag (*)</label>
			<input type="text" name="amount_euro" class="form-control" required
				value="<?= esc($amountEuro) ?>">
		</div>
		<button type="submit" class="btn btn-primary">Speichern</button>
		<a href="<?= site_url('incoming-invoices/show/' . (int) $invoice['id']) ?>" class="btn btn-secondary">Zurück</a>
	</form>
	<form method="post" action="<?= site_url('incoming-invoices/delete/' . (int) $invoice['id']) ?>" class="mt-3"
		onsubmit="return confirm('Eingangsrechnung wirklich löschen?');">
		<?= csrf_field() ?>
		<button type="submit" class="btn btn-danger">Löschen</button>
	</form>
</div>
<?= $this->endSection() ?>
