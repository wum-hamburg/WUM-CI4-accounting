<nav class="navbar bg-light fixed">
<div class="container-fluid">
	<a class="navbar-brand" href="<?= base_url('index.php'); ?>">
		<img src="<?= base_url('images/logo-web.jpg'); ?>" alt="Wum.Hamburg" style="width:64px;height:64px;"/></a>
		<!-- title -->
		<h1 class="navbar-brand text-center mx-auto mb-0">
			<?php
		// Sprachschlüssel für den Titel, z.B. 'UserManagement.create_user'
		$titleKey = $moduleTitleKey ?? null;
		$title = $titleKey ? lang($titleKey) : null;

		// Prüfen, ob ein echter Titel gefunden wurde (lang() gibt sonst den Key zurück)
		if ($titleKey && $title !== $titleKey && !empty($title))
		{
			echo esc($title);
		}
		else
		{
			// Fallback: Zufälliges FontAwesome-Icon anzeigen
			$icons = [
				'fa-face-angry',
				'fa-face-flushed',
				'fa-face-frozen'
			];
			$randomIcon = $icons[array_rand($icons)];
			echo '<i class="fa-solid ' . $randomIcon . ' fa-2x mx-1"></i>';
		}
		?>
		</h1>
		<!--menue -->
		<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
	<span class="navbar-toggler-icon"></span>
</button>
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
<div class="offcanvas-header">
	<h5 class="offcanvas-title" id="offcanvasNavbarLabel">Auswahl:</h5>
	<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>
<div class="offcanvas-body">
<ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
				<?php
				foreach ($menu as $item) :
					if ($item['label'][0]=="-")
					{
						if (isset($drop))
						{
							echo "</ul></li>";//Submenü schließen
							unset($drop);
						}
					?>
					<li class="nav-item">
						<a class="<?= $item['class']; ?>" href="<?= base_url($item['route']); ?>">
						<?= substr($item['label'],1,strlen($item['label'])-1); ?>
					</a>
					</li>
				<?php
				}
				elseif ($item['label'][0]=="*")
				{
				?>
				<li class="nav-item dropdown">
				<a class="<?= $item['class']; ?>" data-bs-toggle="dropdown" aria-expanded="false" 
				href="<?= base_url($item['route']); ?>"> <?= substr($item['label'],1,strlen($item['label'])-1); ?>
				</a>
				<ul class="dropdown-menu">
				<?php
				}
				elseif ($item['label'][0]=="#")
				{
				$drop=TRUE;
				?>
				<li>
					<a class="<?= $item['class']; ?>" href="<?= base_url($item['route']); ?>">
					<?= substr($item['label'],1,strlen($item['label'])-1); ?>
					</a>
				</li>
				<?php
				}
			endforeach; ?>
			</ul>
		</div>
		</div>
		</div>
</nav>