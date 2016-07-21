<?php

require_once 'vendor/autoload.php';
require_once 'RepoSources.php';
$repoBackup = new RepoBackup();
$repoBackup->run();