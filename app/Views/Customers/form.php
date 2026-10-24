<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h2><?= esc($moduleTitle ?? 'Kunde') ?></h2>
	<form method="post" action="<?= esc($action) ?>">
		<?= csrf_field() ?>
		<div class="mb-3">
			<label class="form-label">Firma</label>
			<input type="text" name="company" class="form-control" value="<?= old('company', $customer['company'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Vorname (*)</label>
			<input type="text" name="first_name" class="form-control" required value="<?= old('first_name', $customer['first_name'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Nachname (*)</label>
			<input type="text" name="last_name" class="form-control" required value="<?= old('last_name', $customer['last_name'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Straße (*)</label>
			<input type="text" name="street" class="form-control" required value="<?= old('street', $customer['street'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">PLZ (*)</label>
			<input type="number" name="postal_code" class="form-control" required value="<?= old('postal_code', $customer['postal_code'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Stadt (*)</label>
			<input type="text" name="city" class="form-control" required value="<?= old('city', $customer['city'] ?? 'Hamburg') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Telefon</label>
			<input type="text" name="phone" class="form-control" value="<?= old('phone', $customer['phone'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">E-Mail (*)</label>
			<input type="email" name="email" class="form-control" required value="<?= old('email', $customer['email'] ?? '') ?>">
		</div>
		<?php if (! wum_is_demo()): ?>
		<div class="mb-3">
			<label class="form-label">Rechnungsordner</label>
			<input type="text" name="invoice_folder" class="form-control"
				pattern="[A-Za-z0-9_-]+" maxlength="255"
				placeholder="z. B. mbb-24"
				value="<?= old('invoice_folder', $customer['invoice_folder'] ?? '') ?>">
			<div class="form-text">Unterordner unter invoices/ für PDF-Ablage (nur Buchstaben, Zahlen, _ und -). Leer lassen: wird beim Speichern/Buchen aus Kundendaten bzw. vorhandenem Ordner ermittelt. PDFs öffnen nur eingeloggt über die App.</div>
		</div>
		<?php endif; ?>
		<div class="mb-3">
			<label class="form-label">Bemerkung</label>
			<input type="text" name="note" class="form-control" value="<?= old('note', $customer['note'] ?? '') ?>">
		</div>
		<?php if (!empty($customer['id'])): ?>
		<div class="mb-3">
			<label class="form-label">Status</label>
			<select name="status" class="form-select">
				<option value="1" <?= (int)($customer['status'] ?? 1) === 1 ? 'selected' : '' ?>>Aktiv</option>
				<option value="2" <?= (int)($customer['status'] ?? 1) === 2 ? 'selected' : '' ?>>Einmalig</option>
				<option value="0" <?= (int)($customer['status'] ?? 1) === 0 ? 'selected' : '' ?>>Inaktiv</option>
			</select>
		</div>
		<?php endif; ?>
		<?php if (!empty($customer['id']) && (int) ($customer['status'] ?? 1) === 0): ?>
		<button type="submit" class="btn btn-danger">Löschen!</button>
		<p class="d-inline ms-2 text-muted small">Status auf Aktiv oder Einmalig geändert?</p>
		<?php endif; ?>
		<button type="submit" class="btn btn-primary">Speichern</button>
		<a href="<?= site_url('customers') ?>" class="btn btn-secondary">Zurück</a>
	</form>
</div>
<?= $this->endSection() ?>
