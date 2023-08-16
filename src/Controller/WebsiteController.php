<?php

namespace App\Controller;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Notification;
use App\Entity\Account;
use App\Form\DeliveryType;
use App\Form\ExpeditorType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class WebsiteController extends AbstractController
{
    private $security;
    private $entityManager;
    private $user;

    public function __construct(Security $security, ManagerRegistry $doctrine)
    {

        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
        $this->entityManager = $doctrine->getManager();
        $this->user= $this->security->getUser();


    }
    #[Route('/', name: 'app_website')]
    public function index(): Response
    {
        return $this->render('website/index.html.twig', [
            'controller_name' => 'WebsiteController',
        ]);
    }

    #[Route('/signup/delivery', name: 'app_website_signup_delivery')]
    public function addDelivery(Request $req, SluggerInterface $slugger,UserPasswordHasherInterface $passwordHasher): Response
    {
        $login = $this->security->getUser();
        if($login){
          return  $this->redirectToRoute('app_dashbord');

        }
 
        $user = new User();

        $form = $this->createForm(DeliveryType::class,$user);
        $form->handlerequest($req);
        if($form->isSubmitted()  && $form->isValid()){
            $brochureFile = $form->get('photo')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('upload_directory').'/img/',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $user->setPhoto($newFilename);
            }

            $account =  $this->entityManager->getRepository(Account::class)->find(1);
            $user->setAccount($account);
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);
            $user->setRoles(['DELIVERY_ROLE']);
            $user->setState('waiting');
            try {
                $this->entityManager->persist($user);
            $this->entityManager->flush();

            $admin = $this->entityManager->getRepository(User::class)->findByRoleByAccount("ADMIN_ROLE",$user->getAccount()->getId());
            $notif  = new Notification();
            $notif->setTitle("A new delivery signup");
            $notif->setMessage("New delivery ".$user->getName()." demande is waiting for your validation  if you want to validate this delivery  <a href='/delivery/edit/".$user->getId()."'>click here</a>");
            $notif->setUser($admin);
            $notif->setIcon('<i class="fas fa-user-tie  text-warning"></i>');
            $notif->setCreatedAt(new \DateTime('@' . strtotime('now')));
            $notif->setSeen(0);
            $this->entityManager->persist($notif);
            $this->entityManager->flush();
            } catch (\Throwable $th) {
             
                return $this->render('website/addexpeditor.html.twig', [
                    'form' => $form->createView(),
                    "error"=>$th->getMessage()
                ]); 
            }
          return  $this->redirectToRoute('app_login',array('msg' => "Delivery added successfuly"));
        }
        return $this->render('website/adddelivery.html.twig', [
            'form' => $form->createView(),
        ]); 


}
    #[Route('/signup/expeditor', name: 'app_website_signup_expeditor')]
    public function addExpeditor(Request $req, SluggerInterface $slugger,UserPasswordHasherInterface $passwordHasher): Response
    {
        $login = $this->security->getUser();
        if($login){
          return  $this->redirectToRoute('app_dashbord');

        }
 
        $user = new User();

        $form = $this->createForm(expeditorType::class,$user);
        $form->handlerequest($req);
        if($form->isSubmitted()  && $form->isValid()){
            $brochureFile = $form->get('photo')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('upload_directory').'/img/',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $user->setPhoto($newFilename);
            }

            $account =  $this->entityManager->getRepository(Account::class)->find(1);
            $user->setAccount($account);
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);
            $user->setRoles(['EXPEDITOR_ROLE']);
            $user->setState('waiting');
            try {
                $this->entityManager->persist($user);
            $this->entityManager->flush();
            $admin = $this->entityManager->getRepository(User::class)->findByRoleByAccount("ADMIN_ROLE",$user->getAccount()->getId());
            $notif  = new Notification();
            $notif->setTitle("A new expeditor signup");
            $notif->setMessage("New Expeditor ".$user->getName()." demande is waiting for your validation if you want to validate this expeditor  <a href='/expeditor/edit/".$user->getId()."'>click here</a>");
            $notif->setUser($admin);
            $notif->setIcon('<i class="fas fa-user-tie text-warning"></i>');
            $notif->setCreatedAt(new \DateTime('@' . strtotime('now')));
            $notif->setSeen(0);
            $this->entityManager->persist($notif);
            $this->entityManager->flush();
            } catch (\Throwable $th) {
             
                return $this->render('website/addexpeditor.html.twig', [
                    'form' => $form->createView(),
                    "error"=>$th->getMessage()

                ]); 
            }
         
          return  $this->redirectToRoute('app_login',array('msg' => "Expeditor added successfuly"));
        }
        return $this->render('website/addexpeditor.html.twig', [
            'form' => $form->createView(),
        ]); 


}
}
