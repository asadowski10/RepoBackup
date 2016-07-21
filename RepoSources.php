<?php

interface RepoSource {
	function setCredentials( $params );

	function getRepositories();
}

class BitbucketSource implements RepoSource {

	function setCredentials( $params ) {
		$this->user   = strtolower( $params['user'] );
		$this->pass   = $params['pass'];
		$this->client = new Bitbucket\API\Repositories();
		$this->client->setCredentials( new Bitbucket\API\Authentication\Basic( $params['user'], $params['pass'] ) );
	}

	function getRepositories() {
		$res   = $this->client->all( $this->user );
		$list  = json_decode( $res->getContent(), true );
		$repos = $list['values'];
		while ( ! empty( $list['next'] ) ) {
			$list  = $this->client->getClient()->setApiVersion( '2.0' )->request( $list['next'] );
			$list  = $list = json_decode( $list->getContent(), true );
			$repos = array_merge( $repos, $list['values'] );
		}
		$ret = array();
		foreach ( $repos as $repo ) {
			$url   = $repo['links']['clone'][1]['href'];
			$ret[] = array( 'name' => $repo['name'], 'clone_url' => $url, 'type' => $repo['scm'] );
		}

		return $ret;
	}
}

class BitbucketSourceV2 implements RepoSource {

	public $repositories;
	public $client;
	public $team;

	function setCredentials( $params ) {
		$this->client_id     = $params['client_id'];
		$this->client_secret = $params['client_secret'];
		$this->team          = $params['team'];

		$oauth_params = array(
			'client_id'         => $params['client_id'],
			'client_secret'     => $params['client_secret'],
		);
		$this->client = new \Bitbucket\API\Api();
		$this->client->getClient()->addListener(
			new \Bitbucket\API\Http\Listener\OAuth2Listener($oauth_params)
		);
		/* @var \Bitbucket\API\Repositories $repositories */
		$this->repositories = $this->client->api('Repositories');
	}

	function getRepositories() {
		$res   = $this->repositories->all( $this->team );
		$list  = json_decode( $res->getContent(), true );
		$repos = $list['values'];

		while ( ! empty( $list['next'] ) ) {
			$list  = $this->client->getClient()->setApiVersion( '2.0' )->request( $list['next'] );
			$list  = $list = json_decode( $list->getContent(), true );
			$repos = array_merge( $repos, $list['values'] );
		}

		$ret = array();
		foreach ( $repos as $repo ) {
			$url   = $repo['links']['clone'][1]['href'];
			$ret[] = array( 'name' => $repo['name'], 'clone_url' => $url, 'type' => $repo['scm'] );
		}

		return $ret;
	}
}

class GitHubSource implements RepoSource {

	function setCredentials( $params ) {
		$this->user   = $params['user'];
		$this->client = new \Github\Client(
			new \Github\HttpClient\CachedHttpClient( array( 'cache_dir' => '/tmp/github-api-cache' ) )
		);
		$this->client->authenticate( $params['token'], Github\Client::AUTH_HTTP_TOKEN );
	}

	function getRepositories() {
		$repos = $this->client->api( 'user' )->repositories( $this->user );
		$ret   = array();
		foreach ( $repos as $repo ) {
			$ret[] = array( 'clone_url' => $repo['clone_url'], 'name' => $repo['name'], 'type' => 'git' );
		}

		$gists = $this->client->api( 'gists' )->all();
		foreach ( $gists as $gist ) {
			$ret[] = array( 'clone_url' => $gist['git_pull_url'], 'name' => 'gists/' . $gist['id'], 'type' => 'git' );
		}

		return $ret;
	}
}
