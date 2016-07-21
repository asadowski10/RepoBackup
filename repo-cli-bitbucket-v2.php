<?php
if ( 'cli' !== php_sapi_name() || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}

// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 4 ) {
	echo "Missing parameters.\n";
	echo "script usage: php repo-cli-bitbucket-v2.php [user_id] [user_secret] [team]\n";
	die();
}

//Domain
$user_id = ( isset( $argv[1] ) ) ? $argv[1] : '';
$user_secret = ( isset( $argv[2] ) ) ? $argv[2] : '';
$team = ( isset( $argv[3] ) ) ? strtolower( $argv[3] ) : '';

require_once 'vendor/autoload.php';
require_once 'RepoSources.php';
$repoBackup = new RepoBackup();
$bbSource = new BitbucketSourceV2();
$bbSource->setCredentials( array( 'client_id' => $user_id, 'client_secret' => $user_secret, 'team' => $team ) );
$this->backupRepositories( $bbSource, 'bitbucket/' . $team . '/' );