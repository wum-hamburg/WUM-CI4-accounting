<?= $this->extend('layout'); ?>

<?= $this->section('content'); ?>
<div class="container">
	<h1 class="row justify-content-center"><?= lang('Login.welcome'); ?></h1>
</div>
<div class="row justify-content-center">
	<div class="col-md-6 col-lg-4">
		<div class="card mt-5">
			<div class="card-body">
				<h2 class="card-title text-center mb-4"><?= lang('Login.title'); ?></h2>
				<?php
				if (session()->getFlashdata('login_error')) : ?>
				<div class="alert alert-danger"><?= session()->getFlashdata('login_error'); ?></div>
				<?php
			endif; ?>
				<form action="<?= base_url('login'); ?>" method="post" id="loginForm">
					<div class="mb-3">
						<label for="username" class="form-label"><?= lang('Login.username'); ?></label>
						<input type="username" class="form-control" id="username" name="username" required placeholder="<?= lang('Login.username'); ?>">
					</div>
					<div class="mb-3">
						<label for="password" class="form-label"><?= lang('Login.password'); ?></label>
						<input type="password" class="form-control" id="password" name="password" required placeholder="<?= lang('Login.password'); ?>">
					</div>
					<button type="submit" class="btn btn-primary w-100"><?= lang('Login.login'); ?></button>
				</form>
			</div>
		</div>
	</div>
</div>
<script>
	// Example: Use PHP lang() in JS for client-side validation/messages
	const loginError = "<?= lang('Login.error'); ?>";
	document.getElementById('loginForm').addEventListener('submit', function(e) {
		// Example: client-side validation (expand as needed)
		let username = document.getElementById('username').value.trim();
		let password = document.getElementById('password').value.trim();
		if (!username || !password) {
			e.preventDefault();
			alert(loginError);
		}
	});
</script>
<?= $this->endSection(); ?>
