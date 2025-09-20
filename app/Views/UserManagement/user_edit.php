<!-- UserManagement/user_edit.php -->
<?= $this->extend('layout.php') ?>
<?= $this->section('content') ?>
<!-- formular -->
<div class="container mt-4">
<h2><?= lang('UserManagement.user') ?>: <?= esc($user['username']) ?></h2>
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
<?php
if (session('success_message')) : ?>
<div class="alert alert-success">
	<?= esc(session('success_message')) ?>
</div>
<?php
endif ?>

	<form method="post" action="<?= site_url('users/update/' . $user['id']) ?>">
		<input type="hidden" name="id" value="<?= esc($user['id']) ?>">
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.username') ?></label>
			<input type="text" name="username" class="form-control" required value="<?= esc($user['username']) ?>">
		</div>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.first_name') ?></label>
			<input type="text" name="first_name" class="form-control" required value="<?= esc($user['first_name']) ?>">
		</div>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.last_name') ?></label>
			<input type="text" name="last_name" class="form-control" required value="<?= esc($user['last_name']) ?>">
		</div>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.language') ?></label>
			<select name="language" class="form-select">
				<option value="de" <?= $user['language'] === 'de' ? 'selected' : '' ?>>Deutsch</option>
				<option value="en" <?= $user['language'] === 'en' ? 'selected' : '' ?>>English</option>
				<option value="es" <?= $user['language'] === 'es' ? 'selected' : '' ?>>Español</option>
				<option value="fr" <?= $user['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
			</select>
		</div>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.password') ?></label>
			<input type="password" name="password" class="form-control">
			<small class="form-text text-muted"><?= lang('UserManagement.password_hint') ?? '' ?></small>
		</div>
		<?php
		if (session()->get('rights') === 'superadmin') : ?>
		<div class="mb-3">
			<label class="form-label"><?= lang('UserManagement.rights') ?></label>
			<select name="rights" class="form-select">
				<option value="registered" <?= $user['rights'] === 'registered' ? 'selected' : '' ?>><?= lang('UserManagement.rights_registered') ?></option>
				<option value="admin" <?= $user['rights'] === 'admin' ? 'selected' : '' ?>><?= lang('UserManagement.rights_admin') ?></option>
				<option value="superadmin" <?= $user['rights'] === 'superadmin' ? 'selected' : '' ?>><?= lang('UserManagement.rights_superadmin') ?></option>
			</select>
		</div>
		<?php
	endif; ?>

		<div class="d-flex gap-2">
			<button type="submit" class="btn btn-primary">
				<i class="fas fa-save"></i> <?= lang('UserManagement.save_changes') ?? 'Änderung speichern' ?>
			</button>
			<?php
			if (session()->get('rights') === 'superadmin') : ?>
			<a href="<?= site_url('users/delete/' . $user['id']) ?>"
                   class="btn btn-danger"
                   onclick="return confirm('<?= lang('UserManagement.confirm_delete') ?? 'Wirklich löschen?' ?>');">
				<i class="fas fa-trash"></i> <?= lang('UserManagement.delete') ?? 'Löschen' ?>
			</a>
			<?php
		endif; ?>
		</div>
	</form>
</div>
<?= $this->endSection() ?>
