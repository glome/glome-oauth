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


  class GlomeOAuthStorage extends OAuthStorage {



      /**
       *  TODO: Documentation
       */
      public function checkUserCredentials(IOAuth2Client $client, $username, $password)
      {
          /*

          if (!$client instanceof ClientInterface) {
              throw new \InvalidArgumentException('Client has to implement the ClientInterface');
          }


          try {
              $user = $this->userProvider->loadUserByUsername($username);
          } catch (AuthenticationException $e) {
              return false;
          }

          if (null !== $user) {
              $encoder = $this->encoderFactory->getEncoder($user);

              if ($encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
                  return array(
                      'data' => $user,
                  );
              }
          }

          */

          // TODO: Use fixture and not entity.
          $client = new Client();

          /*
           * Try to login Glome Backend server with credentials
           */
          try {
              $user = $client->post('http://stone.glome.me/users/login.json',
                  ['exceptions' => false, 'body' =>
                      ['user[glomeid]' => $username,
                          'user[password]' => $password]]);

              if ($user->getStatusCode() != 200) {
                  throw new Exception($user);
              }

          } catch (AuthenticationException $e) {
                  return false;
          }

          if (null !== $user->json()) {
              var_export($user->json());
              $encoder = $this->encoderFactory->getEncoder($user);

              if ($encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
                  return array(
                      'data' => $user,
                  );
              }
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