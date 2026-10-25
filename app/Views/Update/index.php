<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Datenbank-Update</h1>
	<p>Es stehen folgende Anpassungen aus:</p>
	<ul>
		<?php foreach ($pending as $patch): ?>
		<li><?= esc($patch['label']) ?></li>
		<?php endforeach; ?>
	</ul>
	<form method="post" action="<?= site_url('update/run') ?>"
		onsubmit="return confirm('Datenbank jetzt aktualisieren?');">
		<?= csrf_field() ?>
		<button type="submit" class="btn btn-warning">
			<i class="fa-solid fa-rotate"></i> Update
		</button>
		<a href="<?= site_url('dashboard') ?>" class="btn btn-secondary">Abbrechen</a>
	</form>
</div>
<?= $this->endSection() ?>
