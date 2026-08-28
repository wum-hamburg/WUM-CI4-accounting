<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
	<h1>Erfassung Eingangsrechnungen</h1>
	<?= $this->include('partials/flash') ?>
	<p class="text-muted">Bitte Buchungsjahr festlegen. Es bleibt bis zur Rückkehr zur Übersicht erhalten.</p>
	<form method="post" action="<?= site_url('incoming-invoices/set-year') ?>" class="mt-3">
		<?= csrf_field() ?>
		<div class="mb-3" style="max-width: 24rem;">
			<label class="form-label" for="booking_year">Buchungsjahr</label>
			<select name="booking_year" id="booking_year" class="form-select" required>
				<?php
				$labels = [
					$years[0] => 'Vorletztes Jahr (' . $years[0] . ')',
					$years[1] => 'Letztes Jahr (' . $years[1] . ')',
					$years[2] => 'Aktuelles Jahr (' . $years[2] . ')',
				];
				foreach ($years as $y):
				?>
				<option value="<?= (int) $y ?>" <?= (int) $y === (int) $years[2] ? 'selected' : '' ?>>
					<?= esc($labels[$y]) ?>
				</option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit" class="btn btn-primary">Weiter</button>
		<a href="<?= site_url('incoming-invoices') ?>" class="btn btn-secondary">Zur Liste</a>
	</form>
</div>
<?= $this->endSection() ?>
