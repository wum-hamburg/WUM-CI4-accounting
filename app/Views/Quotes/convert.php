<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container py-4">
	<h1 class="pb-2 border-bottom">Angebot in Rechnung umwandeln</h1>
	<p>Angebot <strong><?= esc($quote['quote_number']) ?></strong> — <?= esc($quote['company_name']) ?></p>
	<?php if (!empty($hasInvoice)): ?>
		<p class="text-muted">Weitere Rechnung — alle Positionen des Angebots stehen zur Verfügung.</p>
	<?php else: ?>
		<p class="text-muted">Wählen Sie die zu berechnenden Mengen. Restmengen werden angezeigt, Sie können auch mehr als angeboten abrechnen.</p>
	<?php endif; ?>

	<?php if (session('error')): ?>
		<div class="alert alert-danger"><?= esc(session('error')) ?></div>
	<?php endif; ?>

	<form method="post" action="<?= site_url('quotes/' . $quote['id'] . '/convert') ?>">
		<?= csrf_field() ?>
		<h3 class="h6">Angebotspositionen</h3>
		<table class="table table-sm table-hover">
			<thead>
				<tr>
					<th>Pos</th>
					<th>Bezeichnung</th>
					<th class="text-end">Angeboten</th>
					<th class="text-end">Berechnet</th>
					<th class="text-end">Rest</th>
					<th style="width:6rem">Jetzt berechnen</th>
					<th style="width:6rem">Einheit</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($positions as $i => $pos):
				$title = $pos['type'] === 'custom' ? $pos['title'] : ($pos['title'] ?? 'Artikel ' . ($pos['article_number'] ?? ''));
				$offered = (int) ($pos['offered_qty'] ?? $pos['qty'] ?? 0);
				$invoiced = (int) ($pos['invoiced_qty'] ?? 0);
				$remaining = (int) ($pos['remaining_qty'] ?? max(0, $offered - $invoiced));
				$isOptional = wum_is_optional_position($pos);
				$optionalParsed = $isOptional ? wum_parse_optional_title($title) : null;
				$defaultBill = $isOptional ? 0 : ($remaining > 0 ? $remaining : 0);
				$unit = $pos['unit'] ?? 'Stck.';
			?>
				<tr>
					<td><?= esc($pos['position_number'] ?? '') ?></td>
					<td>
						<?= esc($title) ?>
						<?php if ($isOptional && $optionalParsed): ?><span class="badge bg-secondary ms-1" title="Standard: nicht berechnen"><?= esc(rtrim($optionalParsed['label'], ':')) ?></span><?php endif; ?>
					</td>
					<td class="text-end"><?= $offered ?></td>
					<td class="text-end"><?= $invoiced ?></td>
					<td class="text-end"><?= $remaining ?></td>
					<td><input type="number" name="bill_qty[<?= (int)$i ?>]" class="form-control form-control-sm" min="0" value="<?= $defaultBill ?>"></td>
					<td><?= esc($unit) ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h3 class="h6 mt-4">Zusätzliche Positionen</h3>
		<p class="text-muted small">z. B. Extra, mündlich vereinbart, Aufwand — nicht im Angebot enthalten.</p>
		<table class="table table-sm" id="extra-positions">
			<thead>
				<tr><th>Bezeichnung</th><th style="width:6rem">Menge</th><th style="width:6rem">Einheit</th><th style="width:8rem">EP (€)</th><th></th></tr>
			</thead>
			<tbody>
				<tr class="extra-row">
					<td><input type="text" name="extra_title[]" class="form-control form-control-sm" placeholder="z. B. Aufwand"></td>
					<td><input type="number" name="extra_qty[]" class="form-control form-control-sm" min="0"></td>
					<td><?= wum_unit_select('extra_unit[]', 'Stck.') ?></td>
					<td><input type="text" name="extra_price[]" class="form-control form-control-sm" placeholder="0,00"></td>
					<td><button type="button" class="btn btn-sm btn-outline-danger remove-extra-row" title="Zeile entfernen">&times;</button></td>
				</tr>
			</tbody>
		</table>
		<button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="add-extra-row">+ Zusatzposition</button>

		<button type="submit" class="btn btn-primary">Weiter zur Anpassung</button>
		<a href="<?= site_url('dashboard') ?>" class="btn btn-secondary">Abbrechen</a>
	</form>
</div>
<script src="<?= base_url('assets/js/wum-positions.js') ?>"></script>
<script>
wumInitPositionRows({
	tableSelector: '#extra-positions',
	addButtonId: 'add-extra-row',
	rowClass: 'extra-row',
	removeClass: 'remove-extra-row',
	titleName: 'extra_title[]',
	qtyName: 'extra_qty[]',
	unitName: 'extra_unit[]',
	priceName: 'extra_price[]'
});
</script>
<?= $this->endSection() ?>
