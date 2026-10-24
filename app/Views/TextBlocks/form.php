<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h2><?= esc($moduleTitle ?? 'Text') ?></h2>
	<form method="post" action="<?= esc($action) ?>">
		<?= csrf_field() ?>
		<div class="mb-3">
			<label class="form-label">Textschlüssel (z.B. vor1, schluss1) (*)</label>
			<input type="text" name="text_key" class="form-control" required value="<?= old('text_key', $textBlock['text_key'] ?? '') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label">Text (*)</label>
			<textarea name="content" class="form-control" rows="5" required><?= old('content', $textBlock['content'] ?? '') ?></textarea>
		</div>
		<div class="mb-3">
			<label class="form-label">Bemerkung</label>
			<input type="text" name="note" class="form-control" value="<?= old('note', $textBlock['note'] ?? '') ?>">
		</div>
		<?php if (!empty($textBlock['id'])): ?>
		<div class="mb-3">
			<label class="form-label">Status</label>
			<select name="status" class="form-select">
				<option value="1">Standard</option>
				<option value="2" <?= (int)($textBlock['status'] ?? 1) === 2 ? 'selected' : '' ?>>Extra</option>
				<option value="0" <?= (int)($textBlock['status'] ?? 1) === 0 ? 'selected' : '' ?>>Inaktiv</option>
			</select>
		</div>
		<?php endif; ?>
		<?php if (!empty($textBlock['id']) && (int) ($textBlock['status'] ?? 1) === 0): ?>
		<button type="submit" class="btn btn-danger">Löschen!</button>
		<p class="d-inline ms-2 text-muted small">Status auf Standard oder Extra geändert?</p>
		<?php endif; ?>
		<button type="submit" class="btn btn-primary">Speichern</button>
		<a href="<?= site_url('text-blocks') ?>" class="btn btn-secondary">Zurück</a>
	</form>
</div>
<?= $this->endSection() ?>
