<?= $this->extend('layout'); ?>

<?= $this->section('content'); ?>
<div class="text-center mt-5">
	<h1><?= lang('Login.welcome'); ?></h1>
	<p><?= session()->get('user_name'); ?></p>
	<hr />
	<?php
	print_r($_SESSION);

	?>
	<hr />
</div>
<script>
	// Beispiel: Mehrsprachigkeit in JS
	const welcomeMsg = "<?= lang('Login.welcome'); ?>";
	console.log(welcomeMsg);
</script>
<?= $this->endSection(); ?>
