
<?php
if (session()->getFlashdata('success')) : ?>
<div class="alert alert-success">
	<?= esc(session()->getFlashdata('success')) ?>
</div>
<?php
endif; ?>
<?php
if (session()->getFlashdata('error')) : ?>
<div class="alert alert-danger">
	<?= esc(session()->getFlashdata('error')) ?>
</div>
<?php
endif; ?>