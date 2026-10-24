<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Kundenverwaltung</h1>
	<p><a class="btn btn-success" href="<?= site_url('customers/edit/0') ?>">Neuen Kunden anlegen</a></p>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th>Nr.</th><th>Firma</th><th>Vorname</th><th>Nachname</th><th>Stadt</th><th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($activeCustomers as $row):
				$rowClass = ($row['_status'] ?? 1) === 2 ? 'table-secondary' : '';
			?>
			<tr class="<?= $rowClass ?>">
				<td><?= esc($row['customer_number']) ?></td>
				<td><?= esc($row['company']) ?></td>
				<td><?= esc($row['first_name']) ?></td>
				<td><?= esc($row['last_name']) ?></td>
				<td><?= esc($row['city']) ?></td>
				<td><a href="<?= site_url('customers/edit/' . $row['id']) ?>" class="btn btn-sm btn-primary">Bearbeiten</a></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php if ($inactiveCustomers !== []): ?>
	<h3 class="h5 mt-4 text-muted">Inaktiv</h3>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th>Nr.</th><th>Firma</th><th>Vorname</th><th>Nachname</th><th>Stadt</th><th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($inactiveCustomers as $row): ?>
			<tr class="text-muted">
				<td><?= esc($row['customer_number']) ?></td>
				<td><?= esc($row['company']) ?></td>
				<td><?= esc($row['first_name']) ?></td>
				<td><?= esc($row['last_name']) ?></td>
				<td><?= esc($row['city']) ?></td>
				<td><a href="<?= site_url('customers/edit/' . $row['id']) ?>" class="btn btn-sm btn-danger">Bearbeiten</a></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
<?= $this->endSection() ?>
