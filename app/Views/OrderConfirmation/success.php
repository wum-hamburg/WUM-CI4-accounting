<?= $this->extend('layout_public') ?>
<?= $this->section('content') ?>
<div class="order-confirmation-card text-center">
	<div class="mb-3 text-success"><i class="fa-solid fa-circle-check fa-3x"></i></div>
	<h2 class="h5">Vielen Dank!</h2>
	<p><?= esc($message) ?></p>
	<?php if (! empty($downloadUrl)): ?>
		<p class="mt-3">
			<a href="<?= esc($downloadUrl) ?>" class="btn btn-outline-primary" target="_blank" rel="noopener">
				<i class="fa-solid fa-file-pdf"></i> Auftragsbestätigung als PDF
			</a>
		</p>
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
