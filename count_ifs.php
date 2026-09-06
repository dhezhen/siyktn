<?php
$c = file_get_contents('C:\laragon\www\siyktn\storage\framework\views\ceeec5b4dd2145efe6d283f91c729087.php');
echo 'Ifs: '.substr_count($c, '<?php if').' Endifs: '.substr_count($c, '<?php endif;');
