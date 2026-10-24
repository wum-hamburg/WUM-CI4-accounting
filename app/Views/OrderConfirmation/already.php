<?= $this->extend('layout_public') ?>
<?= $this->section('content') ?>
<div class="order-confirmation-card text-center">
	<?php if (! empty($quote) && ! empty($downloadUrl)): ?>
		<h2 class="h5">Angebot <?= esc($quote['quote_number']) ?> bereits bestätigt</h2>
		<p class="text-muted">Diese Auftragsbestätigung wurde bereits übermittelt.</p>
		<a href="<?= esc($downloadUrl) ?>" class="btn btn-outline-primary" target="_blank" rel="noopener">
			<i class="fa-solid fa-file-pdf"></i> PDF anzeigen
		</a>
	<?php else: ?>
		<h2 class="h5"><?= esc($pageTitle ?? 'Nicht verfügbar') ?></h2>
		<p class="text-muted"><?= esc($message ?? 'Dieser Link ist nicht mehr gültig.') ?></p>
	<?php endif; ?>
	<?php if (! empty($isDemo)): ?>
		<p class="mt-3">
			<a href="<?= site_url('dashboard') ?>" class="btn btn-primary">
				<i class="fa-solid fa-arrow-left"></i> Zurück zur Demo
			</a>
		</p>
	<?php endif; ?>
</div>
<?= $this->endSection() ?>
