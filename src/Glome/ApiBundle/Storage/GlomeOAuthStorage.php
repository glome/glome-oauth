<?php
  namespace Glome\ApiBundle\Storage;

  use FOS\OAuthServerBundle\Storage\OAuthStorage as OAuthStorage;
  use OAuth2\Model\IOAuth2Client;
  use OAuth2\Model\OAuth2Client;
  use OAuth2\Model\OAuth2AccessToken;


  // TODO: CLEANUP
  use FOS\RestBundle\Controller\FOSRestController;
  use Glome\ApiBundle\Entity\User;

  use Symfony\Bundle\FrameworkBundle\Controller\Controller;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

  use Symfony\Component\Config\Definition\Exception\Exception;
  use Symfony\Component\HttpFoundation\Request;
  use Symfony\Component\HttpFoundation\Response;
  use Symfony\Component\HttpKernel\Exception\HttpException;
  use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
  use Symfony\Component\Security\Core\Exception\AccessDeniedException;
  use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
  use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

  use FOS\RestBundle\View\RouteRedirectView,
      FOS\RestBundle\View\View,
      FOS\RestBundle\Controller\Annotations\QueryParam,
      FOS\RestBundle\Controller\Annotations\RequestParam,
      FOS\RestBundle\Request\ParamFetcherInterface;

  use Nelmio\ApiDocBundle\Annotation\ApiDoc;

  use GuzzleHttp\Client;
  use FOS\OAuthServerBundle\FOSOAuthServerBundle;
  use Glome\ApiBundle\Entity\GlomeAuthenticationUser;
  use Symfony\Component\Security\Core\SecurityContext;


  use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
  use FOS\OAuthServerBundle\Model\RefreshTokenManagerInterface;
  use FOS\OAuthServerBundle\Model\AuthCodeManagerInterface;
  use FOS\OAuthServerBundle\Model\ClientManagerInterface;
  use FOS\OAuthServerBundle\Model\ClientInterface;
  use Symfony\Component\Security\Core\User\UserProviderInterface;
  use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
  use Symfony\Component\Security\Core\Exception\AuthenticationException;
  use Doctrine\ORM\EntityManager;


  class GlomeOAuthStorage extends OAuthStorage {

      public function __construct(ClientManagerInterface $clientManager, AccessTokenManagerInterface $accessTokenManager,
                                  RefreshTokenManagerInterface $refreshTokenManager, AuthCodeManagerInterface $authCodeManager,
                                  UserProviderInterface $userProvider = null, EncoderFactoryInterface $encoderFactory = null,
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

      public function createAccessToken($tokenString, IOAuth2Client $client, $data, $expires, $scope = null)
      {
          /*
           * N/A
          if (!$client instanceof ClientInterface) {
              throw new \InvalidArgumentException('Client has to implement the ClientInterface');
          }*/

          $token = $this->accessTokenManager->createToken();
          $token->setToken($tokenString);
          $token->setClient($client);
          $token->setExpiresAt($expires);
          $token->setScope($scope);

          if (null !== $data) {
              $token->setUser($data);
          }

          $this->accessTokenManager->updateToken($token);

          return $token;
      }

      /*
       *  TODO: Error catching.
       */
      public function createGlomeUser($username, $password) {

          $client = new Client();

          $user = $client->post('https://stone.glome.me/users.json',
              ['exceptions' => false, 'body' =>
                  ['user[glomeid]' => $username,
                      'user[password]' => $password,
                      'user[password_confirmation]' => $password]]);

          if ($user->getStatusCode() != 200) {
              return false;
          } else {
              return true;
          }


      }

      public function loginGlomeUser($username, $password) {

          $client = new Client();

          $user = $client->post('http://stone.glome.me/users/login.json',
              ['exceptions' => false, 'body' =>
                  ['user[glomeid]' => $username,
                      'user[password]' => $password]]);
          return $user;
      }

      /**
       *  This function checks the user login on the glome backend-server.
       *  Also calls a method to create a user if non existing.
       *
       */
      public function checkUserCredentials(IOAuth2Client $client, $username, $password)
      {

          /*
           * Try to login Glome Backend server with credentials
           */
          try {
              $loginToGlome = $this->loginGlomeUser($username,$password);


              switch ($loginToGlome->getStatusCode()) {
                  case (200):
                      break;

                  case (403):
                      if ($this->createGlomeUser($username, $password) == true) {
                          $loginToGlome = $this->loginGlomeUser($username, $password);

                      } else {
                          throw new Exception("Possibly wrong password");
                      }
                      break;

                  default:
                      throw new Exception($loginToGlome);
                      break;
              }

              $userRepo = $this->em->getRepository('Glome\ApiBundle\Entity\User');

              $user = $userRepo->findOneBy(array('username' => $loginToGlome->json()['glomeid']));

              /**
               * Create user locally
               */
              if ($user == null) {

                  $user = new User();
                  $this->em->persist($user);

                  $user->setUsername($loginToGlome->json()['glomeid']);
                  //$user->json()['password']
                  // TODO: SANITIZE
                  $user->setPassword($_GET['password']);

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

          /*
          $res = $client->get('https://api.github.com/user', ['auth' =>  ['user', 'pass']]);
          echo $res->getStatusCode();
          // "200"
          echo $res->getHeader('content-type');
          // 'application/json; charset=utf8'
          echo $res->getBody();
          // {"type":"User"...'
          var_export($res->json());
          // Outputs the JSON decoded data

          // Send an asynchronous request.
          $req = $client->createRequest('GET', 'http://httpbin.org', ['future' => true]);
          $client->send($req)->then(function ($response) {
              echo 'I completed! ' . $response;
          });
          */
      }
  }