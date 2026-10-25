<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Datenbank – Online-Sicherung</h1>
	<p class="text-muted">Vollbackup (Datenbank + PDF-Ordner) mit Upload nach Hetzner (<code>Wum/</code>).</p>
	<form method="post" action="<?= site_url('backup/start') ?>" onsubmit="document.getElementById('wait').style.display='block';">
		<?= csrf_field() ?>
		<button type="submit" class="btn btn-primary">Online-Datensicherung starten</button>
	</form>
	<div id="wait" class="alert alert-info mt-3" style="display:none;">Sicherung gestartet. Bitte warten …</div>

	<?php if (!empty($jobId)): ?>
	<div class="mt-4" id="backup-job" data-job="<?= esc($jobId) ?>">
		<h2>Aktiver Job</h2>
		<div class="progress"><div class="progress-bar" id="job-bar" style="width:5%">5%</div></div>
		<p id="job-msg" class="mt-2"></p>
	</div>
	<script>
	(function(){
		const el = document.getElementById('backup-job');
		if (!el) return;
		const jobId = el.dataset.job;
		function tick(){
			fetch('<?= site_url('backup/worker') ?>/' + jobId).then(r=>r.json()).then(d=>{
				document.getElementById('job-bar').style.width = (d.progress||0)+'%';
				document.getElementById('job-bar').textContent = (d.progress||0)+'%';
				document.getElementById('job-msg').textContent = d.message||'';
				if (!d.done) setTimeout(tick, 800);
				else location.href = '<?= site_url('backup') ?>';
			});
		}
		tick();
	})();
	</script>
	<?php endif; ?>

	<h2 class="mt-4">Vorhandene Backups</h2>
	<table class="table table-sm">
		<thead><tr><th>Datei</th><th>Größe</th><th>Datum</th></tr></thead>
		<tbody>
		<?php foreach ($backups as $b): ?>
			<tr>
				<td><?= esc($b['filename']) ?></td>
				<td><?= number_format($b['size']/1024, 1) ?> KB</td>
				<td><?= date('d.m.Y H:i', $b['mtime']) ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?= $this->endSection() ?>
