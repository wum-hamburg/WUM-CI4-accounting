<?= $this->include('templates/header.php') ?>
<!--the varable $menu does exist show menu, but if not per examble: Login - I don't want to see a menu--> 
<?php
if (isset($menu)) : ?>
<?= $this->include('partials/menu', [
	'menu' => $menu,
	'moduleTitleKey' => $moduleTitleKey ?? null
]); ?>
<?php
endif; ?>

<!--if the varable $menu don't' exist show languages-->
<?php if (!isset($menu)) : ?>
<?php $locale = service('request')->getLocale() ?? 'de'; ?>
<?php
$languages = [
	'de' => ['label' => 'Deutsch', 'flag' => "\u{1F1E9}\u{1F1EA}"],  // 🇩🇪
	'en' => ['label' => 'English', 'flag' => "\u{1F1EC}\u{1F1E7}"],  // 🇬🇧
	'es' => ['label' => 'Español', 'flag' => "\u{1F1EA}\u{1F1F8}"],  // 🇪🇸
	'fr' => ['label' => 'Français', 'flag' => "\u{1F1EB}\u{1F1F7}"], // 🇫🇷
];

?>
 <div class="language-switcher">
	<?php
	foreach ($languages as $code => $lang) : ?>
	<?php
	if ($code === $locale) : ?>
	<!-- Aktive Sprache: gelb & fett -->
	<span class="active-language" title="<?= esc($lang['label']) ?>">
	<?= esc($lang['label']) ?>
	</span>
	<?php
else : ?>
	<!-- Inaktive Sprache: als Flagge mit Link -->
	<a href="<?= site_url('language/switch/' . $code) ?>" class="inactive-language" title="<?= esc($lang['label']) ?>">
	<?= $lang['flag'] ?>
	</a>
	<?php
endif; ?>
	<?php
endforeach; ?>
</div>
<?php endif; ?>
<div class="container">
<?= $this->include('partials/flash') ?>
</div>
<div class="container">
	<?= $this->renderSection('content'); ?>
</div>
<script>
	// Beispiel: Mehrsprachigkeit in JS
	const welcomeMsg = "<?= lang('Login.welcome'); ?>";
	console.log(welcomeMsg);
</script>
<div style="height: 1cm;">
	<!--need a little bit space -->
</div>
<?= $this->include('templates/footer.php') ?>