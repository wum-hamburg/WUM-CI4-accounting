<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<?php
$isNew = !empty($isNew);
$companyValue = old('company_name', $creditor['company_name'] ?? '');
$amountEuro = old('amount_euro', isset($invoice['amount_cents'])
	? number_format(((int) $invoice['amount_cents']) / 100, 2, ',', '')
	: '');
$defaultDate = sprintf('%04d-%02d-%02d', (int) $bookingYear, (int) date('n'), (int) date('j'));
if (! checkdate((int) date('n'), (int) date('j'), (int) $bookingYear)) {
	$defaultDate = sprintf('%04d-%02d-28', (int) $bookingYear, (int) date('n'));
}
$dateValue = old('invoice_date', $invoice['invoice_date'] ?? $defaultDate);
$minDate = (int) $bookingYear . '-01-01';
$maxDate = (int) $bookingYear . '-12-31';
?>
<div class="container mt-4">
	<h1>Erfassung Eingangsrechnungen</h1>
	<?= $this->include('partials/flash') ?>
	<?php if (!empty($errors) || session()->getFlashdata('errors')):
		$err = $errors ?? session()->getFlashdata('errors');
	?>
	<div class="alert alert-danger">
		<ul class="mb-0">
			<?php foreach ((array) $err as $msg): ?>
			<li><?= esc(is_array($msg) ? implode(' ', $msg) : $msg) ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>
	<p class="mb-3">Buchungsjahr: <strong><?= (int) $bookingYear ?></strong></p>
	<?= $this->include('IncomingInvoices/partials/import_upload') ?>
	<form method="post" action="<?= esc($action) ?>">
		<?= csrf_field() ?>
		<input type="hidden" name="creditor_id" value="<?= $isNew ? '0' : (int) ($creditor['id'] ?? 0) ?>">
		<div class="mb-3" style="max-width: 32rem;">
			<label class="form-label">Firmenname (*)</label>
			<?php if ($isNew): ?>
			<input type="text" name="company_name" class="form-control" required maxlength="255"
				value="<?= esc($companyValue) ?>">
			<?php else: ?>
			<input type="text" class="form-control" value="<?= esc($companyValue) ?>" readonly>
			<input type="hidden" name="company_name" value="<?= esc($companyValue) ?>">
			<?php endif; ?>
		</div>
		<?php if ($isNew): ?>
		<div class="mb-3" style="max-width: 32rem;">
			<label class="form-label">Kundennummer (*)</label>
			<input type="text" name="creditor_number" class="form-control" required maxlength="20"
				value="<?= esc(old('creditor_number', $creditorNumber ?? '')) ?>">
		</div>
		<?php endif; ?>
		<div class="mb-3" style="max-width: 32rem;">
			<label class="form-label">Rechnungsnummer (*)</label>
			<input type="text" name="invoice_number" class="form-control" required maxlength="100"
				value="<?= esc(old('invoice_number', $invoice['invoice_number'] ?? '')) ?>">
		</div>
		<div class="mb-3" style="max-width: 16rem;">
			<label class="form-label">Rechnungsdatum (*)</label>
			<input type="date" name="invoice_date" class="form-control" required
				min="<?= esc($minDate) ?>" max="<?= esc($maxDate) ?>"
				value="<?= esc($dateValue) ?>">
		</div>
		<div class="mb-3" style="max-width: 16rem;">
			<label class="form-label">Betrag (*)</label>
			<input type="text" name="amount_euro" class="form-control" required
				placeholder="0,00" value="<?= esc($amountEuro) ?>">
		</div>
		<button type="submit" class="btn btn-primary">Buchen</button>
		<a href="<?= site_url('incoming-invoices/capture') ?>" class="btn btn-secondary">Zurück</a>
	</form>
</div>
<?= $this->endSection() ?>
