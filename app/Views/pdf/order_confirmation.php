<table cellpadding="4" cellspacing="0" style="width:100%;">
	<tr>
		<td style="width:55%; vertical-align:top;">
			<?= $recipientHtml ?>
		</td>
		<td style="width:45%; text-align:right; vertical-align:top;">
			<br><br><br><br><br>
			<br><br><br><br><br><br>
			Kundennummer: <?= esc($customerNumber) ?><br>
			Datum: <?= esc($confirmationDateDe) ?>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="font-size:1.2em;font-weight:bold;">
			<h2>Auftragsbestätigung zu Angebot Nr. <?= esc($quoteNumber) ?></h2>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<p>Hiermit beauftrage ich das Angebot Nr. <strong><?= esc($quoteNumber) ?></strong> vom <?= esc($quoteDateDe) ?>.</p>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<p><strong>Anmerkungen:</strong></p>
			<p><?= $notesHtml ?></p>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<p><strong>Bestätigungsdatum:</strong> <?= esc($confirmationDateDe) ?></p>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<p><strong>Unterschrift:</strong></p>
			<?php if ($signatureImgTag !== ''): ?>
				<?= $signatureImgTag ?>
			<?php else: ?>
				<p><em>— keine Unterschrift —</em></p>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<p><strong>Datenschutz:</strong></p>
			<table cellpadding="3" cellspacing="0">
				<tr>
					<td style="border:1px solid #333; width:16px; height:16px; text-align:center; font-weight:bold; vertical-align:middle;">
						<?= ! empty($privacyConsented) ? 'X' : '&nbsp;' ?>
					</td>
					<td style="vertical-align:middle;">
						Ich habe die Datenschutzerklärung gelesen und stimme zu.
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="font-size:0.85em;color:#555;">
			<p>Übermittelt am <?= esc($submittedAtDe) ?><?= ! empty($privacyConsented) ? ' · Datenschutzeinwilligung erteilt' : '' ?>.</p>
		</td>
	</tr>
</table>
