<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/quittungsblock.css') ?>">

<div class="quittung-toolbar no-print mb-3">
	<a href="<?= site_url('quittungsblock') ?>" class="btn btn-outline-secondary btn-sm">Zur Übersicht</a>
	<?php if ($readonly): ?>
		<button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">Drucken</button>
		<a href="<?= site_url('quittungsblock/neu') ?>" class="btn btn-success btn-sm">Neue Quittung</a>
	<?php endif; ?>
</div>

<?php
$v = static function (string $key, array $receipt) {
	$old = old($key);
	if ($old !== null) {
		return (string) $old;
	}
	return (string) ($receipt[$key] ?? '');
};
$ro = ! empty($readonly);

$dateInput = old('receipt_date');
if ($dateInput === null) {
	$dateInput = \App\Models\ReceiptModel::dateInputValue($receipt['receipt_date'] ?? null);
}
$dateDisplay = '';
if (! empty($receipt['receipt_date'])) {
	$dateDisplay = \App\Models\ReceiptModel::formatDateDe($receipt['receipt_date']);
} elseif ($dateInput !== '') {
	$dateDisplay = \App\Models\ReceiptModel::formatDateDe($dateInput . ' 00:00:00');
}

$timeInput = old('time_text');
if ($timeInput === null) {
	$timeInput = (string) ($receipt['time_text'] ?? '');
}
?>

<?php if (! $ro): ?>
<form method="post" action="<?= esc($action) ?>" id="quittung-form">
	<?= csrf_field() ?>
<?php endif; ?>

<div class="quittung-sheet">
	<div class="quittung-header">
		<div class="quittung-phone">
			<span class="quittung-phone-label">Phone</span>
			<span class="quittung-phone-number">38 91 91-0</span>
		</div>
		<div class="quittung-art">
			<img src="<?= base_url('images/hansen-quittung.png') ?>" alt="Hermann Hansen Seamen-Service" class="quittung-art-img">
			<div class="quittung-nr">Nr. <?= esc($receiptNumber) ?></div>
		</div>
	</div>

	<div class="quittung-brand">
		<div class="quittung-brand-name">HERMANN HANSEN</div>
		<div class="quittung-brand-sub">Seamen-Service</div>
	</div>
	<hr class="quittung-rule">

	<div class="quittung-field">
		<label>from:</label>
		<input type="text" name="from_text" value="<?= esc($v('from_text', $receipt)) ?>" <?= $ro ? 'readonly' : '' ?>>
	</div>
	<div class="quittung-field">
		<label>via:</label>
		<input type="text" name="via_text" value="<?= esc($v('via_text', $receipt)) ?>" <?= $ro ? 'readonly' : '' ?>>
	</div>
	<div class="quittung-field quittung-field-blank">
		<label>&nbsp;</label>
		<input type="text" name="via2_text" value="<?= esc($v('via2_text', $receipt)) ?>" <?= $ro ? 'readonly' : '' ?> aria-label="via Fortsetzung">
	</div>
	<div class="quittung-field">
		<label>to:</label>
		<input type="text" name="to_text" value="<?= esc($v('to_text', $receipt)) ?>" <?= $ro ? 'readonly' : '' ?>>
	</div>

	<div class="quittung-row">
		<div class="quittung-field quittung-field-sm">
			<label>Time:</label>
			<?php if ($ro): ?>
				<input type="text" value="<?= esc($timeInput) ?>" readonly>
			<?php else: ?>
				<input type="time" name="time_text" step="60" value="<?= esc($timeInput) ?>" required>
			<?php endif; ?>
		</div>
		<div class="quittung-field quittung-field-sm">
			<label>Waitingtime:</label>
			<input type="text" name="waiting_time" value="<?= esc($v('waiting_time', $receipt)) ?>" <?= $ro ? 'readonly' : '' ?>>
			<span class="quittung-suffix">hr(s)</span>
		</div>
	</div>

	<div class="quittung-row">
		<div class="quittung-field quittung-field-md">
			<label>Agent:</label>
			<input type="text" name="agent" value="<?= esc($v('agent', $receipt)) ?>" <?= $ro ? 'readonly' : '' ?>>
		</div>
		<div class="quittung-field quittung-field-md">
			<label>person(s):</label>
			<input type="text" name="persons" value="<?= esc($v('persons', $receipt)) ?>" <?= $ro ? 'readonly' : '' ?>>
		</div>
	</div>

	<div class="quittung-row">
		<div class="quittung-field quittung-field-sm">
			<label>Price:</label>
			<input type="text" name="price" value="<?= esc($v('price', $receipt)) ?>" <?= $ro ? 'readonly' : '' ?>>
		</div>
		<div class="quittung-field quittung-field-sm">
			<label>Date:</label>
			<?php if ($ro): ?>
				<input type="text" value="<?= esc($dateDisplay) ?>" readonly title="Format dd.mm.yyyy">
			<?php else: ?>
				<input type="date" name="receipt_date" class="quittung-date-calendar" lang="de" value="<?= esc($dateInput) ?>" required title="dd.mm.yyyy">
			<?php endif; ?>
		</div>
	</div>

	<div class="quittung-field quittung-signature-row">
		<label>Signature:</label>
		<?php if ($ro): ?>
			<div class="quittung-signature-display">
				<?php if (! empty($receipt['signature_data'])): ?>
					<img src="<?= esc($receipt['signature_data'], 'attr') ?>" alt="Unterschrift">
				<?php else: ?>
					<span class="quittung-line-only"></span>
				<?php endif; ?>
			</div>
		<?php else: ?>
			<div class="quittung-signature-pad-wrap">
				<canvas id="signature-pad" width="520" height="90"></canvas>
				<input type="hidden" name="signature_data" id="signature_data" value="">
				<button type="button" class="btn btn-sm btn-outline-secondary no-print" id="signature-clear">Unterschrift löschen</button>
			</div>
		<?php endif; ?>
	</div>

	<div class="quittung-field">
		<label>Vessel:</label>
		<input type="text" name="vessel" value="<?= esc($v('vessel', $receipt)) ?>" <?= $ro ? 'readonly' : '' ?>>
	</div>
</div>

<?php if (! $ro): ?>
	<div class="mt-3 no-print">
		<button type="submit" class="btn btn-primary">Quittung speichern</button>
		<a href="<?= site_url('quittungsblock') ?>" class="btn btn-outline-secondary">Abbrechen</a>
	</div>
</form>
<script src="<?= base_url('assets/js/signature-pad.js') ?>"></script>
<?php endif; ?>

<?= $this->endSection() ?>
