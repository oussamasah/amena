<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\DeliveryType;
use App\Form\EditDeliveryType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
class DeliveryController extends AbstractController
{
    private $security;
    private $entityManager;
    private $user;
    public function __construct(Security $security,ManagerRegistry $doctrine)
    {
        $this->security = $security;
        $this->entityManager = $doctrine->getManager();
        $this->user= $this->security->getUser();
        if(!$this->user){
            return $this->RedirectToRoute('app_login');    
        }

    }
    #[Route('/delivery', name: 'app_delivery')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $this->user= $this->security->getUser();
        if(!$this->user){
            return $this->RedirectToRoute('app_login');    
        }

        if (in_array('ADMIN_ROLE', $this->user->getRoles(), true)) {

            
            $account = $this->user->getAccount();
        
            $expeditor =  $this->entityManager->getRepository(User::class)->findDeliveryByAccount($account->getId());
            return $this->render('delivery/index.html.twig', [
                'delivery' => $expeditor,
            ]); 
        }else{
          
                return $this->RedirectToRoute('app_dashbord');    
            
        }
       
    }
   

    #[Route('/delivery/edit/{id}', name: 'app_edit_delivery')]
    public function edit(Request $req,$id, SluggerInterface $slugger,UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $this->user= $this->security->getUser();
        $delivery =  $this->entityManager->getRepository(User::class)->find($id);

        $user = $delivery;
        
        $form = $this->createForm(EditDeliveryType::class,$user);
        $form->handlerequest($req);
       
        if($form->isSubmitted()  && $form->isValid()){
            $this->entityManager->persist($user);
            $this->entityManager->flush();
          return  $this->redirectToRoute('app_delivery',array('msg' => "Delivery updated successfuly"));
        }
        return $this->render('delivery/edit.html.twig', [
            'form' => $form->createView(),
        ]); 


    }

    
    #[Route('/delivery/add', name: 'app_add_delivery')]
    public function add(Request $req, SluggerInterface $slugger,UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $this->user= $this->security->getUser();
 
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


            $user->setAccount($this->user->getAccount());
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);
            $user->setRoles(['DELIVERY_ROLE']);
            $user->setState(['active']);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
          return  $this->redirectToRoute('app_delivery',array('msg' => "Delivery added successfuly"));
        }
        return $this->render('delivery/add.html.twig', [
            'form' => $form->createView(),
        ]); 


    }
    
    #[Route('/delivery/activate/{id}', name: 'app_activate_delivery')]
    public function activate(Request $req, $id): Response
    {
        
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $pack = $this->entityManager->getRepository(User::class)->find($id);
        if ($pack) {
            $pack->setState("active");
            $this->entityManager->persist($pack);
            $this->entityManager->flush();
            return $this->redirectToRoute("app_delivery", ["msg" => "Your delivery activated successfuly"]);
        } else {
            return $this->redirectToRoute("app_delivery", ["error" => "The delivery is not disponible"]);
        }
    }
    #[Route('/delivery/inactive/{id}', name: 'app_inactive_delivery')]
    public function inactive(Request $req, $id): Response
    {
        
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $pack = $this->entityManager->getRepository(User::class)->find($id);
        if ($pack) {
            $pack->setState("inactive");
            $this->entityManager->persist($pack);
            $this->entityManager->flush();
            return $this->redirectToRoute("app_delivery", ["msg" => "Your delivery disactivated successfuly"]);
        } else {
            return $this->redirectToRoute("app_delivery", ["error" => "The delivery is not disponible"]);
        }
    }
}
