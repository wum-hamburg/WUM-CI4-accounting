<table cellpadding="5" cellspacing="0" style="width:100%;">
	<tr>
		<td><?= nl2br(trim($recipient)) ?></td>
		<td style="text-align:right">
			<br><br><br><br><br>
			<br><br><br><br><br><br>
			Kundennummer: <?= esc($customerNumber) ?><br>
			<?= esc($dateLabel) ?>: <?= esc($date) ?>
		</td>
	</tr>
	<tr>
		<td colspan="3" style="font-size:1.3em;font-weight:bold;">
			<?php if ($number): ?>
				<h2>
					<?= esc($documentLabel) ?> Nr. <?= esc($number) ?>
					<?php if ($correctionOf): ?>
						<span style="font-size:0.8em;">(Korrekturrechnung zu Nr. <?= esc($correctionOf) ?>)</span>
					<?php endif; ?>
				</h2>
			<?php else: ?>
				<h1>ENTWURF</h1>
				<?php if ($correctionOf): ?>
					<p>Korrekturrechnung zu Nr. <?= esc($correctionOf) ?></p>
				<?php endif; ?>
			<?php endif; ?>
		</td>
	</tr>
	<?php if ($headerHtml !== ''): ?>
	<tr><td colspan="2"><?= nl2br(esc(trim($headerHtml))) ?></td></tr>
	<?php endif; ?>
</table>
<table cellpadding="5" cellspacing="0" style="width:100%;" border="0">
	<tr style="background-color:#cccccc;">
		<td style="width:8%; text-align:center;"><b>Pos</b></td>
		<td colspan="2" style="width:16%; text-align:center;"><b>Menge</b></td>
		<td style="width:42%;"><b>Bezeichnung</b></td>
		<td style="width:14%; text-align:right;"><b>EP</b></td>
		<td style="width:20%; text-align:right;"><b>GP</b></td>
	</tr>
	<?php if ($introBeforeItems !== ''): ?>
	<tr><td colspan="6"><?= nl2br(esc($introBeforeItems)) ?></td></tr>
	<?php endif; ?>
	<?php foreach ($lineItems as $posten): ?>
	<?php if (!empty($posten['is_optional'])): ?>
	<tr>
		<td style="width:8%; text-align:center;"></td>
		<td style="width:7%; text-align:right;"></td>
		<td style="width:9%; text-align:left;"></td>
		<td style="width:42%;"><b><?= esc($posten['optional_prefix']) ?></b></td>
		<td style="width:14%; text-align:right;"></td>
		<td style="width:20%; text-align:right;"></td>
	</tr>
	<tr>
		<td style="width:8%; text-align:center;"><?= esc($posten['position_number']) ?></td>
		<td style="width:7%; text-align:right;"><?= (int) $posten['qty'] ?></td>
		<td style="width:9%; text-align:left;"><?= esc($posten['unit']) ?></td>
		<td style="width:42%;"><?= esc($posten['optional_title'] ?? '') ?></td>
		<td style="width:14%; text-align:right;"><?= format_price($posten['price_cents']) ?></td>
		<td style="width:20%; text-align:right;">(<?= format_price((int) ($posten['display_total_cents'] ?? 0)) ?>)</td>
	</tr>
	<?php else: ?>
	<tr>
		<td style="width:8%; text-align:center;"><?= esc($posten['position_number']) ?></td>
		<td style="width:7%; text-align:right;"><?= (int) $posten['qty'] ?></td>
		<td style="width:9%; text-align:left;"><?= esc($posten['unit']) ?></td>
		<td style="width:42%;"><?= esc($posten['title']) ?></td>
		<td style="width:14%; text-align:right;"><?= format_price($posten['price_cents']) ?></td>
		<td style="width:20%; text-align:right;"><?= format_price($posten['total_cents']) ?></td>
	</tr>
	<?php endif; ?>
	<?php endforeach; ?>
	<?php if ($discountCents): ?>
	<tr>
		<td style="text-align:center;">-</td>
		<td style="text-align:right;">1</td>
		<td>Stck.</td>
		<td>Sonderrabatt <?= (int) $discountPercent ?> %</td>
		<td style="text-align:right;"><?= format_price($discountCents) ?></td>
		<td style="text-align:right;"><?= format_price($discountCents) ?></td>
	</tr>
	<?php endif; ?>
</table>
<table cellpadding="5" cellspacing="0" style="width:100%;" border="1">
	<tr>
		<td style="text-align:right" colspan="5">Netto:</td>
		<td style="text-align:right"><?= format_price($totalCents) ?></td>
	</tr>
</table>
<br><br><br>
<?php if ($footerText !== ''): ?>
	<?= nl2br(esc($footerText)) ?>
<?php endif; ?>
