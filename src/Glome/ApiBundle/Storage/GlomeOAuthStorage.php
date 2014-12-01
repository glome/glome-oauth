<?php
  namespace Glome\ApiBundle\Storage;

  use Symfony\Bundle\FrameworkBundle\Controller\Controller;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

  use Symfony\Component\Config\Definition\Exception\Exception;
  use Symfony\Component\HttpFoundation\Request;
  use Symfony\Component\HttpFoundation\Response;
  use Symfony\Component\HttpKernel\Exception\HttpException;
  use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

  use Symfony\Component\Security\Core\SecurityContext;
  use Symfony\Component\Security\Core\Exception\AccessDeniedException;
  use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
  use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
  use Symfony\Component\Security\Core\User\UserProviderInterface;
  use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
  use Symfony\Component\Security\Core\Exception\AuthenticationException;

  use Doctrine\ORM\EntityManager;

  use FOS\RestBundle\Controller\FOSRestController,
    FOS\RestBundle\View\RouteRedirectView,
    FOS\RestBundle\View\View,
    FOS\RestBundle\Controller\Annotations\QueryParam,
    FOS\RestBundle\Controller\Annotations\RequestParam,
    FOS\RestBundle\Request\ParamFetcherInterface;

  use FOS\OAuthServerBundle\FOSOAuthServerBundle;
  use FOS\OAuthServerBundle\Storage\OAuthStorage as OAuthStorage;
  use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
  use FOS\OAuthServerBundle\Model\RefreshTokenManagerInterface;
  use FOS\OAuthServerBundle\Model\AuthCodeManagerInterface;
  use FOS\OAuthServerBundle\Model\ClientManagerInterface;
  use FOS\OAuthServerBundle\Model\ClientInterface;

  use OAuth2\Model\IOAuth2Client;
  use OAuth2\Model\OAuth2Client;
  use OAuth2\Model\OAuth2AccessToken;

  use GuzzleHttp\Client;

  use Nelmio\ApiDocBundle\Annotation\ApiDoc;

  use Glome\ApiBundle\Entity\User;
  use Glome\ApiBundle\Entity\GlomeAuthenticationUser;

  class GlomeOAuthStorage extends OAuthStorage {
    /**
     * Extended constructor to offer EntityManager (em) for this controller.
     * em is needed for reading/writing User entities.
     */
    public function __construct(
        ClientManagerInterface $clientManager,
        AccessTokenManagerInterface $accessTokenManager,
        RefreshTokenManagerInterface $refreshTokenManager,
        AuthCodeManagerInterface $authCodeManager,
        UserProviderInterface $userProvider = null,
        EncoderFactoryInterface $encoderFactory = null,
        EntityManager $em = null) {

          parent::__construct(
              $clientManager,
              $accessTokenManager,
              $refreshTokenManager,
              $authCodeManager,
              $userProvider,
              $encoderFactory);

          $this->em = $em;
      }

    /**
     * (@inheritdoc)
     */
    public function createAccessToken($tokenString, IOAuth2Client $client, $data, $expires, $scope = null) {
          /*
           * N/A
          if (!$client instanceof ClientInterface) {
              throw new \InvalidArgumentException('Client has to implement the ClientInterface');
          }
          */

          $token = $this->accessTokenManager->createToken();
          $token->setToken($tokenString);
          $token->setClient($client);
          $token->setExpiresAt($expires);
          $token->setScope($scope);

          if ($data !== null) {
              $token->setUser($data);
          }

          $this->accessTokenManager->updateToken($token);

          return $token;
      }

    /**
     * Communicate with Glome Server's API to create actual Glome user.
     *
     * @param $username A username
     * @param $password User's password
     *
     * @return bool TRUE when user creation was successful, FALSE otherwise.
     */
    public function createGlomeUser($username = null, $password = null) {
          $client = new Client();

          $user_data = [];
          if ($username != null) {
              $user_data['user[glomeid]'] = $username;
          }
          if ($password != null) {
              $user_data['user[password]'] = $password;
              $user_data['user[password_confirmation]'] = $password;
          }
        $user_data['application[uid]'] = "stone.glome.me";
        $user_data['application[apikey]'] = "c8ce221f85eb4eb70f8345aa0efbe3f5";

          $user = $client->post('https://stone.glome.me/users.json',
            [
              'exceptions' => false,

              /*
              'body' => [
                'user[glomeid]' => $username,
                'user[password]' => $password,
                'user[password_confirmation]' => $password
              ]
              */
              'body' => $user_data,
            ]);
        switch($user->getStatusCode()) {
            case 200:
            case 201:
                break;
            default:
                throw new \Exception($user);
        }
        return ($user->json());
      }

    /**
     * Login
     *
     * @param $username A user
     * @param $password User's password
     *
     * @return mixed Glome user data or null
     */
    public function loginGlomeUser($username, $password = null) {
          $client = new Client();

          $user = $client->post('http://stone.glome.me/users/login.json',
              [
                'exceptions' => false,
                'body' => [
                  'user[glomeid]' => $username,
                  'user[password]' => $password
                ]
              ]);

          return $user;
      }

      /**
       *  Check user login on the Glome backend-server.
       *  Create user by name, if one does not already exist.
       */
      public function checkUserCredentials(IOAuth2Client $client, $username = null, $password = null) {
          // Check if new user
          if ($username == null) {
              $response = $this->createGlomeUser();
              $username = $response['glomeid'];
          }

          /*
           * Try to login Glome Backend server with credentials
           */
          try {
              $loginToGlome = $this->loginGlomeUser($username, $password);

              switch ($loginToGlome->getStatusCode()) {
                  case (200):
                      break;

                  case (403):
                      throw new Exception("Possibly wrong username or password");
                      /*
                      if ($this->createGlomeUser($username, $password) == true) {
                          $loginToGlome = $this->loginGlomeUser($username, $password);
                      } else {
                          throw new Exception("Possibly wrong password");
                      }
                      */
                      break;

                  default:
                      throw new Exception($loginToGlome);
                      break;
              }

              /**
               * Create local user
               */
              $userRepo = $this->em->getRepository('Glome\ApiBundle\Entity\User');
              $glome_id = $loginToGlome->json()['glomeid'];
              $user = $userRepo->findOneBy(array('username' => $glome_id));

              if ($user == null) {
                  $user = new User();

                  $user->setUsername($glome_id);
                  //$user->json()['password']
                  // TODO: SANITIZE
                  //$user->setPassword($_POST['password']);

                  $this->em->persist($user);
              }

          } catch (AuthenticationException $e) {
              return false;
          }

          if (null !== $loginToGlome->json()) {
              return array(
                  'data' => $user,
              );
          }

          return false;
      }
  }