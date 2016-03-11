<?php
/**
 * Created by michael on 2/24/16.
 */
namespace ms365;

define('RUNNING_IN_DEVELOPMENT', true);
// $running_locally = $_SERVER[SERVER_NAME] == '0.0.0.0';
if (RUNNING_IN_DEVELOPMENT) {
    define('MS365_CLIENT_ID', 'd938a8bc-5fdf-402c-a64a-bf74a1dbd3f9');
    define('MS365_CLIENT_SECRET', 'y4erat8ghOvCij80t1LftzT');
} else {
    // This is CoPro MS365 App Creds
    define('MS365_CLIENT_ID', 'ec15de84-eb91-4a02-b508-c53a0b6cb0c8');
    define('MS365_CLIENT_SECRET', 'shed215');
}




  class oAuthService {
      private static $clientId = MS365_CLIENT_ID;
      private static $clientSecret = MS365_CLIENT_SECRET;
      private static $authority = "https://login.microsoftonline.com";
      private static $authorizeUrl = '/common/oauth2/v2.0/authorize?client_id=%1$s&redirect_uri=%2$s&response_type=code&scope=%3$s';
      private static $tokenUrl = "/common/oauth2/v2.0/token";

      // The app only needs openid (for user's ID info), and Mail.Read
      private static $scopes = array("openid",
          "https://outlook.office.com/mail.read",
          "https://outlook.office.com/calendars.ReadWrite",
          "https://outlook.office.com/Contacts.ReadWrite"
      );

      public static function getLoginUrl( $redirectUri ) {
          // Build scope string. Multiple scopes are separated
          // by a space
          $scopestr = implode( " ", self::$scopes );

          $loginUrl = self::$authority . sprintf( self::$authorizeUrl, self::$clientId, urlencode( $redirectUri ), urlencode( $scopestr ) );

          error_log( "Generated login URL: " . $loginUrl );

          return $loginUrl;
      }


      public static function getTokenFromAuthCode( $authCode, $redirectUri ) {
          // Build the form data to post to the OAuth2 token endpoint
          $token_request_data = array(
              "grant_type"    => "authorization_code",
              "code"          => $authCode,
              "redirect_uri"  => $redirectUri,
              "scope"         => implode( " ", self::$scopes ),
              "client_id"     => self::$clientId,
              "client_secret" => self::$clientSecret
          );

          // Calling http_build_query is important to get the data
          // formatted as expected.
          $token_request_body = http_build_query( $token_request_data );
          error_log( "Request body: " . $token_request_body );

          $curl = curl_init( self::$authority . self::$tokenUrl );
          curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
          curl_setopt( $curl, CURLOPT_POST, true );
          curl_setopt( $curl, CURLOPT_POSTFIELDS, $token_request_body );

          $response = curl_exec( $curl );
          error_log( "curl_exec done." );

          $httpCode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
          error_log( "Request returned status " . $httpCode );
          if ( $httpCode >= 400 ) {
              return array(
                  'errorNumber' => $httpCode,
                  'error'       => 'Token request returned HTTP error ' . $httpCode
              );
          }

          // Check error
          $curl_errno = curl_errno( $curl );
          $curl_err   = curl_error( $curl );
          if ( $curl_errno ) {
              $msg = $curl_errno . ": " . $curl_err;
              error_log( "CURL returned an error: " . $msg );

              return array(
                  'errorNumber' => $curl_errno,
                  'error'       => $msg
              );
          }

          curl_close( $curl );

          // The response is a JSON payload, so decode it into
          // an array.
          $json_vals = json_decode( $response, true );
          error_log( "TOKEN RESPONSE:" );
          foreach ( $json_vals as $key => $value ) {
              error_log( "  {$key}: {$value}" );
          }

          return $json_vals;
      }


      public static function getUserEmailFromIdToken($idToken) {
          error_log("ID TOKEN: ".$idToken);

          // JWT is made of three parts, separated by a '.'
          // First part is the header
          // Second part is the token
          // Third part is the signature
          $token_parts = explode(".", $idToken);

          // We care about the token
          // URL decode first
          $token = strtr($token_parts[1], "-_", "+/");
          // Then base64 decode
          $jwt = base64_decode($token);
          // Finally parse it as JSON
          $json_token = json_decode($jwt, true);
          return $json_token['preferred_username'];
      }
  }
