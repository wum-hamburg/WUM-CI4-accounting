<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Rechnung – Entwurf</h1>
	<?php if (session('pdf_file')): ?>
		<div class="alert alert-success">
			<a href="<?= esc(wum_pdf_url((string) session('pdf_file'))) ?>" target="_blank">Gebuchtes PDF öffnen</a>
		</div>
	<?php endif; ?>
	<?php if (!empty($pdfUrl)): ?>
		<div class="mb-4">
			<p><a href="<?= esc($pdfUrl) ?>" target="_blank" class="btn btn-primary btn-lg">PDF-Entwurf öffnen</a></p>
			<iframe src="<?= esc($pdfUrl) ?>" title="Rechnung Entwurf" class="w-100 border" style="height:70vh; background:#fff;"></iframe>
		</div>
	<?php endif; ?>
	<p class="mb-3"><a href="<?= site_url('documents?step=4') ?>" class="btn btn-outline-secondary">← Zurück bearbeiten</a></p>
	<form method="post" action="<?= site_url('invoices/book') ?>">
		<?= csrf_field() ?>
		<?php
		$postData = $payload['postData'] ?? [];
		if ($postData === [] && session('convert_post_data')) {
			$postData = session('convert_post_data');
		}
		foreach ($postData as $key => $val): ?>
			<?php if (is_array($val)) { foreach ($val as $k => $v): ?>
				<input type="hidden" name="<?= esc($key) ?>[<?= esc($k) ?>]" value="<?= esc($v) ?>">
			<?php endforeach; } else { ?>
				<input type="hidden" name="<?= esc($key) ?>" value="<?= esc($val) ?>">
			<?php } ?>
		<?php endforeach; ?>
		<?php if (! wum_is_demo()): ?>
		<div class="form-check mb-3">
			<input class="form-check-input" type="checkbox" name="send_email" value="1" id="send-email" checked>
			<label class="form-check-label" for="send-email">Nach Buchung per E-Mail versenden</label>
		</div>
		<?php endif; ?>
		<button type="submit" class="btn btn-primary btn-lg w-100">Rechnung <strong>mit Nummer</strong> buchen</button>
	</form>
	<form method="post" action="<?= !empty($fromConvert) ? site_url('invoices/preview-from-convert') : site_url('invoices/preview') ?>" class="mt-3">
		<?= csrf_field() ?>
		<button type="submit" name="abort" value="1" class="btn btn-danger w-100">Abbruch</button>
	</form>
</div>
<?= $this->endSection() ?>
