<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
class LoginController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
    }
    #[Route('/login', name: 'app_login')]
     
         public function index(AuthenticationUtils $authenticationUtils,HubInterface $hub): Response
          {
           /* $update = new Update(
                'https://example.com/books/1',
                json_encode(['message' => 'New package has been approved ID : '])
            );
    
            $hub->publish($update);*/
             // get the login error if there is one
             $error = $authenticationUtils->getLastAuthenticationError();
    
             // last username entered by the user
             $lastUsername = $authenticationUtils->getLastUsername();
    
              return $this->render('login/index.html.twig', [
                 'controller_name' => 'LoginController',
             'last_username' => $lastUsername,
                 'error'         => $error,
              ]);
          }

          #[Route('/logout', name: 'app_logout', methods: ['GET'])]
          public function logout()
          {
              // controller can be blank: it will never be called!
              throw new \Exception('Don\'t forget to activate logout in security.yaml');
          }   

        
}
