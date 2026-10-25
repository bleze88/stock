<?php foreach (flashConsume() as $flash): ?>
<div class="flash flash--<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
