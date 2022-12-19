<?php
namespace SlackRandomiser;

/**
 * Class SlackRequest
 * @package SlackRandomiser
 */
class SlackRequest {

	// Slack OAuth data
	private $access_token;
	private $scope;
	private $team_name;
	private $team_id;
	private $incoming_webhook;

	/**
	 * SlackAccess constructor.
	 * $requestData
	 */
	public function __construct() {
		if (!isset($_SESSION)) {
			$this->access_token     = getenv( 'SLACK_ACCESS_TOKEN' );
			$this->scope            = getenv( 'SLACK_SCOPE' );
			$this->team_name        = getenv( 'SLACK_TEAM_NAME' );
			$this->team_id          = getenv( 'SLACK_TEAM_ID' );
			$this->incoming_webhook = getenv( 'SLACK_WEBHOOK' );
		}
	}

	/**
	 * @param $propertyName
	 *
	 * @return mixed|null
	 */
	public function getWebHookProperty( $propertyName ) {
		if ( isset( $this->incoming_webhook[ $propertyName ] ) ) {
			return $this->incoming_webhook[ $propertyName ];
		}

		return null;
	}

}
