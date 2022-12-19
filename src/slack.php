<?php
namespace SlackRandomiser;

use \SlackRandomiser\SlackRequest;
use \SlackRandomiser\SlackApiException;
use \RmcCue\Request;

/**
 * Class Slack
 * @package SlackRandomiser
 */
class Slack {

	/**
	 * @var string $api_root
	 */
	private static $api_root = 'https://slack.com/api/';

	/**
	 * @var $slash_commands
	 */
	private $slash_commands;

	/**
	 * @var $state
	 */
	private $state;

	/**
	 * @var SlackRequest $access
	 */
	private $access;

	/**
	 * Slack constructor.
	 * @param $requestData
	 */
	public function __construct() {
		$this->access = new SlackRequest();
	}

	/**
	 * Authorise
	 */
	public function authorise()
	{
		$data = [
			'client_id' => getenv('SLACK_CLIENT_ID'),
			'scope' => getenv('SLACK_SCOPE'),
			'redirect_url' => getenv('SLACK_CALLBACK'),
			'state' => $this->state,
			'team' => getenv('SLACK_TEAM_ID'),
		];

		$response = Requests::post( self::$api_root . 'oauth.authorize', $headers, $data );
	}

	/**
	 * @param $slack
	 * @param $action
	 *
	 * @return string
	 */
	function doAction( $slack, $action ) {

		switch ( $action ) {

			// Handles the OAuth callback by exchanging the access code to
			// a valid token and saving it in a file
			case 'oauth':
				// $code = $_GET['code'];
				$code = getenv('SLACK_CODE');

				// Exchange code to valid access token
				try {
					$this->doOauth( $code );
				} catch ( SlackApiException $e ) {
					$message = $e->getMessage();
				}
				break;

			// Sends a notification to Slack
			case 'send_notification':
				$message = isset( $_REQUEST['text'] ) ? $_REQUEST['text'] : 'Hello!';

				try {
					$slack->sendNotification( $message );
					$message = 'Notification sent to Slack channel.';
				} catch ( SlackApiException $e ) {
					$message = $e->getMessage();
				}
				break;

			// Responds to a Slack slash command. Notice that commands are registered
			// at Slack initialization.
			case 'command':
				$slack->doSlashCommand();
				break;

			default:
				break;

		}

		return $message;
	}

	/**
	 * @param $code
	 *
	 * @return \SlackRandomiser\SlackRequest
	 * @throws \SlackRandomiser\SlackApiException
	 */
	public function doOauth( $code ) {

		if (isset($_SESSION['access'])) {
			var_dump($_SESSION['access']);
			return $_SESSION['access'];
		}

		// Set up the request headers
		$headers = array( 'Accept' => 'application/json' );

		// Add the application id and secret to authenticate the request
		$options = array( 'auth' => array( $this->getClientId(), $this->getClientSecret() ) );

		// Add the one-time token to request parameters
		$data = array( 'code' => $code );

		$response = Requests::post( self::$api_root . 'oauth.access', $headers, $data, $options );

		// Handle the JSON response
		$json_response = json_decode( $response->body );

		if ( ! $json_response->ok ) {
			// There was an error in the request
			throw new SlackApiException( $json_response->error );
		}

		// The action was completed successfully, store and return access data
		$this->access = new SlackRequest(
			array(
				'access_token' => $json_response->access_token,
				'scope' => explode( ',', $json_response->scope ),
				'team_name' => $json_response->team_name,
				'team_id' => $json_response->team_id,
				'incoming_webhook' => $json_response->incoming_webhook
			)
		);

		$_SESSION['access'] = $this->access;

		return $this->access;
	}

	/**
	 * Sends a notification to the Slack channel defined in the
	 * authorization (Add to Slack) flow.
	 *
	 * @param string $text          The message to post to Slack
	 * @param array $attachments    Optional list of attachments to send
	 *                              with the notification
	 * @throws SlackApiException
	 */
	public function sendNotification( $text, $attachments = array() ) {
		if ( ! $this->isAuthenticated() ) {
			throw new Slack_API_Exception( 'Access token not specified' );
		}

		// Post to webhook stored in access object
		$headers = array( 'Accept' => 'application/json' );

		$url = $this->access->getWebHookProperty('url');
		$data = json_encode(
			array(
				'text' => $text,
				'attachments' => $attachments,
				'channel' => $this->access->getWebHookProperty('channel')
			)
		);

		$response = Requests::post( $url, $headers, $data );

		if ( $response->body != 'ok' ) {
			throw new Slack_API_Exception( 'There was an error when posting to Slack' );
		}
	}

	/**
	 * @return bool
	 */
	public function isAuthenticated() {
		return isset( $this->access ) && $this->access->is_configured();
	}

	/**
	 * Registers a new slash command to be available through this
	 * @param string    $command    The slash command
	 * @param callback  $callback   The function to call to execute the command
	 */
	public function registerSlashCommand( $command, $callback ) {
		$this->slash_commands[$command] = $callback;
	}

	/**
	 * Runs the slash command passed in the $_POST data if the
	 * command is valid and has been registered using register_slash_command.
	 *
	 * The response written by the function will be read by Slack.
	 */
	public function doSlashCommand() {

		// Collect request parameters
		$token      = isset( $_POST['token'] ) ? $_POST['token'] : '';
		$command    = isset( $_POST['command'] ) ? $_POST['command'] : '';
		$text       = isset( $_POST['text'] ) ? $_POST['text'] : '';
		$user_name  = isset( $_POST['user_name'] ) ? $_POST['user_name'] : '';

		// Use the command verification token to verify the request
		if ( ! empty( $token ) && $this->getCommandToken() == $_POST['token'] ) {
			header( 'Content-Type: application/json' );

			if ( isset( $this->slash_commands[$command] ) ) {
				// This slash command exists, call the callback function to handle the command
				$response = call_user_func( $this->slash_commands[$command], $text, $user_name );
				echo json_encode( $response );
			} else {
				// Unknown slash command
				echo json_encode( array(
					'text' => "Sorry, I don't know how to respond to the command."
				) );
			}
		} else {
			echo json_encode( array(
				'text' => 'Oops... Something went wrong.'
			) );
		}

		// Don't print anything after the response
		exit;
	}

	/**
	 * Returns the Slack client ID.
	 *
	 * @return string   The client ID or empty string if not configured
	 */
	public function getClientId() {

		// If no constant found, look for environment variable
		if ( getenv( 'SLACK_CLIENT_ID' ) ) {
			return getenv( 'SLACK_CLIENT_ID' );
		}

		// Not configured, return empty string
		return '';
	}

	/**
	 * Returns the Slack client secret.
	 *
	 * @return string   The client secret or empty string if not configured
	 */
	private function getClientSecret() {

		// If no constant found, look for environment variable
		if ( getenv( 'SLACK_CLIENT_SECRET' ) ) {
			return getenv( 'SLACK_CLIENT_SECRET' );
		}

		// Not configured, return empty string
		return '';
	}

	/**
	 * Returns the command verification token.
	 *
	 * @return string   The command verification token or empty string if not configured
	 */
	private function getCommandToken() {

		// If no constant found, look for environment variable
		if ( getenv( 'SLACK_COMMAND_TOKEN' ) ) {
			return getenv( 'SLACK_COMMAND_TOKEN' );
		}

		// Not configured, return empty string
		return '';
	}

}
