<?php
require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('auth create sheet');
    $client->setScopes(Google_Service_Sheets::DRIVE,
    Google_Service_Sheets::DRIVE_FILE,
    Google_Service_Sheets::DRIVE_READONLY,
    Google_Service_Sheets::SPREADSHEETS,
    Google_Service_Sheets::SPREADSHEETS_READONLY);
    $client->setAuthConfig('key.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


// Get the API client and construct the service object.

$client = getClient();

$service = new Google_Service_Sheets($client);
$title = 'Mi csv con datos o eso espero';
$spreadsheet = new Google_Service_Sheets_Spreadsheet([
    'properties' => [
        'title' => $title
    ]
]);
$spreadsheet = $service->spreadsheets->create($spreadsheet, [
    'fields' => 'spreadsheetId'
]);

printf("Spreadsheet ID: %s\n", $spreadsheet->spreadsheetId);

/* ****** */

$options = array('valueInputOption' => 'RAW');
$values = [
    ["Name", "Roll No.", "Contact"],
    ["Anis", "001", "+88017300112233"],
    ["Ashik", "002", "+88017300445566"]
];


$body   = new Google_Service_Sheets_ValueRange(['values' => $values]);

$spreadsheetId = $spreadsheet->spreadsheetId;
$result = $service->spreadsheets_values->update($spreadsheetId, 'A1:C3', $body, $options);
print($result->updatedRange. PHP_EOL);

