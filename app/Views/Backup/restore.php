<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Offline-Wiederherstellung</h1>
	<p class="text-muted">Quelle: lokales Backup oder ZIP-Upload (inkl. PDF-Ordner). Kein automatischer Snapshot vor der Wiederherstellung.</p>
	<form method="post" action="<?= site_url('restore/run') ?>" enctype="multipart/form-data">
		<?= csrf_field() ?>
		<div class="mb-3">
			<label class="form-label">Backup auswählen</label>
			<select name="backup_file" class="form-select" id="backup_file">
				<option value="">— Bitte wählen —</option>
				<?php foreach ($backups as $b): ?>
					<option value="<?= esc($b['filename']) ?>"><?= esc($b['filename']) ?> (<?= date('d.m.Y H:i', $b['mtime']) ?>)</option>
				<?php endforeach; ?>
			</select>
			<div class="form-text">Oder unten eine ZIP-Datei vom PC hochladen.</div>
		</div>
		<div class="mb-3">
			<label class="form-label">ZIP vom PC hochladen</label>
			<input type="file" name="backup_upload" class="form-control" accept=".zip,application/zip">
			<div class="form-text">Max. 512&nbsp;MB. Upload hat Vorrang vor der Liste.</div>
		</div>
		<div class="mb-3">
			<label class="form-label">Was wiederherstellen? (*)</label>
			<select name="scope" class="form-select" required>
				<?php foreach ($scopes as $key => $label): ?>
					<option value="<?= esc($key) ?>"><?= esc($label) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="mb-3">
			<label class="form-label">Konfliktstrategie</label>
			<select name="conflict" class="form-select">
				<option value="truncate">Tabelle leeren und importieren</option>
				<option value="skip_existing">Nur leere Tabellen befüllen (vorsichtig)</option>
			</select>
		</div>
		<div class="form-check mb-3">
			<input class="form-check-input" type="checkbox" name="include_pdfs" value="1" id="pdfs" checked>
			<label class="form-check-label" for="pdfs">PDF-Archiv aus Backup mitkopieren</label>
		</div>
		<button type="submit" class="btn btn-warning" onclick="return confirm('Wiederherstellung starten?');">Wiederherstellen</button>
	</form>
</div>
<?= $this->endSection() ?>
