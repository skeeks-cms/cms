<?php

namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\models\CmsOauthAccessToken;
use skeeks\cms\models\CmsOauthAuthorizationCode;
use skeeks\cms\models\CmsOauthClient;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

class OauthController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['authorize'],
                'rules' => [
                    [
                        'actions' => ['authorize'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'register' => ['post'],
                    'token' => ['post'],
                ],
            ],
        ];
    }

    public function actionAuthorizationServer()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'issuer' => $this->issuer(),
            'authorization_endpoint' => Url::to(['/cms/oauth/authorize'], true),
            'token_endpoint' => Url::to(['/cms/oauth/token'], true),
            'registration_endpoint' => Url::to(['/cms/oauth/register'], true),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'code_challenge_methods_supported' => ['S256', 'plain'],
            'scopes_supported' => ['cms.tasks.create'],
        ];
    }

    public function actionProtectedResource()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'resource' => $this->mcpResource(),
            'authorization_servers' => [
                $this->issuer(),
            ],
            'scopes_supported' => ['cms.tasks.create'],
        ];
    }

    public function actionRegister()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->statusCode = 201;

        $payload = $this->getJsonPayload();
        $redirectUris = array_values(array_filter((array)($payload['redirect_uris'] ?? [])));
        if (!$redirectUris) {
            throw new BadRequestHttpException('redirect_uris is required.');
        }

        foreach ($redirectUris as $redirectUri) {
            if (!$this->isAllowedRedirectUri($redirectUri)) {
                throw new BadRequestHttpException('Invalid redirect_uri.');
            }
        }

        $scopes = $this->normalizeScopes($payload['scope'] ?? 'cms.tasks.create');
        if (array_diff($scopes, ['cms.tasks.create'])) {
            throw new BadRequestHttpException('Invalid scope.');
        }

        $clientSecret = Yii::$app->security->generateRandomString(48);
        $client = new CmsOauthClient([
            'created_at' => time(),
            'updated_at' => time(),
            'client_id' => 'mcp_'.Yii::$app->security->generateRandomString(32),
            'secret_hash' => password_hash($clientSecret, PASSWORD_DEFAULT),
            'name' => (string)($payload['client_name'] ?? 'MCP Client'),
            'redirect_uris' => Json::encode($redirectUris),
            'scopes' => Json::encode($scopes ?: ['cms.tasks.create']),
            'is_active' => 1,
        ]);

        if (!$client->save()) {
            throw new BadRequestHttpException('Failed to register OAuth client.');
        }

        return [
            'client_id' => $client->client_id,
            'client_secret' => $clientSecret,
            'client_id_issued_at' => time(),
            'client_secret_expires_at' => 0,
            'redirect_uris' => $redirectUris,
            'grant_types' => ['authorization_code'],
            'response_types' => ['code'],
            'scope' => implode(' ', $client->getScopes()),
            'token_endpoint_auth_method' => 'client_secret_basic',
        ];
    }

    public function actionAuthorize($response_type, $client_id, $redirect_uri = null, $scope = 'cms.tasks.create', $state = null, $code_challenge = null, $code_challenge_method = 'plain', $resource = null)
    {
        if ($response_type !== 'code') {
            throw new BadRequestHttpException('Only response_type=code is supported.');
        }

        $client = CmsOauthClient::findActiveByClientId($client_id);
        if (!$client) {
            throw new BadRequestHttpException('Unknown OAuth client.');
        }

        $redirectUris = $client->getRedirectUris();
        $redirectUri = $redirect_uri ?: reset($redirectUris);
        if (!$redirectUri || !$client->allowsRedirectUri($redirectUri)) {
            throw new BadRequestHttpException('Invalid redirect_uri.');
        }

        if (!$this->isAllowedRedirectUri($redirectUri)) {
            throw new BadRequestHttpException('Invalid redirect_uri.');
        }

        $scopes = $this->normalizeScopes($scope);
        if (!$scopes) {
            $scopes = ['cms.tasks.create'];
        }

        if (!$client->allowsScopes($scopes)) {
            throw new BadRequestHttpException('Invalid scope.');
        }

        if (!$code_challenge) {
            throw new BadRequestHttpException('code_challenge is required.');
        }

        if (!in_array($code_challenge_method, ['S256', 'plain'], true)) {
            throw new BadRequestHttpException('Invalid code_challenge_method.');
        }

        $resource = $this->normalizeResource($resource);
        if ($resource !== $this->mcpResource()) {
            throw new BadRequestHttpException('Invalid resource.');
        }

        $code = Yii::$app->security->generateRandomString(64);
        $authCode = new CmsOauthAuthorizationCode([
            'created_at' => time(),
            'client_id' => $client->id,
            'cms_user_id' => Yii::$app->user->id,
            'code_hash' => CmsOauthAuthorizationCode::hashCode($code),
            'redirect_uri' => $redirectUri,
            'scopes' => Json::encode($scopes),
            'expires_at' => time() + 300,
        ]);
        if ($authCode->hasAttribute('resource')) {
            $authCode->setAttribute('resource', $resource);
        }
        if ($authCode->hasAttribute('code_challenge')) {
            $authCode->setAttribute('code_challenge', $code_challenge);
        }
        if ($authCode->hasAttribute('code_challenge_method')) {
            $authCode->setAttribute('code_challenge_method', $code_challenge_method);
        }

        if (!$authCode->save()) {
            throw new BadRequestHttpException('Failed to create authorization code.');
        }

        $params = ['code' => $code];
        if ($state !== null) {
            $params['state'] = $state;
        }

        return $this->redirect($this->appendQuery($redirectUri, $params));
    }

    public function actionToken()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $grantType = Yii::$app->request->post('grant_type');
        if ($grantType !== 'authorization_code') {
            throw new BadRequestHttpException('Only grant_type=authorization_code is supported.');
        }

        $clientId = Yii::$app->request->post('client_id');
        $clientSecret = Yii::$app->request->post('client_secret');
        $this->readBasicCredentials($clientId, $clientSecret);

        $client = CmsOauthClient::findActiveByClientId($clientId);
        if (!$client || !$client->validateSecret($clientSecret)) {
            throw new UnauthorizedHttpException('Invalid OAuth client credentials.');
        }

        $code = Yii::$app->request->post('code');
        $authCode = CmsOauthAuthorizationCode::findActiveByCode($code);
        if (!$authCode || (int)$authCode->client_id !== (int)$client->id) {
            throw new BadRequestHttpException('Invalid authorization code.');
        }

        $redirectUri = Yii::$app->request->post('redirect_uri');
        if ($redirectUri && $redirectUri !== $authCode->redirect_uri) {
            throw new BadRequestHttpException('Invalid redirect_uri.');
        }

        $resource = $this->normalizeResource(Yii::$app->request->post('resource'));
        if ($resource !== $this->mcpResource()) {
            throw new BadRequestHttpException('Invalid resource.');
        }
        if ($authCode->hasAttribute('resource') && $authCode->getAttribute('resource') && $resource !== $authCode->getAttribute('resource')) {
            throw new BadRequestHttpException('Invalid resource.');
        }

        $codeVerifier = Yii::$app->request->post('code_verifier');
        if (!$this->validateCodeVerifier($authCode, $codeVerifier)) {
            throw new BadRequestHttpException('Invalid code_verifier.');
        }

        $token = Yii::$app->security->generateRandomString(64);
        $expiresIn = 3600 * 24 * 30;
        $accessToken = new CmsOauthAccessToken([
            'created_at' => time(),
            'client_id' => $client->id,
            'cms_user_id' => $authCode->cms_user_id,
            'token_hash' => CmsOauthAccessToken::hashToken($token),
            'scopes' => $authCode->scopes,
            'expires_at' => time() + $expiresIn,
        ]);
        if ($accessToken->hasAttribute('resource')) {
            $accessToken->setAttribute('resource', $resource);
        }

        if (!$accessToken->save()) {
            throw new BadRequestHttpException('Failed to create access token.');
        }

        $authCode->updateAttributes(['used_at' => time()]);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'scope' => implode(' ', (array)Json::decode((string)$authCode->scopes)),
        ];
    }

    protected function readBasicCredentials(&$clientId, &$clientSecret)
    {
        $header = Yii::$app->request->headers->get('Authorization');
        if (!$header || stripos($header, 'Basic ') !== 0) {
            return;
        }

        $decoded = base64_decode(substr($header, 6), true);
        if ($decoded === false || strpos($decoded, ':') === false) {
            return;
        }

        list($clientId, $clientSecret) = explode(':', $decoded, 2);
    }

    protected function normalizeScopes($scope)
    {
        $scope = trim((string)$scope);
        return $scope === '' ? [] : preg_split('/\s+/', $scope);
    }

    protected function getJsonPayload(): array
    {
        $payload = Yii::$app->request->bodyParams ?: Yii::$app->request->post();
        if (!$payload && Yii::$app->request->rawBody) {
            $payload = Json::decode(Yii::$app->request->rawBody);
        }

        return (array)$payload;
    }

    protected function validateCodeVerifier(CmsOauthAuthorizationCode $authCode, $codeVerifier): bool
    {
        if (!$authCode->hasAttribute('code_challenge') || !$authCode->getAttribute('code_challenge')) {
            return true;
        }

        $codeVerifier = (string)$codeVerifier;
        if ($codeVerifier === '') {
            return false;
        }

        $codeChallenge = (string)$authCode->getAttribute('code_challenge');
        $codeChallengeMethod = $authCode->hasAttribute('code_challenge_method')
            ? (string)$authCode->getAttribute('code_challenge_method')
            : 'plain';

        if ($codeChallengeMethod === 'S256') {
            return $this->base64UrlEncode(hash('sha256', $codeVerifier, true)) === $codeChallenge;
        }

        return $codeVerifier === $codeChallenge;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function isAllowedRedirectUri($redirectUri): bool
    {
        $parts = parse_url((string)$redirectUri);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if ($parts['scheme'] === 'https') {
            return true;
        }

        return $parts['scheme'] === 'http' && in_array($parts['host'], ['127.0.0.1', 'localhost'], true);
    }

    protected function normalizeResource($resource): string
    {
        return rtrim((string)$resource, '/');
    }

    protected function mcpResource(): string
    {
        return rtrim(Url::to(['/cms/mcp-task/create'], true), '/');
    }

    protected function issuer(): string
    {
        return rtrim(Yii::$app->request->hostInfo, '/').'/cms/oauth';
    }

    protected function appendQuery($url, array $params)
    {
        $separator = strpos($url, '?') === false ? '?' : '&';
        return $url.$separator.http_build_query($params);
    }
}
