<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Quittungsblock</h1>
	<p>
		<a class="btn btn-success" href="<?= site_url('quittungsblock/neu') ?>">Neue Quittung (Nr. <?= esc($nextNumber) ?>)</a>
	</p>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th>Nr.</th>
				<th>from</th>
				<th>to</th>
				<th>Vessel</th>
				<th>Date</th>
				<th>Time</th>
				<th>Price</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php if ($receipts === []): ?>
			<tr><td colspan="8" class="text-muted">Noch keine Quittungen.</td></tr>
			<?php else: ?>
			<?php foreach ($receipts as $row): ?>
			<tr>
				<td><?= esc($row['receipt_number']) ?></td>
				<td><?= esc($row['from_text']) ?></td>
				<td><?= esc($row['to_text']) ?></td>
				<td><?= esc($row['vessel']) ?></td>
				<td><?= esc(\App\Models\ReceiptModel::formatDateDe($row['receipt_date'] ?? null)) ?></td>
				<td><?= esc($row['time_text'] ?? '') ?></td>
				<td><?= esc($row['price']) ?></td>
				<td><a href="<?= site_url('quittungsblock/' . $row['id']) ?>" class="btn btn-sm btn-primary">Anzeigen</a></td>
			</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<?= $this->endSection() ?>
