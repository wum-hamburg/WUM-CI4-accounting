<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= esc($pageTitle ?? 'Auftragsbestätigung') ?> – WUM Hamburg</title>
	<link href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
	<link href="<?= base_url('assets/bootstrap/css/WUM-Style.css') ?>" rel="stylesheet">
	<link href="<?= base_url('assets/fontawesome/css/all.min.css') ?>" rel="stylesheet">
	<link href="<?= base_url('assets/css/order-confirmation.css') ?>" rel="stylesheet">
	<link rel="icon" type="image/jpeg" href="<?= base_url('images/logo-web.jpg') ?>">
</head>
<body class="order-confirmation-page">
<div class="container py-4">
	<div class="text-center mb-4">
		<img src="<?= base_url('images/logo-web.jpg') ?>" alt="WUM Hamburg" class="order-confirmation-logo">
		<h1 class="h4 mt-2"><?= esc($pageTitle ?? 'Auftragsbestätigung') ?></h1>
	</div>
	<?= $this->renderSection('content') ?>
	<footer class="text-center text-muted small mt-5 pt-3 border-top">
		<p>&copy; 2026 Webseiten und mehr - Hamburg · Version 4.3.022</p>
		<p><a href="https://wum.hamburg/impressum/datenschutz" target="_blank" rel="noopener noreferrer">Datenschutzerklärung</a></p>
	</footer>
</div>
<script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
