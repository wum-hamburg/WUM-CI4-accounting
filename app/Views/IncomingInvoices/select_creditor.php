<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Erfassung Eingangsrechnungen</h1>
	<?= $this->include('partials/flash') ?>
	<p class="mb-3">Buchungsjahr: <strong><?= (int) $bookingYear ?></strong></p>
	<form method="post" action="<?= site_url('incoming-invoices/select-creditor') ?>">
		<?= csrf_field() ?>
		<div class="mb-3" style="max-width: 32rem;">
			<label class="form-label" for="creditor_id">Kreditoren auswählen oder neu anlegen</label>
			<select name="creditor_id" id="creditor_id" class="form-select" required>
				<option value="NEU">Neu anlegen</option>
				<?php foreach ($creditors as $row): ?>
				<option value="<?= (int) $row['id'] ?>"><?= esc(($row['company_name'] ?? '') . ' — ' . ($row['creditor_number'] ?? '')) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit" class="btn btn-primary">Auswählen</button>
		<a href="<?= site_url('incoming-invoices') ?>" class="btn btn-secondary">Zur Liste</a>
	</form>
</div>
<?= $this->endSection() ?>
