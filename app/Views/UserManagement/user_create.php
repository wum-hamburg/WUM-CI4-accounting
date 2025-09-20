<!-- User creation form (Bootstrap 5) -->
<?= $this->extend('layout.php') ?>

<?= $this->section('content') ?>
<!-- the formular  -->
<div class="container mt-4">
	<h2><?= lang('UserManagement.create_user') ?></h2>
	<?php
	if (session('errors')) : ?>
	<div class="alert alert-danger">
		<?php
		foreach (session('errors') as $error) : ?>
		<div><?= esc($error) ?></div>
		<?php
	endforeach ?>
	</div>
	<?php
endif ?>
	<form method="post" action="<?= site_url('users/store') ?>">
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.username') ?></label>
			<input type="text" name="username" class="form-control" required value="<?= old('username') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.first_name') ?></label>
			<input type="text" name="first_name" class="form-control" required value="<?= old('first_name') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.last_name') ?></label>
			<input type="text" name="last_name" class="form-control" required value="<?= old('last_name') ?>">
		</div>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.language') ?></label>
			<select name="language" class="form-select">
				<option value="de">Deutsch</option>
				<option value="en">English</option>
				<option value="es">Español</option>
				<option value="fr">Français</option>
			</select>
		</div>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.password') ?></label>
			<input type="password" name="password" class="form-control" required>
		</div>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.rights') ?></label>
			<select name="rights" class="form-select">
				<option value="registered"><?= lang('UserManagement.rights_registered') ?></option>
				<option value="admin"><?= lang('UserManagement.rights_admin') ?></option>
				<option value="superadmin"><?= lang('UserManagement.rights_superadmin') ?></option>
			</select>
		</div>
		<button type="submit" class="btn btn-success"><?= lang('UserManagement.create_user') ?></button>
	</form>
</div>
<?= $this->endSection() ?>