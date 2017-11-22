<?php

echo shell_exec("php artisan migrate");
echo shell_exec("php artisan queue:listen");

?>
