<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Kreditorenverwaltung</h1>
	<p><a class="btn btn-success" href="<?= site_url('creditors/edit/0') ?>">Neuen Kreditor anlegen</a></p>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th>Firmenname</th><th>Nr.</th><th>Bemerkung</th><th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($activeCreditors as $row):
				$rowClass = ($row['_status'] ?? 1) === 2 ? 'table-secondary' : '';
			?>
			<tr class="<?= $rowClass ?>">
				<td><?= esc($row['company_name']) ?></td>
				<td><?= esc($row['creditor_number'] ?? '') ?></td>
				<td><?= esc($row['note'] ?? '') ?></td>
				<td><a href="<?= site_url('creditors/edit/' . $row['id']) ?>" class="btn btn-sm btn-primary">Bearbeiten</a></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php if ($inactiveCreditors !== []): ?>
	<h3 class="h5 mt-4 text-muted">Inaktiv</h3>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th>Firmenname</th><th>Nr.</th><th>Bemerkung</th><th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($inactiveCreditors as $row): ?>
			<tr class="text-muted">
				<td><?= esc($row['company_name']) ?></td>
				<td><?= esc($row['creditor_number'] ?? '') ?></td>
				<td><?= esc($row['note'] ?? '') ?></td>
				<td><a href="<?= site_url('creditors/edit/' . $row['id']) ?>" class="btn btn-sm btn-danger">Bearbeiten</a></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
<?= $this->endSection() ?>
