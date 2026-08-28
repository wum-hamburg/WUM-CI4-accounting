<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Eingangsrechnungen</h1>
	<?= $this->include('partials/flash') ?>
	<div class="d-flex flex-wrap align-items-end gap-3 mb-3">
		<form method="get" action="<?= site_url('incoming-invoices') ?>" class="d-flex align-items-end gap-2">
			<div>
				<label class="form-label" for="year">Buchungsjahr</label>
				<select name="year" id="year" class="form-select" onchange="this.form.submit()">
					<?php
					$labels = [
						$years[0] => 'Vorletztes Jahr (' . $years[0] . ')',
						$years[1] => 'Letztes Jahr (' . $years[1] . ')',
						$years[2] => 'Aktuelles Jahr (' . $years[2] . ')',
					];
					foreach ($years as $y):
					?>
					<option value="<?= (int) $y ?>" <?= (int) $y === (int) $year ? 'selected' : '' ?>>
						<?= esc($labels[$y]) ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
			<button type="submit" class="btn btn-outline-secondary">Anzeigen</button>
		</form>
		<a class="btn btn-success" href="<?= site_url('incoming-invoices/capture') ?>">Neue Erfassung</a>
	</div>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th>Datum</th>
				<th>Kreditor</th>
				<th>Nr.</th>
				<th>Rechnungsnummer</th>
				<th class="text-end">Betrag</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php if ($rows === []): ?>
			<tr>
				<td colspan="6" class="text-muted">Keine Eingangsrechnungen für <?= (int) $year ?>.</td>
			</tr>
			<?php else: ?>
			<?php foreach ($rows as $row): ?>
			<tr>
				<td><?= esc(format_german_date($row['invoice_date'])) ?></td>
				<td><?= esc($row['company_name'] ?? '—') ?></td>
				<td><?= esc($row['creditor_number'] ?? '—') ?></td>
				<td><?= esc($row['invoice_number']) ?></td>
				<td class="text-end"><?= esc(format_price((int) $row['amount_cents'])) ?></td>
				<td>
					<a href="<?= site_url('incoming-invoices/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-primary">Ändern</a>
					<form method="post" action="<?= site_url('incoming-invoices/delete/' . (int) $row['id']) ?>" class="d-inline"
						onsubmit="return confirm('Eingangsrechnung wirklich löschen?');">
						<?= csrf_field() ?>
						<button type="submit" class="btn btn-sm btn-danger">Löschen</button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<?= $this->endSection() ?>
