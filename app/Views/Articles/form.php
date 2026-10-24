<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h2><?= esc($moduleTitle ?? 'Artikel') ?></h2>
	<form method="post" action="<?= esc($action) ?>">
		<?= csrf_field() ?>
		<div class="mb-3">
			<label class="form-label">Artikelnummer (*)</label>
			<input type="text" name="article_number" class="form-control" required value="<?= old('article_number', $article['article_number'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Artikel (*)</label>
			<input type="text" name="title" class="form-control" required value="<?= old('title', $article['title'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Preis in Euro (*)</label>
			<input type="text" name="price_euro" class="form-control" required value="<?= old('price_euro', isset($article['price_cents']) ? number_format($article['price_cents']/100, 2, ',', '') : '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Bemerkung</label>
			<input type="text" name="note" class="form-control" value="<?= old('note', $article['note'] ?? '') ?>">
		</div>
		<?php if (!empty($article['id'])): ?>
		<div class="mb-3">
			<label class="form-label">Status</label>
			<select name="status" class="form-select">
				<option value="1" <?= (int)($article['status'] ?? 1) === 1 ? 'selected' : '' ?>>Standard</option>
				<option value="2" <?= (int)($article['status'] ?? 1) === 2 ? 'selected' : '' ?>>Extra</option>
				<option value="0" <?= (int)($article['status'] ?? 1) === 0 ? 'selected' : '' ?>>Inaktiv</option>
			</select>
		</div>
		<?php endif; ?>
		<?php if (!empty($article['id']) && (int) ($article['status'] ?? 1) === 0): ?>
		<button type="submit" class="btn btn-danger">Löschen!</button>
		<p class="d-inline ms-2 text-muted small">Status auf Standard oder Extra geändert?</p>
		<?php endif; ?>
		<button type="submit" class="btn btn-primary">Speichern</button>
		<a href="<?= site_url('articles') ?>" class="btn btn-secondary">Zurück</a>
	</form>
</div>
<?= $this->endSection() ?>
