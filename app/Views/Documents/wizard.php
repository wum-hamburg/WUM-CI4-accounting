<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container py-4">
	<h1 class="pb-2 border-bottom"><?= esc($typeLabel) ?> erstellen</h1>
	<?php if (!empty($error)): ?>
		<div class="alert alert-danger"><?= esc($error) ?></div>
	<?php endif; ?>
	<?php
	$stepLabels = [
		2 => 'Kunde',
		3 => $documentType === 'quote' ? 'Positionen & Texte' : 'Artikel & Texte',
		4 => 'Anpassung',
	];
	$displayStep = $step - 1;
	?>
	<nav aria-label="Wizard-Schritte" class="mb-4">
		<ol class="breadcrumb">
			<?php foreach ($stepLabels as $s => $label): ?>
				<li class="breadcrumb-item<?= $s === $step ? ' active' : '' ?>"><?= esc($label) ?></li>
			<?php endforeach; ?>
		</ol>
	</nav>

	<?php if ($step === 2): ?>
		<h2 class="h4">Schritt <?= (int) $displayStep ?>: Kunde wählen</h2>
		<form method="post" action="<?= site_url('documents') ?>">
			<?= csrf_field() ?>
			<?php foreach ($customerGroups as $status => $rows): ?>
				<h3 class="h6"><?= esc($statusLabels[$status] ?? $status) ?></h3>
				<table class="table table-sm table-hover">
					<thead><tr><th></th><th>Firma</th><th>Name</th><th>Stadt</th></tr></thead>
					<tbody>
					<?php foreach ($rows as $row): ?>
						<tr>
							<td><input type="radio" name="customer_number" value="<?= esc($row['customer_number']) ?>" required></td>
							<td><?= esc($row['company']) ?></td>
							<td><?= esc($row['first_name'] . ' ' . $row['last_name']) ?></td>
							<td><?= esc($row['city']) ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>
			<button type="submit" name="save_customer" value="1" class="btn btn-primary">Weiter</button>
		</form>

	<?php elseif ($step === 3): ?>
		<h2 class="h4">Schritt <?= (int) $displayStep ?>: <?= $documentType === 'quote' ? 'Positionen &amp; Texte' : 'Artikel &amp; Texte' ?></h2>
		<form method="post" action="<?= site_url('documents') ?>" id="wizard-step3">
			<?= csrf_field() ?>

			<?php $showCustomPositions = $documentType === 'quote' || !empty($isCorrection); ?>
			<?php if ($showCustomPositions): ?>
				<h3 class="h5 mt-3">Freie Positionen</h3>
				<?php if ($documentType === 'quote'): ?>
				<p class="text-muted small">Einmalige Leistungen ohne Artikelstamm — Bezeichnung, Menge und Preis direkt eingeben. Mit <strong>A</strong> (Alternativ) oder <strong>E</strong> (Eventual) markieren; diese Positionen fließen nicht in die Summe ein.</p>
				<?php else: ?>
				<p class="text-muted small">Zusätzliche Positionen für die Korrekturrechnung — Bezeichnung, Menge und Preis direkt eingeben.</p>
				<?php endif; ?>
				<table class="table table-sm" id="custom-positions">
					<thead>
						<tr><th>Bezeichnung</th><th style="width:6rem">Menge</th><th style="width:6rem">Einheit</th><th style="width:8rem">Preis (€)</th><th style="width:7rem"></th></tr>
					</thead>
					<tbody>
					<?php
					$rows = $customLines !== [] ? $customLines : [['title' => '', 'qty' => '', 'price_cents' => 0, 'unit' => 'Stck.']];
					foreach ($rows as $i => $line):
						$priceEuro = isset($line['price_cents']) ? number_format($line['price_cents'] / 100, 2, ',', '') : '';
						$unit = $line['unit'] ?? 'Stck.';
						$parsed = $documentType === 'quote' ? wum_parse_optional_title($line['title'] ?? '') : null;
						$titleValue = $parsed ? $parsed['detail'] : ($line['title'] ?? '');
						$optionalKind = $parsed ? ($parsed['label'] === 'Alternativ:' ? 'alternativ' : 'eventual') : '';
					?>
						<tr class="custom-row"<?= $optionalKind !== '' ? ' data-optional-prefix="' . esc($optionalKind, 'attr') . '"' : '' ?>>
							<td><input type="text" name="custom_title[]" class="form-control form-control-sm" value="<?= esc($titleValue) ?>"></td>
							<td><input type="number" name="custom_qty[]" class="form-control form-control-sm" min="0" value="<?= esc($line['qty'] ?? '') ?>"></td>
							<td><?= wum_unit_select('custom_unit[]', $unit) ?></td>
							<td><input type="text" name="custom_price[]" class="form-control form-control-sm" placeholder="0,00" value="<?= esc($priceEuro) ?>"></td>
							<td class="text-nowrap">
								<?php if ($documentType === 'quote'): ?>
								<button type="button" class="btn btn-sm <?= $optionalKind === 'alternativ' ? 'btn-secondary active' : 'btn-outline-secondary' ?> prefix-alternativ" title="Alternativ">A</button>
								<button type="button" class="btn btn-sm <?= $optionalKind === 'eventual' ? 'btn-secondary active' : 'btn-outline-secondary' ?> prefix-eventual" title="Eventual">E</button>
								<?php endif; ?>
								<button type="button" class="btn btn-sm btn-outline-danger remove-custom-row" title="Zeile entfernen">&times;</button>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="add-custom-row">+ Position hinzufügen</button>

				<h3 class="h5 mt-3">Katalog-Artikel <span class="text-muted small">(optional)</span></h3>
			<?php else: ?>
				<h3 class="h5 mt-3">Artikel</h3>
			<?php endif; ?>

			<table class="table table-hover">
				<thead><tr><th>Menge</th><th>Nr.</th><th>Artikel</th><th>Preis</th></tr></thead>
				<tbody>
				<?php foreach ($articles as $row): ?>
					<tr>
						<td><input type="number" class="form-control form-control-sm" style="width:5rem" name="quantity[<?= esc($row['article_number']) ?>]" min="0" value="<?= esc($lineItems[$row['article_number']] ?? '') ?>"></td>
						<td><?= esc($row['article_number']) ?></td>
						<td><?= esc($row['title']) ?></td>
						<td><?= format_price((int)$row['price_cents']) ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php foreach ($textCategories as [$prefix, $title]): ?>
				<?php $texts = $textModel->getByPrefix($prefix); if ($texts === []) continue; ?>
				<h4 class="h6"><?= esc($title) ?></h4>
				<ul class="list-group mb-3">
				<?php foreach ($texts as $wert):
					$key = $wert['text_key'];
					$defaultChecked = $documentType === 'invoice' && ($key === 'vor1' || $key === 'schluss1');
					$checked = in_array($key, $textKeys, true) || $defaultChecked;
					$disabled = $documentType === 'invoice' && $key === 'schluss1';
				?>
					<li class="list-group-item">
						<input type="checkbox" name="text_keys[]" value="<?= esc($key) ?>" <?= $checked ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
						<?php if ($disabled): ?><input type="hidden" name="text_keys[]" value="schluss1"><?php endif; ?>
						<?= esc(mb_substr($wert['content'], 0, 120)) ?>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
			<button type="submit" name="save_selection" value="1" class="btn btn-primary">Weiter zur Anpassung</button>
		</form>

		<?php if ($showCustomPositions): ?>
		<script src="<?= base_url('assets/js/wum-positions.js?v=3') ?>"></script>
		<script>
		wumInitPositionRows({
			tableSelector: '#custom-positions',
			addButtonId: 'add-custom-row',
			rowClass: 'custom-row',
			removeClass: 'remove-custom-row',
			titleName: 'custom_title[]',
			qtyName: 'custom_qty[]',
			unitName: 'custom_unit[]',
			priceName: 'custom_price[]',
			formSelector: '#wizard-step3',
			optionalPrefixes: <?= $documentType === 'quote' ? 'true' : 'false' ?>
		});
		</script>
		<?php endif; ?>

	<?php elseif ($step === 4 && $customer): ?>
		<h2 class="h4">Schritt <?= (int) $displayStep ?>: Kontrolle &amp; Anpassung</h2>
		<p>Summe: <strong><?= format_price((int)$totalCents) ?></strong></p>
		<?php
		$previewUrl = $documentType === 'quote' ? site_url('quotes/preview') : site_url('invoices/preview');
		$articleByNumber = [];
		foreach ($articles as $articleRow) {
			$articleByNumber[$articleRow['article_number']] = $articleRow;
		}
		$vorParts = [];
		foreach ($textKeys as $key) {
			if (!str_starts_with($key, 'vor')) {
				continue;
			}
			$block = $textModel->where('text_key', $key)->first();
			if ($block && ($block['content'] ?? '') !== '') {
				$vorParts[] = $block['content'];
			}
		}
		$defaultHeader = implode("\n\n", $vorParts);
		$hinParts = [];
		foreach ($textKeys as $key) {
			if (!str_starts_with($key, 'hin')) {
				continue;
			}
			$block = $textModel->where('text_key', $key)->first();
			if ($block && ($block['content'] ?? '') !== '') {
				$hinParts[] = $block['content'];
			}
		}
		$defaultIntroItems = ($documentType === 'invoice' && $hinParts !== [])
			? implode("\n\n", $hinParts)
			: ($documentType === 'quote' ? 'Angebotene Leistungen:' : 'Aktuelle Rechnungsartikel:');
		?>
		<p class="mb-3">
			<a href="<?= site_url('documents?step=3') ?>" class="btn btn-sm btn-outline-secondary">← Zurück zu Schritt 2</a>
		</p>
		<form method="post" action="<?= esc($previewUrl) ?>" id="wizard-step4">
			<?= csrf_field() ?>
			<h3 class="h6">Positionen</h3>
			<?php if (!empty($isCorrection)): ?>
				<p class="text-muted small">Vorhandene Positionen können angepasst werden. Weitere Positionen lassen sich ergänzen.</p>
			<?php endif; ?>
			<table class="table table-sm mb-2" id="wizard-lines">
				<thead>
					<tr>
						<th style="width:3rem">Pos</th>
						<th>Bezeichnung</th>
						<th style="width:5rem">Menge</th>
						<th style="width:6rem">Einheit</th>
						<th style="width:7rem">EP (€)</th>
						<th class="text-end" style="width:7rem">GP</th>
						<th style="width:2.5rem"></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$lineRows = $previewLines;
				if ($lineRows === [] && $documentType === 'invoice') {
					$lineRows = [[
						'type'            => 'custom',
						'title'           => '',
						'qty'             => '',
						'price_cents'     => 0,
						'unit'            => 'Stck.',
						'position_number' => '',
						'position_source' => 'invoice_extra',
						'article_number'  => '',
					]];
				}
				foreach ($lineRows as $item):
					if ($item['type'] === 'catalog') {
						$articleRow = $articleByNumber[$item['article_number']] ?? null;
						$title = $item['title'] ?? ($articleRow['title'] ?? $item['article_number']);
						$unitCents = (int) ($item['price_cents'] ?? $articleRow['price_cents'] ?? 0);
					} else {
						$title = $item['title'] ?? '';
						$unitCents = (int) ($item['price_cents'] ?? 0);
					}
					$priceEuro = $unitCents > 0 ? number_format($unitCents / 100, 2, ',', '') : '';
					$lineCents = (int) ($item['qty'] ?? 0) * $unitCents;
					$unit = $item['unit'] ?? 'Stck.';
					$isExtra = ($item['position_source'] ?? 'quote') === 'invoice_extra';
					$isOptional = wum_is_optional_position($item);
					$optionalParsed = $isOptional ? wum_parse_optional_title($title) : null;
				?>
					<tr class="<?= $isExtra ? 'invoice-extra-row' : '' ?>">
						<td>
							<?= esc($item['position_number'] ?? '') ?>
							<?php if ($isExtra): ?><span class="badge bg-info text-dark" title="Zusatzposition">Extra</span><?php endif; ?>
							<?php if ($isOptional && $optionalParsed): ?><span class="badge bg-secondary" title="Nicht in der Summe"><?= esc(rtrim($optionalParsed['label'], ':')) ?></span><?php endif; ?>
							<input type="hidden" name="line_position_number[]" value="<?= esc($item['position_number'] ?? '') ?>">
							<input type="hidden" name="line_type[]" value="<?= esc($item['type']) ?>">
							<input type="hidden" name="line_position_source[]" value="<?= esc($item['position_source'] ?? 'quote') ?>">
							<input type="hidden" name="line_article_number[]" value="<?= esc($item['article_number'] ?? '') ?>">
						</td>
						<td><input type="text" name="line_title[]" class="form-control form-control-sm" value="<?= esc($title) ?>"></td>
						<td><input type="number" name="line_qty[]" class="form-control form-control-sm" min="0" value="<?= esc($item['qty'] ?? '') ?>"></td>
						<td><?= wum_unit_select('line_unit[]', $unit) ?></td>
						<td><input type="text" name="line_price[]" class="form-control form-control-sm" value="<?= esc($priceEuro) ?>" placeholder="0,00"></td>
						<td class="text-end line-gp"><?php if ($isOptional && $documentType === 'quote'): ?><span class="text-muted small">nicht in Summe</span><?php elseif (($item['qty'] ?? '') !== ''): ?><?= format_price($lineCents) ?><?php endif; ?></td>
						<td>
							<?php if ($isExtra): ?>
								<button type="button" class="btn btn-sm btn-outline-danger remove-invoice-extra-row" title="Zeile entfernen">&times;</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ($documentType === 'invoice'): ?>
				<button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="add-invoice-line">+ Position hinzufügen</button>
			<?php endif; ?>
			<div class="mb-3">
				<label class="form-label">Vorbemerkung</label>
				<textarea name="intro_header" class="form-control" rows="3"><?= esc($defaultHeader) ?></textarea>
			</div>
			<div class="mb-3">
				<label class="form-label">Text vor Positionen</label>
				<textarea name="intro_items" class="form-control" rows="<?= ($documentType === 'invoice' && $hinParts !== []) ? '4' : '2' ?>"><?= esc($defaultIntroItems) ?></textarea>
			</div>
			<?php if ($documentType === 'quote'): ?>
				<?php foreach ($textKeys as $key): if (!str_starts_with($key, 'hin')) continue;
					$block = $textModel->where('text_key', $key)->first();
				?>
				<div class="mb-3">
					<label class="form-label"><?= esc($key) ?></label>
					<textarea name="<?= esc($key) ?>" class="form-control" rows="3"><?= esc($block['content'] ?? '') ?></textarea>
				</div>
				<?php endforeach; ?>
			<?php elseif ($documentType === 'invoice'): ?>
				<?php foreach ($textKeys as $key): if (!str_starts_with($key, 'schluss')) continue;
					$block = $textModel->where('text_key', $key)->first();
				?>
				<div class="mb-3">
					<label class="form-label"><?= esc($key) ?></label>
					<textarea name="<?= esc($key) ?>" class="form-control" rows="3"><?= esc($block['content'] ?? '') ?></textarea>
				</div>
				<?php endforeach; ?>
			<?php endif; ?>
			<div class="d-flex flex-wrap gap-2">
				<button type="submit" formaction="<?= site_url('documents') ?>" name="save_step4" value="1" class="btn btn-outline-secondary">Änderungen speichern</button>
				<button type="submit" class="btn btn-primary btn-lg"><?= $documentType === 'quote' ? 'Angebot' : 'Rechnung' ?> als Entwurf erstellen</button>
			</div>
		</form>
		<form method="post" action="<?= site_url('documents') ?>" class="mt-3">
			<?= csrf_field() ?>
			<button type="submit" name="abort" value="1" class="btn btn-danger">Abbruch</button>
		</form>
		<?php if ($documentType === 'invoice'): ?>
		<script>
		(function () {
			const addBtn = document.getElementById('add-invoice-line');
			const table = document.getElementById('wizard-lines');
			if (!addBtn || !table) {
				return;
			}
			const unitHtml = '<select name="line_unit[]" class="form-select form-select-sm">'
				+ '<option value="">—</option>'
				+ '<option value="psch.">psch.</option>'
				+ '<option value="Std.">Std.</option>'
				+ '<option value="AW">AW</option>'
				+ '<option value="Stck." selected>Stck.</option>'
				+ '</select>';
			addBtn.addEventListener('click', function () {
				const tbody = table.querySelector('tbody');
				const tr = document.createElement('tr');
				tr.className = 'invoice-extra-row';
				tr.innerHTML = '<td><span class="badge bg-info text-dark" title="Zusatzposition">Extra</span>'
					+ '<input type="hidden" name="line_position_number[]" value="">'
					+ '<input type="hidden" name="line_type[]" value="custom">'
					+ '<input type="hidden" name="line_position_source[]" value="invoice_extra">'
					+ '<input type="hidden" name="line_article_number[]" value="">'
					+ '</td>'
					+ '<td><input type="text" name="line_title[]" class="form-control form-control-sm"></td>'
					+ '<td><input type="number" name="line_qty[]" class="form-control form-control-sm" min="0"></td>'
					+ '<td>' + unitHtml + '</td>'
					+ '<td><input type="text" name="line_price[]" class="form-control form-control-sm" placeholder="0,00"></td>'
					+ '<td class="text-end line-gp"></td>'
					+ '<td><button type="button" class="btn btn-sm btn-outline-danger remove-invoice-extra-row" title="Zeile entfernen">&times;</button></td>';
				tbody.appendChild(tr);
				tr.querySelector('input[name="line_title[]"]').focus();
			});
			table.addEventListener('click', function (e) {
				const btn = e.target.closest('.remove-invoice-extra-row');
				if (!btn) {
					return;
				}
				const row = btn.closest('tr');
				if (row && row.classList.contains('invoice-extra-row')) {
					row.remove();
				}
			});
		})();
		</script>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?= $this->endSection() ?>
