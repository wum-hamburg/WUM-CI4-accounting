<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Textverwaltung</h1>
	<p><a class="btn btn-success" href="<?= site_url('text-blocks/edit/0') ?>">Neuen Text anlegen</a></p>
	<table class="table table-hover">
		<thead><tr><th>Schlüssel</th><th>Text</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($activeBlocks as $block):
			$status = (int) ($block['status'] ?? 1);
			$rowClass = $status === 2 ? 'table-secondary' : '';
		?>
			<tr class="<?= $rowClass ?>">
				<td><?= esc($block['text_key']) ?></td>
				<td><?= esc(mb_substr($block['content'], 0, 80)) ?>…</td>
				<td><a href="<?= site_url('text-blocks/edit/' . $block['id']) ?>" class="btn btn-sm btn-primary">Bearbeiten</a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php if ($inactiveBlocks !== []): ?>
	<h3 class="h5 mt-4 text-muted">Inaktiv</h3>
	<table class="table table-hover">
		<thead><tr><th>Schlüssel</th><th>Text</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($inactiveBlocks as $block): ?>
			<tr class="text-muted">
				<td><?= esc($block['text_key']) ?></td>
				<td><?= esc(mb_substr($block['content'], 0, 80)) ?>…</td>
				<td><a href="<?= site_url('text-blocks/edit/' . $block['id']) ?>" class="btn btn-sm btn-danger">Bearbeiten</a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
<?= $this->endSection() ?>
