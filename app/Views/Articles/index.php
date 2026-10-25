<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Artikelverwaltung</h1>
	<p><a class="btn btn-success" href="<?= site_url('articles/edit/0') ?>">Neuen Artikel anlegen</a></p>
	<table class="table table-hover">
		<thead><tr><th>Nr.</th><th>Bezeichnung</th><th>Preis</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($activeArticles as $article):
			$status = (int) ($article['status'] ?? 1);
			$rowClass = $status === 2 ? 'table-secondary' : '';
		?>
			<tr class="<?= $rowClass ?>">
				<td><?= esc($article['article_number']) ?></td>
				<td><?= esc($article['title']) ?></td>
				<td><?= format_price((int)$article['price_cents']) ?></td>
				<td><a href="<?= site_url('articles/edit/' . $article['id']) ?>" class="btn btn-sm btn-primary">Bearbeiten</a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php if ($inactiveArticles !== []): ?>
	<h3 class="h5 mt-4 text-muted">Inaktiv</h3>
	<table class="table table-hover">
		<thead><tr><th>Nr.</th><th>Bezeichnung</th><th>Preis</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($inactiveArticles as $article): ?>
			<tr class="text-muted">
				<td><?= esc($article['article_number']) ?></td>
				<td><?= esc($article['title']) ?></td>
				<td><?= format_price((int)$article['price_cents']) ?></td>
				<td><a href="<?= site_url('articles/edit/' . $article['id']) ?>" class="btn btn-sm btn-danger">Bearbeiten</a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
<?= $this->endSection() ?>
