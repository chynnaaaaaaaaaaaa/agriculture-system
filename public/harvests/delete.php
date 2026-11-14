<?php
require __DIR__ . '/../../includes/bootstrap.php';
handle_delete(new Harvest(), 'Harvest record', 'harvests/index.php', 'it is still referenced by other records');
