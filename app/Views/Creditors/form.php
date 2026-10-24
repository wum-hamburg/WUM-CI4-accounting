<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h2><?= esc($moduleTitle ?? 'Kreditor') ?></h2>
	<form method="post" action="<?= esc($action) ?>">
		<?= csrf_field() ?>
		<div class="mb-3">
			<label class="form-label">Firmenname (*)</label>
			<input type="text" name="company_name" class="form-control" required maxlength="255"
				value="<?= old('company_name', $creditor['company_name'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Kundennummer (*)</label>
			<input type="text" name="creditor_number" class="form-control" required maxlength="20"
				value="<?= old('creditor_number', $creditor['creditor_number'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Bemerkung</label>
			<input type="text" name="note" class="form-control" maxlength="255"
				value="<?= old('note', $creditor['note'] ?? '') ?>">
		</div>
		<?php if (!empty($creditor['id'])): ?>
		<div class="mb-3">
			<label class="form-label">Status</label>
			<select name="status" class="form-select">
				<option value="1" <?= (int)($creditor['status'] ?? 1) === 1 ? 'selected' : '' ?>>Aktiv</option>
				<option value="2" <?= (int)($creditor['status'] ?? 1) === 2 ? 'selected' : '' ?>>Einmalig</option>
				<option value="0" <?= (int)($creditor['status'] ?? 1) === 0 ? 'selected' : '' ?>>Inaktiv</option>
			</select>
		</div>
		<?php endif; ?>
		<?php if (!empty($creditor['id']) && (int) ($creditor['status'] ?? 1) === 0): ?>
		<button type="submit" class="btn btn-danger">Löschen!</button>
		<p class="d-inline ms-2 text-muted small">Status auf Aktiv oder Einmalig geändert?</p>
		<?php endif; ?>
		<button type="submit" class="btn btn-primary">Speichern</button>
		<a href="<?= site_url('creditors') ?>" class="btn btn-secondary">Zurück</a>
	</form>
</div>
<?= $this->endSection() ?>
