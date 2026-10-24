<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Einnahmenüberschussrechnung</h1>
	<p class="text-muted">Kleinunternehmer gemäß § 19 UStG – Beträge ohne Umsatzsteuer. Steuerjahr ab 2025.</p>
	<div class="d-flex flex-wrap align-items-end gap-3 mb-4">
		<form method="get" action="<?= site_url('guv') ?>" class="d-flex align-items-end gap-2">
			<div>
				<label class="form-label" for="year">Steuerjahr</label>
				<select name="year" id="year" class="form-select" onchange="this.form.submit()">
					<?php foreach ($years as $y): ?>
					<option value="<?= (int) $y ?>" <?= (int) $y === (int) $year ? 'selected' : '' ?>>
						<?= (int) $y ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
			<button type="submit" class="btn btn-outline-secondary">Anzeigen</button>
		</form>
		<?php if (! wum_is_demo()): ?>
		<a class="btn btn-primary" href="<?= site_url('guv/pdf?year=' . (int) $year) ?>" target="_blank">
			<i class="fa-solid fa-file-pdf"></i> PDF mit Logo
		</a>
		<?php endif; ?>
	</div>

	<h2 class="h4">Betriebseinnahmen (Ausgangsrechnungen)</h2>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th>Datum</th>
				<th>Nr.</th>
				<th>Kunde</th>
				<th>Status</th>
				<th class="text-end">Betrag</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php if ($report['income'] === []): ?>
			<tr>
				<td colspan="6" class="text-muted">Keine Ausgangsrechnungen in <?= (int) $year ?>.</td>
			</tr>
			<?php else: ?>
			<?php foreach ($report['income'] as $row): ?>
			<tr>
				<td><?= esc(format_german_date($row['date'])) ?></td>
				<td><?= esc($row['number']) ?></td>
				<td><?= esc($row['name']) ?></td>
				<td>
					<?php if ((int) $row['status'] !== \Config\Wum::INVOICE_CORRECTION): ?>
						<?php if ((int) $row['status'] === \Config\Wum::INVOICE_PAID): ?>
							<form method="post" action="<?= site_url('invoices/' . (int) $row['id'] . '/status') ?>" class="d-inline"
								onsubmit="return confirm('Rechnung <?= esc($row['number']) ?> wieder als offen markieren?');">
								<?= csrf_field() ?>
								<input type="hidden" name="status" value="<?= \Config\Wum::INVOICE_OPEN ?>">
								<input type="hidden" name="redirect" value="guv">
								<input type="hidden" name="year" value="<?= (int) $year ?>">
								<button type="submit" class="btn btn-sm btn-success" title="Bezahlt – klicken zum Zurücksetzen">
									<i class="fa-solid fa-check"></i>
								</button>
							</form>
						<?php else: ?>
							<form method="post" action="<?= site_url('invoices/' . (int) $row['id'] . '/status') ?>" class="d-inline"
								onsubmit="return confirm('Rechnung <?= esc($row['number']) ?> als bezahlt markieren?');">
								<?= csrf_field() ?>
								<input type="hidden" name="status" value="<?= \Config\Wum::INVOICE_PAID ?>">
								<input type="hidden" name="redirect" value="guv">
								<input type="hidden" name="year" value="<?= (int) $year ?>">
								<button type="submit" class="btn btn-sm btn-outline-success" title="Als bezahlt markieren">
									<i class="fa-regular fa-circle"></i>
								</button>
							</form>
						<?php endif; ?>
					<?php else: ?>
						<?= esc($row['status_label']) ?>
					<?php endif; ?>
				</td>
				<td class="text-end<?= (int) $row['status'] === \Config\Wum::INVOICE_OPEN ? ' text-muted' : '' ?>"
					<?= (int) $row['status'] === \Config\Wum::INVOICE_OPEN ? 'title="Offen – nicht in der Summe"' : '' ?>>
					<?= esc(format_price((int) $row['amount_cents'])) ?>
				</td>
				<td class="text-nowrap">
					<?php if (! empty($row['pdf_path'])): ?>
						<a href="<?= esc(wum_pdf_url((string) $row['pdf_path'])) ?>"
						   class="btn btn-sm btn-outline-secondary" target="_blank" title="Rechnung als PDF">PDF</a>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr class="table-light fw-bold">
				<td colspan="4">Summe Einnahmen <span class="fw-normal text-muted small">(ohne offene Rechnungen)</span></td>
				<td class="text-end"><?= esc(format_price((int) $report['income_cents'])) ?></td>
				<td></td>
			</tr>
		</tfoot>
	</table>

	<h2 class="h4 mt-4">Betriebsausgaben (Eingangsrechnungen)</h2>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th>Datum</th>
				<th>Kreditor</th>
				<th>KD-Nr.</th>
				<th>Rechnungsnummer</th>
				<th class="text-end">Betrag</th>
			</tr>
		</thead>
		<tbody>
			<?php if ($report['expenses'] === []): ?>
			<tr>
				<td colspan="5" class="text-muted">Keine Eingangsrechnungen in <?= (int) $year ?>.</td>
			</tr>
			<?php else: ?>
			<?php foreach ($report['expenses'] as $row): ?>
			<tr>
				<td><?= esc(format_german_date($row['date'])) ?></td>
				<td><?= esc($row['name']) ?></td>
				<td><?= esc($row['creditor_no']) ?></td>
				<td><?= esc($row['number']) ?></td>
				<td class="text-end"><?= esc(format_price((int) $row['amount_cents'])) ?></td>
			</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr class="table-light fw-bold">
				<td colspan="4">Summe Ausgaben</td>
				<td class="text-end"><?= esc(format_price((int) $report['expense_cents'])) ?></td>
			</tr>
		</tfoot>
	</table>

	<?php
	$profit = (int) $report['profit_cents'];
	$profitClass = $profit >= 0 ? 'text-success' : 'text-danger';
	$profitLabel = $profit >= 0 ? 'Gewinn' : 'Verlust';
	?>
	<div class="card mt-4" style="max-width: 28rem;">
		<div class="card-body">
			<h2 class="h5 card-title">Ergebnis <?= (int) $year ?></h2>
			<p class="mb-1">Einnahmen: <?= esc(format_price((int) $report['income_cents'])) ?></p>
			<p class="mb-1">Ausgaben: <?= esc(format_price((int) $report['expense_cents'])) ?></p>
			<p class="mb-0 fs-4 fw-bold <?= $profitClass ?>">
				<?= esc($profitLabel) ?>: <?= esc(format_price($profit)) ?>
			</p>
		</div>
	</div>
</div>
<?= $this->endSection() ?>
