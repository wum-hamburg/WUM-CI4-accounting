<!-- UserManagement/user_chose.php -->
<?= $this->extend('layout.php') ?>

<?= $this->section('content') ?>
<!-- the formular  -->
<div class="container mt-4">
	<div class="row">
		<div class="col-md-6">
			<label for="userSelect" class="form-label"><?= lang('UserManagement.user') ?></label>
			<select id="userSelect" class="form-select" onchange="handleUserChange(this)">
				<option value=""><?= lang('UserManagement.please_select') ?? 'Bitte auswählen' ?></option>
				<option value="new"><?= lang('UserManagement.create_user') ?></option>
				<?php
				foreach ($users as $user) : ?>
				<option value="<?= esc($user['id']) ?>">
					<?= esc($user['username']) ?>
					<?php
					if ($user['username'] === $superadminUsername) : ?>
					(<?= lang('UserManagement.rights_superadmin') ?>)
					<?php
				endif; ?>
				</option>
				<?php
			endforeach; ?>
			</select>
		</div>
	</div>
</div>

<script>
	function handleUserChange(select)
	{
		const value = select.value;
		if (value === "")
			return;
		if (value === "new") {
			window.location.href = "<?= site_url('users/create') ?>";
		} else {
			window.location.href = "<?= site_url('users/edit') ?>/" + value;
		}
	}
</script>
<?= $this->endSection() ?>