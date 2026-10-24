<?php
/** @var int $year */
/** @var array $report */
helper('wum');
$profit      = (int) $report['profit_cents'];
$profitLabel = $profit >= 0 ? 'Gewinn' : 'Verlust';
?>
<h2>Einnahmenüberschussrechnung <?= (int) $year ?></h2>
<p>Kleinunternehmer gemäß § 19 UStG – Beträge ohne Umsatzsteuer.</p>
<p><?= esc(config('Wum')->companyTaxNumber) ?></p>
<h3>Betriebseinnahmen (Ausgangsrechnungen)</h3>
<table cellpadding="4" cellspacing="0" border="1" style="width:100%;">
	<tr style="background-color:#cccccc;">
		<td width="18%"><b>Datum</b></td>
		<td width="22%"><b>Nr.</b></td>
		<td width="30%"><b>Kunde</b></td>
		<td width="15%"><b>Status</b></td>
		<td width="15%" align="right"><b>Betrag</b></td>
	</tr>
	<?php
	$incomeRows = array_values(array_filter(
	    $report['income'],
	    static fn (array $row): bool => (int) ($row['status'] ?? 0) !== \Config\Wum::INVOICE_OPEN
	));
	?>
	<?php if ($incomeRows === []): ?>
	<tr>
		<td colspan="5">Keine bezahlten Ausgangsrechnungen.</td>
	</tr>
	<?php else: ?>
	<?php foreach ($incomeRows as $row): ?>
	<tr>
		<td><?= esc(format_german_date($row['date'])) ?></td>
		<td><?= esc($row['number']) ?></td>
		<td><?= esc($row['name']) ?></td>
		<td><?= esc($row['status_label']) ?></td>
		<td align="right"><?= esc(format_price((int) $row['amount_cents'])) ?></td>
	</tr>
	<?php endforeach; ?>
	<?php endif; ?>
	<tr>
		<td colspan="4" align="right"><b>Summe Einnahmen</b></td>
		<td align="right"><b><?= esc(format_price((int) $report['income_cents'])) ?></b></td>
	</tr>
</table>
<br pagebreak="true" />
<h3>Betriebsausgaben (Eingangsrechnungen)</h3>
<table cellpadding="4" cellspacing="0" border="1" style="width:100%;">
	<tr style="background-color:#cccccc;">
		<td width="14%"><b>Datum</b></td>
		<td width="33%"><b>Kreditor</b></td>
		<td width="19%"><b>KD-Nr.</b></td>
		<td width="19%"><b>Rechnung</b></td>
		<td width="15%" align="right"><b>Betrag</b></td>
	</tr>
	<?php if ($report['expenses'] === []): ?>
	<tr>
		<td colspan="5">Keine Eingangsrechnungen.</td>
	</tr>
	<?php else: ?>
	<?php foreach ($report['expenses'] as $row): ?>
	<tr>
		<td><?= esc(format_german_date($row['date'])) ?></td>
		<td><?= esc($row['name']) ?></td>
		<td><?= esc($row['creditor_no']) ?></td>
		<td><?= esc($row['number']) ?></td>
		<td align="right"><?= esc(format_price((int) $row['amount_cents'])) ?></td>
	</tr>
	<?php endforeach; ?>
	<?php endif; ?>
	<tr>
		<td colspan="4" align="right"><b>Summe Ausgaben:</b></td>
		<td align="right"><b><span style="color:#cc0000;">-<?= esc(format_price((int) $report['expense_cents'])) ?></span></b></td>
	</tr>
</table>
<br>
<h3>Ergebnis:</h3>
<table cellpadding="6" cellspacing="0" border="1" style="width:100%;">
	<tr>
		<td width="70%">Summe Einnahmen:</td>
		<td width="30%" align="right"><?= esc(format_price((int) $report['income_cents'])) ?></td>
	</tr>
	<tr>
		<td>Summe Ausgaben:</td>
		<td align="right"><span style="color:#cc0000;">-<?= esc(format_price((int) $report['expense_cents'])) ?></span></td>
	</tr>
	<tr>
		<td><b><?= esc($profitLabel) ?> zum 31.12.<?= (int) $year ?>:</b></td>
		<td align="right"><b><?= esc(format_price($profit)) ?></b></td>
	</tr>
</table>
<p style="font-size:9px;">Ermittelt nach Rechnungsdatum der bezahlten Ausgangsrechnungen und Korrekturen sowie dem Buchungsjahr der Eingangsrechnungen. Offene Rechnungen sind nicht enthalten. Alle erfassten Zahlungen sind im Buchungsjahr <?= (int) $year ?> erfolgt.</p>
