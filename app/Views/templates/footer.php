<div class="card-footer">
	<div class="card-group">
		<!-- Bereich 1: Benutzerinfo -->
		<div class="card" style="align-items: center;">
			<div class="card-body">
				<h5 class="card-title"><?= lang('Layout.logged_in_user') ?></h5>
				<p class="card-text">
					<?= esc(session()->get('first_name')) . ' ' . esc(session()->get('last_name')) ?>
				</p>
				<?php
				if (!session()->get('logged_in')) : ?>
				<a href="<?= site_url('login') ?>" class="btn btn-primary">
					<i class="fa-solid fa-key"></i> <?= lang('Layout.login') ?>
				</a>
				<?php
			else : ?>
				<a href="<?= site_url('logout') ?>" class="btn btn-dark">
					<i class="fa-solid fa-door-open"></i> <?= lang('Layout.logout') ?>
				</a>
				<?php
			endif; ?>
			</div>
		</div>
		<!-- Bereich 2: Projektinfo -->
		<div class="card" style="align-items: center;">
			<div class="card-body">
				<h5 class="card-title"><?= lang('Layout.project_from') ?></h5>
				<p class="card-text">
					&copy; 2025 Webseiten und mehr - Hamburg <br />
					<?= lang('Layout.owner') ?> <br />
					<hr /> All rights reserved.
				</p>
			</div>
		</div>
		<!-- Bereich 3: Version & Logo -->
		<div class="card" style="align-items: center;">
			<div class="card-body">
				<h5 class="card-title"><?= lang('Layout.version') ?></h5>
				<p class="card-text">
					<a href="<?= base_url('/') ?>">
					<img src="<?= base_url('images/logo-web.jpg') ?>" alt="WUM-HH" class="img-thumbnail mx-auto" style="width: 100px;">
					</a>
				</p>
			</div>
		</div>
	</div>
</div>

<script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"> </script>
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js'); ?>"> </script>
<em>&copy; 2025</em>
</body>
</html>
