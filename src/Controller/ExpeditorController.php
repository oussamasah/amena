<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ExpeditorType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
class ExpeditorController extends AbstractController
{
    private $security;
    private $entityManager;
    private $user;
    public function __construct(Security $security,ManagerRegistry $doctrine)
    {
        $this->security = $security;
        $this->entityManager = $doctrine->getManager();

    }
    #[Route('/expeditor', name: 'app_expeditor')]
    public function index(): Response
    {
        $this->user= $this->security->getUser();
        if(!$this->user){
            return $this->RedirectToRoute('app_login');    
        }

        if (in_array('ADMIN_ROLE', $this->user->getRoles(), true)) {

            
            $account = $this->user->getAccount();
        
            $expeditor =  $this->entityManager->getRepository(User::class)->findExpeditorByAccount($account->getId());
            return $this->render('expeditor/index.html.twig', [
                'expeditor' => $expeditor,
            ]); 
        }else{
          
                return $this->RedirectToRoute('app_dashbord');    
            
        }
       
    }


    
    #[Route('/expeditor/add', name: 'app_add_expeditor')]
    public function add(Request $req, SluggerInterface $slugger,UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->user= $this->security->getUser();
        if(!$this->user){
            return $this->RedirectToRoute('app_login');    
        }
        $user = new User();

        $form = $this->createForm(ExpeditorType::class,$user);
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


            $user->setAccount($this->user->getAccount());
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);
            $user->setRoles(['EXPEDITOR_ROLE']);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
          return  $this->redirectToRoute('app_expeditor',array('msg' => "Expeditor added successfuly"));
        }
        return $this->render('expeditor/add.html.twig', [
            'form' => $form->createView(),
        ]); 


    }
}
