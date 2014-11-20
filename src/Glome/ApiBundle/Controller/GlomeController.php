<?php

namespace Glome\ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Glome\ApiBundle\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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


class GlomeController extends FOSRestController
{
    public function indexAction($name)
    {
        return $this->render('GlomeApiBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     *  @Route("/api/glomeLoginAction", name="/api/glomeLoginAction")
     *  @Method({"GET","POST"})
     */
    public function glomeLoginAction(Request $request)
    {
        $credentials = $request->request->get('form');

        print_r($credentials['glomeid']);
        $client = new Client();

        $res = $client->post('http://stone.glome.me/users/login.json',
            ['exceptions' => false, 'body' =>
                ['user[glomeid]' => $credentials['glomeid'],
                    'user[password]' => $credentials['password']]]);

        if ($res->getStatusCode() != 200) {
          return $this->redirect($this->generateUrl('/api/login', array('reload' => 1)));
        }

        echo var_export($res->json());

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

    /**
     *  @Route("/api/login", name="/api/login")
     *  @Method({"GET","POST"})
     */
    public function loginAction(Request $request)
    {
        if ($request->isMethod('POST')) {
          if ($request->get('reload') != 1) {
              $glomeid = $request->request->get('form[glomeid]','no glomeid');
              $password = $request->request->get('form[password]','no password');
              $lel = $this->forward('GlomeApiBundle:Glome:glomeLogin', array(
                  'glomeid'  => $glomeid,
                  'password' => $password,
              ));
              return $lel;
              //return $this->redirect($this->generateUrl('/api/glomeLoginAction', array('glomeid' => $glomeid, 'password' => $password)));
          }
        }

        // create a task and give it some dummy data for this example
        $user = new GlomeAuthenticationUser();

        $form = $this->createFormBuilder($user)
            ->add('glomeid', 'text')
            ->add('password', 'text', array('required' => false))
            ->add('save', 'submit', array('label' => 'Login'))
            ->getForm();

        return $this->render('GlomeApiBundle:Glome:index.html.twig', array(
            'form' => $form->createView(),
        ));


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

    public function loginCheckAction(Request $request)
    {

    }
}
